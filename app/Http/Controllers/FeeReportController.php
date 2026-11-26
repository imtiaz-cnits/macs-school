<?php

namespace App\Http\Controllers;

use App\Models\FeePayment;
use App\Models\FeeInvoice;
use App\Models\Classes; // বা Classes (আপনার মডেলে যে নাম আছে)
use App\Services\AdmissionFeeReportService;
use App\Services\FeeReceiptService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FeeReportController extends Controller
{
    public function index(Request $request)
    {
        /** @var FeeReceiptService $receiptService */
        $receiptService = app(FeeReceiptService::class);
        /** @var AdmissionFeeReportService $admissionReportService */
        $admissionReportService = app(AdmissionFeeReportService::class);

        // Specific Month + Year resolution (backwards-compatible with start_date/end_date custom range)
        $monthMap = [
            '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
            '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
            '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December',
        ];

        $rawMonth = trim((string) $request->input('month', ''));
        $selectedMonth = '';
        $resolvedMonthNum = null;

        if ($rawMonth !== '') {
            if (is_numeric($rawMonth)) {
                $paddedMonth = str_pad((string) ((int) $rawMonth), 2, '0', STR_PAD_LEFT);
                if (isset($monthMap[$paddedMonth])) {
                    $resolvedMonthNum = $paddedMonth;
                    $selectedMonth = $monthMap[$paddedMonth];
                }
            } else {
                foreach ($monthMap as $num => $name) {
                    if (strcasecmp($name, $rawMonth) === 0) {
                        $resolvedMonthNum = $num;
                        $selectedMonth = $name;
                        break;
                    }
                }
            }
        }

        $rawYear = $request->input('year');
        $fallbackYear = $request->filled('start_date') && strtotime((string) $request->start_date)
            ? (int) date('Y', strtotime((string) $request->start_date))
            : (int) date('Y');
        $selectedYear = (is_numeric($rawYear) && (int) $rawYear >= 2000 && (int) $rawYear <= 2100)
            ? (int) $rawYear
            : $fallbackYear;

        if ($resolvedMonthNum !== null) {
            $startDate = sprintf('%04d-%02d-01', $selectedYear, (int) $resolvedMonthNum);
            $endDate = date('Y-m-t', strtotime($startDate));
        } else {
            // ডিফল্টভাবে চলতি মাসের শুরু থেকে আজকের তারিখ সেট করা হচ্ছে
            $startDate = $request->start_date ?? date('Y-m-01');
            $endDate = $request->end_date ?? date('Y-m-d');
        }

        $currentYearInt = (int) date('Y');
        $availableYears = collect(range($currentYearInt + 1, max(2020, $currentYearInt - 5)))
            ->push($selectedYear)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        $classId = $request->class_id;
        $branchId = $request->branch_id;
        $dueType = $request->input('due_type', 'period'); // 'period' (Monthly Due) | 'cumulative' (Cumulative Arrears)

        $admissionAvailable = $receiptService->admissionTablesAvailable();
        $paymentRelations = ['student.schoolClass', 'student.branch', 'invoice.feeSetup.category', 'collector'];
        $invoiceRelations = ['student.schoolClass', 'student.branch', 'feeSetup.category'];
        if ($admissionAvailable) {
            $paymentRelations = array_merge($paymentRelations, [
                'invoice.admissionFeeStructure',
                'invoice.admissionHeadAllocations.category',
                'admissionHeadAllocations',
            ]);
            $invoiceRelations = array_merge($invoiceRelations, [
                'admissionFeeStructure',
                'admissionHeadAllocations.category',
                'payments' => fn ($pq) => $pq->where('status', 'Active')->with('admissionHeadAllocations'),
            ]);
        }

        // ১. পেমেন্ট বা কালেকশনের কোয়েরি (Strictly Active Payments ONLY by payment_date)
        $paymentsQuery = FeePayment::with($paymentRelations)
            ->where('status', 'Active')
            ->whereBetween('payment_date', [$startDate, $endDate]);

        // ২. বকেয়া বা ডিউ এর কোয়েরি (Due / Arrears Report)
        $duesQuery = FeeInvoice::with($invoiceRelations)
            ->where('status', '!=', 'Cancelled')
            ->where('due_amount', '>', 0);

        // Phase 3 Fix: Eliminate created_at fallback and support distinct business logic for Period Due vs Cumulative Arrears
        if ($dueType === 'cumulative') {
            // Cumulative Arrears: All outstanding dues up to selected end_date
            // Excludes future-dated invoices (due_date > $endDate)
            $duesQuery->where('due_date', '<=', $endDate);
        } else {
            // Monthly / Period Due: Dues strictly falling within selected date range (due_date)
            // Future months generated in advance are strictly excluded
            $duesQuery->whereBetween('due_date', [$startDate, $endDate]);
        }

        // যদি নির্দিষ্ট ব্রাঞ্চ ফিল্টার থাকে
        if ($branchId) {
            $paymentsQuery->whereHas('student', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
            $duesQuery->whereHas('student', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        // যদি কোনো নির্দিষ্ট ক্লাস সিলেক্ট করে সার্চ করা হয়
        if ($classId) {
            $paymentsQuery->whereHas('student', function ($q) use ($classId) {
                $q->where('class_id', $classId);
            });
            $duesQuery->whereHas('student', function ($q) use ($classId) {
                $q->where('class_id', $classId);
            });
        }

        // ডাটাবেস থেকে ডাটা আনা হচ্ছে
        $payments = $paymentsQuery->orderBy('created_at', 'desc')->get();
        $dues = $duesQuery->orderBy('due_date', 'asc')->get();

        // মাস বের করার স্ট্যান্ডার্ড হেল্পার ফাংশন (FeeInvoice এর সেন্ট্রাল month_name ব্যবহার করে)
        $resolveMonth = function ($inv) {
            if (!$inv) return 'General';
            return $inv->month_name ?: 'General';
        };

        // রিসিট নম্বর থেকে কমন মাস্টার রিসিট প্রিফিক্স বের করার হেল্পার ফাংশন (Canonical FeePayment Extractor)
        $resolveMasterReceipt = function ($receiptNo) {
            return FeePayment::extractMasterReceiptNo($receiptNo);
        };

        // হেল্পার ফাংশন: পেমেন্ট রেকর্ড থেকে প্রকৃত কালেকশন তারিখ ও সময় নির্ধারণ
        $resolveCollectionDate = function ($paymentItems) {
            $first = $paymentItems->first();
            if (!$first) {
                return (object) [
                    'formatted' => 'Date unavailable',
                    'raw_date' => null,
                    'has_time' => false,
                ];
            }

            // FeePayment মডেলের created_at এবং payment_date
            $createdAt = $first->created_at ? Carbon::parse($first->created_at) : null;
            $paymentDateStr = $first->payment_date ? trim((string)$first->payment_date) : null;

            if ($createdAt && $paymentDateStr) {
                // payment_date এবং created_at একই দিন হলে created_at এর সঠিক তারিখ ও সময় ব্যবহার
                if ($createdAt->format('Y-m-d') === date('Y-m-d', strtotime($paymentDateStr))) {
                    return (object) [
                        'formatted' => $createdAt->format('d M Y, h:i A'),
                        'raw_date' => $createdAt,
                        'has_time' => true,
                    ];
                }

                // ব্যাকডেটেড পেমেন্ট হলে payment_date এর সাথে created_at এর সময় যুক্ত করা
                $timePart = $createdAt->format('H:i:s');
                $combined = Carbon::parse($paymentDateStr . ' ' . $timePart);
                return (object) [
                    'formatted' => $combined->format('d M Y, h:i A'),
                    'raw_date' => $combined,
                    'has_time' => true,
                ];
            }

            if ($createdAt) {
                return (object) [
                    'formatted' => $createdAt->format('d M Y, h:i A'),
                    'raw_date' => $createdAt,
                    'has_time' => true,
                ];
            }

            if ($paymentDateStr) {
                // শুধুমাত্র payment_date থাকলে কৃত্রিম 12:00 AM সময় না বানিয়ে শুধু তারিখ প্রদর্শন
                $d = Carbon::parse($paymentDateStr);
                return (object) [
                    'formatted' => $d->format('d M Y'),
                    'raw_date' => $d,
                    'has_time' => false,
                ];
            }

            return (object) [
                'formatted' => 'Date unavailable',
                'raw_date' => null,
                'has_time' => false,
            ];
        };

        $monthOrderMap = [
            'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
            'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
            'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12
        ];

        // Priority order for fee categories: Monthly Fee (1), Monthly Exam Fee (2), Term Exam Fee (3), others (4, alphabetical)
        $categoryPriorityMap = [
            'monthly fee' => 1,
            'monthly exam fee' => 2,
            'term exam fee' => 3,
        ];

        $categorySortComparator = function ($catA, $catB) use ($categoryPriorityMap) {
            $normA = strtolower(trim((string) $catA));
            $normB = strtolower(trim((string) $catB));

            $prioA = $categoryPriorityMap[$normA] ?? 4;
            $prioB = $categoryPriorityMap[$normB] ?? 4;

            if ($prioA !== $prioB) {
                return $prioA <=> $prioB;
            }

            return strcasecmp($normA, $normB);
        };

        // Strict Active Payment & Explicit Admission Head Isolation Metadata Preparation (Pure BCMath)
        foreach ($payments as $p) {
            $catInfo = $admissionReportService->aggregateActivePaymentCategoryAmounts($p);
            $p->report_effective_amount = $catInfo['effective_total'];
            $p->report_category_names = array_keys($catInfo['by_category']);

            if ($catInfo['is_admission']) {
                $structureName = trim((string) ($p->invoice?->admissionFeeStructure?->structure_name ?? 'Admission Fee'));
                if (!empty($catInfo['head_details'])) {
                    $headNames = array_map(fn (array $hd): string => $hd['head_name'], $catInfo['head_details']);
                    $p->report_fee_label = "Admission Fee Package — {$structureName} (" . implode(', ', $headNames) . ')';
                } else {
                    $p->report_fee_label = "Admission Fee Package — {$structureName} (No active heads matched)";
                    $p->report_category_names = ['Admission Fee Package'];
                }
            } else {
                $p->report_fee_label = (string) ($p->invoice?->feeSetup?->category?->name ?? 'Fee');
                if (empty($p->report_category_names)) {
                    $p->report_category_names = [$p->report_fee_label];
                }
            }
        }

        // কালেকশনগুলোকে স্টুডেন্ট ও মাস্টার রিসিট অনুযায়ী গ্রুপ করা হচ্ছে (Single Transaction / Bulk Collection Consolidated)
        $collections = $payments->groupBy(function ($item) use ($resolveMasterReceipt) {
            $masterReceipt = $resolveMasterReceipt($item->receipt_no);
            return $item->student_id . '_' . $masterReceipt;
        })->map(function ($items) use ($resolveMasterReceipt, $resolveMonth, $monthOrderMap, $resolveCollectionDate, $categorySortComparator, $admissionReportService) {
            $first = $items->first();
            $masterReceipt = $resolveMasterReceipt($first->receipt_no);

            $totalAmount = '0.00';
            foreach ($items as $it) {
                $totalAmount = bcadd($totalAmount, $admissionReportService->toDecimal($it->report_effective_amount), 2);
            }

            $colDateInfo = $resolveCollectionDate($items);

            $categories = $items->flatMap(function ($i) {
                return $i->report_category_names ?: ['Fee'];
            })->unique()->filter()->sort($categorySortComparator)->values();

            $months = $items->map(function ($i) use ($resolveMonth) {
                return $resolveMonth($i->invoice);
            })->unique()->filter()->values();

            // Group payments by resolved category names, sort by priority, and sort months chronologically (Jan to Dec)
            $categoryMonthsRaw = [];
            foreach ($items as $it) {
                $itMonth = $resolveMonth($it->invoice);
                foreach ($it->report_category_names ?: ['Fee'] as $catName) {
                    $categoryMonthsRaw[$catName][] = $itMonth;
                }
            }
            $categoryMonths = collect($categoryMonthsRaw)->map(function ($mArr) use ($monthOrderMap) {
                return collect($mArr)->unique()->filter()->sortBy(function ($m) use ($monthOrderMap) {
                    return $monthOrderMap[strtolower(trim($m))] ?? 99;
                })->values();
            })->sortKeysUsing($categorySortComparator);

            // Formatted string summary for export (e.g. Monthly Fee - Jan, Feb; Term Exam Fee - Apr)
            $feeDetailsSummary = $categoryMonths->map(function ($catMonths, $catName) {
                $validMonths = $catMonths->filter(function ($m) {
                    return !empty($m) && !in_array(strtolower(trim($m)), ['one time', 'one_time', 'general', '']);
                });
                if ($validMonths->isNotEmpty()) {
                    return $catName . ' - ' . $validMonths->join(', ');
                }
                return $catName;
            })->join('; ');

            $methods = $items->map(function ($i) {
                return $i->payment_method ?? 'Cash';
            })->unique()->filter()->values();

            // Sort payment items in breakdown dropdown by category priority, then by chronological month
            $sortedItems = $items->sort(function ($itemA, $itemB) use ($categorySortComparator, $monthOrderMap, $resolveMonth) {
                $catA = $itemA->report_category_names[0] ?? 'Fee';
                $catB = $itemB->report_category_names[0] ?? 'Fee';

                $catCmp = $categorySortComparator($catA, $catB);
                if ($catCmp !== 0) {
                    return $catCmp;
                }

                $monthA = $resolveMonth($itemA->invoice);
                $monthB = $resolveMonth($itemB->invoice);
                $ordA = $monthOrderMap[strtolower(trim($monthA))] ?? 99;
                $ordB = $monthOrderMap[strtolower(trim($monthB))] ?? 99;

                if ($ordA !== $ordB) {
                    return $ordA <=> $ordB;
                }

                return $itemA->id <=> $itemB->id;
            })->values();

            return (object) [
                'receipt_no' => $masterReceipt,
                'payment_date' => $colDateInfo->raw_date ? $colDateInfo->raw_date->format('Y-m-d') : null,
                'collection_date_formatted' => $colDateInfo->formatted,
                'collection_date_has_time' => $colDateInfo->has_time,
                'created_at' => $first->created_at,
                'student' => $first->student,
                'categories' => $categories,
                'categories_summary' => $categories->join(', '),
                'months' => $months,
                'months_summary' => $months->join(', '),
                'category_months' => $categoryMonths,
                'fee_details_summary' => $feeDetailsSummary,
                'items_count' => $items->count(),
                'payment_method' => $methods->join(', '),
                'paid_amount' => $totalAmount,
                'items' => $sortedItems
            ];
        })->values();

        // সামারি ক্যালকুলেশন - কালেকশন (Pure BCMath)
        $totalCollected = '0.00';
        $methodBreakdownArr = [];
        foreach ($payments as $p) {
            $effAmt = $admissionReportService->toDecimal($p->report_effective_amount);
            $totalCollected = bcadd($totalCollected, $effAmt, 2);
            $methodKey = (string) ($p->payment_method ?? 'Cash');
            $methodBreakdownArr[$methodKey] = bcadd($methodBreakdownArr[$methodKey] ?? '0.00', $effAmt, 2);
        }
        $methodBreakdown = collect($methodBreakdownArr);

        $totalPaymentRecordsCount = $payments->count();
        $totalCollectionCount = $collections->count();
        $uniquePayingStudentsCount = $payments->pluck('student_id')->unique()->count();

        // সামারি ক্যালকুলেশন - বকেয়া (Dues - Pure BCMath)
        $totalDue = '0.00';
        foreach ($dues as $dueInv) {
            $totalDue = bcadd($totalDue, $admissionReportService->toDecimal($dueInv->due_amount), 2);
        }
        $totalDueInvoicesCount = $dues->count();
        $uniqueDefaulterStudentsCount = $dues->pluck('student_id')->unique()->count();

        $resolveDueCategories = function (FeeInvoice $inv): array {
            if (!$inv->isAdmissionInvoice()) {
                return [(string) ($inv->feeSetup->category->name ?? 'Fee')];
            }

            $unpaidHeads = $inv->admissionHeadAllocations
                ->filter(fn ($h): bool => bccomp((string) $h->due_amount, '0.00', 2) > 0)
                ->pluck('fee_category_name_snapshot')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (!empty($unpaidHeads)) {
                return $unpaidHeads;
            }

            $allHeadCats = $inv->admissionHeadAllocations
                ->pluck('fee_category_name_snapshot')
                ->filter()
                ->unique()
                ->values()
                ->all();

            return !empty($allHeadCats) ? $allHeadCats : ['Admission Fee'];
        };

        // স্টুডেন্ট অনুযায়ী গ্রুপ করা ডিফল্টার লিস্ট (টোটাল ডিউ মাস ও মাসের নাম সহ)
        $defaulters = $dues->groupBy('student_id')->map(function ($invoices) use ($resolveMonth, $categorySortComparator, $resolveDueCategories, $admissionReportService) {
            $firstInv = $invoices->first();
            $student = $firstInv->student;

            $months = $invoices->map(function ($inv) use ($resolveMonth) {
                return $resolveMonth($inv);
            })->unique()->filter()->values();

            $categories = $invoices->flatMap(function ($inv) use ($resolveDueCategories) {
                return $resolveDueCategories($inv);
            })->unique()->filter()->sort($categorySortComparator)->values();

            $studentDueTotal = '0.00';
            foreach ($invoices as $inv) {
                $studentDueTotal = bcadd($studentDueTotal, $admissionReportService->toDecimal($inv->due_amount), 2);
            }

            return (object) [
                'student' => $student,
                'invoices' => $invoices,
                'total_due' => $studentDueTotal,
                'total_invoices' => $invoices->count(),
                'due_months' => $months,
                'due_months_count' => $months->count(),
                'categories' => $categories,
                'latest_due_date' => $invoices->max('due_date')
            ];
        })->sortByDesc('total_due')->values();

        // মাস অনুযায়ী মোট ডিউ টাকার সমষ্টি ও হিসাব (Pure BCMath)
        $dueMonthBreakdown = $dues->groupBy(function ($inv) use ($resolveMonth) {
            return $resolveMonth($inv);
        })->map(function ($group) use ($admissionReportService) {
            $amt = '0.00';
            foreach ($group as $inv) {
                $amt = bcadd($amt, $admissionReportService->toDecimal($inv->due_amount), 2);
            }
            return (object) [
                'amount' => $amt,
                'invoices_count' => $group->count(),
                'students_count' => $group->pluck('student_id')->unique()->count()
            ];
        });

        // পরিপূরক সামারি মেট্রিক (উভয় রিপোর্টের সারাংশ ভিউ-তে প্রদর্শনের জন্য)
        // ১. কিউমুলেটিভ বকেয়া সারাংশ (Cumulative Arrears up to $endDate)
        $cumulativeQuery = FeeInvoice::where('status', '!=', 'Cancelled')
            ->where('due_amount', '>', 0)
            ->where('due_date', '<=', $endDate);
        if ($branchId) {
            $cumulativeQuery->whereHas('student', fn($q) => $q->where('branch_id', $branchId));
        }
        if ($classId) {
            $cumulativeQuery->whereHas('student', fn($q) => $q->where('class_id', $classId));
        }
        $cumulativeTotalDue = '0.00';
        foreach ($cumulativeQuery->get(['id', 'due_amount']) as $cInv) {
            $cumulativeTotalDue = bcadd($cumulativeTotalDue, $admissionReportService->toDecimal($cInv->due_amount), 2);
        }
        $cumulativeDueInvoicesCount = (int) $cumulativeQuery->count();
        $cumulativeDefaulterCount = (int) $cumulativeQuery->distinct('student_id')->count('student_id');

        // ২. পিরিয়ড বকেয়া সারাংশ (Period Due strictly between $startDate and $endDate)
        $periodQuery = FeeInvoice::where('status', '!=', 'Cancelled')
            ->where('due_amount', '>', 0)
            ->whereBetween('due_date', [$startDate, $endDate]);
        if ($branchId) {
            $periodQuery->whereHas('student', fn($q) => $q->where('branch_id', $branchId));
        }
        if ($classId) {
            $periodQuery->whereHas('student', fn($q) => $q->where('class_id', $classId));
        }
        $periodTotalDue = '0.00';
        foreach ($periodQuery->get(['id', 'due_amount']) as $pInv) {
            $periodTotalDue = bcadd($periodTotalDue, $admissionReportService->toDecimal($pInv->due_amount), 2);
        }
        $periodDueInvoicesCount = (int) $periodQuery->count();
        $periodDefaulterCount = (int) $periodQuery->distinct('student_id')->count('student_id');

        // ড্রপডাউনের জন্য ব্রাঞ্চ ও ক্লাসের লিস্ট
        $branches = \App\Models\Branch::all();
        $classes = Classes::all();

        // সিএসভি এক্সপোর্টের জন্য প্রস্তুতকৃত ডাটা
        $collectionExportData = $collections->map(function ($col, $index) use ($admissionReportService) {
            return [
                'sl' => $index + 1,
                'receipt_no' => $col->receipt_no ?? 'N/A',
                'date' => $col->collection_date_formatted ?? 'Date unavailable',
                'student_name' => $col->student->student_name ?? 'N/A',
                'student_id' => $col->student->student_identity ?? 'N/A',
                'class' => $col->student->schoolClass->class_name ?? 'N/A',
                'roll' => $col->student->roll_number ?? 'N/A',
                'category' => $col->fee_details_summary ?: ($col->categories_summary ?: 'Fee'),
                'month' => $col->months_summary ?: 'General',
                'items_count' => $col->items_count,
                'method' => $col->payment_method ?? 'Cash',
                'amount' => $admissionReportService->toDecimal($col->paid_amount),
            ];
        });

        $defaultersExportData = $defaulters->map(function ($def, $index) use ($admissionReportService) {
            return [
                'sl' => $index + 1,
                'student_name' => $def->student->student_name ?? 'N/A',
                'student_id' => $def->student->student_identity ?? 'N/A',
                'phone' => $def->student->phone ?? 'N/A',
                'class' => $def->student->schoolClass->class_name ?? 'N/A',
                'roll' => $def->student->roll_number ?? 'N/A',
                'due_months_count' => $def->due_months_count,
                'due_months' => $def->due_months->join(', '),
                'categories' => $def->categories->join(', '),
                'total_invoices' => $def->total_invoices,
                'total_due' => $admissionReportService->toDecimal($def->total_due),
            ];
        });

        $totalCollectedFormatted = $admissionReportService->toDecimal($totalCollected);
        $totalDueFormatted = $admissionReportService->toDecimal($totalDue);

        return view('pages.fees.reports', compact(
            'payments',
            'collections',
            'dues',
            'defaulters',
            'totalCollected',
            'totalDue',
            'totalCollectedFormatted',
            'totalDueFormatted',
            'totalCollectionCount',
            'totalPaymentRecordsCount',
            'uniquePayingStudentsCount',
            'totalDueInvoicesCount',
            'uniqueDefaulterStudentsCount',
            'methodBreakdown',
            'dueMonthBreakdown',
            'startDate',
            'endDate',
            'selectedMonth',
            'selectedYear',
            'availableYears',
            'classId',
            'branchId',
            'branches',
            'classes',
            'collectionExportData',
            'defaultersExportData',
            'dueType',
            'cumulativeTotalDue',
            'cumulativeDueInvoicesCount',
            'cumulativeDefaulterCount',
            'periodTotalDue',
            'periodDueInvoicesCount',
            'periodDefaulterCount'
        ));
    }

    // ২. ক্যাটাগরি বা খাত অনুযায়ী ফি সামারি রিপোর্ট (Legacy + Head-Decomposed Admission Invoices)
    public function summaryReport(Request $request)
    {
        /** @var FeeReceiptService $receiptService */
        $receiptService = app(FeeReceiptService::class);
        /** @var AdmissionFeeReportService $admissionReportService */
        $admissionReportService = app(AdmissionFeeReportService::class);

        // ড্রপডাউনের ডাটা
        $branches = \App\Models\Branch::all();
        $sessions = \App\Models\SessionYear::latest()->get();
        $classes = \App\Models\Classes::all(); // আপনার মডেলে SchoolClass থাকলে সেটা দিবেন

        // ফিল্টারের ইনপুটগুলো
        $branchId = $request->branch_id;
        $sessionId = $request->session_year_id;
        $classId = $request->class_id;

        $admissionAvailable = $receiptService->admissionTablesAvailable();
        $relations = ['feeSetup.category'];
        if ($admissionAvailable) {
            $relations = array_merge($relations, [
                'admissionFeeStructure',
                'admissionHeadAllocations.category',
                'payments' => fn ($pq) => $pq->where('status', 'Active')->with('admissionHeadAllocations'),
            ]);
        }

        // ইনভয়েস কোয়েরি (Legacy feeSetup এবং Admission Structure উভয় সমর্থনসহ)
        $query = FeeInvoice::with($relations)->where('status', '!=', 'Cancelled');

        if (!$admissionAvailable) {
            $query->whereNull('admission_fee_structure_id');
        }

        if ($branchId || $sessionId || $classId) {
            $query->where(function ($scopeQuery) use ($branchId, $sessionId, $classId, $admissionAvailable): void {
                // Legacy Scope Filter
                $scopeQuery->where(function ($legacyQ) use ($branchId, $sessionId, $classId): void {
                    $legacyQ->whereNull('fee_invoices.admission_fee_structure_id')
                            ->whereHas('feeSetup', function ($q) use ($branchId, $sessionId, $classId): void {
                                if ($branchId) {
                                    $q->where('branch_id', $branchId);
                                }
                                if ($sessionId) {
                                    $q->where('session_year_id', $sessionId);
                                }
                                if ($classId) {
                                    $q->where('class_id', $classId);
                                }
                            });
                });

                // Admission Historical Scope Filter (branch_id, session_year_id, and historical admission_fee_structures.class_id)
                if ($admissionAvailable) {
                    $scopeQuery->orWhere(function ($admQ) use ($branchId, $sessionId, $classId): void {
                        $admQ->whereNotNull('fee_invoices.admission_fee_structure_id');
                        if ($branchId) {
                            $admQ->where('fee_invoices.branch_id', $branchId);
                        }
                        if ($sessionId) {
                            $admQ->where('fee_invoices.session_year_id', $sessionId);
                        }
                        if ($classId) {
                            $admQ->whereHas('admissionFeeStructure', fn ($sq) => $sq->where('class_id', $classId));
                        }
                    });
                }
            });
        }

        $invoices = $query->get();

        $categoryPriorityMap = [
            'monthly fee' => 1,
            'monthly exam fee' => 2,
            'term exam fee' => 3,
        ];

        $categorySortComparator = function ($catA, $catB) use ($categoryPriorityMap) {
            $normA = strtolower(trim((string) $catA));
            $normB = strtolower(trim((string) $catB));

            $prioA = $categoryPriorityMap[$normA] ?? 4;
            $prioB = $categoryPriorityMap[$normB] ?? 4;

            if ($prioA !== $prioB) {
                return $prioA <=> $prioB;
            }

            return strcasecmp($normA, $normB);
        };

        // Pure BCMath Category Aggregation (Legacy Invoice + Head-Decomposed Admission Invoice)
        $bucketTotals = [];
        $overallNet = '0.00';
        $overallPaid = '0.00';
        $overallDue = '0.00';

        foreach ($invoices as $invoice) {
            if ($invoice->isAdmissionInvoice() && $admissionAvailable) {
                $headSummary = $admissionReportService->summarizeAdmissionInvoiceActiveHeads($invoice);
                foreach ($headSummary['heads'] as $head) {
                    $catName = $head['category_name'];
                    if (!isset($bucketTotals[$catName])) {
                        $bucketTotals[$catName] = ['net' => '0.00', 'paid' => '0.00', 'due' => '0.00'];
                    }
                    $bucketTotals[$catName]['net'] = bcadd($bucketTotals[$catName]['net'], $head['net'], 2);
                    $bucketTotals[$catName]['paid'] = bcadd($bucketTotals[$catName]['paid'], $head['collected'], 2);
                    $bucketTotals[$catName]['due'] = bcadd($bucketTotals[$catName]['due'], $head['due'], 2);

                    $overallNet = bcadd($overallNet, $head['net'], 2);
                    $overallPaid = bcadd($overallPaid, $head['collected'], 2);
                    $overallDue = bcadd($overallDue, $head['due'], 2);
                }
            } else {
                $catName = (string) ($invoice->feeSetup->category->name ?? 'Uncategorized');
                $invNet = $admissionReportService->toDecimal($invoice->net_amount);
                $invFine = $invoice->fineAmountDecimal();
                $invPaid = $admissionReportService->toDecimal($invoice->paid_amount);
                $invDue = $admissionReportService->clampNonNegative($admissionReportService->toDecimal($invoice->due_amount));

                $finePaid = '0.00';
                $fineDue = '0.00';
                if (bccomp($invFine, '0.00', 2) > 0) {
                    if (bccomp($invPaid, $invNet, 2) > 0) {
                        $overNet = bcsub($invPaid, $invNet, 2);
                        $finePaid = bccomp($overNet, $invFine, 2) <= 0 ? $overNet : $invFine;
                    }
                    $fineDueRaw = $admissionReportService->clampNonNegative(bcsub($invFine, $finePaid, 2));
                    $fineDue = bccomp($fineDueRaw, $invDue, 2) <= 0 ? $fineDueRaw : $invDue;
                }

                $basePaid = $admissionReportService->clampNonNegative(bcsub($invPaid, $finePaid, 2));
                $baseDue = $admissionReportService->clampNonNegative(bcsub($invDue, $fineDue, 2));

                if (!isset($bucketTotals[$catName])) {
                    $bucketTotals[$catName] = ['net' => '0.00', 'paid' => '0.00', 'due' => '0.00'];
                }
                $bucketTotals[$catName]['net'] = bcadd($bucketTotals[$catName]['net'], $invNet, 2);
                $bucketTotals[$catName]['paid'] = bcadd($bucketTotals[$catName]['paid'], $basePaid, 2);
                $bucketTotals[$catName]['due'] = bcadd($bucketTotals[$catName]['due'], $baseDue, 2);

                if (bccomp($invFine, '0.00', 2) > 0) {
                    if (!isset($bucketTotals['Fine'])) {
                        $bucketTotals['Fine'] = ['net' => '0.00', 'paid' => '0.00', 'due' => '0.00'];
                    }
                    $bucketTotals['Fine']['net'] = bcadd($bucketTotals['Fine']['net'], $invFine, 2);
                    $bucketTotals['Fine']['paid'] = bcadd($bucketTotals['Fine']['paid'], $finePaid, 2);
                    $bucketTotals['Fine']['due'] = bcadd($bucketTotals['Fine']['due'], $fineDue, 2);
                }

                $overallNet = bcadd($overallNet, bcadd($invNet, $invFine, 2), 2);
                $overallPaid = bcadd($overallPaid, $invPaid, 2);
                $overallDue = bcadd($overallDue, $invDue, 2);
            }
        }

        $categorySummary = collect($bucketTotals)->map(function (array $totals) use ($admissionReportService) {
            return (object) [
                'total_net' => $totals['net'],
                'total_paid' => $totals['paid'],
                'total_due' => $totals['due'],
                'percentage' => $admissionReportService->percentage($totals['paid'], $totals['net']),
            ];
        })->sortKeysUsing($categorySortComparator);

        $overallPercentage = $admissionReportService->percentage($overallPaid, $overallNet);

        return view('pages.fees.summary_report', compact(
            'branches',
            'sessions',
            'classes',
            'categorySummary',
            'branchId',
            'sessionId',
            'classId',
            'overallNet',
            'overallPaid',
            'overallDue',
            'overallPercentage'
        ));
    }
}
