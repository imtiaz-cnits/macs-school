<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add discontinuation metadata columns to students table
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'discontinued_month')) {
                $table->string('discontinued_month', 20)->nullable()->index()->after('status');
            }
            if (!Schema::hasColumn('students', 'inactive_at')) {
                $table->timestamp('inactive_at')->nullable()->after('discontinued_month');
            }
            if (!Schema::hasColumn('students', 'inactive_reason')) {
                $table->string('inactive_reason', 255)->nullable()->after('inactive_at');
            }
        });

        // 2. Allow 'Cancelled' status in fee_invoices table
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE fee_invoices MODIFY COLUMN status ENUM('Unpaid', 'Partial', 'Paid', 'Cancelled') NOT NULL DEFAULT 'Unpaid'");
        } else {
            Schema::table('fee_invoices', function (Blueprint $table) {
                $table->string('status', 20)->default('Unpaid')->change();
            });
        }

        // 3. Create student_status_logs table for complete audit trail
        if (!Schema::hasTable('student_status_logs')) {
            Schema::create('student_status_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('previous_status', 20);
                $table->string('new_status', 20);
                $table->string('action_type', 30); // 'discontinuation', 'reactivation', 'toggle'
                $table->string('last_active_month', 20)->nullable();
                $table->date('effective_date')->nullable();
                $table->string('reason', 255)->nullable();
                $table->json('reconciled_invoice_ids')->nullable();
                $table->decimal('reconciled_amount', 10, 2)->default(0.00);
                $table->json('skipped_invoice_ids')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Guard against rollback if Cancelled invoices exist to protect financial history
        if (Schema::hasTable('fee_invoices')) {
            $cancelledCount = DB::table('fee_invoices')->where('status', 'Cancelled')->count();
            if ($cancelledCount > 0) {
                throw new \RuntimeException("Cannot rollback migration: {$cancelledCount} invoice(s) currently have 'Cancelled' status. Rollback aborted to protect financial history and avoid data truncation.");
            }
        }

        Schema::dropIfExists('student_status_logs');

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE fee_invoices MODIFY COLUMN status ENUM('Unpaid', 'Partial', 'Paid') NOT NULL DEFAULT 'Unpaid'");
        }

        Schema::table('students', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('students', 'inactive_reason')) $cols[] = 'inactive_reason';
            if (Schema::hasColumn('students', 'inactive_at')) $cols[] = 'inactive_at';
            if (Schema::hasColumn('students', 'discontinued_month')) $cols[] = 'discontinued_month';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
