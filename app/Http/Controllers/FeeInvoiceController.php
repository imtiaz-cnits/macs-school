<?php

namespace App\Http\Controllers;

use App\Models\AdmissionInvoiceHeadAllocation;
use App\Models\AdmissionPaymentHeadAllocation;
use App\Models\FeeInvoice;
use App\Models\FeeSetup;
use App\Models\Student;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\SessionYear;
use App\Models\FeeCategory;
use App\Models\FeePayment;
use App\Models\FeeAdjustmentLog;
use App\Models\StudentStatusLog;
use App\Services\AdmissionCollectionService;
use App\Services\AdmissionInvoiceService;
use App\Services\FeeGenerationService;
use App\Services\FeeReceiptService;
use App\Services\FinancialTransactionRunner;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Exception;

class FeeInvoiceController extends Controller
{

  // ইনভয়েস জেনারেট করার পেজ দেখানোর জন্য
  public function index()
    {
        $branches = Branch::all();
        $classes = Classes::all();
        $sessions = SessionYear::latest()->get();
        $categories = FeeCategory::where('status', 'Active')->get();

        // লিমিট সরিয়ে সব তথ্য আনা হচ্ছে (Generated History)
        $generatedBatches = FeeInvoice::select(
                'fee_setup_id', 
                'due_date', 
                DB::raw('count(id) as total_students'), 
                DB::raw('sum(net_amount) as total_amount'), 
                DB::raw('MAX(created_at) as generated_at')
            )
            ->whereNotNull('fee_setup_id')
            ->with(['feeSetup.schoolClass', 'feeSetup.category', 'feeSetup.branch', 'feeSetup.sessionYear'])
            ->groupBy('fee_setup_id', 'due_date')
            ->orderBy('generated_at', 'desc')
            ->get();

        return view('pages.fees.invoice_generate', compact('branches', 'classes', 'sessions', 'categories', 'generatedBatches'));
    }

