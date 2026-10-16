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
        // 1. Add category to expense line items
        Schema::table('expense_items', function (Blueprint $table) {
            $table->foreignId('expense_category_id')
                ->nullable()
                ->after('expense_id')
                ->constrained('expense_categories')
                ->nullOnDelete();

            $table->index('expense_category_id');
        });

        // 2. Make header-level expense_category_id nullable for multi-category vouchers
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('expense_category_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_items', function (Blueprint $table) {
            $table->dropForeign(['expense_category_id']);
            $table->dropColumn('expense_category_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('expense_category_id')->nullable(false)->change();
        });
    }
};
