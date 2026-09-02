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
        // ডিফল্টভাবে চলতি মাসের শুরু থেকে আজকের তারিখ সেট করা হচ্ছে
        $startDate = $request->start_date ?? date('Y-m-01');
        $endDate = $request->end_date ?? date('Y-m-d');
        $classId = $request->class_id;
        $branchId = $request->branch_id;

        // ১. পেমেন্ট বা কালেকশনের কোয়েরি
        $paymentsQuery = FeePayment::with(['student.schoolClass', 'student.branch', 'invoice.feeSetup.category', 'collector'])
                                   ->whereBetween('payment_date', [$startDate, $endDate]);

        // ২. বকেয়া বা ডিউ এর কোয়েরি
        $duesQuery = FeeInvoice::with(['student.schoolClass', 'student.branch', 'feeSetup.category'])
                               ->where('due_amount', '>', 0);

        // যদি নির্দিষ্ট তারিখ রেঞ্জ ফিল্টার করা হয়
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $duesQuery->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('due_date', [$startDate, $endDate])
                  ->orWhereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            });
        }

        // যদি নির্দিষ্ট ব্রাঞ্চ ফিল্টার থাকে
        if ($branchId) {
            $paymentsQuery->whereHas('student', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
            $duesQuery->whereHas('student', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

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

        // মাস বের করার স্ট্যান্ডার্ড হেল্পার ফাংশন
        $resolveMonth = function($inv) {
            if ($inv->feeSetup && $inv->feeSetup->fee_month && !in_array(strtolower(trim($inv->feeSetup->fee_month)), ['monthly', 'one time', 'one_time', ''])) {
                return ucfirst(trim($inv->feeSetup->fee_month));
            }
            if ($inv->due_date) {
                return date('F', strtotime($inv->due_date));
            }
            if ($inv->created_at) {
                return date('F', strtotime($inv->created_at));
            }
            return 'General';
        };

        // সামারি ক্যালকুলেশন - কালেকশন
        $totalCollected = $payments->sum('paid_amount');
        $totalCollectionCount = $payments->count();
        $uniquePayingStudentsCount = $payments->pluck('student_id')->unique()->count();

        // কোন মেথডে কত টাকা আসলো তার হিসাব
        $methodBreakdown = $payments->groupBy('payment_method')->map(function ($row) {
            return $row->sum('paid_amount');
        });

        // সামারি ক্যালকুলেশন - বকেয়া (Dues)
        $totalDue = $dues->sum('due_amount');
        $totalDueInvoicesCount = $dues->count();
        $uniqueDefaulterStudentsCount = $dues->pluck('student_id')->unique()->count();

        // স্টুডেন্ট অনুযায়ী গ্রুপ করা ডিফল্টার লিস্ট (টোটাল ডিউ মাস ও মাসের নাম সহ)
        $defaulters = $dues->groupBy('student_id')->map(function($invoices) use ($resolveMonth) {
            $firstInv = $invoices->first();
            $student = $firstInv->student;

            $months = $invoices->map(function($inv) use ($resolveMonth) {
                return $resolveMonth($inv);
            })->unique()->filter()->values();

            $categories = $invoices->map(function($inv) {
                return $inv->feeSetup->category->name ?? 'Fee';
            })->unique()->filter()->values();

            return (object) [
                'student' => $student,
                'invoices' => $invoices,
                'total_due' => $invoices->sum('due_amount'),
                'total_invoices' => $invoices->count(),
                'due_months' => $months,
                'due_months_count' => $months->count(),
                'categories' => $categories,
                'latest_due_date' => $invoices->max('due_date')
            ];
        })->sortByDesc('total_due')->values();

        // মাস অনুযায়ী মোট ডিউ টাকার সমষ্টি ও হিসাব
        $dueMonthBreakdown = $dues->groupBy(function($inv) use ($resolveMonth) {
            return $resolveMonth($inv);
        })->map(function($group) {
            return (object) [
                'amount' => $group->sum('due_amount'),
                'invoices_count' => $group->count(),
                'students_count' => $group->pluck('student_id')->unique()->count()
            ];
        });

        // ড্রপডাউনের জন্য ব্রাঞ্চ ও ক্লাসের লিস্ট
        $branches = \App\Models\Branch::all();
        $classes = Classes::all();

        return view('pages.fees.reports', compact(
            'payments', 'dues', 'defaulters', 'totalCollected', 'totalDue', 
            'totalCollectionCount', 'uniquePayingStudentsCount',
            'totalDueInvoicesCount', 'uniqueDefaulterStudentsCount',
            'methodBreakdown', 'dueMonthBreakdown',
            'startDate', 'endDate', 'classId', 'branchId', 'branches', 'classes'
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