    // বাল্ক ইনভয়েস জেনারেট করার লজিক (একসাথে পুরো ক্লাসের বিল)
    public function generate(Request $request, FeeGenerationService $generationService)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'class_id' => 'required|exists:classes,id',
            'session_year_id' => 'required|exists:session_years,id',
            'fee_category_id' => 'required|exists:fee_categories,id',
            'fee_month' => 'nullable|string',
            'due_date' => 'required|date'
        ]);

        $params = [
            'branch_id'       => (int) $request->branch_id,
            'class_id'        => (int) $request->class_id,
            'session_year_id' => (int) $request->session_year_id,
            'fee_category_id' => (int) $request->fee_category_id,
            'fee_month'       => $request->fee_month,
            'due_date'        => $request->due_date,
            'user_id'         => Auth::id(),
        ];

        $result = $generationService->generate($params);

        if (!$result['success']) {
            if (!empty($result['lock_failed'])) {
                return back()->with('error', 'Notice: Invoice generation is currently locked by another active process. Please wait a moment and try again.');
            }
            return back()->with('error', 'Generation Error: ' . ($result['error'] ?? 'Failed to generate invoices.'));
        }

        if ($result['total_generated'] > 0) {
            return back()->with('success', "Success! Generated {$result['total_generated']} new invoices for this selection.");
        } else {
            return back()->with('error', 'Notice: Invoices were already generated for all eligible students in this selection.');
        }
    }

    // POS (Thermal) মেশিনে ইনভয়েস প্রিন্ট করার জন্য
    public function printPos($id)
    {
        /** @var FeeReceiptService $receiptService */
        $receiptService = app(FeeReceiptService::class);

        $invoice = FeeInvoice::with($receiptService->invoiceReceiptRelations())
            ->findOrFail($id);

        $receiptData = $receiptService->buildSingleInvoiceReceiptData($invoice);

        return view('pages.fees.pos_invoice', compact('invoice', 'receiptData'));
    }

    /**
     * Manual Admission Invoice Generation / Regeneration (`-V2` after Cancelled invoice).
     * Strictly limited to Admin & Super Admin; Editor is blocked cleanly (403).
     */
    public function generateAdmission(
        Request $request,
        int $student_id,
        AdmissionInvoiceService $admissionInvoiceService
    ): JsonResponse {
        $actor = Auth::user();
        if (!$actor) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if (!method_exists($actor, 'hasRole') || (!$actor->hasRole('admin') && !$actor->hasRole('super-admin'))) {
            return response()->json([
                'success' => false,
                'message' => 'Only Administrators and Super Administrators may generate or regenerate Admission Fee invoices.',
            ], 403);
        }

        if (
            !Schema::hasTable('admission_fee_structures')
            || !Schema::hasTable('admission_fee_structure_items')
            || !Schema::hasTable('admission_invoice_head_allocations')
            || !Schema::hasColumn('fee_invoices', 'admission_fee_structure_id')
        ) {
            return response()->json([
                'success' => false,
                'tables_available' => false,
                'message' => 'Admission schema migrations are pending. Please contact the system administrator.',
            ], 422);
        }

        $validated = $request->validate([
            'discount_type' => 'nullable|in:none,fixed,percentage',
            'discount_value' => 'nullable|max:20',
            'due_date' => 'nullable|date',
        ]);

        try {
            $student = Student::findOrFail($student_id);

            $rawDiscVal = $validated['discount_value'] ?? '0.00';
            $normalizedDiscVal = AdmissionCollectionService::normalizeHttpMonetaryInput(
                ($rawDiscVal === '' || $rawDiscVal === null) ? '0.00' : $rawDiscVal
            );

            $result = $admissionInvoiceService->generateForStudent($student, [
                'discount_type' => $validated['discount_type'] ?? 'none',
                'discount_value' => $normalizedDiscVal,
                'due_date' => $validated['due_date'] ?? null,
                'fallback_to_legacy' => false,
            ], (int) $actor->id);

            if (!$result['success'] || !$result['invoice']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Unable to generate Admission Fee invoice.',
                ], 422);
            }

            $invoice = $result['invoice'];

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'invoice' => $invoice,
                'pos_print_url' => route('fees.invoice.pos_print', $invoice->id),
            ], 201);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Target student record was not found.',
            ], 404);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (ValidationException $e) {
            $firstMessage = collect($e->errors())->flatten()->first() ?? $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => $firstMessage,
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Restore a mistakenly cancelled invoice with strict financial integrity, V8 5-tier lock order,
     * active-payment ledger verification, and Admission scope / tuition overlap protection.
     */
    public function restoreInvoice(Request $request, $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|min:3|max:255',
            'confirm' => 'required',
            'student_id' => 'nullable|exists:students,id',
        ]);

        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $user = Auth::user();
        if (!$user->hasRole('admin') && !$user->hasRole('super-admin') && !$user->hasRole('editor')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. You do not have permission to restore cancelled invoices.'], 403);
        }

        $operatorId = (int) $user->id;
        $reason = trim((string) $request->reason);
        $expectedStudentId = $request->filled('student_id') ? (int) $request->student_id : null;
        $runner = app(FinancialTransactionRunner::class);

        try {
            $payload = $runner->runWithDeadlockRetry(function () use ($id, $operatorId, $reason, $expectedStudentId): array {
                $invoiceLookup = FeeInvoice::select('id', 'student_id')->findOrFail($id);

                // V8 5-Tier Lock Order: Tier 1 Student -> Tier 2 FeeInvoices (ASC) -> Tier 3 FeePayments (ASC)
                $student = Student::where('id', $invoiceLookup->student_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $studentInvoices = FeeInvoice::where('student_id', $student->id)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                /** @var FeeInvoice|null $invoice */
                $invoice = $studentInvoices->firstWhere('id', (int) $id);
                if (!$invoice) {
                    throw new ModelNotFoundException('Target invoice not found for locked student.');
                }

                // 1. Verify invoice is currently Cancelled
                if (!$invoice->isCancelled()) {
                    throw ValidationException::withMessages([
                        'invoice_id' => ["Only cancelled invoices can be restored. Current status of invoice {$invoice->invoice_no} is '{$invoice->status}'."],
                    ]);
                }

                // 2. Verify student matches if supplied
                if ($expectedStudentId !== null && (int) $invoice->student_id !== $expectedStudentId) {
                    throw ValidationException::withMessages([
                        'student_id' => ['Invoice does not belong to the specified student.'],
                    ]);
                }

                // Tier 3: Lock all payments for this invoice ASC
                $lockedPayments = FeePayment::where('fee_invoice_id', $invoice->id)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                $isAdmissionInvoice = $invoice->isAdmissionInvoice();
                $lockedInvoiceHeads = collect();
                $lockedPaymentHeads = collect();

                if ($isAdmissionInvoice) {
                    if (Schema::hasTable('admission_invoice_head_allocations')) {
                        $lockedInvoiceHeads = AdmissionInvoiceHeadAllocation::where('fee_invoice_id', $invoice->id)
                            ->orderBy('id', 'asc')
                            ->lockForUpdate()
                            ->get();
                        $invoice->setRelation('admissionHeadAllocations', $lockedInvoiceHeads);
                    }

                    if (Schema::hasTable('admission_payment_head_allocations')) {
                        $lockedPaymentHeads = AdmissionPaymentHeadAllocation::where('fee_invoice_id', $invoice->id)
                            ->orderBy('id', 'asc')
                            ->lockForUpdate()
                            ->get();
                    }

                    $invoice->load('admissionFeeStructure');
                }

                // Pre-load legacy feeSetup relation ONLY on non-admission rows.
                $existingActiveLegacyInvoices = $studentInvoices->filter(
                    fn (FeeInvoice $inv): bool => !is_null($inv->fee_setup_id)
                        && (int) $inv->id !== (int) $invoice->id
                        && $inv->status !== 'Cancelled'
                );
                if ($existingActiveLegacyInvoices->isNotEmpty()) {
                    $existingActiveLegacyInvoices->load('feeSetup.category');
                }

                // 3. Duplicate Billing / Scope Conflict Prevention
                if ($isAdmissionInvoice) {
                    $existingActiveAdmission = $studentInvoices->first(function (FeeInvoice $inv) use ($invoice): bool {
                        return $inv->isAdmissionInvoice()
                            && (int) $inv->id !== (int) $invoice->id
                            && $inv->status !== 'Cancelled'
                            && (int) $inv->branch_id === (int) $invoice->branch_id
                            && (int) $inv->session_year_id === (int) $invoice->session_year_id;
                    });

                    if ($existingActiveAdmission) {
                        throw ValidationException::withMessages([
                            'invoice_id' => [
                                "Cannot restore invoice {$invoice->invoice_no}: An active invoice ({$existingActiveAdmission->invoice_no}) already exists for {$student->student_name} in this Branch and Session.",
                            ],
                        ]);
                    }

                    $activeLegacyAdmission = $existingActiveLegacyInvoices->first(function (FeeInvoice $inv) use ($invoice): bool {
                        $catName = strtolower(trim((string) ($inv->feeSetup?->category?->name ?? '')));
                        return $catName === 'admission fee'
                            && (int) ($inv->branch_id ?? $inv->feeSetup?->branch_id) === (int) $invoice->branch_id
                            && (int) ($inv->session_year_id ?? $inv->feeSetup?->session_year_id) === (int) $invoice->session_year_id;
                    });

                    if ($activeLegacyAdmission) {
                        throw ValidationException::withMessages([
                            'invoice_id' => [
                                "Cannot restore invoice {$invoice->invoice_no}: An active legacy Admission invoice ({$activeLegacyAdmission->invoice_no}) already exists for {$student->student_name}.",
                            ],
                        ]);
                    }

                    $structure = $invoice->admissionFeeStructure;
                    if ($structure && (bool) $structure->includes_monthly_tuition && !empty($structure->tuition_month_number)) {
                        $tuitionMonth = (int) $structure->tuition_month_number;
                        $conflictingMonthly = $existingActiveLegacyInvoices->first(function (FeeInvoice $inv) use ($invoice, $tuitionMonth): bool {
                            $invSessionId = (int) ($inv->session_year_id ?? $inv->feeSetup?->session_year_id);
                            if ($invSessionId !== (int) $invoice->session_year_id) {
                                return false;
                            }
                            if (!FeeGenerationService::isMonthlyTuitionCategoryName($inv->feeSetup?->category?->name)) {
                                return false;
                            }
                            $parsed = FeeInvoice::extractYearMonthFromInvoiceNo($inv->invoice_no);
                            $resolvedMonth = $parsed
                                ? (int) $parsed['month']
                                : Student::resolveMonthNumber((string) ($inv->feeSetup?->fee_month ?? ''));
                            if (!$resolvedMonth && !empty($inv->due_date)) {
                                $resolvedMonth = (int) date('n', strtotime((string) $inv->due_date));
                            }

                            return $resolvedMonth === $tuitionMonth;
                        });

                        if ($conflictingMonthly) {
                            throw ValidationException::withMessages([
                                'invoice_id' => [
                                    "Cannot restore invoice {$invoice->invoice_no}: A non-cancelled Monthly Fee invoice ({$conflictingMonthly->invoice_no}) already covers its tuition month.",
                                ],
                            ]);
                        }
                    }

                    $billingPeriod = $invoice->billing_period;
                } else {
                    $invoice->load('feeSetup.category');
                    $billingPeriod = $invoice->billing_period;
                    $feeSetupId = $invoice->fee_setup_id;
                    $feeCategoryId = $invoice->feeSetup->fee_category_id ?? null;

                    foreach ($existingActiveLegacyInvoices as $activeInv) {
                        $otherPeriod = $activeInv->billing_period;
                        $otherSetupId = $activeInv->fee_setup_id;
                        $otherCategoryId = $activeInv->feeSetup->fee_category_id ?? null;

                        $isSamePeriod = ($otherPeriod === $billingPeriod);
                        $isSameFeeType = ($otherSetupId === $feeSetupId) || ($feeCategoryId && $otherCategoryId === $feeCategoryId);

                        if ($isSamePeriod && $isSameFeeType) {
                            throw ValidationException::withMessages([
                                'invoice_id' => [
                                    "Cannot restore invoice {$invoice->invoice_no}: An active invoice ({$activeInv->invoice_no}) already exists for {$student->student_name} for the period {$billingPeriod}. To prevent duplicate billing, the conflicting active invoice must be reviewed and resolved first.",
                                ],
                            ]);
                        }
                    }
                }

                // 4. Inspect linked ACTIVE payments only (exclude Reversed payments!)
                $netAmount = bcadd((string) ($invoice->net_amount ?? '0'), '0.00', 2);
                $totalPayable = $isAdmissionInvoice ? $netAmount : $invoice->totalPayableAmount();
                $verifiedPaymentsSum = '0.00';
                foreach ($lockedPayments as $p) {
                    if (($p->status ?? 'Active') === 'Active') {
                        $verifiedPaymentsSum = bcadd(
                            $verifiedPaymentsSum,
                            bcadd((string) ($p->paid_amount ?? '0'), '0.00', 2),
                            2
                        );
                    }
                }

                if (bccomp($verifiedPaymentsSum, $totalPayable, 2) > 0) {
                    throw ValidationException::withMessages([
                        'invoice_id' => [
                            'Inconsistent payment records detected: verified active payments total (৳' .
                            number_format((float) $verifiedPaymentsSum, 2) .
                            ') exceeds invoice total payable (৳' .
                            number_format((float) $totalPayable, 2) .
                            '). Restoration rejected for manual accounting review.',
                        ],
                    ]);
                }

                // If Admission invoice has heads, synchronize heads from Active payment head allocations
                if ($isAdmissionInvoice && $lockedInvoiceHeads->isNotEmpty()) {
                    $activeHeadPaid = [];
                    foreach ($lockedPaymentHeads as $ph) {
                        if (($ph->status ?? 'Active') === 'Active') {
                            $hId = (int) $ph->admission_invoice_head_allocation_id;
                            $activeHeadPaid[$hId] = bcadd(
                                $activeHeadPaid[$hId] ?? '0.00',
                                bcadd((string) ($ph->allocated_paid_amount ?? '0'), '0.00', 2),
                                2
                            );
                        }
                    }

                    $sumHeadNet = '0.00';
                    $sumHeadPaid = '0.00';
                    foreach ($lockedInvoiceHeads as $head) {
                        $hNet = bcadd((string) $head->net_amount, '0.00', 2);
                        $hPaid = $activeHeadPaid[(int) $head->id] ?? '0.00';
                        if (bccomp($hPaid, $hNet, 2) > 0) {
                            throw ValidationException::withMessages([
                                'invoice_id' => ["Inconsistent payment records detected on Admission head '{$head->head_name_snapshot}'."],
                            ]);
                        }
                        $head->allocated_paid_amount = $hPaid;
                        $head->due_amount = bcsub($hNet, $hPaid, 2);
                        $head->save();

                        $sumHeadNet = bcadd($sumHeadNet, $hNet, 2);
                        $sumHeadPaid = bcadd($sumHeadPaid, $hPaid, 2);
                    }

                    if (bccomp($sumHeadNet, $netAmount, 2) !== 0 || bccomp($sumHeadPaid, $verifiedPaymentsSum, 2) !== 0) {
                        throw ValidationException::withMessages([
                            'invoice_id' => ['Inconsistent payment records detected: head allocation ledger does not reconcile with invoice totals.'],
                        ]);
                    }
                }

                // 5. Calculate restored status & due strictly via BCMath (no fake Paid on 0 payment)
                $restoredDue = bcsub($totalPayable, $verifiedPaymentsSum, 2);
                if (bccomp($verifiedPaymentsSum, '0.00', 2) <= 0) {
                    $restoredStatus = 'Unpaid';
                    $restoredDue = $totalPayable;
                } elseif (bccomp($restoredDue, '0.00', 2) <= 0) {
                    $restoredStatus = 'Paid';
                    $restoredDue = '0.00';
                } else {
                    $restoredStatus = 'Partial';
                }

                // 6. Preserve original amounts, update status and due
                $prevDue = bcadd((string) ($invoice->due_amount ?? '0'), '0.00', 2);
                $invoice->paid_amount = $verifiedPaymentsSum;
                $invoice->due_amount = $restoredDue;
                $invoice->status = $restoredStatus;
                $invoice->save();

                // 7. Audit Trail Logging
                FeeAdjustmentLog::create([
                    'fee_invoice_id' => $invoice->id,
                    'student_id' => $student->id,
                    'user_id' => $operatorId,
                    'invoice_no' => $invoice->invoice_no,
                    'student_identity' => $student->student_identity,
                    'mode' => 'set_amount',
                    'scope' => 'single',
                    'adjustment_amount' => $restoredDue,
                    'previous_amount' => $invoice->amount,
                    'previous_discount' => $invoice->discount,
                    'previous_net_amount' => $invoice->net_amount,
                    'previous_due_amount' => $prevDue,
                    'new_amount' => $invoice->amount,
                    'new_discount' => $invoice->discount,
                    'new_net_amount' => $invoice->net_amount,
                    'new_due_amount' => $restoredDue,
                    'paid_amount' => $verifiedPaymentsSum,
                    'reason' => mb_substr('Restored Cancelled Invoice: ' . $reason, 0, 255),
                ]);

                StudentStatusLog::create([
                    'student_id' => $student->id,
                    'user_id' => $operatorId,
                    'previous_status' => $student->status,
                    'new_status' => $student->status,
                    'action_type' => 'invoice_restoration',
                    'last_active_month' => $billingPeriod,
                    'effective_date' => now()->toDateString(),
                    'reason' => "Restored invoice {$invoice->invoice_no} ({$restoredStatus}): {$reason}",
                    'reconciled_invoice_ids' => [$invoice->id],
                    'reconciled_amount' => $restoredDue,
                ]);

                return [
                    'invoice' => $invoice->fresh(),
                    'restored_status' => $restoredStatus,
                    'restored_due' => $restoredDue,
                    'billing_period' => $billingPeriod,
                ];
            });

            $restoredInv = $payload['invoice'];

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => "Invoice {$restoredInv->invoice_no} has been restored successfully as {$payload['restored_status']} with due ৳" . number_format((float) $payload['restored_due'], 2) . '.',
                'invoice' => [
                    'id' => $restoredInv->id,
                    'invoice_no' => $restoredInv->invoice_no,
                    'amount' => (float) $restoredInv->amount,
                    'discount' => (float) $restoredInv->discount,
                    'net_amount' => (float) $restoredInv->net_amount,
                    'paid_amount' => (float) $restoredInv->paid_amount,
                    'due_amount' => (float) $restoredInv->due_amount,
                    'status' => $restoredInv->status,
                    'billing_period' => $payload['billing_period'],
                ],
            ], 200);
        } catch (ValidationException $e) {
            $firstMessage = collect($e->errors())->flatten()->first() ?? $e->getMessage();
            return response()->json([
                'success' => false,
                'message' => $firstMessage,
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Restore Invoice Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Restoration failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}