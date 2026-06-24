<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
        
            $table->string('student_identity')->unique()->index(); 
            
            // Student Basic Info
            $table->string('admission_number')->nullable();
            $table->string('roll_number');
            $table->date('admission_date')->nullable();
            $table->string('student_name');
            $table->string('name_in_bangla')->nullable();
            $table->string('birth_certificate')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('religion')->nullable();
            
            // Photo & Documents
            $table->string('photo')->nullable();
            $table->string('document_file')->nullable(); 
            
            $table->date('dob');
            $table->string('gender');
            $table->string('email')->nullable();
            
            // Parents & Guardian Info
            $table->string('father_name');
            $table->string('father_nid')->nullable();
            $table->string('father_mobile');
            $table->string('father_occupation')->nullable();
            
            $table->string('mother_name');
            $table->string('mother_nid')->nullable();
            $table->string('mother_mobile');
            $table->string('mother_occupation')->nullable(); 
            
            // Present Address (Structured)
            $table->string('present_village');
            $table->string('present_post_office');
            $table->string('present_district');
            $table->string('present_post_code')->nullable();
            $table->string('present_division');

            // Permanent Address (Structured)
            $table->string('permanent_village');
            $table->string('permanent_post_office');
            $table->string('permanent_district');
            $table->string('permanent_post_code')->nullable();
            $table->string('permanent_division');

            $table->string('guardian_name')->nullable();
            $table->string('guardian_occupation')->nullable();
            $table->string('guardian_mobile');

            // Academic Info & Settings
            $table->string('sms_status')->default('Active');

            // Foreign Keys (Relations)
            $table->unsignedBigInteger('branch_id'); 
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id');
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->unsignedBigInteger('session_year_id');
            $table->unsignedBigInteger('user_id'); 

            // Setting up Foreign Key Constraints
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->restrictOnDelete();
            $table->foreign('section_id')->references('id')->on('sections')->restrictOnDelete();
            $table->foreign('shift_id')->references('id')->on('shifts')->restrictOnDelete();
            $table->foreign('session_year_id')->references('id')->on('session_years')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};