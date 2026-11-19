<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentCustomFee;
use App\Models\FeeAdjustmentLog;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\Section;
use App\Models\SessionYear;
use App\Models\FeeCategory;
use App\Models\FeeSetup;
use App\Models\AdmissionFeeStructure;
use App\Models\User;
use App\Services\AdmissionCollectionService;
use App\Services\AdmissionInvoiceService;
use App\Services\AdmissionPaymentReversalService;
use App\Services\FeeReceiptService;
use App\Services\FinancialTransactionRunner;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Exception;

class FeeCollectionController extends Controller
{
    // ১. স্টুডেন্ট সার্চ, বকেয়া লিস্ট এবং পেমেন্ট হিস্ট্রি দেখানোর পেজ
    public function index(Request $request)
    {
        $student = null;
        $invoices = collect();
        $paymentHistory = collect(); // নাম পরিবর্তন করে paymentHistory রাখা হলো
        /** @var FeeReceiptService $receiptService */
        $receiptService = app(FeeReceiptService::class);
        $hasAdmissionSchema = $this->collectionAdmissionSchemaReady($receiptService);
        
        // ড্রপডাউনের জন্য ডাটা নিয়ে আসছি
        $branches = Branch::all();
        $classes = Classes::all();
        $sections = Section::all();
        $sessions = SessionYear::latest()->get();
        $categories = FeeCategory::where('status', 'Active')->get();

        $search = trim($request->get('search', $request->get('student_identity', '')));

        if ($request->get('mode') !== 'bulk' && !empty($search)) {
            // ১. স্টুডেন্ট আইডি দিয়ে সরাসরি চেক
            $student = Student::with(['schoolClass', 'section', 'branch'])
                              ->where('student_identity', $search)
                              ->first();

            // ২. আইডি না মিললে রোল নম্বর দিয়ে ফিল্টার চেক
            if (!$student && is_numeric($search) && strlen($search) <= 5) {
                $rollQuery = Student::with(['schoolClass', 'section', 'branch']);

                if ($request->filled('branch_id')) {
                    $rollQuery->where('branch_id', $request->branch_id);
                }
                if ($request->filled('session_year_id')) {
                    $rollQuery->where('session_year_id', $request->session_year_id);
                }
                if ($request->filled('class_id')) {
                    $rollQuery->where('class_id', $request->class_id);
                }
                if ($request->filled('section_id')) {
                    $rollQuery->where('section_id', $request->section_id);
                }

                $rollQuery->where(function($q) use ($search) {
                    $q->where('roll_number', $search)
                      ->orWhere('roll_number', (string)(int)$search)
                      ->orWhere('roll_number', sprintf('%02d', (int)$search));
                });

                $matchedRollStudents = $rollQuery->get();
                if ($matchedRollStudents->count() === 1) {
                    $student = $matchedRollStudents->first();
                } elseif ($matchedRollStudents->count() > 1) {
                    // একাধিক সেকশন থাকলে যে শিক্ষার্থীর বকেয়া আছে তাকে প্রাধান্য দেওয়া
                    $withDues = $matchedRollStudents->filter(function($st) {
                        return $st->invoices()->whereIn('status', ['Unpaid', 'Partial'])->where('due_amount', '>', 0)->exists();
                    });
                    if ($withDues->count() === 1) {
                        $student = $withDues->first();
                    }
                }
            }

            // ৩. পূর্ণ মোবাইল নম্বর দিয়ে চেক
            if (!$student && is_numeric($search) && strlen($search) >= 10) {
                $mobileQuery = Student::with(['schoolClass', 'section', 'branch'])
                    ->where('father_mobile', $search);
                if ($mobileQuery->count() === 1) {
                    $student = $mobileQuery->first();
                }
            }

            // ৪. পূর্ণ নাম দিয়ে চেক
            if (!$student && !is_numeric($search)) {
                $nameQuery = Student::with(['schoolClass', 'section', 'branch'])
                    ->where('student_name', $search);
                if ($request->filled('class_id')) {
                    $nameQuery->where('class_id', $request->class_id);
                }
                if ($nameQuery->count() === 1) {
                    $student = $nameQuery->first();
                }
            }

            if ($student) {
                $invoiceRelations = ['feeSetup.category'];
                $paymentRelations = ['invoice.feeSetup.category', 'collector'];
                if ($hasAdmissionSchema) {
                    $invoiceRelations[] = 'admissionFeeStructure';
                    $invoiceRelations[] = 'admissionHeadAllocations';
                    $paymentRelations[] = 'reversedBy';
                    $paymentRelations[] = 'invoice.admissionFeeStructure';
                    $paymentRelations[] = 'invoice.admissionHeadAllocations';
                    $paymentRelations[] = 'admissionHeadAllocations';
                }

                // ১. স্টুডেন্টের আনপেইড বা আংশিক পেইড ইনভয়েসগুলো (বকেয়া - লেটেস্ট মাস সবার উপরে)
                $invoices = FeeInvoice::with($invoiceRelations)
                                      ->where('student_id', $student->id)
                                      ->whereIn('status', ['Unpaid', 'Partial'])
                                      ->where('due_amount', '>', 0)
                                      ->orderBy('due_date', 'desc')
                                      ->orderBy('id', 'desc')
                                      ->get();

                if ($hasAdmissionSchema) {
                    $invoices->each(function (FeeInvoice $inv): void {
                        if ($inv->isAdmissionInvoice()) {
                            $sortedHeads = $inv->admissionHeadAllocations
                                ->sort(fn ($a, $b): int => [$a->sort_order, $a->id] <=> [$b->sort_order, $b->id])
                                ->map(static fn ($h): array => [
                                    'id' => (int) $h->id,
                                    'head_name' => (string) $h->head_name_snapshot,
                                    'category_name' => (string) $h->fee_category_name_snapshot,
                                    'amount' => bcadd((string) $h->amount, '0.00', 2),
                                    'discount' => bcadd((string) $h->discount, '0.00', 2),
                                    'net_amount' => bcadd((string) $h->net_amount, '0.00', 2),
                                    'allocated_paid_amount' => bcadd((string) $h->allocated_paid_amount, '0.00', 2),
                                    'due_amount' => bcadd((string) $h->due_amount, '0.00', 2),
                                    'is_discountable' => (bool) $h->is_discountable,
                                    'is_tuition_head' => (bool) $h->is_tuition_head,
                                    'sort_order' => (int) $h->sort_order,
                                ])
                                ->values()
                                ->all();
                            $inv->setAttribute('prepared_admission_heads', $sortedHeads);
                            $inv->setAttribute('prepared_structure_name', (string) ($inv->admissionFeeStructure->structure_name ?? 'Admission Fee Package'));
                        }
                    });
                }

                // ২. স্টুডেন্টের পেমেন্ট হিস্ট্রি (পেমেন্ট টেবিল থেকে এনে গ্রুপ করা হচ্ছে)
                $rawPayments = FeePayment::with($paymentRelations)
                                          ->where('student_id', $student->id)
                                          ->orderBy('created_at', 'desc')
                                          ->orderBy('id', 'desc')
                                          ->get();

                if ($hasAdmissionSchema) {
                    $rawPayments->each(function (FeePayment $pay) use ($receiptService): void {
                        if ($pay->invoice && $pay->invoice->isAdmissionInvoice()) {
                            $pay->setAttribute('prepared_alloc_selection', $receiptService->selectOrderedPaymentHeadAllocations($pay));
                        }
                    });
                }

                // একই মাস্টার রিসিটের পেমেন্টগুলোকে একটি গ্রুপে (Single Row) আনা হচ্ছে (REC-BULK- ও REC- উভয় সহ)
                $paymentHistory = $rawPayments->groupBy(function (FeePayment $item): string {
                    return FeePayment::extractMasterReceiptNo($item->receipt_no);
                });
            }
        }

        // বাল্ক ইনভয়েস কুয়েরি (Class / Category wise bulk dues)
        $bulkInvoices = collect();
        if ($request->get('mode') === 'bulk' && $request->filled(['branch_id', 'session_year_id', 'class_id', 'fee_category_id'])) {
            $query = FeeInvoice::with(['student.schoolClass', 'student.section', 'feeSetup.category'])
                ->whereIn('status', ['Unpaid', 'Partial'])
                ->where('due_amount', '>', 0);

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

        $dueStudents = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        $standardFeeColumns = collect();
        $standardFeeRows = collect();
        $standardFeeScopeLabel = '';
        $hasSearchQuery = !empty($search);

        if ($request->get('mode') !== 'bulk' && !$student) {
            if ($hasSearchQuery) {
                $query = Student::with(['schoolClass', 'section', 'branch'])
                    ->withSum(['invoices as total_due' => function($q) {
                        $q->whereIn('status', ['Unpaid', 'Partial'])
                          ->where('due_amount', '>', 0);
                    }], 'due_amount');

                if ($request->filled('branch_id')) {
                    $query->where('branch_id', $request->branch_id);
                }
                if ($request->filled('session_year_id')) {
                    $query->where('session_year_id', $request->session_year_id);
                }
                if ($request->filled('class_id')) {
                    $query->where('class_id', $request->class_id);
                }
                if ($request->filled('section_id')) {
                    $query->where('section_id', $request->section_id);
                }

                $isNumeric = is_numeric($search);
                if ($isNumeric && strlen($search) <= 5) {
                    // রোল নম্বর দিয়ে সার্চ করলে শুধু স্পেসিফিক রোল নম্বরটি ফিল্টার হবে
                    $query->where(function($q) use ($search) {
                        $q->where('roll_number', $search)
                          ->orWhere('roll_number', (string)(int)$search)
                          ->orWhere('roll_number', sprintf('%02d', (int)$search));
                    });
                } elseif ($isNumeric && strlen($search) >= 10) {
                    // মোবাইল অথবা পূর্ণ আইডি
                    $query->where(function($q) use ($search) {
                        $q->where('student_identity', $search)
                          ->orWhere('father_mobile', $search);
                    });
                } else {
                    // টেক্সট অথবা আংশিক সার্চ
                    $query->where(function($q) use ($search) {
                        $q->where('student_identity', 'like', "%{$search}%")
                          ->orWhere('student_name', 'like', "%{$search}%")
                          ->orWhere('father_mobile', 'like', "%{$search}%");
                    });
                }

                $dueStudents = $query->orderBy('class_id')
                    ->orderBy('roll_number')
                    ->paginate(10)
                    ->withQueryString();

                if ($dueStudents->total() === 0) {
                    session()->now('error', 'No student found matching "' . $search . '"!');
                }
            } else {
                // Class-wise Standard Fee Reference Chart (Default landing table on /fees/collection)
                $activeSessionId = $request->filled('session_year_id')
                    ? (int) $request->session_year_id
                    : (int) ($sessions->first()?->id ?? 0);
                $activeBranchId = $request->filled('branch_id')
                    ? (int) $request->branch_id
                    : null;
                $activeClassId = $request->filled('class_id')
                    ? (int) $request->class_id
                    : null;

                $scopeParts = [];
                if ($activeBranchId) {
                    $bName = $branches->firstWhere('id', $activeBranchId)?->branch_name;
                    if ($bName) {
                        $scopeParts[] = $bName;
                    }
                } elseif ($branches->count() === 1) {
                    $scopeParts[] = $branches->first()->branch_name;
                } else {
                    $scopeParts[] = 'All Branches';
                }

                $activeSessionObj = $activeSessionId ? $sessions->firstWhere('id', $activeSessionId) : null;
                if ($activeSessionObj) {
                    $scopeParts[] = 'Session ' . $activeSessionObj->session_name;
                }
                $standardFeeScopeLabel = implode(' • ', $scopeParts);

                // 1. Load active FeeSetups for the active scope
                $setupsQuery = FeeSetup::with('category')
                    ->where('status', 'Active');
                if ($activeBranchId) {
                    $setupsQuery->where('branch_id', $activeBranchId);
                }
                if ($activeSessionId) {
                    $setupsQuery->where('session_year_id', $activeSessionId);
                }
                if ($activeClassId) {
                    $setupsQuery->where('class_id', $activeClassId);
                }
                $activeSetups = $setupsQuery->get();

                // 2. Load active AdmissionFeeStructures (preferring specific session override, falling back to All-Time)
                $activeAdmByClass = collect();
                if ($hasAdmissionSchema) {
                    $admQuery = AdmissionFeeStructure::query()
                        ->where('status', 'Active')
                        ->where(function ($q) use ($activeSessionId) {
                            $q->whereNull('session_year_id');
                            if ($activeSessionId) {
                                $q->orWhere('session_year_id', $activeSessionId);
                            }
                        });
                    if ($activeBranchId) {
                        $admQuery->where('branch_id', $activeBranchId);
                    }
                    if ($activeClassId) {
                        $admQuery->where('class_id', $activeClassId);
                    }
                    $activeAdmByClass = $admQuery->get()
                        ->groupBy('class_id')
                        ->map(function ($group) use ($activeSessionId) {
                            // Prefer session-specific override over All-Time default, then highest version
                            return $group->sortByDesc(function ($s) use ($activeSessionId) {
                                $sessionScore = ($activeSessionId && (int) $s->session_year_id === $activeSessionId) ? 1 : 0;
                                return ($sessionScore * 1000000) + ((int) $s->version * 1000) + (int) $s->id;
                            })->first();
                        });
                }

                // 3. Collect distinct non-admission FeeSetup categories in priority order
                $priorityMap = [
                    'monthly fee' => 1,
                    'monthly exam fee' => 2,
                    'term exam fee' => 3,
                ];

                $hasAnyAdmissionStructure = $activeAdmByClass->isNotEmpty();

                $setupCategoryMap = [];
                $legacyAdmissionByClass = [];
                foreach ($activeSetups as $setup) {
                    $catName = trim((string) ($setup->category?->name ?? ''));
                    if ($catName === '') {
                        continue;
                    }
                    if (strcasecmp($catName, 'Admission Fee') === 0) {
                        $legacyAdmissionByClass[(int) $setup->class_id][] = (float) $setup->amount;
                        if ($hasAnyAdmissionStructure) {
                            continue;
                        }
                    }
                    $catId = (int) $setup->fee_category_id;
                    $setupCategoryMap[$catId] = $catName;
                }

                $orderedCategories = collect($setupCategoryMap)
                    ->map(fn (string $name, int $id): array => ['id' => $id, 'name' => $name])
                    ->sort(function (array $a, array $b) use ($priorityMap): int {
                        $pa = $priorityMap[strtolower($a['name'])] ?? 50;
                        $pb = $priorityMap[strtolower($b['name'])] ?? 50;
                        if ($pa !== $pb) {
                            return $pa <=> $pb;
                        }
                        return strcasecmp($a['name'], $b['name']);
                    })
                    ->values();

                // Group setup amounts by [class_id][fee_category_id] => unique sorted amounts
                $amountsByClassAndCat = [];
                foreach ($activeSetups as $setup) {
                    $cid = (int) $setup->class_id;
                    $catId = (int) $setup->fee_category_id;
                    $amountsByClassAndCat[$cid][$catId][] = (float) $setup->amount;
                }

                $standardFeeColumns = $orderedCategories->map(fn (array $c): array => [
                    'key' => 'cat_' . $c['id'],
                    'label' => $c['name'],
                    'type' => strtolower($c['name']) === 'monthly fee' ? 'monthly' : (str_contains(strtolower($c['name']), 'exam') ? 'exam' : 'other'),
                ])->values();

                if ($hasAnyAdmissionStructure) {
                    $standardFeeColumns->push([
                        'key' => 'admission_package',
                        'label' => 'Admission Package',
                        'type' => 'admission',
                    ]);
                }

                $targetClasses = $activeClassId
                    ? $classes->where('id', $activeClassId)->values()
                    : $classes->values();

                $standardFeeRows = $targetClasses->map(function ($cls) use ($orderedCategories, $amountsByClassAndCat, $hasAnyAdmissionStructure, $activeAdmByClass, $legacyAdmissionByClass): array {
                    $cid = (int) $cls->id;
                    $cells = [];

                    foreach ($orderedCategories as $cat) {
                        $vals = $amountsByClassAndCat[$cid][$cat['id']] ?? [];
                        if (empty($vals)) {
                            $cells['cat_' . $cat['id']] = null;
                        } else {
                            $minVal = min($vals);
                            $maxVal = max($vals);
                            $cells['cat_' . $cat['id']] = abs($maxVal - $minVal) < 0.005
                                ? '৳ ' . number_format($minVal, 2)
                                : '৳ ' . number_format($minVal, 0) . ' – ৳ ' . number_format($maxVal, 0);
                        }
                    }

                    if ($hasAnyAdmissionStructure) {
                        $admStruct = $activeAdmByClass->get($cid);
                        if ($admStruct) {
                            $cells['admission_package'] = [
                                'amount' => '৳ ' . number_format((float) $admStruct->total_amount, 2),
                                'includes_tuition' => (bool) $admStruct->includes_monthly_tuition,
                                'is_all_time' => $admStruct->session_year_id === null,
                            ];
                        } elseif (!empty($legacyAdmissionByClass[$cid])) {
                            $cells['admission_package'] = [
                                'amount' => '৳ ' . number_format(max($legacyAdmissionByClass[$cid]), 2),
                                'includes_tuition' => false,
                                'is_all_time' => false,
                            ];
                        } else {
                            $cells['admission_package'] = null;
                        }
                    }

                    return [
                        'class_id' => $cid,
                        'class_name' => (string) $cls->class_name,
                        'cells' => $cells,
                    ];
                })->values();
            }
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
            'dueStudents',
            'hasAdmissionSchema',
            'standardFeeColumns',
            'standardFeeRows',
            'standardFeeScopeLabel',
            'hasSearchQuery'
        ));
    }

