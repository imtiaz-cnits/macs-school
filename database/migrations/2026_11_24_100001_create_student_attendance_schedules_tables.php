<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_attendance_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('mode', 20)->default('normal'); // 'normal' | 'exam'
            $table->boolean('is_default')->default(false);
            $table->boolean('is_template')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedBigInteger('exam_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedTinyInteger('priority')->default(10);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('exam_id', 'fk_sas_exam')
                ->references('id')->on('exams')->nullOnDelete();
            $table->foreign('branch_id', 'fk_sas_branch')
                ->references('id')->on('branches')->nullOnDelete();
            $table->foreign('created_by', 'fk_sas_created_by')
                ->references('id')->on('users')->nullOnDelete();

            $table->index(['mode', 'is_active', 'is_template', 'start_date', 'end_date'], 'idx_sas_lookup');
            $table->index(['branch_id', 'mode', 'is_active'], 'idx_sas_branch_mode');
        });

        Schema::create('student_attendance_schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->string('slot_name', 120);
            $table->string('shift_group', 20)->default('Morning'); // 'Morning' | 'Day'
            $table->time('on_time_start');
            $table->time('on_time_end');
            $table->time('late_end');
            $table->time('absent_from');
            $table->time('check_out_from');
            $table->unsignedSmallInteger('early_grace_minutes')->default(60);
            $table->unsignedSmallInteger('debounce_minutes')->default(10);
            $table->unsignedSmallInteger('min_checkout_gap_minutes')->default(15);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('schedule_id', 'fk_sass_schedule')
                ->references('id')->on('student_attendance_schedules')->cascadeOnDelete();

            $table->index(['schedule_id', 'sort_order'], 'idx_sass_schedule_sort');
        });

        Schema::create('student_attendance_schedule_slot_classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('slot_id');
            $table->unsignedBigInteger('class_id');
            $table->timestamps();

            $table->foreign('schedule_id', 'fk_sassc_schedule')
                ->references('id')->on('student_attendance_schedules')->cascadeOnDelete();
            $table->foreign('slot_id', 'fk_sassc_slot')
                ->references('id')->on('student_attendance_schedule_slots')->cascadeOnDelete();
            $table->foreign('class_id', 'fk_sassc_class')
                ->references('id')->on('classes')->cascadeOnDelete();

            $table->unique(['schedule_id', 'class_id'], 'uq_sassc_schedule_class');
            $table->index('slot_id', 'idx_sassc_slot');
            $table->index('class_id', 'idx_sassc_class');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendance_schedule_slot_classes');
        Schema::dropIfExists('student_attendance_schedule_slots');
        Schema::dropIfExists('student_attendance_schedules');
    }
};
