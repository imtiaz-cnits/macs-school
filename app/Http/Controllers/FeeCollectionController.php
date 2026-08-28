<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\Section;
use App\Models\SessionYear;
use App\Models\FeeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class FeeCollectionController extends Controller
{
    // ১. স্টুডেন্ট সার্চ, বকেয়া লিস্ট এবং পেমেন্ট হিস্ট্রি দেখানোর পেজ
    public function index(Request $request)
    {
        $student = null;
        $invoices = collect();
        $paymentHistory = collect(); // নাম পরিবর্তন করে paymentHistory রাখা হলো
        
        // ড্রপডাউনের জন্য ডাটা নিয়ে আসছি
        $branches = Branch::all();
        $classes = Classes::all();
        $sections = Section::all();
        $sessions = SessionYear::latest()->get();
        $categories = FeeCategory::where('status', 'Active')->get();

        if ($request->get('mode') !== 'bulk' && $request->filled('student_identity')) {
            $student = Student::with(['schoolClass', 'section', 'branch'])
                              ->where('student_identity', $request->student_identity)
                              ->first();

            if ($student) {
                // ১. স্টুডেন্টের আনপেইড বা আংশিক পেইড ইনভয়েসগুলো (বকেয়া)
                $invoices = FeeInvoice::with('feeSetup.category')
                                      ->where('student_id', $student->id)
                                      ->whereIn('status', ['Unpaid', 'Partial'])
                                      ->orderBy('due_date', 'asc')
                                      ->get();

                // ২. স্টুডেন্টের পেমেন্ট হিস্ট্রি (পেমেন্ট টেবিল থেকে এনে গ্রুপ করা হচ্ছে)
                $rawPayments = FeePayment::with('invoice.feeSetup.category')
                                          ->where('student_id', $student->id)
                                          ->orderBy('created_at', 'desc')
                                          ->get();

                // একই মাস্টার রিসিটের পেমেন্টগুলোকে একটি গ্রুপে (Single Row) আনা হচ্ছে
                $paymentHistory = $rawPayments->groupBy(function($item) {
                    // ডুপ্লিকেট এরর এড়াতে আমরা যে ID যুক্ত করেছিলাম, সেটা বাদ দিয়ে আসল রিসিট নম্বরটি বের করা হচ্ছে
                    $parts = explode('-', $item->receipt_no);
                    return $parts[0] . '-' . $parts[1] . '-' . $parts[2]; // e.g. REC-20260405-9352
                });
            } else {
                return redirect()->route('fees.collection.index')->with('error', 'No student found with this ID!');
            }
        }

        // বাল্ক ইনভয়েস কুয়েরি (Class / Category wise bulk dues)
        $bulkInvoices = collect();
        if ($request->get('mode') === 'bulk' && $request->filled(['branch_id', 'session_year_id', 'class_id', 'fee_category_id'])) {
            $query = FeeInvoice::with(['student.schoolClass', 'student.section', 'feeSetup.category'])
                ->whereIn('status', ['Unpaid', 'Partial']);

            $query->whereHas('student', function($q) use ($request) {
                $q->where('branch_id', $request->branch_id)
                  ->where('class_id', $request->class_id);
                if ($request->filled('section_id')) {
                    $q->where('section_id', $request->section_id);
                }
            });

            $query->whereHas('feeSetup', function($q) use ($request) {
                $q->where('session_year_id', $request->session_year_id)
                  ->where('fee_category_id', $request->fee_category_id);
                if ($request->filled('fee_month')) {
                    $q->where(function($sub) use ($request) {
                        $sub->where('fee_month', $request->fee_month)
                            ->orWhere('fee_month', 'Monthly')
                            ->orWhereNull('fee_month')
                            ->orWhere('fee_month', '');
                    });
                }
            });

            if ($request->filled('fee_month')) {
                $monthIndex = array_search(ucfirst(strtolower($request->fee_month)), [
                    'January', 'February', 'March', 'April', 'May', 'June', 
                    'July', 'August', 'September', 'October', 'November', 'December'
                ]);
                if ($monthIndex !== false) {
                    $monthNum = $monthIndex + 1;
                    $query->whereMonth('due_date', $monthNum);
                }
            }

            $bulkInvoices = $query->orderBy('invoice_no', 'asc')->get();
        }

        $dueStudents = collect();
        if ($request->get('mode') !== 'bulk' && !$request->filled('student_identity')) {
            $dueStudents = Student::with(['schoolClass', 'section', 'branch'])
                ->whereHas('invoices', function($q) {
                    $q->whereIn('status', ['Unpaid', 'Partial']);
                })
                ->withSum(['invoices as total_due' => function($q) {
                    $q->whereIn('status', ['Unpaid', 'Partial']);
                }], 'due_amount')
                ->latest('updated_at')
                ->paginate(10)
                ->withQueryString();
        }

        // ভিউ ফাইল লোড করা
        return view('pages.fees.collection', compact(
            'student', 
            'invoices', 
            'paymentHistory', 
            'branches', 
            'classes', 
            'sections', 
            'sessions', 
            'categories', 
            'bulkInvoices',
            'dueStudents'
        ));
    }

    // ২. সিঙ্গেল টাকা জমা নেওয়ার লজিক
    public function store(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:fee_invoices,id',
            'pay_amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'transaction_id' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // ডাটাবেস লক করে ইনভয়েস আনা হচ্ছে যাতে একই সাথে ডাবল পেমেন্ট না হয়
            $invoice = FeeInvoice::lockForUpdate()->findOrFail($request->invoice_id);

            if ($request->pay_amount > $invoice->due_amount) {
                return back()->with('error', 'Error: Paying amount cannot be greater than the Due amount!');
            }

            // ইউনিক মানি রিসিট নম্বর তৈরি
            $receiptNo = 'REC-' . date('Ymd') . '-' . rand(1000, 9999);

            // পেমেন্ট রেকর্ড সেভ করা
            FeePayment::create([
                'receipt_no' => $receiptNo,
                'fee_invoice_id' => $invoice->id,
                'student_id' => $invoice->student_id,
                'paid_amount' => $request->pay_amount,
                'payment_date' => date('Y-m-d'),
                'payment_method' => $request->payment_method,
                'transaction_id' => $request->transaction_id,
                'note' => $request->note,
                'collected_by' => Auth::id()
            ]);

            // ইনভয়েসের ব্যালেন্স আপডেট করা
            $invoice->paid_amount += $request->pay_amount;
            $invoice->due_amount -= $request->pay_amount;
            
            // যদি বকেয়া ০ হয় তবে স্ট্যাটাস Paid, নাহলে Partial
            $invoice->status = $invoice->due_amount <= 0 ? 'Paid' : 'Partial';
            $invoice->save();

            DB::commit();

            // পেমেন্ট সাকসেস হলে রিসিট প্রিন্ট করার অপশনসহ রিডাইরেক্ট
            return redirect()->route('fees.collection.index', ['student_identity' => $invoice->student->student_identity])
                             ->with('success', 'Payment collected successfully! Receipt No: ' . $receiptNo)
                             ->with('print_invoice_id', $invoice->id); 

        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Payment failed due to system error: ' . $e->getMessage());
        }
    }

    // ৩. একসাথে একাধিক বিল (Bulk) পেমেন্ট নেওয়ার লজিক
    public function bulkStore(Request $request)
    {
        $request->validate([
            'invoice_ids' => 'required|array', // চেকবক্স থেকে আইডিগুলো আসবে
            'invoice_ids.*' => 'exists:fee_invoices,id',
            'payment_method' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            // সবার জন্য একটি মাত্র কমন মাস্টার রিসিট নম্বর তৈরি
            $masterReceiptNo = 'REC-' . date('Ymd') . '-' . rand(1000, 9999);
            $totalPaid = 0;
            $studentId = null;

            foreach ($request->invoice_ids as $inv_id) {
                $invoice = FeeInvoice::lockForUpdate()->find($inv_id);
                
                if ($invoice && $invoice->due_amount > 0) {
                    $studentId = $invoice->student_id; 
                    $payAmount = $invoice->due_amount; 
                    
                    // পেমেন্ট রেকর্ড
                    FeePayment::create([
                        // ডাটাবেসের unique এরর এড়াতে রিসিটের সাথে ইনভয়েস আইডি যোগ করা হলো
                        'receipt_no' => $masterReceiptNo . '-' . $invoice->id, 
                        'fee_invoice_id' => $invoice->id,
                        'student_id' => $invoice->student_id,
                        'paid_amount' => $payAmount,
                        'payment_date' => date('Y-m-d'),
                        'payment_method' => $request->payment_method,
                        'collected_by' => Auth::id()
                    ]);

                    // ইনভয়েস আপডেট
                    $invoice->paid_amount += $payAmount;
                    $invoice->due_amount = 0;
                    $invoice->status = 'Paid';
                    $invoice->save();

                    $totalPaid += $payAmount;
                }
            }

            DB::commit();

            $studentInfo = Student::find($studentId);
            return redirect()->route('fees.collection.index', ['student_identity' => $studentInfo->student_identity])
                             ->with('success', "Bulk Payment of ৳{$totalPaid} collected! Receipt No: " . $masterReceiptNo)
                             ->with('print_receipt_no', $masterReceiptNo); // মূল রিসিট নম্বরটি পাঠানো হলো

        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Bulk Payment failed: ' . $e->getMessage());
        }
    }

    // ৪. Master POS (Thermal) রিসিট প্রিন্ট (একাধিক বিল একসাথে)
    public function printBulkPos($receipt_no)
    {
        // 'like' ব্যবহার করে এই মাস্টার রিসিটের আন্ডারে যতগুলো পেমেন্ট হয়েছে সব আনা হলো
        $payments = FeePayment::with(['invoice.feeSetup.category', 'student.schoolClass', 'collector'])
                              ->where('receipt_no', 'like', $receipt_no . '%')
                              ->get();

        if ($payments->isEmpty()) {
            abort(404, 'Receipt not found');
        }

        $student = $payments->first()->student;
        $collector = $payments->first()->collector;
        $date = $payments->first()->created_at;

        // Check if multiple students are present in this master receipt
        $isMultipleStudents = $payments->pluck('student_id')->unique()->count() > 1;

        return view('pages.fees.pos_bulk_receipt', compact('payments', 'receipt_no', 'student', 'collector', 'date', 'isMultipleStudents'));
    }

    // ৫. একসাথে একাধিক স্টুডেন্টের ফি কালেকশন নেওয়ার লজিক (ইন্ডিভিজুয়াল রিসিট একসাথে প্রিন্ট)
    public function printBulkIndividualPos($receipt_no)
    {
        // 'like' ব্যবহার করে এই মাস্টার রিসিটের আন্ডারে যতগুলো পেমেন্ট হয়েছে সব আনা হলো
        $payments = FeePayment::with(['invoice.student.schoolClass', 'invoice.student.section', 'invoice.feeSetup.category', 'invoice.user'])
                              ->where('receipt_no', 'like', $receipt_no . '%')
                              ->get();

        if ($payments->isEmpty()) {
            abort(404, 'Receipt not found');
        }

        // Get unique invoices associated with these payments
        $invoices = $payments->map(function($pay) {
            return $pay->invoice;
        })->unique('id');

        return view('pages.fees.pos_invoice_individual_bulk', compact('invoices', 'receipt_no'));
    }

    // ৫.১. সকল রশিদ/পেমেন্টের তালিকা দেখানোর লজিক
    public function paymentsIndex(Request $request)
    {
        $query = FeePayment::with(['student.schoolClass', 'student.section', 'invoice.feeSetup.category', 'collector'])
                           ->orderBy('id', 'desc');

        // Apply Branch filter
        if ($request->filled('branch_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }

        // Apply Class filter
        if ($request->filled('class_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('class_id', $request->class_id);
            });
        }

        // Apply Date Range filters
        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        // Apply Payment Method filter
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Apply General Search Query
        if ($request->filled('search_query')) {
            $search = $request->search_query;
            $query->where(function($q) use ($search) {
                $q->where('receipt_no', 'like', "%{$search}%")
                  ->orWhereHas('student', function($sq) use ($search) {
                      $sq->where('student_name', 'like', "%{$search}%")
                        ->orWhere('student_identity', 'like', "%{$search}%");
                  });
            });
        }

        $payments = $query->paginate(15)->withQueryString();

        $branches = Branch::all();
        $classes = Classes::all();

        return view('pages.fees.payments', compact('payments', 'branches', 'classes'));
    }

    // ৫.২. একাধিক নির্দিষ্ট রশিদ একসাথে প্রিন্ট করার লজিক
    public function printSelectedIndividualPos(Request $request)
    {
        $paymentIds = $request->input('payment_ids', []);
        if (empty($paymentIds)) {
            return back()->with('error', 'Please select at least one receipt to print.');
        }

        $payments = FeePayment::with(['invoice.student.schoolClass', 'invoice.student.section', 'invoice.feeSetup.category', 'invoice.user'])
                              ->whereIn('id', $paymentIds)
                              ->get();

        if ($payments->isEmpty()) {
            abort(404, 'No receipts found');
        }

        // Get unique invoices associated with these payments
        $invoices = $payments->map(function($pay) {
            return $pay->invoice;
        })->unique('id');

        $receipt_no = 'SELECTED-' . date('Ymd') . '-' . rand(10, 99);

        return view('pages.fees.pos_invoice_individual_bulk', compact('invoices', 'receipt_no'));
    }

    // ৫. একসাথে একাধিক স্টুডেন্টের ফি কালেকশন নেওয়ার লজিক (Bulk Collection)
    public function bulkStudentsStore(Request $request)
    {
        $request->validate([
            'invoice_ids' => 'required|array',
            'invoice_ids.*' => 'exists:fee_invoices,id',
            'paying_amounts' => 'required|array',
            'payment_method' => 'required|string',
            'transaction_id' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $totalCollected = 0;
            $count = 0;
            // Generate a master receipt prefix for the entire bulk collection batch
            $masterReceiptNo = 'REC-BULK-' . date('Ymd') . '-' . rand(1000, 9999);

            foreach ($request->invoice_ids as $invId) {
                // Paying amount for this specific invoice
                $payAmount = floatval($request->paying_amounts[$invId] ?? 0);

                if ($payAmount <= 0) {
                    continue; // Skip zero/negative payments
                }

                $invoice = FeeInvoice::with(['student', 'feeSetup'])->lockForUpdate()->findOrFail($invId);

                $feeSetup = $invoice->feeSetup;
                $feeCategoryId = $feeSetup->fee_category_id;
                $originalSetupAmount = floatval($feeSetup->amount);

                if ($payAmount != $originalSetupAmount) {
                    // Update or create the customized fee configuration for this student and category
                    \App\Models\StudentCustomFee::updateOrCreate(
                        [
                            'student_id' => $invoice->student_id,
                            'fee_category_id' => $feeCategoryId,
                        ],
                        [
                            'amount' => $payAmount
                        ]
                    );

                    // Recalculate this invoice's net amount, discount and due amount
                    $invoice->amount = $originalSetupAmount;
                    $invoice->discount = max(0, $originalSetupAmount - $payAmount);
                    $invoice->net_amount = $payAmount;
                    $invoice->due_amount = max(0, $payAmount - $invoice->paid_amount);
                } else {
                    // Revert/Delete the customized fee configuration if it matches standard setup
                    \App\Models\StudentCustomFee::where('student_id', $invoice->student_id)
                                                 ->where('fee_category_id', $feeCategoryId)
                                                 ->delete();

                    // Revert the invoice parameters to standard
                    $invoice->amount = $originalSetupAmount;
                    $invoice->discount = 0;
                    $invoice->net_amount = $originalSetupAmount;
                    $invoice->due_amount = max(0, $originalSetupAmount - $invoice->paid_amount);
                }

                if ($payAmount > $invoice->due_amount) {
                    return back()->with('error', "Error: Paying amount for student {$invoice->student->student_name} cannot be greater than the Due amount!");
                }

                // Master receipt number connected to individual invoice ID
                $receiptNo = $masterReceiptNo . '-' . $invoice->id;

                // পেমেন্ট রেকর্ড সেভ করা
                FeePayment::create([
                    'receipt_no' => $receiptNo,
                    'fee_invoice_id' => $invoice->id,
                    'student_id' => $invoice->student_id,
                    'paid_amount' => $payAmount,
                    'payment_date' => date('Y-m-d'),
                    'payment_method' => $request->payment_method,
                    'transaction_id' => $request->transaction_id,
                    'note' => $request->note,
                    'collected_by' => Auth::id()
                ]);

                // ইনভয়েসের ব্যালেন্স আপডেট করা
                $invoice->paid_amount += $payAmount;
                $invoice->due_amount -= $payAmount;
                
                // যদি বকেয়া ০ হয় তবে স্ট্যাটাস Paid, নাহলে Partial
                $invoice->status = $invoice->due_amount <= 0 ? 'Paid' : 'Partial';
                $invoice->save();

                $totalCollected += $payAmount;
                $count++;
            }

            DB::commit();

            return redirect()->route('fees.collection.index', [
                'mode' => 'bulk',
                'branch_id' => $request->branch_id,
                'session_year_id' => $request->session_year_id,
                'class_id' => $request->class_id,
                'section_id' => $request->section_id,
                'fee_category_id' => $request->fee_category_id,
                'fee_month' => $request->fee_month,
            ])->with('success', "Successfully collected payments for {$count} student(s)! Total: ৳" . number_format($totalCollected, 2))
              ->with('print_receipt_no', $masterReceiptNo);

        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Bulk Collection failed: ' . $e->getMessage());
        }
    }
}