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
        Schema::create('fee_categories', function (Blueprint $table) {
        $table->id();
        $table->string('name')->unique(); // e.g., Tuition Fee, Admission Fee
        $table->text('description')->nullable();
        
        // যে অ্যাডমিন বা ইউজার এটি তৈরি করেছে তার আইডি
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
        Schema::dropIfExists('fee_categories');
    }
};
