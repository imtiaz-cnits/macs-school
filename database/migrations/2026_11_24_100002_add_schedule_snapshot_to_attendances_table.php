<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'schedule_mode')) {
                $table->string('schedule_mode', 20)->nullable()->after('status');
            }
            if (!Schema::hasColumn('attendances', 'schedule_slot_id')) {
                $table->unsignedBigInteger('schedule_slot_id')->nullable()->after('schedule_mode');
                $table->foreign('schedule_slot_id', 'fk_att_schedule_slot')
                    ->references('id')->on('student_attendance_schedule_slots')->nullOnDelete();
            }
            if (!Schema::hasColumn('attendances', 'schedule_slot_name')) {
                $table->string('schedule_slot_name', 120)->nullable()->after('schedule_slot_id');
            }

            $table->index(['schedule_mode', 'attendance_date'], 'idx_att_schedule_mode');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_att_schedule_mode');

            if (Schema::hasColumn('attendances', 'schedule_slot_id')) {
                $table->dropForeign('fk_att_schedule_slot');
            }

            $dropCols = array_values(array_filter(
                ['schedule_mode', 'schedule_slot_id', 'schedule_slot_name'],
                static fn (string $col): bool => Schema::hasColumn('attendances', $col)
            ));

            if (!empty($dropCols)) {
                $table->dropColumn($dropCols);
            }
        });
    }
};
