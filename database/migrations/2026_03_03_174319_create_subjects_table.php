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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('subject_name'); 
            $table->string('subject_code')->nullable(); 
            $table->string('subject_type')->default('Theory'); 
            $table->string('status')->default('Active');
            
            
            $table->unsignedBigInteger('user_id')->nullable(); 
            
            $table->timestamps();

            
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
