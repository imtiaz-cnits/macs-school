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
        Schema::create('grades', function (Blueprint $table) {
        $table->id();
        $table->string('grade_name'); // Ex: A+, A, A-
        $table->decimal('grade_point', 3, 2); // Ex: 5.00, 4.00
        $table->integer('min_mark'); // Ex: 80
        $table->integer('max_mark'); // Ex: 100
        $table->string('remarks')->nullable(); // Ex: Outstanding, Very Good
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
