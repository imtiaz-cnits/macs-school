<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_fee_structure_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admission_fee_structure_id');
            $table->unsignedBigInteger('fee_category_id');
            $table->string('head_name', 150);
            $table->decimal('amount', 10, 2);
            $table->boolean('is_discountable')->default(true);
            $table->boolean('is_tuition_head')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['admission_fee_structure_id', 'fee_category_id', 'head_name'], 'uq_afsi_struct_cat_head');
            $table->index(['admission_fee_structure_id', 'sort_order'], 'idx_afsi_struct_sort');

            $table->foreign('admission_fee_structure_id', 'fk_afsi_structure')
                  ->references('id')->on('admission_fee_structures')->onDelete('restrict');
            $table->foreign('fee_category_id', 'fk_afsi_category')
                  ->references('id')->on('fee_categories')->onDelete('restrict');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE admission_fee_structure_items
                ADD CONSTRAINT chk_afsi_amount_non_neg CHECK (amount >= 0.00)
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admission_invoice_head_allocations') && DB::table('admission_invoice_head_allocations')->exists()) {
            throw new \RuntimeException("ROLLBACK ABORTED (Migration 2): Allocations reference 'admission_fee_structure_items'.");
        }
        if (Schema::hasTable('admission_fee_structure_items') && DB::table('admission_fee_structure_items')->exists()) {
            throw new \RuntimeException("ROLLBACK ABORTED (Migration 2): Configured fee head items exist in 'admission_fee_structure_items'.");
        }
        Schema::dropIfExists('admission_fee_structure_items');
    }
};
