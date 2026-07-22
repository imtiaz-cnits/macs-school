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
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('status')->default('Present')->change();
        });

        Schema::table('staff_attendances', function (Blueprint $table) {
            $table->string('status')->default('Present')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_attendances', function (Blueprint $table) {
            $table->enum('status', ['Present', 'Late', 'Absent'])->default('Present')->change();
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->enum('status', ['Present', 'Absent', 'Late'])->default('Present')->change();
        });
    }
};
