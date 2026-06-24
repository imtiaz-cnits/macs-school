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
        Schema::create('fee_invoices', function (Blueprint $table) {
        $table->id();
        $table->string('invoice_no')->unique(); // e.g., INV-2026-0001
        $table->foreignId('student_id')->constrained()->cascadeOnDelete();
        $table->foreignId('fee_setup_id')->constrained()->cascadeOnDelete();
        $table->decimal('amount', 10, 2); // মূল ফি
        $table->decimal('discount', 10, 2)->default(0); // যদি ছাড় দেওয়া হয়
        $table->decimal('net_amount', 10, 2); // amount - discount
        $table->decimal('paid_amount', 10, 2)->default(0); // কত টাকা জমা দিয়েছে
        $table->decimal('due_amount', 10, 2); // কত বকেয়া আছে
        $table->enum('status', ['Unpaid', 'Partial', 'Paid'])->default('Unpaid');
        $table->date('due_date')->nullable(); // জমার শেষ তারিখ
        
        // যে অ্যাডমিন এই ইনভয়েস বা বিলটি জেনারেট করেছে তার আইডি
        $table->foreignId('user_id')->constrained('users');

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_invoices');
    }
};
