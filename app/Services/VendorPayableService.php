<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseVendor;
use App\Models\User;
use App\Models\VendorAuditLog;
use App\Models\VendorOpeningBalance;
use App\Models\VendorPayment;
use App\Models\VendorPaymentAllocation;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VendorPayableService
{
    /**
     * Record an immediate payment associated with an Expense Voucher.
     */
    public function recordVoucherPayment(
        Expense $expense,
        float $paidAmount,
        string $paymentMethod,
        ?string $refNo = null,
        ?string $receiptPath = null,
        ?string $note = null,
        ?int $userId = null
    ): ?VendorPayment {
        if ($paidAmount <= 0 || !$expense->vendor_id) {
            return null;
        }

        $userId = $userId ?? Auth::id();
        $paymentNo = VendorPayment::generatePaymentNo();

        $payment = VendorPayment::create([
            'payment_no'     => $paymentNo,
            'vendor_id'      => $expense->vendor_id,
            'branch_id'      => $expense->branch_id,
            'expense_id'     => $expense->id,
            'payment_date'   => $expense->expense_date,
            'payment_method' => $paymentMethod,
            'amount'         => $paidAmount,
            'reference_no'   => $refNo,
            'receipt_file'   => $receiptPath,
            'note'           => $note ?? "Immediate payment on voucher {$expense->voucher_no}",
            'created_from'   => 'voucher',
            'user_id'        => $userId,
        ]);

        $payment->allocations()->create([
            'allocatable_type' => Expense::class,
            'allocatable_id'   => $expense->id,
            'allocated_amount' => $paidAmount,
            'note'             => "Full/Partial initial payment for voucher {$expense->voucher_no}",
        ]);

        VendorAuditLog::record(
            action: 'voucher_payment',
            entityType: VendorPayment::class,
            entityId: $payment->id,
            vendorId: $expense->vendor_id,
            amount: $paidAmount,
            reason: "Initial voucher payment for {$expense->voucher_no}",
            newData: $payment->toArray()
        );

        return $payment;
    }

    /**
     * Record a payment transaction from the dedicated Vendor Payments page.
     * Uses row-level locking (lockForUpdate) and strict non-overpayment validation.
     *
     * @throws ValidationException
     */
    public function recordPayment(
        int $vendorId,
        float $totalAmount,
        string $paymentDate,
        string $paymentMethod,
        ?string $refNo = null,
        ?string $receiptPath = null,
        ?string $note = null,
        array $allocations = [],
        ?int $branchId = null,
        ?int $userId = null
    ): VendorPayment {
        $vendor = ExpenseVendor::findOrFail($vendorId);

        if ($totalAmount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Payment amount must be greater than zero.'],
            ]);
        }

        if (empty($allocations)) {
            throw ValidationException::withMessages([
                'allocations' => ['At least one bill or opening balance must be allocated for this payment.'],
            ]);
        }

        // Validate allocation total matches payment total
        $allocatedSum = 0.0;
        foreach ($allocations as $alloc) {
            $amt = round((float) ($alloc['amount'] ?? 0), 2);
            if ($amt > 0) {
                $allocatedSum += $amt;
            }
        }
        $allocatedSum = round($allocatedSum, 2);

        if (abs($allocatedSum - round($totalAmount, 2)) > 0.01) {
            throw ValidationException::withMessages([
                'allocations' => ["Total allocated amount (৳ {$allocatedSum}) does not match the payment amount (৳ {$totalAmount})."],
            ]);
        }

        // Fix 5: Sort allocations deterministically by type and ID to prevent InnoDB deadlocks
        usort($allocations, function ($a, $b) {
            $typeCompare = strcmp($a['type'] ?? '', $b['type'] ?? '');
            if ($typeCompare !== 0) {
                return $typeCompare;
            }
            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        });

        // Fix 5: Idempotency protection against rapid repeated form submissions
        $allocationFingerprint = md5(json_encode(array_map(fn($a) => [
            'type' => $a['type'] ?? '',
            'id' => (int) ($a['id'] ?? 0),
            'amount' => round((float) ($a['amount'] ?? 0), 2),
        ], $allocations)));
        $idempotencyKey = "vendor_payment_lock:{$vendorId}:" . round($totalAmount, 2) . ":{$allocationFingerprint}";

        if (!\Illuminate\Support\Facades\Cache::add($idempotencyKey, 1, now()->addSeconds(5))) {
            throw ValidationException::withMessages([
                'amount' => ['A payment with identical allocations was just submitted. Please wait a few seconds before retrying.'],
            ]);
        }

        $userId = $userId ?? Auth::id();

        return DB::transaction(function () use (
            $vendor,
            $totalAmount,
            $paymentDate,
            $paymentMethod,
            $refNo,
            $receiptPath,
            $note,
            $allocations,
            $branchId,
            $userId
        ) {
            $paymentNo = VendorPayment::generatePaymentNo();

            $payment = VendorPayment::create([
                'payment_no'     => $paymentNo,
                'vendor_id'      => $vendor->id,
                'branch_id'      => $branchId,
                'payment_date'   => $paymentDate,
                'payment_method' => $paymentMethod,
                'amount'         => $totalAmount,
                'reference_no'   => $refNo ? trim($refNo) : null,
                'receipt_file'   => $receiptPath,
                'note'           => $note ? trim($note) : null,
                'created_from'   => 'direct',
                'user_id'        => $userId,
            ]);

            foreach ($allocations as $idx => $alloc) {
                $allocAmount = round((float) ($alloc['amount'] ?? 0), 2);
                if ($allocAmount <= 0) {
                    continue;
                }

                $type = $alloc['type'] ?? 'bill';
                $id = (int) ($alloc['id'] ?? 0);

                if ($type === 'bill') {
                    $bill = Expense::where('id', $id)
                        ->where('vendor_id', $vendor->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$bill) {
                        throw ValidationException::withMessages([
                            "allocations.{$idx}" => ["Bill #{$id} does not exist or does not belong to vendor {$vendor->name}."],
                        ]);
                    }

                    $currentDue = $bill->due_amount;
                    if ($allocAmount > ($currentDue + 0.01)) {
                        throw ValidationException::withMessages([
                            "allocations.{$idx}" => [
                                "Payment amount (৳ " . number_format($allocAmount, 2) . ") exceeds remaining balance (৳ " . number_format($currentDue, 2) . ") on voucher {$bill->voucher_no}."
                            ],
                        ]);
                    }

                    $bill->paid_amount = round((float) $bill->paid_amount + $allocAmount, 2);
                    $bill->updatePaymentStatus();

                    $payment->allocations()->create([
                        'allocatable_type' => Expense::class,
                        'allocatable_id'   => $bill->id,
                        'allocated_amount' => $allocAmount,
                        'note'             => "Settlement for voucher {$bill->voucher_no}",
                    ]);

                } elseif ($type === 'opening_balance') {
                    $ob = VendorOpeningBalance::where('id', $id)
                        ->where('vendor_id', $vendor->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$ob) {
                        throw ValidationException::withMessages([
                            "allocations.{$idx}" => ["Opening balance #{$id} does not belong to vendor {$vendor->name}."],
                        ]);
                    }

                    $currentDue = $ob->due_amount;
                    if ($allocAmount > ($currentDue + 0.01)) {
                        throw ValidationException::withMessages([
                            "allocations.{$idx}" => [
                                "Payment amount (৳ " . number_format($allocAmount, 2) . ") exceeds remaining opening balance (৳ " . number_format($currentDue, 2) . ")."
                            ],
                        ]);
                    }

                    $ob->paid_amount = round((float) $ob->paid_amount + $allocAmount, 2);
                    $ob->updateStatus();

                    $payment->allocations()->create([
                        'allocatable_type' => VendorOpeningBalance::class,
                        'allocatable_id'   => $ob->id,
                        'allocated_amount' => $allocAmount,
                        'note'             => "Settlement for opening balance #{$ob->id}" . ($ob->reference_no ? " (Ref: {$ob->reference_no})" : ''),
                    ]);
                }
            }

            VendorAuditLog::record(
                action: 'payment_recorded',
                entityType: VendorPayment::class,
                entityId: $payment->id,
                vendorId: $vendor->id,
                amount: $totalAmount,
                reason: "Payment {$paymentNo} recorded with " . count($allocations) . " allocation(s)",
                newData: $payment->load('allocations')->toArray(),
                operator: $userId ? User::find($userId) : null
            );

            return $payment;
        });
    }

    /**
     * Record an opening balance for a vendor safely.
     * Prevents duplicate imports and validates positive amounts.
     *
     * @throws ValidationException
     */
    public function recordOpeningBalance(
        int $vendorId,
        float $amount,
        string $effectiveDate,
        ?string $refNo = null,
        ?string $refDate = null,
        ?int $branchId = null,
        ?string $note = null,
        ?int $userId = null
    ): VendorOpeningBalance {
        $vendor = ExpenseVendor::findOrFail($vendorId);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'opening_balance' => ['Opening balance must be greater than zero.'],
            ]);
        }

        // Prevent duplicate opening balance imports with the same reference number for this vendor
        if (!empty($refNo)) {
            $duplicate = VendorOpeningBalance::where('vendor_id', $vendorId)
                ->where('reference_no', trim($refNo))
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'reference_no' => ["An opening balance with reference '{$refNo}' already exists for this vendor."],
                ]);
            }
        }

        $userId = $userId ?? Auth::id();

        $ob = VendorOpeningBalance::create([
            'vendor_id'       => $vendor->id,
            'branch_id'       => $branchId,
            'opening_balance' => round($amount, 2),
            'paid_amount'     => 0.00,
            'effective_date'  => $effectiveDate,
            'reference_no'    => $refNo ? trim($refNo) : null,
            'reference_date'  => $refDate ?: null,
            'status'          => 'Unpaid',
            'note'            => $note ? trim($note) : null,
            'user_id'         => $userId,
        ]);

        VendorAuditLog::record(
            action: 'opening_balance_created',
            entityType: VendorOpeningBalance::class,
            entityId: $ob->id,
            vendorId: $vendor->id,
            amount: $amount,
            reason: "Initial opening balance registered",
            newData: $ob->toArray(),
            operator: $userId ? User::find($userId) : null
        );

        return $ob;
    }

    /**
     * Get all eligible outstanding payables (bills and opening balances) for a vendor.
     */
    public function getVendorOutstandingItems(int $vendorId): array
    {
        $openingBalances = VendorOpeningBalance::where('vendor_id', $vendorId)
            ->whereIn('status', ['Unpaid', 'Partially Paid'])
            ->orderBy('effective_date', 'asc')
            ->get()
            ->map(function ($ob) {
                return [
                    'type'             => 'opening_balance',
                    'id'               => $ob->id,
                    'reference'        => $ob->reference_no ?: "OB-#{$ob->id}",
                    'date'             => $ob->effective_date->format('Y-m-d'),
                    'due_date'         => null,
                    'is_overdue'       => false,
                    'original_amount'  => (float) $ob->opening_balance,
                    'paid_amount'      => (float) $ob->paid_amount,
                    'due_amount'       => (float) $ob->due_amount,
                    'description'      => 'Opening Balance' . ($ob->reference_no ? " (Ref: {$ob->reference_no})" : ''),
                ];
            })
            ->filter(fn($item) => $item['due_amount'] > 0)
            ->values();

        $bills = Expense::where('vendor_id', $vendorId)
            ->whereIn('payment_status', ['Unpaid', 'Partially Paid'])
            ->orderBy('expense_date', 'asc')
            ->get()
            ->map(function ($bill) {
                return [
                    'type'             => 'bill',
                    'id'               => $bill->id,
                    'reference'        => $bill->voucher_no,
                    'date'             => $bill->expense_date->format('Y-m-d'),
                    'due_date'         => $bill->due_date ? $bill->due_date->format('Y-m-d') : null,
                    'is_overdue'       => $bill->is_overdue,
                    'original_amount'  => (float) $bill->amount,
                    'paid_amount'      => (float) $bill->paid_amount,
                    'due_amount'       => (float) $bill->due_amount,
                    'description'      => $bill->note ?: ($bill->category?->name ?? 'Expense Bill'),
                ];
            })
            ->filter(fn($item) => $item['due_amount'] > 0)
            ->values();

        $all = $openingBalances->concat($bills);
        $totalOutstanding = (float) $all->sum('due_amount');

        return [
            'items'            => $all->toArray(),
            'total_outstanding'=> round($totalOutstanding, 2),
        ];
    }

    /**
     * Compile an authoritative chronological vendor ledger.
     * Computes running balance strictly row by row without drifting totals.
     */
    public function getVendorLedger(int $vendorId, ?string $startDate = null, ?string $endDate = null, ?int $branchId = null): array
    {
        $vendor = ExpenseVendor::findOrFail($vendorId);

        // 1. Calculate Prior Opening Balance (Opening balances + Bills - Payments before startDate)
        $priorDue = 0.0;
        if (!empty($startDate)) {
            $priorOB = VendorOpeningBalance::where('vendor_id', $vendorId)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->where('effective_date', '<', $startDate)
                ->sum('opening_balance');

            $priorBills = Expense::where('vendor_id', $vendorId)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->where('expense_date', '<', $startDate)
                ->sum('amount');

            $priorPayments = VendorPayment::where('vendor_id', $vendorId)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->where('payment_date', '<', $startDate)
                ->sum('amount');

            // For expenses prior to startDate that do not have canonical VendorPayment rows or allocation rows,
            // take their recorded paid_amount so historical settled amounts are not miscounted as unpaid.
            $priorLegacyPaid = Expense::where('vendor_id', $vendorId)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->where('expense_date', '<', $startDate)
                ->whereDoesntHave('payments')
                ->whereDoesntHave('paymentAllocations')
                ->sum('paid_amount');

            $priorDue = max(0.0, round((float) $priorOB + (float) $priorBills - (float) $priorPayments - (float) $priorLegacyPaid, 2));
        }

        // 2. Query in-period events
        // A. Opening Balances effective within period
        $obQuery = VendorOpeningBalance::where('vendor_id', $vendorId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        if (!empty($startDate)) {
            $obQuery->where('effective_date', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $obQuery->where('effective_date', '<=', $endDate);
        }
        $inPeriodOB = $obQuery->get();

        // B. Bills within period
        $billsQuery = Expense::with(['items.category', 'category'])
            ->where('vendor_id', $vendorId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        if (!empty($startDate)) {
            $billsQuery->where('expense_date', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $billsQuery->where('expense_date', '<=', $endDate);
        }
        $inPeriodBills = $billsQuery->get();

        // C. Payments within period
        $paymentsQuery = VendorPayment::with(['allocations.allocatable'])
            ->where('vendor_id', $vendorId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        if (!empty($startDate)) {
            $paymentsQuery->where('payment_date', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $paymentsQuery->where('payment_date', '<=', $endDate);
        }
        $inPeriodPayments = $paymentsQuery->get();

        // 3. Assemble unified chronological feed
        $timeline = collect();

        foreach ($inPeriodOB as $ob) {
            $timeline->push([
                'date'          => $ob->effective_date->format('Y-m-d'),
                'type'          => 'Opening Balance',
                'reference'     => $ob->reference_no ?: "OB-#{$ob->id}",
                'description'   => $ob->note ?: 'Pre-existing payable balance',
                'debit'         => 0.00, // payment
                'credit'        => (float) $ob->opening_balance, // liability increase
                'payment_method'=> null,
                'due_date'      => null,
                'status'        => $ob->status,
                'raw'           => $ob,
                'timestamp'     => strtotime($ob->effective_date->format('Y-m-d') . ' 00:00:00'),
            ]);
        }

        foreach ($inPeriodBills as $bill) {
            $timeline->push([
                'date'          => $bill->expense_date->format('Y-m-d'),
                'type'          => 'Expense Bill',
                'reference'     => $bill->voucher_no,
                'description'   => $bill->note ?: ($bill->category?->name ?? 'Expense Voucher'),
                'debit'         => 0.00,
                'credit'        => (float) $bill->amount, // liability increase
                'payment_method'=> $bill->payment_method,
                'due_date'      => $bill->due_date ? $bill->due_date->format('Y-m-d') : null,
                'status'        => $bill->payment_status,
                'raw'           => $bill,
                'timestamp'     => strtotime($bill->expense_date->format('Y-m-d') . ' 00:00:01'),
            ]);

            // If a bill has paid_amount > 0 and no separate VendorPayment or allocation records (legacy voucher or direct model entry),
            // record its immediate settlement so the running balance reflects it accurately.
            if ($bill->paid_amount > 0 && $bill->payments()->count() === 0 && $bill->paymentAllocations()->count() === 0) {
                $timeline->push([
                    'date'          => $bill->expense_date->format('Y-m-d'),
                    'type'          => 'Payment Settlement',
                    'reference'     => $bill->voucher_no . '-PAY',
                    'description'   => "Direct settlement at voucher entry ({$bill->payment_method})",
                    'debit'         => (float) $bill->paid_amount,
                    'credit'        => 0.00,
                    'payment_method'=> $bill->payment_method,
                    'due_date'      => null,
                    'status'        => 'Completed',
                    'raw'           => $bill,
                    'timestamp'     => strtotime($bill->expense_date->format('Y-m-d') . ' 00:00:02'),
                ]);
            }
        }

        foreach ($inPeriodPayments as $pay) {
            $timeline->push([
                'date'          => $pay->payment_date->format('Y-m-d'),
                'type'          => 'Payment Settlement',
                'reference'     => $pay->payment_no,
                'description'   => $pay->note ?: ("Payment via {$pay->payment_method}" . ($pay->reference_no ? " (Trx: {$pay->reference_no})" : '')),
                'debit'         => (float) $pay->amount, // liability reduction
                'credit'        => 0.00,
                'payment_method'=> $pay->payment_method,
                'due_date'      => null,
                'status'        => 'Completed',
                'raw'           => $pay,
                'timestamp'     => strtotime($pay->payment_date->format('Y-m-d') . ' 00:00:02'),
            ]);
        }

        // Sort by timestamp asc
        $sorted = $timeline->sortBy('timestamp')->values();

        // Calculate running balance row by row
        $runningBalance = $priorDue;
        $ledgerRows = [];
        $totalPeriodBilled = 0.0;
        $totalPeriodPaid = 0.0;

        foreach ($sorted as $row) {
            $runningBalance += ($row['credit'] - $row['debit']);
            $totalPeriodBilled += $row['credit'];
            $totalPeriodPaid += $row['debit'];

            $ledgerRows[] = array_merge($row, [
                'running_balance' => round($runningBalance, 2),
            ]);
        }

        // Closing balance
        $closingDue = round($runningBalance, 2);

        // Overdue calculation for this vendor
        $overdueDue = (float) Expense::where('vendor_id', $vendorId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->overdue()
            ->get()
            ->sum(fn($b) => $b->due_amount);

        return [
            'vendor'              => $vendor,
            'prior_due'           => round($priorDue, 2),
            'total_period_billed' => round($totalPeriodBilled, 2),
            'billed_in_period'    => round($totalPeriodBilled, 2),
            'total_period_paid'   => round($totalPeriodPaid, 2),
            'paid_in_period'      => round($totalPeriodPaid, 2),
            'closing_due'         => $closingDue,
            'closing_payable'     => $closingDue,
            'overdue_amount'      => round($overdueDue, 2),
            'rows'                => $ledgerRows,
            'transactions'        => $ledgerRows,
        ];
    }

    /**
     * Compile comprehensive Payables Dashboard KPIs and summary tables.
     */
    public function getPayablesDashboardData(?int $branchId = null): array
    {
        $today = date('Y-m-d');
        $sevenDaysLater = date('Y-m-d', strtotime('+7 days'));

        // 1. Outstanding Bills
        $billsQuery = Expense::with(['vendor', 'branch', 'category'])
            ->outstanding()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        $outstandingBills = $billsQuery->get();
        $totalBillsDue = (float) $outstandingBills->sum(fn($b) => $b->due_amount);

        // 2. Outstanding Opening Balances
        $obQuery = VendorOpeningBalance::with(['vendor', 'branch'])
            ->whereIn('status', ['Unpaid', 'Partially Paid'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        $outstandingOB = $obQuery->get();
        $totalOBDue = (float) $outstandingOB->sum(fn($ob) => $ob->due_amount);

        // Total Outstanding Payable (Formula from Phase 2: Opening Due + New Bills Due - Payments)
        $totalOutstandingPayable = round($totalBillsDue + $totalOBDue, 2);

        // Counts
        $unpaidCount = Expense::where('payment_status', 'Unpaid')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))->count()
            + VendorOpeningBalance::where('status', 'Unpaid')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))->count();

        $partiallyPaidCount = Expense::where('payment_status', 'Partially Paid')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))->count()
            + VendorOpeningBalance::where('status', 'Partially Paid')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))->count();

        $paidCount = Expense::where('payment_status', 'Paid')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))->count();

        // Due Soon (due_date between today and today+7 days)
        $dueSoonBills = $outstandingBills->filter(function ($b) use ($today, $sevenDaysLater) {
            return $b->due_date && $b->due_date->format('Y-m-d') >= $today && $b->due_date->format('Y-m-d') <= $sevenDaysLater;
        });
        $totalDueSoon = (float) $dueSoonBills->sum(fn($b) => $b->due_amount);

        // Overdue (due_date < today)
        $overdueBills = $outstandingBills->filter(function ($b) use ($today) {
            return $b->due_date && $b->due_date->format('Y-m-d') < $today;
        });
        $totalOverdue = (float) $overdueBills->sum(fn($b) => $b->due_amount);

        // Vendor-wise summary list
        $vendors = ExpenseVendor::active()->with(['openingBalances', 'expenses'])->get();
        $vendorSummaries = $vendors->map(function ($v) use ($branchId) {
            $obDue = (float) $v->openingBalances
                ->when($branchId, fn($obs) => $obs->where('branch_id', $branchId))
                ->sum(fn($ob) => $ob->due_amount);

            $billsDue = (float) $v->expenses
                ->when($branchId, fn($exps) => $exps->where('branch_id', $branchId))
                ->sum(fn($exp) => $exp->due_amount);

            $overdue = (float) $v->expenses
                ->when($branchId, fn($exps) => $exps->where('branch_id', $branchId))
                ->filter(fn($b) => $b->is_overdue)
                ->sum(fn($exp) => $exp->due_amount);

            $totalDue = round($obDue + $billsDue, 2);

            return [
                'id'             => $v->id,
                'name'           => $v->name,
                'contact_person' => $v->contact_person,
                'phone'          => $v->phone,
                'opening_due'    => round($obDue, 2),
                'bills_due'      => round($billsDue, 2),
                'total_due'      => $totalDue,
                'overdue_amount' => round($overdue, 2),
            ];
        })
        ->filter(fn($v) => $v['total_due'] > 0)
        ->sortByDesc('total_due')
        ->values();

        // Recent payment activity (last 10 payments)
        $recentPayments = VendorPayment::with(['vendor', 'creator'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        // Upcoming / Overdue bills list (sorted by due_date asc)
        $criticalBills = $outstandingBills
            ->filter(fn($b) => !empty($b->due_date))
            ->sortBy('due_date')
            ->take(10)
            ->values();

        return [
            'totalOutstandingPayable' => $totalOutstandingPayable,
            'totalBillsDue'           => round($totalBillsDue, 2),
            'totalOBDue'              => round($totalOBDue, 2),
            'totalDueSoon'            => round($totalDueSoon, 2),
            'totalOverdue'            => round($totalOverdue, 2),
            'unpaidCount'             => $unpaidCount,
            'partiallyPaidCount'      => $partiallyPaidCount,
            'paidCount'               => $paidCount,
            'vendorSummaries'         => $vendorSummaries,
            'recentPayments'          => $recentPayments,
            'criticalBills'           => $criticalBills,
        ];
    }
}
