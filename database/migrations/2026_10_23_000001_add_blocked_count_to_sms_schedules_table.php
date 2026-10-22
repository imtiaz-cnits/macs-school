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
        if (Schema::hasTable('sms_schedules') && !Schema::hasColumn('sms_schedules', 'blocked_count')) {
            Schema::table('sms_schedules', function (Blueprint $table) {
                $table->unsignedInteger('blocked_count')->default(0)->after('failed_count');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sms_schedules') && Schema::hasColumn('sms_schedules', 'blocked_count')) {
            Schema::table('sms_schedules', function (Blueprint $table) {
                $table->dropColumn('blocked_count');
            });
        }
    }
};
