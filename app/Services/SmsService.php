<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SmsLog;
use Carbon\Carbon;

class SmsService
{
    protected $username;
    protected $apiKey;
    protected $senderId;
    protected $testMode;
    protected $testRecipient;

    public function __construct()
    {
        $this->username = config('services.mim_sms.username');
        $this->apiKey = config('services.mim_sms.api_key');
        $this->senderId = config('services.mim_sms.sender_id');
        $this->testMode = config('services.mim_sms.test_mode', true);
        $this->testRecipient = config('services.mim_sms.test_recipient');
    }

    /**
     * Send single SMS using MIM SMS V2 API
     *
     * @param string $mobileNumber
     * @param string $message
     * @param int|null $studentId
     * @param string|null $eventKey
     * @param string|null $eventType
     * @return bool
     */
    public function sendSms(
        string $mobileNumber,
        string $message,
        ?int $studentId = null,
        ?string $eventKey = null,
        ?string $eventType = null
    ): bool
    {
        // 1. Idempotency fast-check if eventKey is specified
        if ($eventKey && SmsLog::where('event_key', $eventKey)->exists()) {
            Log::info("MIM SMS skipped: duplicate event_key {$eventKey} already recorded.");
            return false;
        }

        // Normalize mobile number to Bangladeshi format (starts with 8801)
        $cleanNumber = preg_replace('/[^0-9]/', '', $mobileNumber);
        if (strlen($cleanNumber) == 11 && str_starts_with($cleanNumber, '01')) {
            $cleanNumber = '88' . $cleanNumber;
        }

        if (!preg_match('/^8801[0-9]{9}$/', $cleanNumber)) {
            Log::warning("MIM SMS blocked: recipient number {$mobileNumber} has an invalid format.");
            return false;
        }

        if (empty($this->username) || empty($this->apiKey) || empty($this->senderId)) {
            Log::error('MIM SMS is not configured. Set MIM_SMS_USERNAME, MIM_SMS_API_KEY, and MIM_SMS_SENDER_ID.');
            return false;
        }

        // 2. Atomic Database Reservation: prevent concurrent race conditions
        $smsLog = null;
        if ($eventKey) {
            try {
                $smsLog = SmsLog::create([
                    'student_id'    => $studentId,
                    'event_key'     => $eventKey,
                    'event_type'    => $eventType,
                    'mobile_number' => $mobileNumber,
                    'message'       => $message,
                    'status'        => 'Attempted',
                    'created_at'    => Carbon::now('Asia/Dhaka'),
                    'updated_at'    => Carbon::now('Asia/Dhaka'),
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException | \Illuminate\Database\QueryException $e) {
                Log::warning("MIM SMS atomic reservation skipped: duplicate event_key {$eventKey} collision detected.");
                return false;
            }
        }

        // Whitelist check: Only send to allowed numbers in test mode
        if ($this->testMode) {
            $allowedNumbers = array_filter(array_map(
                fn ($number) => preg_replace('/[^0-9]/', '', trim($number)),
                explode(',', (string) $this->testRecipient)
            ));
            $allowedNumbers = array_map(function ($number) {
                return strlen($number) === 11 && str_starts_with($number, '01')
                    ? '88' . $number
                    : $number;
            }, $allowedNumbers);

            if (!in_array($cleanNumber, $allowedNumbers)) {
                Log::info('MIM SMS blocked: recipient is not the configured test number.');
                
                if ($smsLog) {
                    $smsLog->update(['status' => 'Blocked (Test Mode)']);
                } else {
                    SmsLog::create([
                        'student_id'    => $studentId,
                        'event_key'     => $eventKey,
                        'event_type'    => $eventType,
                        'mobile_number' => $mobileNumber,
                        'message'       => $message,
                        'status'        => 'Blocked (Test Mode)',
                        'created_at'    => Carbon::now('Asia/Dhaka'),
                        'updated_at'    => Carbon::now('Asia/Dhaka'),
                    ]);
                }
                
                return false;
            }
        }

        try {
            $url = 'https://api.mimsms.com/api/V2/SMS';
            
            $response = Http::timeout(5)->post($url, [
                'userName'        => $this->username,
                'apiKey'          => $this->apiKey,
                'mobileNumber'    => $cleanNumber,
                'senderName'      => $this->senderId,
                'transactionType' => 'T',
                'message'         => $message
            ]);

            $isSuccess = $response->successful();
            
            Log::info("MIM SMS Send status to {$cleanNumber}: " . ($isSuccess ? 'Success' : 'Failed') . " - Response: " . $response->body());

            $finalStatus = $isSuccess ? 'Sent' : 'Failed';

            if ($smsLog) {
                $smsLog->update(['status' => $finalStatus]);
            } else {
                SmsLog::create([
                    'student_id'    => $studentId,
                    'event_key'     => $eventKey,
                    'event_type'    => $eventType,
                    'mobile_number' => $mobileNumber,
                    'message'       => $message,
                    'status'        => $finalStatus,
                    'created_at'    => Carbon::now('Asia/Dhaka'),
                    'updated_at'    => Carbon::now('Asia/Dhaka'),
                ]);
            }

            return $isSuccess;

        } catch (\Exception $e) {
            Log::error("MIM SMS sending failed for {$mobileNumber}: " . $e->getMessage());
            
            if ($smsLog) {
                $smsLog->update(['status' => 'Failed']);
            } else {
                SmsLog::create([
                    'student_id'    => $studentId,
                    'event_key'     => $eventKey,
                    'event_type'    => $eventType,
                    'mobile_number' => $mobileNumber,
                    'message'       => $message,
                    'status'        => 'Failed',
                    'created_at'    => Carbon::now('Asia/Dhaka'),
                    'updated_at'    => Carbon::now('Asia/Dhaka'),
                ]);
            }

            return false;
        }
    }
}