    // ২. সিঙ্গেল টাকা জমা নেওয়ার লজিক
    public function store(Request $request)
    {
        $actor = $this->authorizeFeeAdmin($request);

        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:fee_invoices,id',
            'pay_amount' => 'nullable|numeric|min:0',
            'head_allocations' => 'nullable|array',
            'head_allocations.*' => 'nullable|numeric|min:0',
            'include_tuition_in_auto_split' => 'nullable|boolean',
            'direct_discount_amount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:100',
            'payment_date' => 'nullable|date',
            'transaction_id' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:500',
            'idempotency_key' => 'nullable|string|max:120',
        ]);

        if ($validator->fails()) {
            $firstMessage = $validator->errors()->first();
            return $this->respondValidationFailure($request, $validator->errors()->toArray(), $firstMessage);
        }

        /** @var FinancialTransactionRunner $runner */
        $runner = app(FinancialTransactionRunner::class);
        /** @var AdmissionCollectionService $collectionService */
        $collectionService = app(AdmissionCollectionService::class);

        try {
            $directDiscountStr = $request->filled('direct_discount_amount')
                ? $this->normalizeControllerDecimal($request->input('direct_discount_amount'), 'direct_discount_amount')
                : '0.00';
            $hasDirectDiscount = bccomp($directDiscountStr, '0.00', 2) > 0;

            if (!$hasDirectDiscount && trim((string) $request->input('payment_method', '')) === '') {
                return $this->respondValidationFailure(
                    $request,
                    ['payment_method' => ['The payment method field is required.']],
                    'The payment method field is required.'
                );
            }

            $result = $runner->runWithDeadlockRetry(function () use ($request, $actor, $collectionService, $directDiscountStr, $hasDirectDiscount): array {
                $invoiceLookup = FeeInvoice::select('id', 'student_id', 'admission_fee_structure_id')
                    ->findOrFail((int) $request->invoice_id);

                if ($invoiceLookup->isAdmissionInvoice()) {
                    $payload = [
                        'payment_method' => (string) $request->input('payment_method', ''),
                        'payment_date' => $request->input('payment_date'),
                        'transaction_id' => $request->input('transaction_id'),
                        'note' => $request->input('note'),
                        'idempotency_key' => $request->input('idempotency_key'),
                        'discount_reason' => $request->input('discount_reason'),
                    ];

                    if ($hasDirectDiscount) {
                        $payload['direct_discount_amount'] = $directDiscountStr;
                    }

                    if ($request->filled('pay_amount')) {
                        $payload['pay_amount'] = $this->normalizeControllerDecimal($request->input('pay_amount'), 'pay_amount');
                    }

                    if ($request->has('include_tuition_in_auto_split')) {
                        $payload['include_tuition_in_auto_split'] = $request->boolean('include_tuition_in_auto_split');
                    }

                    if ($request->has('head_allocations') && is_array($request->input('head_allocations'))) {
                        $normalizedHeads = [];
                        foreach ($request->input('head_allocations') as $headId => $headAmount) {
                            $normalizedHeads[$headId] = $this->normalizeControllerDecimal(
                                $headAmount === null || $headAmount === '' ? '0.00' : $headAmount,
                                "head_allocations.{$headId}"
                            );
                        }
                        $payload['head_allocations'] = $normalizedHeads;
                    }

                    return $collectionService->collectSinglePaymentWithinTransaction(
                        (int) $invoiceLookup->id,
                        $payload,
                        (int) $actor->id
                    );
                }

                if ($hasDirectDiscount) {
                    throw ValidationException::withMessages([
                        'direct_discount_amount' => ['Direct collection discount in this modal is supported only for Admission Fee invoices. Please use Adjust Fee for monthly or legacy invoices.'],
                    ]);
                }

                if (trim((string) $request->input('payment_method', '')) === '') {
                    throw ValidationException::withMessages([
                        'payment_method' => ['The payment method field is required.'],
                    ]);
                }

                if ($request->has('head_allocations') && is_array($request->input('head_allocations')) && count($request->input('head_allocations')) > 0) {
                    throw ValidationException::withMessages([
                        'head_allocations' => ['Head-wise payment breakdown is supported only for multi-head Admission Fee invoices.'],
                    ]);
                }

                $payAmount = $this->normalizePositiveControllerDecimal($request->input('pay_amount'), 'pay_amount');

                return $this->collectLegacySingleWithinTransaction(
                    (int) $invoiceLookup->id,
                    (int) $invoiceLookup->student_id,
                    $payAmount,
                    $request,
                    (int) $actor->id,
                    $collectionService
                );
            });

            /** @var FeeInvoice $invoice */
            $invoice = $result['invoice'];
            $receiptNo = $result['receipt_no'];
            $discountApplied = $result['discount_applied'] ?? '0.00';
            $studentIdentity = $invoice->student->student_identity ?? null;

            if ($receiptNo !== null) {
                $message = bccomp((string) $discountApplied, '0.00', 2) > 0
                    ? "Direct discount (৳{$discountApplied}) applied & payment collected! Receipt No: {$receiptNo}"
                    : "Payment collected successfully! Receipt No: {$receiptNo}";
            } else {
                $message = "Direct discount of ৳{$discountApplied} applied successfully to {$invoice->invoice_no}.";
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'discount_applied' => $discountApplied,
                    'receipt_no' => $receiptNo,
                    'paid_amount' => $result['paid_amount'],
                    'payment' => $result['payment'],
                    'invoice' => $invoice,
                    'head_allocations' => $result['head_allocations'] ?? [],
                ], 200);
            }

            return redirect()->route('fees.collection.index', ['student_identity' => $studentIdentity])
                             ->with('success', $message)
                             ->with('print_invoice_id', $invoice->id);
        } catch (AuthorizationException $e) {
            return $this->respondForbiddenFailure($request, $e->getMessage());
        } catch (ValidationException $e) {
            $firstMessage = collect($e->errors())->flatten()->first() ?? $e->getMessage();
            return $this->respondValidationFailure($request, $e->errors(), $firstMessage);
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment failed due to system error: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withInput()->with('error', 'Payment failed due to system error: ' . $e->getMessage());
        }
    }

    // ৩. একসাথে একাধিক বিল (Bulk) পেমেন্ট নেওয়ার লজিক
    public function bulkStore(Request $request)
    {
        $actor = $this->authorizeFeeAdmin($request);

        $validator = Validator::make($request->all(), [
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'exists:fee_invoices,id',
            'paying_amounts' => 'nullable|array',
            'paying_amounts.*' => 'nullable|numeric|min:0.01',
            'payment_method' => 'required|string|max:100',
            'payment_date' => 'nullable|date',
            'transaction_id' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:500',
            'idempotency_key' => 'nullable|string|max:120',
        ]);

        if ($validator->fails()) {
            $firstMessage = $validator->errors()->first();
            return $this->respondValidationFailure($request, $validator->errors()->toArray(), $firstMessage);
        }

        /** @var AdmissionCollectionService $collectionService */
        $collectionService = app(AdmissionCollectionService::class);

        try {
            $payingMap = $request->input('paying_amounts');
            $items = [];

            foreach (collect($request->input('invoice_ids', []))->map(fn ($id): int => (int) $id)->unique()->sort()->values() as $invId) {
                if (is_array($payingMap) && array_key_exists($invId, $payingMap) && $payingMap[$invId] !== null && $payingMap[$invId] !== '') {
                    $items[] = [
                        'invoice_id' => $invId,
                        'pay_amount' => $this->normalizePositiveControllerDecimal($payingMap[$invId], "paying_amounts.{$invId}"),
                        'pay_full' => false,
                    ];
                } else {
                    $items[] = [
                        'invoice_id' => $invId,
                        'pay_full' => true,
                    ];
                }
            }

            $result = $collectionService->collectBulkPayments($items, [
                'receipt_prefix' => 'REC',
                'payment_method' => (string) $request->payment_method,
                'payment_date' => $request->input('payment_date'),
                'transaction_id' => $request->input('transaction_id'),
                'note' => $request->input('note'),
                'idempotency_key' => $request->input('idempotency_key'),
                'allow_skip_unpayable' => false,
            ], (int) $actor->id);

            $studentInfo = Student::find($result['primary_student_id']);
            $masterReceiptNo = $result['master_receipt_no'];
            $totalPaid = $result['total_collected'];

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Bulk Payment of ৳{$totalPaid} collected! Receipt No: {$masterReceiptNo}",
                    'master_receipt_no' => $masterReceiptNo,
                    'total_collected' => $totalPaid,
                    'collected_count' => $result['collected_count'],
                    'payments' => $result['payments'],
                    'invoices' => $result['invoices'],
                ], 200);
            }

            return redirect()->route('fees.collection.index', ['student_identity' => $studentInfo?->student_identity])
                             ->with('success', "Bulk Payment of ৳{$totalPaid} collected! Receipt No: " . $masterReceiptNo)
                             ->with('print_receipt_no', $masterReceiptNo);
        } catch (AuthorizationException $e) {
            return $this->respondForbiddenFailure($request, $e->getMessage());
        } catch (ValidationException $e) {
            $firstMessage = collect($e->errors())->flatten()->first() ?? $e->getMessage();
            return $this->respondValidationFailure($request, $e->errors(), $firstMessage);
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bulk Payment failed: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withInput()->with('error', 'Bulk Payment failed: ' . $e->getMessage());
        }
    }

    // ৪. Master POS (Thermal) রিসিট প্রিন্ট (একাধিক বিল একসাথে)
    public function printBulkPos($receipt_no)
    {
        /** @var FeeReceiptService $receiptService */
        $receiptService = app(FeeReceiptService::class);

        $rawPayments = $receiptService->fetchPaymentsForMasterReceipt((string) $receipt_no);

        if ($rawPayments->isEmpty()) {
            abort(404, 'Receipt not found');
        }

        $masterReceipt = $receiptService->buildMasterReceiptData($rawPayments, (string) $receipt_no);

        $payments = $masterReceipt['sorted_payments'];
        $student = $masterReceipt['student'];
        $collector = $masterReceipt['collector'];
        $date = $masterReceipt['date'];
        $isMultipleStudents = $masterReceipt['is_multiple_students'];

        return view('pages.fees.pos_bulk_receipt', compact(
            'payments',
            'receipt_no',
            'student',
            'collector',
            'date',
            'isMultipleStudents',
            'masterReceipt'
        ));
    }

    // ৫. একসাথে একাধিক স্টুডেন্টের ফি কালেকশন নেওয়ার লজিক (ইন্ডিভিজুয়াল রিসিট একসাথে প্রিন্ট)
    public function printBulkIndividualPos($receipt_no)
    {
        /** @var FeeReceiptService $receiptService */
        $receiptService = app(FeeReceiptService::class);

        $payments = $receiptService->fetchPaymentsForMasterReceipt((string) $receipt_no);

        if ($payments->isEmpty()) {
            abort(404, 'Receipt not found');
        }

        $individualReceiptData = $receiptService->buildBulkIndividualReceiptData($payments, (string) $receipt_no, false);
        $invoices = $individualReceiptData['invoices'];
        $slipCards = $individualReceiptData['slip_cards'];

        return view('pages.fees.pos_invoice_individual_bulk', compact(
            'invoices',
            'receipt_no',
            'slipCards',
            'individualReceiptData'
        ));
    }

    // ৫.১. সকল রশিদ/পেমেন্টের তালিকা দেখানোর লজিক
    public function paymentsIndex(Request $request)
    {
        /** @var FeeReceiptService $receiptService */
        $receiptService = app(FeeReceiptService::class);
        $admissionSchemaReady = $this->collectionAdmissionSchemaReady($receiptService);
        $hasPaymentStatusColumn = Schema::hasColumn('fee_payments', 'status');

        $paymentRelations = ['student.schoolClass', 'student.section', 'invoice.feeSetup.category', 'collector'];
        if ($admissionSchemaReady) {
            $paymentRelations[] = 'invoice.admissionFeeStructure';
            $paymentRelations[] = 'invoice.admissionHeadAllocations';
            $paymentRelations[] = 'admissionHeadAllocations';
            $paymentRelations[] = 'reversedBy';
        }

        $query = FeePayment::with($paymentRelations)
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

        // Apply Payment Status filter (All / Active / Reversed)
        $statusFilter = '';
        if ($hasPaymentStatusColumn && $request->filled('status')) {
            $requestedStatus = trim((string) $request->input('status'));
            if (in_array($requestedStatus, ['Active', 'Reversed'], true)) {
                $statusFilter = $requestedStatus;
                $query->where('status', $requestedStatus);
            }
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
            $query->where(function($q) use ($search, $hasPaymentStatusColumn) {
                $q->where('receipt_no', 'like', "%{$search}%");
                if ($hasPaymentStatusColumn) {
                    $q->orWhere('reversal_reference', 'like', "%{$search}%");
                }
                $q->orWhereHas('student', function($sq) use ($search) {
                    $sq->where('student_name', 'like', "%{$search}%")
                      ->orWhere('student_identity', 'like', "%{$search}%");
                });
            });
        }

        $payments = $query->paginate(15)->withQueryString();

        // Prepare display & FeeReceiptService allocations strictly inside Controller (Blade Separation Condition 3)
        $payments->getCollection()->each(function (FeePayment $pay) use ($receiptService, $admissionSchemaReady, $hasPaymentStatusColumn): void {
            $isAdmPayInv = $admissionSchemaReady
                && $pay->invoice
                && method_exists($pay->invoice, 'isAdmissionInvoice')
                && $pay->invoice->isAdmissionInvoice();

            $description = $isAdmPayInv
                ? ('Admission Fee Package — ' . ($pay->invoice->admissionFeeStructure->structure_name ?? 'Admission Fee'))
                : ($pay->invoice?->feeSetup?->category?->name ?? 'Fee Payment');

            $monthName = 'One Time';
            if ($isAdmPayInv) {
                $monthName = $pay->invoice->month_name ?? 'One Time';
            } elseif ($pay->invoice && $pay->invoice->feeSetup) {
                if ($pay->invoice->feeSetup->fee_month && strtolower($pay->invoice->feeSetup->fee_month) !== 'monthly') {
                    $monthName = $pay->invoice->feeSetup->fee_month;
                } elseif ($pay->invoice->due_date) {
                    $monthName = date('F', strtotime($pay->invoice->due_date));
                } else {
                    $monthName = date('F', strtotime($pay->invoice->created_at));
                }
            }

            $isReversed = $hasPaymentStatusColumn && $pay->isReversed();
            $allocSelection = $isAdmPayInv
                ? $receiptService->selectOrderedPaymentHeadAllocations($pay)
                : [
                    'allocations' => collect(),
                    'has_status_mismatch' => false,
                    'missing_expected_allocations' => false,
                    'used_provenance_fallback' => false,
                ];

            $headChips = $allocSelection['allocations']->map(static function ($alloc) use ($isReversed): array {
                return [
                    'head_name' => (string) $alloc->head_name_snapshot,
                    'amount_formatted' => \App\Helpers\NumberToWords::formatDecimal($alloc->allocated_paid_amount),
                    'unsynced_orig' => $isReversed && method_exists($alloc, 'isReversed') && !$alloc->isReversed(),
                ];
            })->values()->all();

            $pay->setAttribute('prepared_is_admission', $isAdmPayInv);
            $pay->setAttribute('prepared_description', $description);
            $pay->setAttribute('prepared_month', $monthName);
            $pay->setAttribute('prepared_is_reversed', $isReversed);
            $pay->setAttribute('prepared_status_label', $isReversed ? 'Reversed' : 'Active');
            $pay->setAttribute('prepared_reversal_ref', $hasPaymentStatusColumn ? $pay->reversal_reference : null);
            $pay->setAttribute('prepared_reversal_reason', $hasPaymentStatusColumn ? $pay->reversal_reason : null);
            $pay->setAttribute('prepared_reversed_at_formatted', ($hasPaymentStatusColumn && $pay->reversed_at) ? date('d M Y, h:i A', strtotime((string) $pay->reversed_at)) : null);
            $pay->setAttribute('prepared_reversed_by_name', $hasPaymentStatusColumn ? ($pay->reversedBy?->name ?? null) : null);
            $pay->setAttribute('prepared_head_chips', $headChips);
            $pay->setAttribute('prepared_has_alloc_mismatch', (bool) ($allocSelection['has_status_mismatch'] ?? false));
            $pay->setAttribute('prepared_missing_allocs', (bool) ($allocSelection['missing_expected_allocations'] ?? false));
        });

        $actor = Auth::user();
        $canReversePayments = $admissionSchemaReady
            && $actor
            && method_exists($actor, 'hasRole')
            && ($actor->hasRole('admin') || $actor->hasRole('super-admin'));
        $schemaWarning = $admissionSchemaReady
            ? null
            : 'Admission schema migrations are pending. Please contact the system administrator.';

        $branches = Branch::all();
        $classes = Classes::all();

        return view('pages.fees.payments', compact(
            'payments',
            'branches',
            'classes',
            'statusFilter',
            'admissionSchemaReady',
            'hasPaymentStatusColumn',
            'schemaWarning',
            'canReversePayments'
        ));
    }

    // ৫.২. একাধিক নির্দিষ্ট রশিদ একসাথে প্রিন্ট করার লজিক
    public function printSelectedIndividualPos(Request $request)
    {
        $paymentIds = $request->input('payment_ids', []);
        if (empty($paymentIds)) {
            return back()->with('error', 'Please select at least one receipt to print.');
        }

        /** @var FeeReceiptService $receiptService */
        $receiptService = app(FeeReceiptService::class);

        $payments = $receiptService->fetchPaymentsByIds((array) $paymentIds);

        if ($payments->isEmpty()) {
            abort(404, 'No receipts found');
        }

        $receipt_no = 'SELECTED-' . date('Ymd') . '-' . rand(10, 99);
        $individualReceiptData = $receiptService->buildBulkIndividualReceiptData($payments, $receipt_no, true);
        $invoices = $individualReceiptData['invoices'];
        $slipCards = $individualReceiptData['slip_cards'];

        return view('pages.fees.pos_invoice_individual_bulk', compact(
            'invoices',
            'receipt_no',
            'slipCards',
            'individualReceiptData'
        ));
    }

    // ৫. একসাথে একাধিক স্টুডেন্টের ফি কালেকশন নেওয়ার লজিক (Bulk Collection)
    public function bulkStudentsStore(Request $request)
    {
        $actor = $this->authorizeFeeAdmin($request);

        $validator = Validator::make($request->all(), [
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'exists:fee_invoices,id',
            'paying_amounts' => 'required|array',
            'payment_method' => 'required|string|max:100',
            'payment_date' => 'nullable|date',
            'transaction_id' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:500',
            'idempotency_key' => 'nullable|string|max:120',
        ]);

        if ($validator->fails()) {
            $firstMessage = $validator->errors()->first();
            return $this->respondValidationFailure($request, $validator->errors()->toArray(), $firstMessage);
        }

        /** @var FinancialTransactionRunner $runner */
        $runner = app(FinancialTransactionRunner::class);
        /** @var AdmissionCollectionService $collectionService */
        $collectionService = app(AdmissionCollectionService::class);

        try {
            $rawInvoiceIds = collect($request->input('invoice_ids', []))
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->sort()
                ->values();

            $rawAmounts = $request->input('paying_amounts', []);
            $payableAmountsByInvoice = [];

            foreach ($rawInvoiceIds as $invId) {
                if (!array_key_exists($invId, $rawAmounts) || $rawAmounts[$invId] === null || trim((string) $rawAmounts[$invId]) === '') {
                    continue;
                }

                $normalized = $this->normalizeControllerDecimal($rawAmounts[$invId], "paying_amounts.{$invId}");
                if (bccomp($normalized, '0.00', 2) <= 0) {
                    continue;
                }

                $payableAmountsByInvoice[$invId] = $normalized;
            }

            if (empty($payableAmountsByInvoice)) {
                throw ValidationException::withMessages([
                    'paying_amounts' => ['No positive payment amounts were specified for the selected invoices.'],
                ]);
            }

            ksort($payableAmountsByInvoice, SORT_NUMERIC);
            $sortedPayableIds = array_keys($payableAmountsByInvoice);
            $paymentDate = $request->filled('payment_date')
                ? Carbon::parse((string) $request->payment_date)->toDateString()
                : date('Y-m-d');
            $paymentYmd = Carbon::parse($paymentDate)->format('Ymd');
            $hasPaymentBranchCol = Schema::hasColumn('fee_payments', 'branch_id');

            $result = $runner->runWithDeadlockRetry(function () use (
                $sortedPayableIds,
                $payableAmountsByInvoice,
                $request,
                $actor,
                $paymentDate,
                $paymentYmd,
                $hasPaymentBranchCol,
                $collectionService
            ): array {
                $lockContext = $collectionService->acquireGlobalLocksForInvoices($sortedPayableIds);

                if ($request->filled('idempotency_key')) {
                    $collectionService->claimDatabaseIdempotencyKey(
                        'collect_bulk_students',
                        (string) $request->input('idempotency_key'),
                        (int) $actor->id,
                        implode(',', $sortedPayableIds)
                    );
                }

                $masterReceiptNo = $collectionService->allocateNextDatedSequence('receipt_no', 'REC-BULK', $paymentYmd, true);
                $totalCollected = '0.00';
                $count = 0;

                foreach ($sortedPayableIds as $invId) {
                    /** @var FeeInvoice|null $invoice */
                    $invoice = $lockContext['invoices']->get($invId);
                    /** @var Student|null $student */
                    $student = $invoice ? $lockContext['students']->get((int) $invoice->student_id) : null;

                    if (!$invoice || !$student) {
                        throw ValidationException::withMessages([
                            'invoice_ids' => ["Invoice ID {$invId} or its associated student record could not be found."],
                        ]);
                    }

                    if ($invoice->isCancelled()) {
                        throw ValidationException::withMessages([
                            'invoice_ids' => ["Cannot collect payment for a cancelled invoice ({$invoice->invoice_no})."],
                        ]);
                    }

                    $payAmount = $payableAmountsByInvoice[$invId];
                    $receiptNo = $masterReceiptNo . '-' . $invoice->id;

                    if ($invoice->isAdmissionInvoice()) {
                        // Admission Invoices MUST NEVER mutate StudentCustomFee or overwrite invoice discount/net.
                        $admissionResult = $collectionService->collectSinglePaymentWithinTransaction(
                            $invId,
                            [
                                'pay_amount' => $payAmount,
                                'payment_method' => (string) $request->payment_method,
                                'payment_date' => $paymentDate,
                                'transaction_id' => $request->input('transaction_id'),
                                'note' => $request->input('note'),
                                'receipt_no' => $receiptNo,
                            ],
                            (int) $actor->id,
                            $lockContext
                        );

                        $totalCollected = bcadd($totalCollected, $admissionResult['paid_amount'], 2);
                        $count++;
                        continue;
                    }

                    if ($invoice->isZeroPayableWaived() || bccomp($collectionService->toDecimalString($invoice->due_amount), '0.00', 2) <= 0) {
                        throw ValidationException::withMessages([
                            'invoice_ids' => ["Invoice ({$invoice->invoice_no}) has no payable balance due."],
                        ]);
                    }

                    $invoice->load('feeSetup');
                    $feeSetup = $invoice->feeSetup;
                    $feeCategoryId = $feeSetup->fee_category_id;
                    $originalSetupAmount = $collectionService->toDecimalString($feeSetup->amount);

                    $lockedPayments = $lockContext['payments_by_invoice']->get($invId, collect());
                    $activePaid = $lockedPayments->isNotEmpty()
                        ? $collectionService->sumActivePayments($lockedPayments)
                        : $collectionService->toDecimalString($invoice->paid_amount);

                    if (bccomp($payAmount, $originalSetupAmount, 2) !== 0) {
                        StudentCustomFee::updateOrCreate(
                            [
                                'student_id' => $invoice->student_id,
                                'fee_category_id' => $feeCategoryId,
                            ],
                            [
                                'amount' => $payAmount,
                            ]
                        );

                        $invoice->amount = $originalSetupAmount;
                        $diff = bcsub($originalSetupAmount, $payAmount, 2);
                        $invoice->discount = bccomp($diff, '0.00', 2) > 0 ? $diff : '0.00';
                        $invoice->net_amount = $payAmount;
                        $recalcDue = bcsub($payAmount, $activePaid, 2);
                        $invoice->due_amount = bccomp($recalcDue, '0.00', 2) > 0 ? $recalcDue : '0.00';
                    } else {
                        StudentCustomFee::where('student_id', $invoice->student_id)
                            ->where('fee_category_id', $feeCategoryId)
                            ->delete();

                        $invoice->amount = $originalSetupAmount;
                        $invoice->discount = '0.00';
                        $invoice->net_amount = $originalSetupAmount;
                        $recalcDue = bcsub($originalSetupAmount, $activePaid, 2);
                        $invoice->due_amount = bccomp($recalcDue, '0.00', 2) > 0 ? $recalcDue : '0.00';
                    }

                    $dueDecimal = $collectionService->toDecimalString($invoice->due_amount);
                    if (bccomp($payAmount, $dueDecimal, 2) > 0) {
                        throw ValidationException::withMessages([
                            "paying_amounts.{$invId}" => [
                                "Paying amount for student {$student->student_name} cannot be greater than the Due amount (৳{$dueDecimal})!",
                            ],
                        ]);
                    }

                    $paymentData = [
                        'receipt_no' => $receiptNo,
                        'fee_invoice_id' => $invoice->id,
                        'student_id' => $invoice->student_id,
                        'paid_amount' => $payAmount,
                        'payment_date' => $paymentDate,
                        'payment_method' => $request->payment_method,
                        'transaction_id' => $request->transaction_id,
                        'note' => $request->note,
                        'collected_by' => $actor->id,
                    ];
                    if ($hasPaymentBranchCol && !empty($invoice->branch_id)) {
                        $paymentData['branch_id'] = $invoice->branch_id;
                    }
                    FeePayment::create($paymentData);

                    $newPaid = bcadd($activePaid, $payAmount, 2);
                    $newDue = bcsub($collectionService->toDecimalString($invoice->net_amount), $newPaid, 2);
                    $invoice->paid_amount = $newPaid;
                    $invoice->due_amount = bccomp($newDue, '0.00', 2) > 0 ? $newDue : '0.00';
                    $invoice->status = bccomp($invoice->due_amount, '0.00', 2) <= 0 ? 'Paid' : 'Partial';
                    $invoice->save();

                    $totalCollected = bcadd($totalCollected, $payAmount, 2);
                    $count++;
                }

                return [
                    'master_receipt_no' => $masterReceiptNo,
                    'total_collected' => $totalCollected,
                    'count' => $count,
                ];
            });

            $count = $result['count'];
            $totalCollected = $result['total_collected'];
            $masterReceiptNo = $result['master_receipt_no'];

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully collected payments for {$count} student(s)! Total: ৳" . number_format((float) $totalCollected, 2),
                    'master_receipt_no' => $masterReceiptNo,
                    'total_collected' => $totalCollected,
                    'collected_count' => $count,
                ], 200);
            }

            return redirect()->route('fees.collection.index', [
                'mode' => 'bulk',
                'branch_id' => $request->branch_id,
                'session_year_id' => $request->session_year_id,
                'class_id' => $request->class_id,
                'section_id' => $request->section_id,
                'fee_category_id' => $request->fee_category_id,
                'fee_month' => $request->fee_month,
            ])->with('success', "Successfully collected payments for {$count} student(s)! Total: ৳" . number_format((float) $totalCollected, 2))
              ->with('print_receipt_no', $masterReceiptNo);
        } catch (AuthorizationException $e) {
            return $this->respondForbiddenFailure($request, $e->getMessage());
        } catch (ValidationException $e) {
            $firstMessage = collect($e->errors())->flatten()->first() ?? $e->getMessage();
            return $this->respondValidationFailure($request, $e->errors(), $firstMessage);
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bulk Collection failed: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withInput()->with('error', 'Bulk Collection failed: ' . $e->getMessage());
        }
    }

    /**
     * Reverse a full payment receipt immutably (supports both multi-head Admission Invoice
     * payments and legacy single-head Fee Invoice payments).
     */
    public function reversePayment(Request $request, $id): JsonResponse
    {
        $actor = $this->authorizeFeeAdmin($request);

        if ($request->hasAny(['amount', 'reverse_amount', 'reversed_amount', 'partial_amount'])) {
            return response()->json([
                'success' => false,
                'message' => 'Partial payment reversal is prohibited. Only immutable full receipt reversal is supported.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|min:3|max:255',
            'idempotency_key' => 'nullable|string|max:120',
            'student_id' => 'nullable|integer|exists:students,id',
            'fee_invoice_id' => 'nullable|integer|exists:fee_invoices,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        /** @var AdmissionPaymentReversalService $reversalService */
        $reversalService = app(AdmissionPaymentReversalService::class);

        try {
            $hints = [];
            if ($request->filled('student_id')) {
                $hints['student_id'] = (int) $request->input('student_id');
            }
            if ($request->filled('fee_invoice_id')) {
                $hints['fee_invoice_id'] = (int) $request->input('fee_invoice_id');
            }

            $result = $reversalService->reversePayment(
                (int) $id,
                (string) $request->input('reason'),
                (int) $actor->id,
                $request->filled('idempotency_key') ? (string) $request->input('idempotency_key') : null,
                $hints
            );

            return response()->json([
                'success' => true,
                'message' => "Payment receipt ({$result['payment']->receipt_no}) reversed successfully. Reference: {$result['reversal_reference']}",
                'is_admission_invoice' => $result['is_admission_invoice'],
                'reversal_reference' => $result['reversal_reference'],
                'reversed_amount' => $result['reversed_amount'],
                'payment' => $result['payment'],
                'invoice' => $result['invoice'],
                'head_allocations' => $result['head_allocations'],
            ], 200);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Target payment receipt was not found.',
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
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment reversal failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Single Collection: Update custom fee amount inline and sync all unpaid invoices for this student & category.
     */
    public function updateCustomFeeAjax(Request $request)
    {
        $this->authorizeFeeAdmin($request);

        $request->validate([
            'invoice_id' => 'required|exists:fee_invoices,id',
            'amount' => 'required|numeric|min:0',
        ]);

        /** @var FinancialTransactionRunner $runner */
        $runner = app(FinancialTransactionRunner::class);

        try {
            $newAmountStr = $this->normalizeControllerDecimal($request->input('amount'), 'amount');

            $result = $runner->runWithDeadlockRetry(function () use ($request, $newAmountStr): array {
                $invoiceLookup = FeeInvoice::select('id', 'student_id')->findOrFail((int) $request->invoice_id);

                // Strict Lock Hierarchy: Tier 1 Student -> Tier 2 All Student FeeInvoices ASC -> Tier 3 FeePayments ASC
                $student = Student::where('id', $invoiceLookup->student_id)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->firstOrFail();

                $studentInvoices = FeeInvoice::where('student_id', $student->id)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                /** @var FeeInvoice|null $invoice */
                $invoice = $studentInvoices->firstWhere('id', (int) $invoiceLookup->id);
                if (!$invoice) {
                    throw new ModelNotFoundException('Target invoice not found for locked student.');
                }

                if ($invoice->isAdmissionInvoice()) {
                    throw ValidationException::withMessages([
                        'invoice_id' => [
                            "Cannot mutate Admission Fee invoice ({$invoice->invoice_no}) via legacy custom fee inline editor.",
                        ],
                    ]);
                }

                if ($invoice->isCancelled()) {
                    throw ValidationException::withMessages([
                        'invoice_id' => ["Cannot update custom fee for a cancelled invoice ({$invoice->invoice_no})."],
                    ]);
                }

                // Pre-load feeSetup strictly on non-admission legacy invoices only.
                $legacyInvoices = $studentInvoices->filter(fn (FeeInvoice $inv): bool => !is_null($inv->fee_setup_id));
                if ($legacyInvoices->isNotEmpty()) {
                    $legacyInvoices->load('feeSetup');
                }

                $studentId = (int) $student->id;
                $feeSetup = $invoice->feeSetup;
                $feeCategoryId = (int) $feeSetup->fee_category_id;
                $originalSetupAmount = bcadd((string) $feeSetup->amount, '0.00', 2);

                $affectedInvoices = $legacyInvoices->filter(function (FeeInvoice $inv) use ($feeCategoryId): bool {
                    return is_null($inv->admission_fee_structure_id)
                        && in_array($inv->status, ['Unpaid', 'Partial'], true)
                        && (int) ($inv->feeSetup?->fee_category_id ?? 0) === $feeCategoryId;
                })->values();

                // Tier 3: Lock payments ASC for affected invoices BEFORE mutating records
                $affectedIds = $affectedInvoices->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all();
                if (!empty($affectedIds)) {
                    FeePayment::whereIn('fee_invoice_id', $affectedIds)
                        ->orderBy('id', 'asc')
                        ->lockForUpdate()
                        ->get();
                }

                if (bccomp($newAmountStr, $originalSetupAmount, 2) !== 0) {
                    StudentCustomFee::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'fee_category_id' => $feeCategoryId,
                        ],
                        [
                            'amount' => $newAmountStr,
                        ]
                    );
                } else {
                    StudentCustomFee::where('student_id', $studentId)
                        ->where('fee_category_id', $feeCategoryId)
                        ->delete();
                }

                $updatedInvoicesData = [];

                foreach ($affectedInvoices as $inv) {
                    $setupAmount = bcadd((string) $inv->feeSetup->amount, '0.00', 2);
                    $invPaid = bcadd((string) $inv->paid_amount, '0.00', 2);
                    $diffDisc = bcsub($setupAmount, $newAmountStr, 2);
                    $invDiscount = bccomp($diffDisc, '0.00', 2) > 0 ? $diffDisc : '0.00';
                    $diffDue = bcsub($newAmountStr, $invPaid, 2);
                    $invDue = bccomp($diffDue, '0.00', 2) > 0 ? $diffDue : '0.00';

                    $inv->amount = $setupAmount;
                    $inv->discount = $invDiscount;
                    $inv->net_amount = $newAmountStr;
                    $inv->due_amount = $invDue;
                    $inv->status = bccomp($invDue, '0.00', 2) <= 0
                        ? 'Paid'
                        : (bccomp($invPaid, '0.00', 2) > 0 ? 'Partial' : 'Unpaid');
                    $inv->save();

                    $updatedInvoicesData[] = [
                        'id' => $inv->id,
                        'net_amount' => bcadd((string) $inv->net_amount, '0.00', 2),
                        'due_amount' => bcadd((string) $inv->due_amount, '0.00', 2),
                        'discount' => bcadd((string) $inv->discount, '0.00', 2),
                        'paid_amount' => bcadd((string) $inv->paid_amount, '0.00', 2),
                        'status' => $inv->status,
                    ];
                }

                return [
                    'student_id' => $studentId,
                    'category_id' => $feeCategoryId,
                    'updated_invoices' => $updatedInvoicesData,
                ];
            });

            $allPendingInvoices = FeeInvoice::where('student_id', $result['student_id'])
                ->whereIn('status', ['Unpaid', 'Partial'])
                ->get();

            $summary = $this->buildPendingSummary($allPendingInvoices);

            return response()->json([
                'success' => true,
                'message' => 'Custom fee updated successfully across all matching invoices.',
                'category_id' => $result['category_id'],
                'new_amount' => $newAmountStr,
                'updated_invoices' => $result['updated_invoices'],
                'summary' => $summary,
            ]);
        } catch (ValidationException $e) {
            $firstMessage = collect($e->errors())->flatten()->first() ?? $e->getMessage();
            return response()->json([
                'success' => false,
                'message' => $firstMessage,
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update custom fee: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Single Collection: Adjust invoice fee amount (increase, decrease, or set exact)
     * either for this specific invoice (single month) or continuous across all upcoming eligible invoices.
     */
    public function adjustInvoiceFee(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $actor = $this->authorizeFeeAdmin($request);

        $request->validate([
            'invoice_id' => 'required|exists:fee_invoices,id',
            'mode' => 'required|in:increase,decrease,set_amount',
            'amount' => 'required|numeric|min:0',
            'scope' => 'required|in:single,continuous',
            'reason' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:255',
        ]);

        $operatorId = (int) $actor->id;
        $reason = $request->filled('reason')
            ? trim((string) $request->reason)
            : ($request->filled('note') ? trim((string) $request->note) : null);

        /** @var FinancialTransactionRunner $runner */
        $runner = app(FinancialTransactionRunner::class);

        try {
            $inputAmount = $this->normalizeControllerDecimal($request->input('amount'), 'amount');
            $mode = (string) $request->mode;
            $scope = (string) $request->scope;

            $result = $runner->runWithDeadlockRetry(function () use ($request, $operatorId, $reason, $inputAmount, $mode, $scope): array {
                $invoiceLookup = FeeInvoice::select('id', 'student_id')->findOrFail((int) $request->invoice_id);

                // Strict Lock Hierarchy: Tier 1 Student -> Tier 2 FeeInvoices ASC -> Tier 3 FeePayments ASC
                $student = Student::where('id', $invoiceLookup->student_id)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->firstOrFail();

                // In continuous mode, lock ALL student invoices at Tier 2 in ASC order BEFORE touching Tier 3 FeePayments.
                $studentInvoicesQuery = FeeInvoice::where('student_id', $student->id)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate();

                if ($scope !== 'continuous') {
                    $studentInvoicesQuery->where('id', $invoiceLookup->id);
                }

                $lockedStudentInvoices = $studentInvoicesQuery->get();

                /** @var FeeInvoice|null $invoice */
                $invoice = $lockedStudentInvoices->firstWhere('id', (int) $invoiceLookup->id);
                if (!$invoice) {
                    throw new ModelNotFoundException('Target invoice not found for locked student.');
                }

                if ($invoice->isAdmissionInvoice()) {
                    throw ValidationException::withMessages([
                        'invoice_id' => [
                            "Cannot adjust Admission Fee invoice ({$invoice->invoice_no}) using single-head fee adjustment tools.",
                        ],
                    ]);
                }

                if ($invoice->isCancelled()) {
                    throw ValidationException::withMessages([
                        'invoice_id' => ["Cannot adjust fees for a cancelled invoice ({$invoice->invoice_no})."],
                    ]);
                }

                $legacyInvoices = $lockedStudentInvoices->filter(fn (FeeInvoice $inv): bool => !is_null($inv->fee_setup_id));
                if ($legacyInvoices->isNotEmpty()) {
                    $legacyInvoices->load('feeSetup.category');
                }

                // Tier 3: Lock all payments for locked Tier 2 invoices in ASC order (zero Tier 2 locks afterward)
                $lockedInvoiceIds = $lockedStudentInvoices->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all();
                $lockedPaymentsByInvoice = FeePayment::whereIn('fee_invoice_id', $lockedInvoiceIds)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get()
                    ->groupBy('fee_invoice_id');

                /** @var \Illuminate\Support\Collection<int, FeePayment> $lockedPayments */
                $lockedPayments = $lockedPaymentsByInvoice->get($invoice->id, collect());

                $studentId = (int) $student->id;
                $feeSetup = $invoice->feeSetup;
                $feeCategoryId = (int) $feeSetup->fee_category_id;
                $setupAmount = bcadd((string) $feeSetup->amount, '0.00', 2);

                $currentAmount = bcadd((string) $invoice->amount, '0.00', 2);
                $currentDiscount = bcadd((string) $invoice->discount, '0.00', 2);
                $currentNet = bcadd((string) $invoice->net_amount, '0.00', 2);
                $paidAmount = $lockedPayments->isNotEmpty()
                    ? app(AdmissionCollectionService::class)->sumActivePayments($lockedPayments)
                    : bcadd((string) $invoice->paid_amount, '0.00', 2);

                if ($mode === 'increase') {
                    $adjustmentDelta = $inputAmount;
                    $newNet = bcadd($currentNet, $inputAmount, 2);
                    $newDiscount = $currentDiscount;
                    $newAmount = bcadd($newNet, $newDiscount, 2);
                    $newDue = bcsub($newNet, $paidAmount, 2);
                } elseif ($mode === 'decrease') {
                    $diffMax = bcsub($currentNet, $paidAmount, 2);
                    $maxAllowableReduction = bccomp($diffMax, '0.00', 2) > 0 ? $diffMax : '0.00';

                    if (bccomp($inputAmount, $maxAllowableReduction, 2) > 0) {
                        $hypotheticalNet = bcsub($currentNet, $inputAmount, 2);
                        throw ValidationException::withMessages([
                            'amount' => [
                                'Reduction of ৳' . number_format((float) $inputAmount, 2) .
                                ' would make the net bill ৳' . number_format((float) $hypotheticalNet, 2) .
                                ', which is less than the already collected payment of ৳' . number_format((float) $paidAmount, 2) .
                                '. Maximum allowable reduction is ৳' . number_format((float) $maxAllowableReduction, 2) . '.',
                            ],
                        ]);
                    }

                    $adjustmentDelta = $inputAmount;
                    $newAmount = $currentAmount;
                    $newDiscount = bcadd($currentDiscount, $inputAmount, 2);
                    $newNet = bcsub($newAmount, $newDiscount, 2);
                    $newDue = bcsub($newNet, $paidAmount, 2);
                } else {
                    if (bccomp($inputAmount, $paidAmount, 2) < 0) {
                        throw ValidationException::withMessages([
                            'amount' => [
                                'Desired net fee of ৳' . number_format((float) $inputAmount, 2) .
                                ' cannot be less than the already collected payment of ৳' . number_format((float) $paidAmount, 2) . '.',
                            ],
                        ]);
                    }

                    $deltaRaw = bcsub($inputAmount, $currentNet, 2);
                    $adjustmentDelta = bccomp($deltaRaw, '0.00', 2) < 0 ? bcmul($deltaRaw, '-1', 2) : $deltaRaw;

                    if (bccomp($inputAmount, $currentAmount, 2) <= 0) {
                        $newAmount = $currentAmount;
                        $newDiscount = bcsub($currentAmount, $inputAmount, 2);
                        $newNet = $inputAmount;
                    } else {
                        $newAmount = $inputAmount;
                        $newDiscount = '0.00';
                        $newNet = $inputAmount;
                    }
                    $newDue = bcsub($newNet, $paidAmount, 2);
                }

                $newDue = bccomp($newDue, '0.00', 2) > 0 ? $newDue : '0.00';
                $newStatus = bccomp($newDue, '0.00', 2) <= 0
                    ? 'Paid'
                    : (bccomp($paidAmount, '0.00', 2) > 0 ? 'Partial' : 'Unpaid');

                $updatedInvoicesData = [];

                if ($scope === 'continuous') {
                    $hasActivePayments = $lockedPayments->contains(fn (FeePayment $p): bool => ($p->status ?? 'Active') === 'Active');
                    if (bccomp($paidAmount, '0.00', 2) > 0 || $hasActivePayments) {
                        throw ValidationException::withMessages([
                            'scope' => [
                                "Continuous fee adjustment cannot be initiated from an invoice with existing payment records. Please choose 'This Month Only' or use an unpaid invoice without payments.",
                            ],
                        ]);
                    }

                    if (bccomp($newNet, $setupAmount, 2) !== 0) {
                        StudentCustomFee::updateOrCreate(
                            [
                                'student_id' => $studentId,
                                'fee_category_id' => $feeCategoryId,
                            ],
                            [
                                'amount' => $newNet,
                            ]
                        );
                    } else {
                        StudentCustomFee::where('student_id', $studentId)
                            ->where('fee_category_id', $feeCategoryId)
                            ->delete();
                    }

                    // Filter eligible unpaid legacy invoices strictly from already-locked Tier 2 & Tier 3 sets (id ASC).
                    $eligibleInvoices = $legacyInvoices->filter(function (FeeInvoice $inv) use ($feeCategoryId, $lockedPaymentsByInvoice): bool {
                        if (!is_null($inv->admission_fee_structure_id)) {
                            return false;
                        }
                        if ($inv->status !== 'Unpaid' || bccomp((string) ($inv->paid_amount ?? '0'), '0.00', 2) !== 0) {
                            return false;
                        }
                        if ((int) ($inv->feeSetup?->fee_category_id ?? 0) !== $feeCategoryId) {
                            return false;
                        }

                        $invPayments = $lockedPaymentsByInvoice->get($inv->id, collect());
                        return $invPayments->every(fn (FeePayment $p): bool => ($p->status ?? 'Active') !== 'Active');
                    })->values();

                    foreach ($eligibleInvoices as $inv) {
                        $invSetupAmount = bcadd((string) $inv->feeSetup->amount, '0.00', 2);
                        $invPrevAmount = bcadd((string) $inv->amount, '0.00', 2);
                        $invPrevDiscount = bcadd((string) $inv->discount, '0.00', 2);
                        $invPrevNet = bcadd((string) $inv->net_amount, '0.00', 2);
                        $invPrevDue = bcadd((string) $inv->due_amount, '0.00', 2);

                        if (bccomp($newNet, $invSetupAmount, 2) > 0) {
                            $inv->amount = $newNet;
                            $inv->discount = '0.00';
                        } else {
                            $inv->amount = $invSetupAmount;
                            $diffDisc = bcsub($invSetupAmount, $newNet, 2);
                            $inv->discount = bccomp($diffDisc, '0.00', 2) > 0 ? $diffDisc : '0.00';
                        }

                        $inv->net_amount = $newNet;
                        $inv->due_amount = $newNet;
                        $inv->status = 'Unpaid';
                        $inv->save();

                        FeeAdjustmentLog::create([
                            'fee_invoice_id' => $inv->id,
                            'student_id' => $studentId,
                            'user_id' => $operatorId,
                            'invoice_no' => $inv->invoice_no,
                            'student_identity' => $student->student_identity ?? null,
                            'mode' => $mode,
                            'scope' => 'continuous',
                            'adjustment_amount' => $adjustmentDelta,
                            'previous_amount' => $invPrevAmount,
                            'previous_discount' => $invPrevDiscount,
                            'previous_net_amount' => $invPrevNet,
                            'previous_due_amount' => $invPrevDue,
                            'new_amount' => $inv->amount,
                            'new_discount' => $inv->discount,
                            'new_net_amount' => $inv->net_amount,
                            'new_due_amount' => $inv->due_amount,
                            'paid_amount' => '0.00',
                            'reason' => $reason,
                        ]);

                        $updatedInvoicesData[] = [
                            'id' => $inv->id,
                            'amount' => bcadd((string) $inv->amount, '0.00', 2),
                            'discount' => bcadd((string) $inv->discount, '0.00', 2),
                            'net_amount' => bcadd((string) $inv->net_amount, '0.00', 2),
                            'due_amount' => bcadd((string) $inv->due_amount, '0.00', 2),
                            'paid_amount' => bcadd((string) $inv->paid_amount, '0.00', 2),
                            'status' => $inv->status,
                        ];
                    }
                } else {
                    $prevDue = bcadd((string) $invoice->due_amount, '0.00', 2);

                    $invoice->amount = $newAmount;
                    $invoice->discount = $newDiscount;
                    $invoice->net_amount = $newNet;
                    $invoice->due_amount = $newDue;
                    $invoice->status = $newStatus;
                    $invoice->save();

                    FeeAdjustmentLog::create([
                        'fee_invoice_id' => $invoice->id,
                        'student_id' => $studentId,
                        'user_id' => $operatorId,
                        'invoice_no' => $invoice->invoice_no,
                        'student_identity' => $student->student_identity ?? null,
                        'mode' => $mode,
                        'scope' => 'single',
                        'adjustment_amount' => $adjustmentDelta,
                        'previous_amount' => $currentAmount,
                        'previous_discount' => $currentDiscount,
                        'previous_net_amount' => $currentNet,
                        'previous_due_amount' => $prevDue,
                        'new_amount' => $invoice->amount,
                        'new_discount' => $invoice->discount,
                        'new_net_amount' => $invoice->net_amount,
                        'new_due_amount' => $invoice->due_amount,
                        'paid_amount' => $paidAmount,
                        'reason' => $reason,
                    ]);

                    $updatedInvoicesData[] = [
                        'id' => $invoice->id,
                        'amount' => bcadd((string) $invoice->amount, '0.00', 2),
                        'discount' => bcadd((string) $invoice->discount, '0.00', 2),
                        'net_amount' => bcadd((string) $invoice->net_amount, '0.00', 2),
                        'due_amount' => bcadd((string) $invoice->due_amount, '0.00', 2),
                        'paid_amount' => bcadd((string) $invoice->paid_amount, '0.00', 2),
                        'status' => $invoice->status,
                    ];
                }

                return [
                    'student_id' => $studentId,
                    'month_name' => $invoice->month_name,
                    'updated_invoices' => $updatedInvoicesData,
                ];
            });

            $allPendingInvoices = FeeInvoice::where('student_id', $result['student_id'])
                ->whereIn('status', ['Unpaid', 'Partial'])
                ->get();

            $summary = $this->buildPendingSummary($allPendingInvoices);

            return response()->json([
                'success' => true,
                'message' => $scope === 'continuous'
                    ? 'Continuous fee updated successfully across all eligible unpaid invoices.'
                    : "Fee for {$result['month_name']} adjusted successfully.",
                'scope' => $scope,
                'updated_invoices' => $result['updated_invoices'],
                'summary' => $summary,
            ]);
        } catch (ValidationException $e) {
            $firstMessage = collect($e->errors())->flatten()->first() ?? $e->getMessage();
            return response()->json([
                'success' => false,
                'message' => $firstMessage,
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Adjustment failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute single legacy invoice payment collection inside an active transaction
     * obeying Tier 1 (Student) -> Tier 2 (FeeInvoice) -> Tier 3 (FeePayment ASC).
     *
     * @return array{receipt_no: string, paid_amount: string, payment: FeePayment, invoice: FeeInvoice}
     */
    private function collectLegacySingleWithinTransaction(
        int $invoiceId,
        int $studentId,
        string $payAmount,
        Request $request,
        int $actorUserId,
        AdmissionCollectionService $collectionService
    ): array {
        // Tier 1: Student -> Tier 2: FeeInvoice -> Tier 3: FeePayments ASC
        $lockedStudent = Student::where('id', $studentId)->lockForUpdate()->firstOrFail();
        $lockedInvoice = FeeInvoice::where('id', $invoiceId)->orderBy('id', 'asc')->lockForUpdate()->firstOrFail();

        if ((int) $lockedInvoice->student_id !== (int) $lockedStudent->id) {
            throw ValidationException::withMessages([
                'invoice_id' => ['Invoice does not belong to the expected student record.'],
            ]);
        }

        $lockedPayments = FeePayment::where('fee_invoice_id', $lockedInvoice->id)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        if ($request->filled('idempotency_key')) {
            $collectionService->claimDatabaseIdempotencyKey(
                'collect_legacy_single',
                (string) $request->input('idempotency_key'),
                $actorUserId,
                (string) $lockedInvoice->id
            );
        }

        if ($lockedInvoice->isCancelled()) {
            throw ValidationException::withMessages([
                'invoice_id' => ["Error: Cannot collect payment for a cancelled invoice ({$lockedInvoice->invoice_no})!"],
            ]);
        }

        $activePaid = $lockedPayments->isNotEmpty()
            ? $collectionService->sumActivePayments($lockedPayments)
            : $collectionService->toDecimalString($lockedInvoice->paid_amount);

        $netDecimal = $collectionService->toDecimalString($lockedInvoice->net_amount);
        $currentDue = bcsub($netDecimal, $activePaid, 2);

        if ($lockedInvoice->isZeroPayableWaived() || bccomp($currentDue, '0.00', 2) <= 0) {
            throw ValidationException::withMessages([
                'invoice_id' => ["Error: Invoice ({$lockedInvoice->invoice_no}) has no payable balance due!"],
            ]);
        }

        if (bccomp($payAmount, $currentDue, 2) > 0) {
            throw ValidationException::withMessages([
                'pay_amount' => ['Error: Paying amount cannot be greater than the Due amount!'],
            ]);
        }

        $paymentDate = $request->filled('payment_date')
            ? Carbon::parse((string) $request->payment_date)->toDateString()
            : date('Y-m-d');
        $receiptNo = $collectionService->allocateNextDatedSequence(
            'receipt_no',
            'REC',
            Carbon::parse($paymentDate)->format('Ymd'),
            true
        );

        $paymentData = [
            'receipt_no' => $receiptNo,
            'fee_invoice_id' => $lockedInvoice->id,
            'student_id' => $lockedInvoice->student_id,
            'paid_amount' => $payAmount,
            'payment_date' => $paymentDate,
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
            'note' => $request->note,
            'collected_by' => $actorUserId,
        ];
        if (Schema::hasColumn('fee_payments', 'branch_id') && !empty($lockedInvoice->branch_id)) {
            $paymentData['branch_id'] = $lockedInvoice->branch_id;
        }

        $payment = FeePayment::create($paymentData);

        $newPaid = bcadd($activePaid, $payAmount, 2);
        $newDue = bcsub($netDecimal, $newPaid, 2);
        $lockedInvoice->paid_amount = $newPaid;
        $lockedInvoice->due_amount = bccomp($newDue, '0.00', 2) > 0 ? $newDue : '0.00';
        $lockedInvoice->status = bccomp($lockedInvoice->due_amount, '0.00', 2) <= 0 ? 'Paid' : 'Partial';
        $lockedInvoice->save();

        return [
            'receipt_no' => $receiptNo,
            'paid_amount' => $payAmount,
            'payment' => $payment,
            'invoice' => $lockedInvoice->fresh('student'),
        ];
    }

    /**
     * Metadata-only schema readiness check for admission multi-head collection/payments views.
     */
    private function collectionAdmissionSchemaReady(FeeReceiptService $receiptService): bool
    {
        return $receiptService->admissionTablesAvailable()
            && Schema::hasTable('admission_fee_structure_items')
            && Schema::hasColumn('fee_invoices', 'admission_fee_structure_id')
            && Schema::hasColumn('fee_payments', 'status');
    }

    /**
     * Defense-in-depth authorization guard across Controller & Service layers.
     * Blocks editor, teacher, user, student, or suspended accounts.
     */
    private function authorizeFeeAdmin(Request $request): User
    {
        $user = Auth::user();
        if (!$user || !AdmissionInvoiceService::userCanGrantDiscount($user)) {
            $message = 'Unauthorized. Only Admin and Super Admin users may perform fee collection or payment reversal.';
            if ($request->expectsJson()) {
                abort(response()->json(['success' => false, 'message' => $message], 403));
            }
            abort(403, $message);
        }

        return $user;
    }

    private function normalizeControllerDecimal(mixed $rawValue, string $fieldName): string
    {
        $converted = AdmissionCollectionService::normalizeHttpMonetaryInput($rawValue);

        try {
            return \App\Services\AdmissionDiscountEngine::validateMonetaryString($converted, $fieldName);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                $fieldName => [$e->getMessage()],
            ]);
        }
    }

    private function normalizePositiveControllerDecimal(mixed $rawValue, string $fieldName): string
    {
        $normalized = $this->normalizeControllerDecimal($rawValue, $fieldName);
        if (bccomp($normalized, '0.00', 2) <= 0) {
            throw ValidationException::withMessages([
                $fieldName => ['Payment amount must be greater than 0.00.'],
            ]);
        }

        return $normalized;
    }

    private function buildPendingSummary($allPendingInvoices): array
    {
        $totalBill = '0.00';
        $totalDue = '0.00';
        $totalDiscount = '0.00';

        foreach ($allPendingInvoices as $pendingInv) {
            $totalBill = bcadd($totalBill, bcadd((string) ($pendingInv->net_amount ?? '0'), '0.00', 2), 2);
            $totalDue = bcadd($totalDue, bcadd((string) ($pendingInv->due_amount ?? '0'), '0.00', 2), 2);
            $totalDiscount = bcadd($totalDiscount, bcadd((string) ($pendingInv->discount ?? '0'), '0.00', 2), 2);
        }

        return [
            'invoices_count' => $allPendingInvoices->count(),
            'total_bill' => number_format((float) $totalBill, 2),
            'total_due' => number_format((float) $totalDue, 2),
            'total_discount' => number_format((float) $totalDiscount, 2),
        ];
    }

    private function respondValidationFailure(Request $request, array $errors, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $errors,
            ], 422);
        }

        return back()->withInput()->withErrors($errors)->with('error', $message);
    }

    private function respondForbiddenFailure(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        abort(403, $message);
    }
}