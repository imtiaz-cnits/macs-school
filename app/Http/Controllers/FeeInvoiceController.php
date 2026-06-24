<?php

namespace App\Http\Controllers;

use App\Models\FeeInvoice;
use App\Models\FeeSetup;
use App\Models\Student;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\SessionYear;
use App\Models\FeeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // ১. চেক করা হচ্ছে এই ক্যাটাগরির ফি সেটআপ করা আছে কিনা
        $feeSetup = FeeSetup::where([
            'branch_id' => $request->branch_id,
            'class_id' => $request->class_id,
            'session_year_id' => $request->session_year_id,
            'fee_category_id' => $request->fee_category_id,
            'fee_month' => $request->fee_month,
        ])->first();

        if (!$feeSetup) {
            return back()->with('error', 'Error: No Fee Setup found! Please set up the fee amount for this class and month first.');
        }

        // ২. এই ক্লাসের সকল 'Active' স্টুডেন্টদের খুঁজে বের করা
        $students = Student::where([
            'branch_id' => $request->branch_id,
            'class_id' => $request->class_id,
            'session_year_id' => $request->session_year_id,
            'sms_status' => 'Active' // শুধু অ্যাক্টিভ স্টুডেন্টদের বিল হবে
        ])->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'Error: No active students found in this class!');
        }

        $generatedCount = 0;

        // ==========================================
        // সিরিয়াল ইনভয়েস নম্বর তৈরি করার স্মার্ট লজিক
        // ==========================================
        $branchCode = str_pad($request->branch_id, 2, "0", STR_PAD_LEFT);
        $prefix = 'INV-' . date('Y') . $branchCode . '-'; // যেমন: INV-202602-

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


        // ডাটাবেস ট্রানজেকশন
        DB::beginTransaction();
        try {
            foreach ($students as $student) {
                // ৩. চেক করা হচ্ছে এই স্টুডেন্টের নামে অলরেডি এই বিলটি করা আছে কিনা
                $exists = FeeInvoice::where('student_id', $student->id)
                                    ->where('fee_setup_id', $feeSetup->id)
                                    ->exists();

                if (!$exists) {
                    
                    // ৪. প্রতিবার নতুন ইনভয়েসের জন্য সিরিয়াল ১ করে বাড়ানো হচ্ছে
                    $lastSerial++;
                    // নতুন ইনভয়েস নম্বর তৈরি (যেমন: INV-202602-0001)
                    $invoiceNo = $prefix . str_pad($lastSerial, 4, '0', STR_PAD_LEFT);

                    FeeInvoice::create([
                        'invoice_no' => $invoiceNo,
                        'student_id' => $student->id,
                        'fee_setup_id' => $feeSetup->id,
                        'amount' => $feeSetup->amount,
                        'discount' => 0,
                        'net_amount' => $feeSetup->amount,
                        'due_amount' => $feeSetup->amount,
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
}