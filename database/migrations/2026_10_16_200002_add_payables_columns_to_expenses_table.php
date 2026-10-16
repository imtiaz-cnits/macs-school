<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('expense_date');
            $table->decimal('paid_amount', 12, 2)->default(0.00)->after('amount');
            $table->string('payment_status', 20)->default('Paid')->after('paid_amount'); // 'Paid', 'Partially Paid', 'Unpaid'

            $table->index('due_date');
            $table->index('payment_status');
        });

        // Historical Compatibility & Audit Reconciliation Strategy:
        // Prior to this migration, MACS School recorded all expenses as immediate cash/bank disbursements.
        // There was no payables ledger, due date, or unpaid status column.
        //
        // In order to prevent historical expenses from falsely appearing as 100% outstanding liabilities
        // (which would artificially inflate accounts payable by the entire lifetime expense total),
        // legacy records default to 'Paid' with paid_amount equal to the voucher amount.
        //
        // CUTOFF & OPENING BALANCE RECONCILIATION:
        // Because historical expense rows lack proof of external vendor settlement (some may have had informal dues),
        // administrators MUST NOT manually rewrite historical expense vouchers.
        // Instead, any verified pre-system outstanding vendor debts must be entered through the
        // `VendorOpeningBalance` feature with an established transition cutoff date.
        \Illuminate\Support\Facades\DB::table('expenses')
            ->where('payment_status', 'Paid')
            ->where('paid_amount', 0)
            ->update(['paid_amount' => \Illuminate\Support\Facades\DB::raw('amount')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['due_date']);
            $table->dropIndex(['payment_status']);
            $table->dropColumn(['due_date', 'paid_amount', 'payment_status']);
        });
    }
};
