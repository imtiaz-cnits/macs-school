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
        Schema::create('attendances', function (Blueprint $table) {
        $table->id();
    
        // ফরেন কি রিলেশনশিপসমূহ
        $table->unsignedBigInteger('student_id');
        $table->unsignedBigInteger('branch_id')->nullable();      // ব্রাঞ্চ আইডি
        $table->unsignedBigInteger('session_year_id')->nullable(); // সেশন আইডি
        $table->unsignedBigInteger('class_id');
        $table->unsignedBigInteger('section_id')->nullable();     // সেকশন আইডি
        $table->unsignedBigInteger('teacher_id')->nullable();     // ক্লাস গ্রহণকারী শিক্ষক [নতুন]
        $table->unsignedBigInteger('user_id');                    // ডাটা এন্ট্রি অপারেটর/অ্যাডমিন

        // হাজিরার তথ্য
        $table->date('attendance_date');
        $table->enum('status', ['Present', 'Absent', 'Late'])->default('Present');
        $table->string('remarks')->nullable();

        // ফরেন কি কনস্ট্রেইনসমূহ
        $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
        $table->foreign('session_year_id')->references('id')->on('session_years')->cascadeOnDelete();
        $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
        $table->foreign('section_id')->references('id')->on('sections')->cascadeOnDelete();
        $table->foreign('teacher_id')->references('id')->on('teachers')->nullOnDelete(); // শিক্ষক ডিলিট হলেও হাজিরা রেকর্ড থাকবে
        $table->foreign('user_id')->references('id')->on('users')->cascadeOnUpdate()->restrictOnDelete();

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
