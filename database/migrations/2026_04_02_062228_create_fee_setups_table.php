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
        Schema::create('fee_setups', function (Blueprint $table) {
        $table->id();
        $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
        $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
        $table->foreignId('session_year_id')->constrained()->cascadeOnDelete();
        $table->foreignId('fee_category_id')->constrained()->cascadeOnDelete();
        $table->decimal('amount', 10, 2); // ফি এর পরিমাণ
        $table->string('fee_month')->nullable(); // যদি মাসিক ফি হয় (e.g., January)
        
        $table->foreignId('user_id')->constrained('users'); 
        
        $table->enum('status', ['Active', 'Inactive'])->default('Active');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_setups');
    }
};
