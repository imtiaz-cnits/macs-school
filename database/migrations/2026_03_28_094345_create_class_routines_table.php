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
        Schema::create('class_routines', function (Blueprint $table) {
           $table->id();
            
            // Foreign Keys (আপনার বর্তমান টেবিলগুলোর সাথে লিংক করার জন্য)
            $table->unsignedBigInteger('session_year_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id');
            
            // রুটিনের মূল ডাটা
            $table->string('day'); // বারের নাম (যেমন: Saturday, Sunday)
            $table->time('start_time'); // ক্লাস শুরুর সময়
            $table->time('end_time'); // ক্লাস শেষের সময়
            $table->string('room_number')->nullable(); // ক্লাস রুম নাম্বার
            
            $table->timestamps();

            // Relations
            $table->foreign('session_year_id')->references('id')->on('session_years')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('teachers')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_routines');
    }
};
