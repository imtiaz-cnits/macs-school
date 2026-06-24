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
       Schema::create('marks', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('session_year_id'); // নতুন অ্যাড হলো
        $table->unsignedBigInteger('branch_id');       // নতুন অ্যাড হলো
        
        $table->unsignedBigInteger('exam_id');
        $table->unsignedBigInteger('class_id');
        $table->unsignedBigInteger('section_id')->nullable();
        $table->unsignedBigInteger('subject_id');
        $table->unsignedBigInteger('student_id');
        
        // প্রাপ্ত নম্বর (কিন্ডারগার্টেন স্ট্যান্ডার্ড)
        $table->decimal('ct_mark', 5, 2)->default(0);      // টিউটোরিয়াল প্রাপ্ত নম্বর
        $table->decimal('written_mark', 5, 2)->default(0); // লিখিত প্রাপ্ত নম্বর
        $table->decimal('mcq_mark', 5, 2)->default(0);     // নৈর্ব্যক্তিক (থাকলে)
        $table->decimal('total_mark', 5, 2)->default(0);   // মোট নম্বর
        
        // গ্রেডিং ইনফো
        $table->string('letter_grade', 5)->nullable();      // Ex: A+ 
        $table->decimal('grade_point', 3, 2)->default(0);   // Ex: 5.00
        
        $table->boolean('is_absent')->default(false); // অনুপস্থিতি
        $table->timestamps();
        
        // ডুপ্লিকেট এন্ট্রি ব্লক করার জন্য (session_year_id যুক্ত করা হলো)
        $table->unique(['session_year_id', 'exam_id', 'class_id', 'subject_id', 'student_id'], 'unique_student_mark');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marks');
    }
};
