<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_invoice_head_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fee_invoice_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('admission_fee_structure_item_id');
            $table->unsignedBigInteger('fee_category_id');
            $table->string('fee_category_name_snapshot', 150);
            $table->string('head_name_snapshot', 150);
            $table->string('branch_name_snapshot', 150);
            $table->decimal('amount', 10, 2);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('net_amount', 10, 2);
            $table->decimal('allocated_paid_amount', 10, 2)->default(0.00);
            $table->decimal('due_amount', 10, 2);
            $table->boolean('is_discountable')->default(true);
            $table->boolean('is_tuition_head')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['id', 'fee_invoice_id', 'student_id', 'branch_id'], 'uq_aiha_composite');
            $table->unique(['fee_invoice_id', 'admission_fee_structure_item_id'], 'uq_aiha_invoice_item');

            $table->foreign(['fee_invoice_id', 'student_id', 'branch_id'], 'fk_aiha_invoice_composite')
                  ->references(['id', 'student_id', 'branch_id'])->on('fee_invoices')->onDelete('restrict');
            $table->foreign('admission_fee_structure_item_id', 'fk_aiha_structure_item')
                  ->references('id')->on('admission_fee_structure_items')->onDelete('restrict');
            $table->foreign('fee_category_id', 'fk_aiha_category')
                  ->references('id')->on('fee_categories')->onDelete('restrict');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE admission_invoice_head_allocations
                ADD CONSTRAINT chk_aiha_math CHECK (net_amount = (amount - discount) AND due_amount = (net_amount - allocated_paid_amount)),
                ADD CONSTRAINT chk_aiha_bounds CHECK (amount >= 0.00 AND discount >= 0.00 AND discount <= amount AND allocated_paid_amount >= 0.00 AND allocated_paid_amount <= net_amount),
                ADD CONSTRAINT chk_aiha_nondisc_zero CHECK (is_discountable = 1 OR discount = 0.00)
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admission_payment_head_allocations') && DB::table('admission_payment_head_allocations')->exists()) {
            throw new \RuntimeException("ROLLBACK ABORTED (Migration 4): Child rows exist in 'admission_payment_head_allocations'.");
        }
        if (Schema::hasTable('admission_invoice_head_allocations') && DB::table('admission_invoice_head_allocations')->exists()) {
            throw new \RuntimeException("ROLLBACK ABORTED (Migration 4): Historical head allocations exist in 'admission_invoice_head_allocations'.");
        }
        Schema::dropIfExists('admission_invoice_head_allocations');
    }
};
