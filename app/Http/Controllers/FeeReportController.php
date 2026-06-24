<?php

namespace App\Http\Controllers;

use App\Models\FeePayment;
use App\Models\FeeInvoice;
use App\Models\Classes; // বা Classes (আপনার মডেলে যে নাম আছে)
use Illuminate\Http\Request;

class FeeReportController extends Controller
{
    public function index(Request $request)
    {
        // ডিফল্টভাবে আজকের তারিখ সেট করা হচ্ছে
        $startDate = $request->start_date ?? date('Y-m-d');
        $endDate = $request->end_date ?? date('Y-m-d');
        $classId = $request->class_id;

        // ১. পেমেন্ট বা কালেকশনের কোয়েরি
        $paymentsQuery = FeePayment::with(['student.schoolClass', 'invoice.feeSetup.category', 'collector'])
                                   ->whereBetween('payment_date', [$startDate, $endDate]);

        // ২. বকেয়া বা ডিউ এর কোয়েরি
        $duesQuery = FeeInvoice::with(['student.schoolClass', 'feeSetup.category'])
                               ->where('due_amount', '>', 0);

        // যদি কোনো নির্দিষ্ট ক্লাস সিলেক্ট করে সার্চ করা হয়
        if ($classId) {
            $paymentsQuery->whereHas('student', function($q) use ($classId) {
                $q->where('class_id', $classId);
            });
            
            $duesQuery->whereHas('student', function($q) use ($classId) {
                $q->where('class_id', $classId);
            });
        }

        // ডাটাবেস থেকে ডাটা আনা হচ্ছে
        $payments = $paymentsQuery->orderBy('created_at', 'desc')->get();
        $dues = $duesQuery->orderBy('due_date', 'asc')->get();

        // সামারি ক্যালকুলেশন
        $totalCollected = $payments->sum('paid_amount');
        $totalDue = $dues->sum('due_amount');

        // কোন মেথডে (ক্যাশ/বিকাশ) কত টাকা আসলো তার হিসাব
        $methodBreakdown = $payments->groupBy('payment_method')->map(function ($row) {
            return $row->sum('paid_amount');
        });

        // ড্রপডাউনের জন্য ক্লাসের লিস্ট
       $classes = Classes::all();

        return view('pages.fees.reports', compact(
            'payments', 'dues', 'totalCollected', 'totalDue', 
            'methodBreakdown', 'startDate', 'endDate', 'classId', 'classes'
        ));
    }

    // ২. ক্যাটাগরি বা খাত অনুযায়ী ফি সামারি রিপোর্ট
    public function summaryReport(Request $request)
    {
        // ড্রপডাউনের ডাটা
        $branches = \App\Models\Branch::all();
        $sessions = \App\Models\SessionYear::latest()->get();
        $classes = \App\Models\Classes::all(); // আপনার মডেলে SchoolClass থাকলে সেটা দিবেন

        // ফিল্টারের ইনপুটগুলো
        $branchId = $request->branch_id;
        $sessionId = $request->session_year_id;
        $classId = $request->class_id;

        // ইনভয়েস কোয়েরি (FeeSetup এর সাথে জয়েন করে)
        $query = FeeInvoice::with('feeSetup.category');

        // যদি ফিল্টার সিলেক্ট করা থাকে
        if ($branchId || $sessionId || $classId) {
            $query->whereHas('feeSetup', function($q) use ($branchId, $sessionId, $classId) {
                if ($branchId) $q->where('branch_id', $branchId);
                if ($sessionId) $q->where('session_year_id', $sessionId);
                if ($classId) $q->where('class_id', $classId);
            });
        }

        $invoices = $query->get();

        // ম্যাজিক: ক্যাটাগরির নাম দিয়ে গ্রুপ করে টোটাল বের করা হচ্ছে
        $categorySummary = $invoices->groupBy(function($invoice) {
            return $invoice->feeSetup->category->name ?? 'Uncategorized';
        })->map(function($group) {
            $net = $group->sum('net_amount');
            $paid = $group->sum('paid_amount');
            $due = $group->sum('due_amount');
            
            // কত পারসেন্ট কালেকশন হলো তার হিসাব
            $percentage = $net > 0 ? round(($paid / $net) * 100, 1) : 0;

            return (object) [
                'total_net' => $net,
                'total_paid' => $paid,
                'total_due' => $due,
                'percentage' => $percentage
            ];
        });

        // ওভারঅল সামারি কার্ডের জন্য
        $overallNet = $invoices->sum('net_amount');
        $overallPaid = $invoices->sum('paid_amount');
        $overallDue = $invoices->sum('due_amount');
        $overallPercentage = $overallNet > 0 ? round(($overallPaid / $overallNet) * 100, 1) : 0;

        return view('pages.fees.summary_report', compact(
            'branches', 'sessions', 'classes', 'categorySummary',
            'branchId', 'sessionId', 'classId',
            'overallNet', 'overallPaid', 'overallDue', 'overallPercentage'
        ));
    }
}