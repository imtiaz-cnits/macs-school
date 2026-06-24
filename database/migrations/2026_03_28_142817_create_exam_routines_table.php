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
        Schema::create('exam_routines', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->unsignedBigInteger('session_year_id');
            $table->unsignedBigInteger('exam_id'); // কোন পরীক্ষা
            $table->unsignedBigInteger('class_id'); // কোন ক্লাস
            $table->unsignedBigInteger('subject_id'); // কোন বিষয়
            
            // রুটিনের ডাটা
            $table->date('exam_date'); // পরীক্ষার তারিখ
            $table->time('start_time'); // শুরুর সময়
            $table->time('end_time'); // শেষের সময়
            $table->string('room_number')->nullable(); // রুম নাম্বার (ঐচ্ছিক)
            
            $table->timestamps();

            // Relations
            $table->foreign('session_year_id')->references('id')->on('session_years')->cascadeOnDelete();
            $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_routines');
    }
};
