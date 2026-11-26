<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Defensive cleanup if an environment ran the earlier un-released separate-invoice draft.
        if (Schema::hasColumn('fee_invoices', 'parent_fee_invoice_id')
            || Schema::hasColumn('fee_invoices', 'auto_fine_parent_id')
        ) {
            Schema::table('fee_invoices', function (Blueprint $table): void {
                if (DB::connection()->getDriverName() === 'mysql') {
                    try {
                        $table->dropForeign('fk_fi_parent_invoice');
                    } catch (\Throwable) {
                    }
                    try {
                        $table->dropUnique('uq_fi_auto_fine_parent');
                    } catch (\Throwable) {
                    }
                    try {
                        $table->dropIndex('idx_fi_parent_invoice');
                    } catch (\Throwable) {
                    }
                }

                $legacyCols = [];
                foreach (['auto_fine_parent_id', 'parent_fee_invoice_id'] as $col) {
                    if (Schema::hasColumn('fee_invoices', $col)) {
                        $legacyCols[] = $col;
                    }
                }
                if (!empty($legacyCols)) {
                    $table->dropColumn($legacyCols);
                }
            });
        }

        Schema::table('fee_invoices', function (Blueprint $table): void {
            if (!Schema::hasColumn('fee_invoices', 'fine_amount')) {
                $table->decimal('fine_amount', 10, 2)
                    ->default(0.00)
                    ->after('net_amount');
            }

            if (!Schema::hasColumn('fee_invoices', 'fine_source')) {
                $table->string('fine_source', 16)
                    ->nullable()
                    ->after('fine_amount');
            }

            if (!Schema::hasColumn('fee_invoices', 'fine_reason')) {
                $table->string('fine_reason', 255)
                    ->nullable()
                    ->after('fine_source');
            }

            if (!Schema::hasColumn('fee_invoices', 'auto_fine_applied_at')) {
                $table->timestamp('auto_fine_applied_at')
                    ->nullable()
                    ->after('fine_reason');
            }

            if (!Schema::hasColumn('fee_invoices', 'auto_fine_waived_at')) {
                $table->timestamp('auto_fine_waived_at')
                    ->nullable()
                    ->after('auto_fine_applied_at');
            }

            $table->index(
                ['status', 'auto_fine_applied_at', 'auto_fine_waived_at'],
                'idx_fi_auto_fine_scan'
            );
        });

        Schema::table('fee_payments', function (Blueprint $table): void {
            if (!Schema::hasColumn('fee_payments', 'fine_amount')) {
                $table->decimal('fine_amount', 10, 2)
                    ->default(0.00)
                    ->after('paid_amount');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('fee_payments', 'fine_amount')) {
            Schema::table('fee_payments', function (Blueprint $table): void {
                $table->dropColumn('fine_amount');
            });
        }

        Schema::table('fee_invoices', function (Blueprint $table): void {
            try {
                $table->dropIndex('idx_fi_auto_fine_scan');
            } catch (\Throwable) {
            }

            $dropCols = [];
            foreach ([
                'auto_fine_waived_at',
                'auto_fine_applied_at',
                'fine_reason',
                'fine_source',
                'fine_amount',
            ] as $col) {
                if (Schema::hasColumn('fee_invoices', $col)) {
                    $dropCols[] = $col;
                }
            }

            if (!empty($dropCols)) {
                $table->dropColumn($dropCols);
            }
        });
    }
};
