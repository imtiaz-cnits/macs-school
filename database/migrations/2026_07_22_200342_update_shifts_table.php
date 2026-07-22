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
        Schema::table('shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('shifts', 'name')) {
                $table->string('name')->after('id')->nullable();
            }
            $table->enum('type', ['student', 'staff'])->after('name')->default('student');
            $table->time('in_time_start')->after('type')->nullable();
            $table->time('in_time_end')->after('in_time_start')->nullable();
            $table->time('late_time_end')->after('in_time_end')->nullable();
            $table->time('absent_time')->after('late_time_end')->nullable();
            $table->time('out_time')->after('absent_time')->nullable();
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->after('created_by')->nullable();
            $table->foreign('shift_id')->references('id')->on('shifts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn('shift_id');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['name', 'type', 'in_time_start', 'in_time_end', 'late_time_end', 'absent_time', 'out_time']);
        });
    }
};
