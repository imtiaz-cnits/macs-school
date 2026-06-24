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
        Schema::create('teachers', function (Blueprint $table) {
           $table->id();
            
            // এই আইডি দিয়ে শিক্ষক সিস্টেমে লগইন করবেন (users টেবিলের সাথে লিংক)
            $table->unsignedBigInteger('user_id')->unique(); 
            
            // শিক্ষকের বিস্তারিত তথ্য
            $table->string('employee_id')->unique(); // যেমন: TEA-2024-001
            $table->string('designation'); // যেমন: Senior Teacher, Assistant Teacher
            $table->string('department')->nullable(); // যেমন: Science, Arts, English
            $table->string('phone');
            $table->text('address');
            $table->string('gender');
            $table->string('blood_group')->nullable();
            $table->string('photo')->nullable();
            $table->date('joining_date');
            
            // কোন অ্যাডমিন এই শিক্ষককে সিস্টেমে অ্যাড করেছেন তার রেকর্ড
            $table->unsignedBigInteger('created_by');

            // Foreign Key Relations
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
