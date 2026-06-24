<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
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

        if ($request->filled('student_identity')) {
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

        // ভিউ ফাইল লোড করা
        return view('pages.fees.collection', compact('student', 'invoices', 'paymentHistory'));
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

        return view('pages.fees.pos_bulk_receipt', compact('payments', 'receipt_no', 'student', 'collector', 'date'));
    }
}