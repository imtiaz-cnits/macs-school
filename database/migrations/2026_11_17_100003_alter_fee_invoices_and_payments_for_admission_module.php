<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $this->upMySql();
            return;
        }

        $this->upPortable();
    }

    public function down(): void
    {
        if (Schema::hasTable('admission_payment_head_allocations') && DB::table('admission_payment_head_allocations')->exists()) {
            throw new \RuntimeException("ROLLBACK ABORTED (Migration 3): Child rows exist in 'admission_payment_head_allocations'.");
        }
        if (Schema::hasTable('admission_invoice_head_allocations') && DB::table('admission_invoice_head_allocations')->exists()) {
            throw new \RuntimeException("ROLLBACK ABORTED (Migration 3): Child rows exist in 'admission_invoice_head_allocations'.");
        }
        if (Schema::hasColumn('fee_payments', 'status') && DB::table('fee_payments')->where('status', 'Reversed')->exists()) {
            throw new \RuntimeException("ROLLBACK ABORTED (Migration 3): Reversed payment audit records exist in 'fee_payments'.");
        }
        if (DB::table('fee_invoices')->whereNull('fee_setup_id')->orWhereNotNull('admission_fee_structure_id')->exists()) {
            throw new \RuntimeException("ROLLBACK ABORTED (Migration 3): Admission invoices or NULL fee_setup_id records exist in 'fee_invoices'.");
        }

        if (DB::getDriverName() === 'mysql') {
            $this->downMySql();
            return;
        }

        $this->downPortable();
    }

    private function upMySql(): void
    {
        // =========================================================================
        // PART A: FEE_INVOICES UPGRADE & HISTORICAL BACKFILL
        // =========================================================================
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropForeign('fee_invoices_fee_setup_id_foreign');
            $table->unsignedBigInteger('fee_setup_id')->nullable()->change();
            $table->foreign('fee_setup_id', 'fee_invoices_fee_setup_id_foreign')
                  ->references('id')->on('fee_setups')->onDelete('restrict');

            $table->unsignedBigInteger('admission_fee_structure_id')->nullable()->after('fee_setup_id');
            $table->unsignedBigInteger('branch_id')->nullable()->after('student_id');
            $table->unsignedBigInteger('session_year_id')->nullable()->after('branch_id');
            $table->string('branch_name_snapshot', 150)->nullable()->after('session_year_id');
        });

        DB::statement("
            UPDATE fee_invoices fi
            INNER JOIN fee_setups fs ON fi.fee_setup_id = fs.id
            SET fi.branch_id = fs.branch_id,
                fi.session_year_id = fs.session_year_id
        ");

        DB::statement("
            UPDATE fee_invoices fi
            INNER JOIN students s ON fi.student_id = s.id
            SET fi.branch_id = s.branch_id,
                fi.session_year_id = s.session_year_id
            WHERE fi.branch_id IS NULL OR fi.session_year_id IS NULL
        ");

        DB::statement("
            UPDATE fee_invoices fi
            INNER JOIN branches b ON fi.branch_id = b.id
            SET fi.branch_name_snapshot = b.branch_name
            WHERE fi.branch_name_snapshot IS NULL
        ");

        $unmappedInvoices = DB::table('fee_invoices')->whereNull('branch_id')->orWhereNull('session_year_id')->count();
        if ($unmappedInvoices > 0) {
            throw new \RuntimeException("Migration 3 Aborted: {$unmappedInvoices} invoices remain without branch_id or session_year_id.");
        }

        DB::statement("ALTER TABLE fee_invoices MODIFY branch_id BIGINT UNSIGNED NOT NULL, MODIFY session_year_id BIGINT UNSIGNED NOT NULL");

        // Note: VIRTUAL is required (rather than STORED) because `student_id` has an existing
        // `ON DELETE CASCADE` FK (`fee_invoices_student_id_foreign`) in production; InnoDB natively
        // indexes VIRTUAL generated columns to enforce `UNIQUE` without altering legacy student FK.
        DB::statement("
            ALTER TABLE fee_invoices
            ADD COLUMN admission_active_scope VARCHAR(100) GENERATED ALWAYS AS (
                CASE
                    WHEN admission_fee_structure_id IS NOT NULL AND status != 'Cancelled'
                    THEN CONCAT(student_id, '-', branch_id, '-', session_year_id)
                    ELSE NULL
                END
            ) VIRTUAL AFTER branch_name_snapshot
        ");

        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->unique('admission_active_scope', 'uq_fi_student_admission_scope');
            $table->unique(['id', 'student_id', 'branch_id'], 'uq_fi_invoice_composite');
            $table->foreign('admission_fee_structure_id', 'fk_fi_admission_fee_structure')
                  ->references('id')->on('admission_fee_structures')->onDelete('restrict');
            $table->foreign('branch_id', 'fk_fi_branch')
                  ->references('id')->on('branches')->onDelete('restrict');
            $table->foreign('session_year_id', 'fk_fi_session_year')
                  ->references('id')->on('session_years')->onDelete('restrict');
        });

        DB::statement("
            ALTER TABLE fee_invoices
            ADD CONSTRAINT chk_fi_setup_or_admission_xor CHECK (
                (fee_setup_id IS NOT NULL AND admission_fee_structure_id IS NULL)
                OR
                (fee_setup_id IS NULL AND admission_fee_structure_id IS NOT NULL)
            )
        ");

        // =========================================================================
        // PART B: FEE_PAYMENTS UPGRADE & AUTHORITATIVE COMPOSITE FK REPLACEMENT
        // =========================================================================
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('student_id');
            $table->enum('status', ['Active', 'Reversed'])->default('Active')->after('payment_method');
            $table->decimal('reversed_amount', 10, 2)->nullable()->after('status');
            $table->timestamp('reversed_at')->nullable()->after('reversed_amount');
            $table->unsignedBigInteger('reversed_by')->nullable()->after('reversed_at');
            $table->string('reversal_reason', 255)->nullable()->after('reversed_by');
            $table->string('reversal_reference', 100)->nullable()->after('reversal_reason');
        });

        DB::statement("
            UPDATE fee_payments fp
            INNER JOIN fee_invoices fi ON fp.fee_invoice_id = fi.id
            SET fp.branch_id = fi.branch_id
        ");

        $unmappedPayments = DB::table('fee_payments')->whereNull('branch_id')->count();
        if ($unmappedPayments > 0) {
            throw new \RuntimeException("Migration 3 Aborted: {$unmappedPayments} payments remain with NULL branch_id.");
        }

        DB::statement("ALTER TABLE fee_payments MODIFY branch_id BIGINT UNSIGNED NOT NULL");

        Schema::table('fee_payments', function (Blueprint $table) {
            $table->unique('reversal_reference', 'uq_fp_reversal_reference');
            $table->unique(['id', 'fee_invoice_id', 'student_id', 'branch_id'], 'uq_fp_payment_composite');
            $table->foreign('branch_id', 'fk_fp_branch')
                  ->references('id')->on('branches')->onDelete('restrict');
            $table->foreign('reversed_by', 'fk_fp_reversed_by')
                  ->references('id')->on('users')->onDelete('restrict');
            $table->foreign(['fee_invoice_id', 'student_id', 'branch_id'], 'fk_fp_invoice_composite')
                  ->references(['id', 'student_id', 'branch_id'])->on('fee_invoices')->onDelete('restrict');

            $table->dropForeign('fee_payments_fee_invoice_id_foreign');
            $table->dropIndex('fee_payments_fee_invoice_id_foreign');
        });

        DB::statement("
            ALTER TABLE fee_payments
            ADD CONSTRAINT chk_fp_reversal_integrity CHECK (
                (status = 'Active' AND reversed_amount IS NULL AND reversed_at IS NULL AND reversed_by IS NULL AND reversal_reason IS NULL AND reversal_reference IS NULL)
                OR
                (status = 'Reversed' AND reversed_amount = paid_amount AND reversed_at IS NOT NULL AND reversed_by IS NOT NULL AND reversal_reason IS NOT NULL AND reversal_reference IS NOT NULL)
            )
        ");
    }

    private function downMySql(): void
    {
        DB::statement("ALTER TABLE fee_payments DROP CHECK chk_fp_reversal_integrity");

        Schema::table('fee_payments', function (Blueprint $table) {
            $table->index('fee_invoice_id', 'fee_payments_fee_invoice_id_foreign');
            $table->dropForeign('fk_fp_invoice_composite');
            $table->dropIndex('fk_fp_invoice_composite');
            $table->dropForeign('fk_fp_reversed_by');
            $table->dropForeign('fk_fp_branch');
            $table->dropUnique('uq_fp_payment_composite');
            $table->dropUnique('uq_fp_reversal_reference');
            $table->dropColumn([
                'branch_id', 'status', 'reversed_amount', 'reversed_at',
                'reversed_by', 'reversal_reason', 'reversal_reference'
            ]);
            $table->foreign('fee_invoice_id', 'fee_payments_fee_invoice_id_foreign')
                  ->references('id')->on('fee_invoices')->onDelete('cascade');
        });

        DB::statement("ALTER TABLE fee_invoices DROP CHECK chk_fi_setup_or_admission_xor");

        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropForeign('fk_fi_admission_fee_structure');
            $table->dropForeign('fk_fi_branch');
            $table->dropForeign('fk_fi_session_year');
            $table->dropUnique('uq_fi_student_admission_scope');
            $table->dropUnique('uq_fi_invoice_composite');
            $table->dropColumn([
                'admission_active_scope', 'admission_fee_structure_id',
                'branch_id', 'session_year_id', 'branch_name_snapshot'
            ]);

            $table->dropForeign('fee_invoices_fee_setup_id_foreign');
            $table->unsignedBigInteger('fee_setup_id')->nullable(false)->change();
            $table->foreign('fee_setup_id', 'fee_invoices_fee_setup_id_foreign')
                  ->references('id')->on('fee_setups')->onDelete('cascade');
        });
    }

    /**
     * Portable non-MySQL path (SQLite PHPUnit :memory: RefreshDatabase compatibility).
     */
    private function upPortable(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('fee_setup_id')->nullable()->change();
            $table->unsignedBigInteger('admission_fee_structure_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('session_year_id')->nullable();
            $table->string('branch_name_snapshot', 150)->nullable();
            $table->string('admission_active_scope', 100)->nullable();

            $table->unique('admission_active_scope', 'uq_fi_student_admission_scope');
            $table->unique(['id', 'student_id', 'branch_id'], 'uq_fi_invoice_composite');
        });

        if (DB::table('fee_invoices')->exists()) {
            DB::statement("
                UPDATE fee_invoices
                SET branch_id = (SELECT branch_id FROM fee_setups WHERE fee_setups.id = fee_invoices.fee_setup_id),
                    session_year_id = (SELECT session_year_id FROM fee_setups WHERE fee_setups.id = fee_invoices.fee_setup_id)
                WHERE fee_setup_id IS NOT NULL
            ");
        }

        Schema::table('fee_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('status', 20)->default('Active');
            $table->decimal('reversed_amount', 10, 2)->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->string('reversal_reason', 255)->nullable();
            $table->string('reversal_reference', 100)->nullable();

            $table->unique('reversal_reference', 'uq_fp_reversal_reference');
            $table->unique(['id', 'fee_invoice_id', 'student_id', 'branch_id'], 'uq_fp_payment_composite');
        });

        if (DB::table('fee_payments')->exists()) {
            DB::statement("
                UPDATE fee_payments
                SET branch_id = (SELECT branch_id FROM fee_invoices WHERE fee_invoices.id = fee_payments.fee_invoice_id)
                WHERE branch_id IS NULL
            ");
        }
    }

    private function downPortable(): void
    {
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->dropUnique('uq_fp_payment_composite');
            $table->dropUnique('uq_fp_reversal_reference');
            $table->dropColumn([
                'branch_id', 'status', 'reversed_amount', 'reversed_at',
                'reversed_by', 'reversal_reason', 'reversal_reference'
            ]);
        });

        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropUnique('uq_fi_student_admission_scope');
            $table->dropUnique('uq_fi_invoice_composite');
            $table->dropColumn([
                'admission_active_scope', 'admission_fee_structure_id',
                'branch_id', 'session_year_id', 'branch_name_snapshot'
            ]);
            $table->unsignedBigInteger('fee_setup_id')->nullable(false)->change();
        });
    }
};
