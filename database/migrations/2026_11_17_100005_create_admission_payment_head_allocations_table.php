<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_payment_head_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fee_payment_id');
            $table->unsignedBigInteger('fee_invoice_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('admission_invoice_head_allocation_id');
            $table->unsignedBigInteger('fee_category_id');
            $table->string('fee_category_name_snapshot', 150);
            $table->string('head_name_snapshot', 150);
            $table->decimal('allocated_paid_amount', 10, 2);
            $table->enum('status', ['Active', 'Reversed'])->default('Active');
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversal_reference', 100)->nullable();
            $table->timestamps();

            $table->index(['admission_invoice_head_allocation_id', 'status'], 'idx_apha_head_status');
            $table->index(['fee_payment_id', 'status'], 'idx_apha_payment_status');

            $table->foreign(['fee_payment_id', 'fee_invoice_id', 'student_id', 'branch_id'], 'fk_apha_payment_4col')
                  ->references(['id', 'fee_invoice_id', 'student_id', 'branch_id'])->on('fee_payments')->onDelete('restrict');
            $table->foreign(['admission_invoice_head_allocation_id', 'fee_invoice_id', 'student_id', 'branch_id'], 'fk_apha_head_4col')
                  ->references(['id', 'fee_invoice_id', 'student_id', 'branch_id'])->on('admission_invoice_head_allocations')->onDelete('restrict');
            $table->foreign('fee_category_id', 'fk_apha_category')
                  ->references('id')->on('fee_categories')->onDelete('restrict');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE admission_payment_head_allocations
                ADD CONSTRAINT chk_apha_positive CHECK (allocated_paid_amount > 0.00),
                ADD CONSTRAINT chk_apha_reversal_sync CHECK (
                    (status = 'Active' AND reversed_at IS NULL AND reversal_reference IS NULL)
                    OR
                    (status = 'Reversed' AND reversed_at IS NOT NULL AND reversal_reference IS NOT NULL)
                )
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admission_payment_head_allocations') && DB::table('admission_payment_head_allocations')->exists()) {
            throw new \RuntimeException("ROLLBACK ABORTED (Migration 5): Historical payment allocations exist in 'admission_payment_head_allocations'.");
        }
        Schema::dropIfExists('admission_payment_head_allocations');
    }
};
