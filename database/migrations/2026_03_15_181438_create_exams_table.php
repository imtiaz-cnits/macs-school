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
        Schema::create('exams', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // Ex: 1st Term Exam 2026, Annual Exam
        $table->unsignedBigInteger('session_year_id');
        $table->unsignedBigInteger('branch_id')->nullable();
        $table->date('start_date')->nullable();
        $table->date('end_date')->nullable();
        $table->enum('status', ['upcoming', 'running', 'completed', 'published'])->default('upcoming');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
