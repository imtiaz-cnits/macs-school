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
        Schema::create('exam_schedules', function (Blueprint $table) {
            $table->id();
            
            // নতুন যুক্ত করা ব্রাঞ্চ আইডি
            $table->unsignedBigInteger('branch_id');
            
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('subject_id');
            
            // মার্কস ডিস্ট্রিবিউশন
            $table->decimal('full_marks', 5, 2)->default(100);
            $table->decimal('pass_marks', 5, 2)->default(33);
            
            $table->decimal('ct_marks', 5, 2)->default(0);      // Class Test / Tutorial / Assignment
            $table->decimal('written_marks', 5, 2)->default(0); // মূল লিখিত পরীক্ষা
            $table->decimal('mcq_marks', 5, 2)->default(0);     // যদি ক্লাস ৪/৫ এর জন্য থাকে
            
            $table->date('exam_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();

            // (Optional) Foreign Key Constraints - ডাটাবেস সিকিউরিটির জন্য
            // $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            // $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnDelete();
            // $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            // $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_schedules');
    }
};