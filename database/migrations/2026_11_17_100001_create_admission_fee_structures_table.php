<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isMySql = DB::getDriverName() === 'mysql';

        Schema::create('admission_fee_structures', function (Blueprint $table) use ($isMySql) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('session_year_id');
            $table->string('structure_name', 150);
            $table->unsignedInteger('version')->default(1);
            $table->enum('status', ['Active', 'Inactive', 'Archived'])->default('Active');
            if (!$isMySql) {
                $table->unsignedTinyInteger('active_version')
                      ->nullable()
                      ->virtualAs("CASE WHEN status = 'Active' THEN 1 ELSE NULL END");
            }
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->boolean('includes_monthly_tuition')->default(false);
            $table->unsignedTinyInteger('tuition_month_number')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->unique(['branch_id', 'class_id', 'session_year_id', 'version'], 'uq_afs_branch_class_session_ver');
            if (!$isMySql) {
                $table->unique(['branch_id', 'class_id', 'session_year_id', 'active_version'], 'uq_afs_one_active_scope');
            }

            $table->foreign('branch_id', 'fk_afs_branch')
                  ->references('id')->on('branches')->onDelete('restrict');
            $table->foreign('class_id', 'fk_afs_class')
                  ->references('id')->on('classes')->onDelete('restrict');
            $table->foreign('session_year_id', 'fk_afs_session_year')
                  ->references('id')->on('session_years')->onDelete('restrict');
            $table->foreign('created_by', 'fk_afs_created_by')
                  ->references('id')->on('users')->onDelete('restrict');
        });

        if ($isMySql) {
            DB::statement("
                ALTER TABLE admission_fee_structures
                ADD COLUMN active_version TINYINT UNSIGNED GENERATED ALWAYS AS (
                    CASE WHEN status = 'Active' THEN 1 ELSE NULL END
                ) STORED AFTER status,
                ADD UNIQUE KEY uq_afs_one_active_scope (branch_id, class_id, session_year_id, active_version),
                ADD CONSTRAINT chk_afs_total_non_neg CHECK (total_amount >= 0.00),
                ADD CONSTRAINT chk_afs_tuition_month CHECK (
                    (includes_monthly_tuition = 0 AND tuition_month_number IS NULL)
                    OR
                    (includes_monthly_tuition = 1 AND tuition_month_number BETWEEN 1 AND 12)
                )
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admission_fee_structure_items') && DB::table('admission_fee_structure_items')->exists()) {
            throw new \RuntimeException("ROLLBACK ABORTED (Migration 1): Child rows exist in 'admission_fee_structure_items'.");
        }
        if (Schema::hasColumn('fee_invoices', 'admission_fee_structure_id') && DB::table('fee_invoices')->whereNotNull('admission_fee_structure_id')->exists()) {
            throw new \RuntimeException("ROLLBACK ABORTED (Migration 1): Invoices reference 'admission_fee_structures'.");
        }
        if (Schema::hasTable('admission_fee_structures') && DB::table('admission_fee_structures')->exists()) {
            throw new \RuntimeException("ROLLBACK ABORTED (Migration 1): Configured structures exist in 'admission_fee_structures'. Manual truncation required.");
        }
        Schema::dropIfExists('admission_fee_structures');
    }
};
