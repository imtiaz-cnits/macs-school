<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            ->with(['feeSetup.schoolClass', 'feeSetup.category', 'feeSetup.branch', 'feeSetup.sessionYear'])
            ->groupBy('fee_setup_id', 'due_date')
            ->orderBy('generated_at', 'desc')
            ->get();

        return view('pages.fees.invoice_generate', compact('branches', 'classes', 'sessions', 'categories', 'generatedBatches'));
    }

    // বাল্ক ইনভয়েস জেনারেট করার লজিক (একসাথে পুরো ক্লাসের বিল)
   // বাল্ক ইনভয়েস জেনারেট করার লজিক (একসাথে পুরো ক্লাসের বিল)
    public function generate(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'class_id' => 'required|exists:classes,id',
            'session_year_id' => 'required|exists:session_years,id',
            'fee_category_id' => 'required|exists:fee_categories,id',
            'fee_month' => 'nullable|string',
            'due_date' => 'required|date'
        ]);

        // ১. চেক করা হচ্ছে এই ক্যাটাগরির ফি সেটআপ করা আছে কিনা (নির্দিষ্ট মাস OR 'Monthly' সেটআপ)
        $feeSetup = FeeSetup::where([
            'branch_id' => $request->branch_id,
            'class_id' => $request->class_id,
            'session_year_id' => $request->session_year_id,
            'fee_category_id' => $request->fee_category_id,
        ])
        ->where(function ($query) use ($request) {
            $query->where('fee_month', $request->fee_month)
                  ->orWhere('fee_month', 'Monthly');
        })
        ->first();

        if (!$feeSetup) {
            return back()->with('error', 'Error: No Fee Setup found! Please set up the fee amount for this class and month first.');
        }

        // ==========================================
        // সিরিয়াল ইনভয়েস নম্বর তৈরি ও টার্গেট পিরিয়ড হিসাব (INV-YYYYMM-XXXX)
        // ==========================================
        $monthsMap = [
            'january' => '01', 'february' => '02', 'march' => '03',
            'april' => '04', 'may' => '05', 'june' => '06',
            'july' => '07', 'august' => '08', 'september' => '09',
            'october' => '10', 'november' => '11', 'december' => '12'
        ];
        $selectedMonthKey = strtolower(trim($request->fee_month ?? ''));
        $paddedMonth = $monthsMap[$selectedMonthKey] ?? date('m', strtotime($request->due_date));
        $targetYear = date('Y', strtotime($request->due_date));
        $prefix = "INV-{$targetYear}{$paddedMonth}-";

        // ২. এই ক্লাসের সকল এলিজিবল স্টুডেন্টদের খুঁজে বের করা (সেন্ট্রাল রুল)
        $students = Student::eligibleForBilling((int) $targetYear, $paddedMonth)
            ->where([
                'branch_id' => $request->branch_id,
                'class_id' => $request->class_id,
                'session_year_id' => $request->session_year_id,
            ])->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'Error: No eligible students found in this class for the selected billing period!');
        }

        $generatedCount = 0;

        // ডাটাবেস থেকে এই প্রিফিক্সের সর্বশেষ ইনভয়েসটি বের করা
        $lastInvoice = FeeInvoice::where('invoice_no', 'like', $prefix . '%')
                                 ->orderBy('id', 'desc')
                                 ->first();

        $lastSerial = 0;
        if ($lastInvoice) {
            // যদি আগে ইনভয়েস থাকে, তবে শেষের সিরিয়াল নম্বরটি (যেমন: 0045) আলাদা করে ইন্টিজারে রূপান্তর করা
            $lastSerial = (int) str_replace($prefix, '', $lastInvoice->invoice_no);
        }
        // ==========================================

        $isMonthlyRecurring = strtolower(trim($feeSetup->fee_month ?? '')) === 'monthly';

        // ডাটাবেস ট্রানজেকশন
        DB::beginTransaction();
        try {
            foreach ($students as $student) {
                // ৩. চেক করা হচ্ছে এই স্টুডেন্টের নামে অলরেডি এই বিলটি করা আছে কিনা
                if ($isMonthlyRecurring) {
                    $exists = FeeInvoice::where('student_id', $student->id)
                                        ->where('fee_setup_id', $feeSetup->id)
                                        ->where('invoice_no', 'like', $prefix . '%')
                                        ->exists();
                } else {
                    // নির্দিষ্ট মাসের ফি সেটআপের জন্য শিক্ষার্থীর শুধুমাত্র ১টি ইনভয়েস থাকবে
                    $exists = FeeInvoice::where('student_id', $student->id)
                                        ->where('fee_setup_id', $feeSetup->id)
                                        ->exists();
                }

                if (!$exists) {
                    
                    // ৪. প্রতিবার নতুন ইনভয়েসের জন্য সিরিয়াল ১ করে বাড়ানো হচ্ছে
                    $lastSerial++;
                    // নতুন ইনভয়েস নম্বর তৈরি (যেমন: INV-202603-0001)
                    $invoiceNo = $prefix . str_pad($lastSerial, 4, '0', STR_PAD_LEFT);

                    // Check if the student has a customized fee for this category
                    $customFee = \App\Models\StudentCustomFee::where('student_id', $student->id)
                                                             ->where('fee_category_id', $feeSetup->fee_category_id)
                                                             ->first();
                    
                    $amount = $feeSetup->amount;
                    $discount = 0;
                    
                    if ($customFee) {
                        $amount = $customFee->amount;
                        $discount = max(0, $feeSetup->amount - $customFee->amount);
                    }

                    FeeInvoice::create([
                        'invoice_no' => $invoiceNo,
                        'student_id' => $student->id,
                        'fee_setup_id' => $feeSetup->id,
                        'amount' => $feeSetup->amount,
                        'discount' => $discount,
                        'net_amount' => $amount,
                        'due_amount' => $amount,
                        'status' => 'Unpaid',
                        'due_date' => $request->due_date,
                        'user_id' => Auth::id()
                    ]);
                    
                    $generatedCount++;
                }
            }
            DB::commit();
            
            if ($generatedCount > 0) {
                return back()->with('success', "Success! Generated {$generatedCount} new invoices for this class.");
            } else {
                return back()->with('error', 'Notice: Invoices were already generated for all students in this class.');
            }
            
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'System Error: ' . $e->getMessage());
        }
    }

    // POS (Thermal) মেশিনে ইনভয়েস প্রিন্ট করার জন্য
    public function printPos($id)
    {
        $invoice = FeeInvoice::with(['student.schoolClass', 'student.section', 'feeSetup.category', 'user'])
                             ->findOrFail($id);

        return view('pages.fees.pos_invoice', compact('invoice'));
    }

    /**
     * Restore a mistakenly cancelled invoice with strict financial integrity and audit logging.
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

        $operatorId = $user->id;
        $reason = trim($request->reason);

        DB::beginTransaction();
        try {
            $invoice = FeeInvoice::with(['student', 'feeSetup.category', 'payments'])->lockForUpdate()->findOrFail($id);

            // 1. Verify invoice is currently Cancelled
            if (!$invoice->isCancelled()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Only cancelled invoices can be restored. Current status of invoice {$invoice->invoice_no} is '{$invoice->status}'."
                ], 422);
            }

            // 2. Verify student matches if supplied
            if ($request->filled('student_id') && (int)$invoice->student_id !== (int)$request->student_id) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Invoice does not belong to the specified student."
                ], 422);
            }

            $student = $invoice->student;
            if (!$student) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Associated student record not found."
                ], 422);
            }

            // 3. Duplicate Billing Prevention Check
            $billingPeriod = $invoice->billing_period;
            $feeSetupId = $invoice->fee_setup_id;
            $feeCategoryId = $invoice->feeSetup->fee_category_id ?? null;

            $existingActiveInvoices = FeeInvoice::with('feeSetup')
                ->where('student_id', $student->id)
                ->where('status', '!=', 'Cancelled')
                ->where('id', '!=', $invoice->id)
                ->get();

            foreach ($existingActiveInvoices as $activeInv) {
                $otherPeriod = $activeInv->billing_period;
                $otherSetupId = $activeInv->fee_setup_id;
                $otherCategoryId = $activeInv->feeSetup->fee_category_id ?? null;

                $isSamePeriod = ($otherPeriod === $billingPeriod);
                $isSameFeeType = ($otherSetupId === $feeSetupId) || ($feeCategoryId && $otherCategoryId === $feeCategoryId);

                if ($isSamePeriod && $isSameFeeType) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot restore invoice {$invoice->invoice_no}: An active invoice ({$activeInv->invoice_no}) already exists for {$student->student_name} for the period {$billingPeriod}. To prevent duplicate billing, the conflicting active invoice must be reviewed and resolved first."
                    ], 422);
                }
            }

            // 4. Inspect linked payments & receipts
            $netAmount = round((float) $invoice->net_amount, 2);
            $verifiedPaymentsSum = round((float) FeePayment::where('fee_invoice_id', $invoice->id)->sum('paid_amount'), 2);

            if ($verifiedPaymentsSum > $netAmount) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Inconsistent payment records detected: verified payments total (৳" . number_format($verifiedPaymentsSum, 2) . ") exceeds invoice net amount (৳" . number_format($netAmount, 2) . "). Restoration rejected for manual accounting review."
                ], 422);
            }

            // 5. Calculate correct restored due and status from verified payments
            $actualPaid = $verifiedPaymentsSum;
            $restoredDue = round(max(0, $netAmount - $actualPaid), 2);

            if ($actualPaid <= 0) {
                $restoredStatus = 'Unpaid';
                $restoredDue = $netAmount;
            } elseif ($restoredDue <= 0 && $actualPaid >= $netAmount) {
                $restoredStatus = 'Paid';
                $restoredDue = 0.00;
            } else {
                $restoredStatus = 'Partial';
            }

            // 6. Preserve original amounts, update status and due
            $prevDue = (float) $invoice->due_amount;
            $invoice->paid_amount = $actualPaid;
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
                'paid_amount' => $actualPaid,
                'reason' => 'Restored Cancelled Invoice: ' . $reason,
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

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => "Invoice {$invoice->invoice_no} has been restored successfully as {$restoredStatus} with due ৳" . number_format($restoredDue, 2) . ".",
                'invoice' => [
                    'id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'amount' => (float) $invoice->amount,
                    'discount' => (float) $invoice->discount,
                    'net_amount' => (float) $invoice->net_amount,
                    'paid_amount' => (float) $invoice->paid_amount,
                    'due_amount' => (float) $invoice->due_amount,
                    'status' => $invoice->status,
                    'billing_period' => $billingPeriod,
                ]
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Restore Invoice Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Restoration failed: ' . $e->getMessage()
            ], 500);
        }
    }
}