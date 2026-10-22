<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SmsSchedule;
use App\Models\Student;
use App\Models\SmsLog;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DispatchScheduledSms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:dispatch-scheduled {--id= : Specific schedule ID to dispatch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch pending scheduled notice SMS messages that are due';

    /**
     * Execute the console command.
     */
    public function handle(SmsService $smsService)
    {
        $now = Carbon::now('Asia/Dhaka');
        $scheduleId = $this->option('id');

        if ($scheduleId) {
            $query = SmsSchedule::where('id', $scheduleId)
                ->whereIn('status', ['pending', 'processing']);
        } else {
            $query = SmsSchedule::where(function ($q) use ($now) {
                $q->where('status', 'pending')
                  ->where('scheduled_at', '<=', $now);
            })->orWhere(function ($q) use ($now) {
                // Stalled processing recovery after 15 minutes of inactivity (e.g. following a worker crash)
                $q->where('status', 'processing')
                  ->where('dispatched_at', '<=', $now->copy()->subMinutes(15));
            });
        }

        $dueSchedules = $query->orderBy('scheduled_at', 'asc')->get();

        if ($dueSchedules->isEmpty()) {
            $this->info("No due scheduled SMS broadcasts found at {$now->toDateTimeString()}.");
            return 0;
        }

        $this->info("Found {$dueSchedules->count()} scheduled broadcast(s) due for dispatch.");

        $ratePerSms = (float) config('services.mim_sms.rate_per_sms', 0.33);

        foreach ($dueSchedules as $item) {
            // Atomic state transition: acquire lock and transition to 'processing'
            $schedule = DB::transaction(function () use ($item, $now, $scheduleId) {
                $sch = SmsSchedule::where('id', $item->id)
                    ->where(function ($q) use ($now, $scheduleId) {
                        $q->where('status', 'pending');
                        if ($scheduleId) {
                            $q->orWhere('status', 'processing');
                        } else {
                            $q->orWhere(function ($sub) use ($now) {
                                $sub->where('status', 'processing')
                                    ->where('dispatched_at', '<=', $now->copy()->subMinutes(15));
                            });
                        }
                    })
                    ->lockForUpdate()
                    ->first();

                if ($sch) {
                    $sch->update([
                        'status'        => 'processing',
                        'dispatched_at' => $now,
                    ]);
                }
                return $sch;
            });

            if (!$schedule) {
                $this->warn("Schedule #{$item->id} was already locked or processed by another worker. Skipping.");
                continue;
            }

            $this->info("Processing Schedule #{$schedule->id} ({$schedule->schedule_uuid})...");

            try {
                $snapshot = $schedule->recipient_snapshot ?? [];
                $message = $schedule->message;

                // Deterministic batchId based on schedule_uuid ensures exact-once idempotency across retries/crashes
                $batchId = 'sch_' . $schedule->schedule_uuid;

                $studentsMap = $snapshot['students_map'] ?? []; // phone => [student_id1, ...]
                $standaloneCustom = $snapshot['standalone_custom_phones'] ?? ($snapshot['custom_phones'] ?? []);

                $sentCount = 0;
                $failedCount = 0;
                $siblingSharedCount = 0;

                // 1. Dispatch student guardian phones
                foreach ($studentsMap as $phone => $studentIds) {
                    // Re-check student eligibility at execution time (safety verification)
                    $eligibleStudents = Student::whereIn('id', (array)$studentIds)
                        ->where('status', 'Active')
                        ->where('sms_status', 'Active')
                        ->get();

                    if ($eligibleStudents->isEmpty()) {
                        Log::info("Scheduled SMS #{$schedule->id}: student recipient(s) no longer active. Skipping phone {$phone}.");
                        continue;
                    }

                    $primaryStudent = $eligibleStudents->first();
                    $eventKey = $batchId . '_sstd_' . $primaryStudent->id;

                    // Idempotency pre-check: skip if already sent or ambiguous in a prior attempt
                    $existingLog = SmsLog::where('event_key', $eventKey)->first();
                    if ($existingLog) {
                        if (in_array($existingLog->status, ['Sent', 'Blocked (Test Mode)'], true)) {
                            $sentCount++;
                            continue;
                        }
                        if ($existingLog->status === 'Ambiguous') {
                            $failedCount++;
                            Log::warning("Scheduled SMS #{$schedule->id}: Skipping event_key {$eventKey} due to prior Ambiguous gateway status.");
                            continue;
                        }
                    }

                    $sent = $smsService->sendSms(
                        $phone,
                        $message,
                        $primaryStudent->id,
                        $eventKey,
                        'scheduled_notice'
                    );

                    $logStatus = SmsLog::where('event_key', $eventKey)->value('status');
                    $isProcessed = $sent || in_array($logStatus, ['Sent', 'Blocked (Test Mode)'], true);

                    if ($isProcessed) {
                        $sentCount++;

                        // Record sibling shared logs
                        if ($eligibleStudents->count() > 1) {
                            for ($i = 1; $i < $eligibleStudents->count(); $i++) {
                                $sibling = $eligibleStudents[$i];
                                $siblingSharedCount++;
                                try {
                                    SmsLog::create([
                                        'student_id'    => $sibling->id,
                                        'event_key'     => $eventKey . '_std_' . $sibling->id,
                                        'event_type'    => 'scheduled_notice',
                                        'mobile_number' => $phone,
                                        'message'       => $message,
                                        'status'        => 'Sent (Sibling Shared)',
                                        'created_at'    => Carbon::now('Asia/Dhaka'),
                                        'updated_at'    => Carbon::now('Asia/Dhaka'),
                                    ]);
                                } catch (\Exception $e) {
                                    // Ignore duplicate key collision on log
                                }
                            }
                        }
                    } else {
                        $failedCount++;
                    }
                }

                // 2. Dispatch standalone custom numbers
                foreach ($standaloneCustom as $cPhone) {
                    $eventKey = $batchId . '_scust_' . $cPhone;

                    // Idempotency pre-check: skip if already sent or ambiguous in a prior attempt
                    $existingLog = SmsLog::where('event_key', $eventKey)->first();
                    if ($existingLog) {
                        if (in_array($existingLog->status, ['Sent', 'Blocked (Test Mode)'], true)) {
                            $sentCount++;
                            continue;
                        }
                        if ($existingLog->status === 'Ambiguous') {
                            $failedCount++;
                            Log::warning("Scheduled SMS #{$schedule->id}: Skipping event_key {$eventKey} due to prior Ambiguous gateway status.");
                            continue;
                        }
                    }

                    $sent = $smsService->sendSms(
                        $cPhone,
                        $message,
                        null,
                        $eventKey,
                        'scheduled_notice'
                    );

                    $logStatus = SmsLog::where('event_key', $eventKey)->value('status');
                    $isProcessed = $sent || in_array($logStatus, ['Sent', 'Blocked (Test Mode)'], true);

                    if ($isProcessed) {
                        $sentCount++;
                    } else {
                        $failedCount++;
                    }
                }

                $segments = max(1, (int)$schedule->total_segments);
                $actualCost = round($sentCount * $segments * $ratePerSms, 2);

                $finalStatus = 'completed';
                if ($failedCount > 0 && $sentCount > 0) {
                    $finalStatus = 'partial_failure';
                } elseif ($failedCount > 0 && $sentCount === 0) {
                    $finalStatus = 'failed';
                } elseif ($sentCount === 0 && $failedCount === 0) {
                    $finalStatus = 'completed'; // No deliverable recipients remained
                }

                $schedule->update([
                    'status'       => $finalStatus,
                    'sent_count'   => $sentCount,
                    'failed_count' => $failedCount,
                    'actual_cost'  => $actualCost,
                ]);

                $this->info("Schedule #{$schedule->id} finished with status: {$finalStatus} (Sent: {$sentCount}, Failed: {$failedCount}, Sibling Deduplicated: {$siblingSharedCount}).");

            } catch (\Throwable $e) {
                Log::error("Scheduled SMS dispatch failed for Schedule #{$schedule->id}: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);

                $schedule->update([
                    'status'        => 'failed',
                    'error_message' => Str::limit($e->getMessage(), 500),
                ]);

                $this->error("Error dispatching Schedule #{$schedule->id}: {$e->getMessage()}");
            }
        }

        return 0;
    }
}
