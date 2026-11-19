<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Disable migration transaction wrapping so SQLite PRAGMA foreign_keys works as intended.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        if (!Schema::hasTable('admission_fee_structures')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->upgradeSqlite();

            return;
        }

        $this->upgradeMysql();
    }

    public function down(): void
    {
        if (!Schema::hasTable('admission_fee_structures')) {
            return;
        }

        $hasAllTimeRows = DB::table('admission_fee_structures')
            ->whereNull('session_year_id')
            ->exists();

        if ($hasAllTimeRows) {
            throw new RuntimeException(
                'Cannot rollback nullable session_year_id while All-Time (NULL session_year_id) Admission Fee Structures exist.'
            );
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->downgradeSqlite();

            return;
        }

        $this->downgradeMysql();
    }

    private function upgradeMysql(): void
    {
        DB::statement('ALTER TABLE admission_fee_structures DROP FOREIGN KEY fk_afs_session_year');

        DB::statement(
            'ALTER TABLE admission_fee_structures
                MODIFY COLUMN session_year_id BIGINT UNSIGNED NULL,
                ADD COLUMN scope_session_key BIGINT UNSIGNED GENERATED ALWAYS AS (COALESCE(session_year_id, 0)) STORED AFTER session_year_id'
        );

        // Add replacement branch-leading unique key BEFORE dropping uq_afs_branch_class_session_ver so fk_afs_branch retains an index.
        DB::statement(
            'ALTER TABLE admission_fee_structures
                ADD UNIQUE KEY uq_afs_branch_class_scope_ver (branch_id, class_id, scope_session_key, version)'
        );

        DB::statement(
            'ALTER TABLE admission_fee_structures
                DROP INDEX uq_afs_branch_class_session_ver,
                DROP INDEX uq_afs_one_active_scope'
        );

        DB::statement(
            'ALTER TABLE admission_fee_structures
                ADD UNIQUE KEY uq_afs_one_active_scope (branch_id, class_id, scope_session_key, active_version),
                ADD CONSTRAINT fk_afs_session_year FOREIGN KEY (session_year_id) REFERENCES session_years(id) ON DELETE RESTRICT'
        );
    }

    private function downgradeMysql(): void
    {
        DB::statement('ALTER TABLE admission_fee_structures DROP FOREIGN KEY fk_afs_session_year');

        DB::statement(
            'ALTER TABLE admission_fee_structures
                ADD UNIQUE KEY uq_afs_branch_class_session_ver (branch_id, class_id, session_year_id, version)'
        );

        DB::statement(
            'ALTER TABLE admission_fee_structures
                DROP INDEX uq_afs_branch_class_scope_ver,
                DROP INDEX uq_afs_one_active_scope'
        );

        DB::statement(
            'ALTER TABLE admission_fee_structures
                DROP COLUMN scope_session_key,
                MODIFY COLUMN session_year_id BIGINT UNSIGNED NOT NULL,
                ADD UNIQUE KEY uq_afs_one_active_scope (branch_id, class_id, session_year_id, active_version),
                ADD CONSTRAINT fk_afs_session_year FOREIGN KEY (session_year_id) REFERENCES session_years(id) ON DELETE RESTRICT'
        );
    }

    private function upgradeSqlite(): void
    {
        $rows = DB::table('admission_fee_structures')->orderBy('id')->get();

        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            Schema::drop('admission_fee_structures');

            DB::statement("
                CREATE TABLE admission_fee_structures (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    branch_id INTEGER NOT NULL,
                    class_id INTEGER NOT NULL,
                    session_year_id INTEGER NULL,
                    scope_session_key INTEGER GENERATED ALWAYS AS (COALESCE(session_year_id, 0)) VIRTUAL,
                    structure_name VARCHAR(150) NOT NULL,
                    version INTEGER NOT NULL DEFAULT 1,
                    status TEXT NOT NULL DEFAULT 'Active' CHECK (status IN ('Active', 'Inactive', 'Archived')),
                    active_version INTEGER GENERATED ALWAYS AS (CASE WHEN status = 'Active' THEN 1 ELSE NULL END) VIRTUAL,
                    total_amount NUMERIC(10, 2) NOT NULL DEFAULT 0.00 CHECK (total_amount >= 0.00),
                    includes_monthly_tuition INTEGER NOT NULL DEFAULT 0 CHECK (includes_monthly_tuition IN (0, 1)),
                    tuition_month_number INTEGER NULL,
                    notes TEXT NULL,
                    created_by INTEGER NOT NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    CONSTRAINT fk_afs_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE RESTRICT,
                    CONSTRAINT fk_afs_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE RESTRICT,
                    CONSTRAINT fk_afs_session_year FOREIGN KEY (session_year_id) REFERENCES session_years(id) ON DELETE RESTRICT,
                    CONSTRAINT fk_afs_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
                    CONSTRAINT chk_afs_tuition_month CHECK (
                        (includes_monthly_tuition = 0 AND tuition_month_number IS NULL)
                        OR (includes_monthly_tuition = 1 AND tuition_month_number BETWEEN 1 AND 12)
                    )
                )
            ");

            DB::statement(
                'CREATE UNIQUE INDEX uq_afs_branch_class_scope_ver ON admission_fee_structures (branch_id, class_id, scope_session_key, version)'
            );
            DB::statement(
                'CREATE UNIQUE INDEX uq_afs_one_active_scope ON admission_fee_structures (branch_id, class_id, scope_session_key, active_version)'
            );
            DB::statement('CREATE INDEX fk_afs_class ON admission_fee_structures (class_id)');
            DB::statement('CREATE INDEX fk_afs_session_year ON admission_fee_structures (session_year_id)');
            DB::statement('CREATE INDEX fk_afs_created_by ON admission_fee_structures (created_by)');

            $this->reinsertSqliteRows($rows);
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function downgradeSqlite(): void
    {
        $rows = DB::table('admission_fee_structures')->orderBy('id')->get();

        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            Schema::drop('admission_fee_structures');

            DB::statement("
                CREATE TABLE admission_fee_structures (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    branch_id INTEGER NOT NULL,
                    class_id INTEGER NOT NULL,
                    session_year_id INTEGER NOT NULL,
                    structure_name VARCHAR(150) NOT NULL,
                    version INTEGER NOT NULL DEFAULT 1,
                    status TEXT NOT NULL DEFAULT 'Active' CHECK (status IN ('Active', 'Inactive', 'Archived')),
                    active_version INTEGER GENERATED ALWAYS AS (CASE WHEN status = 'Active' THEN 1 ELSE NULL END) VIRTUAL,
                    total_amount NUMERIC(10, 2) NOT NULL DEFAULT 0.00 CHECK (total_amount >= 0.00),
                    includes_monthly_tuition INTEGER NOT NULL DEFAULT 0 CHECK (includes_monthly_tuition IN (0, 1)),
                    tuition_month_number INTEGER NULL,
                    notes TEXT NULL,
                    created_by INTEGER NOT NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    CONSTRAINT fk_afs_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE RESTRICT,
                    CONSTRAINT fk_afs_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE RESTRICT,
                    CONSTRAINT fk_afs_session_year FOREIGN KEY (session_year_id) REFERENCES session_years(id) ON DELETE RESTRICT,
                    CONSTRAINT fk_afs_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
                    CONSTRAINT chk_afs_tuition_month CHECK (
                        (includes_monthly_tuition = 0 AND tuition_month_number IS NULL)
                        OR (includes_monthly_tuition = 1 AND tuition_month_number BETWEEN 1 AND 12)
                    )
                )
            ");

            DB::statement(
                'CREATE UNIQUE INDEX uq_afs_branch_class_session_ver ON admission_fee_structures (branch_id, class_id, session_year_id, version)'
            );
            DB::statement(
                'CREATE UNIQUE INDEX uq_afs_one_active_scope ON admission_fee_structures (branch_id, class_id, session_year_id, active_version)'
            );
            DB::statement('CREATE INDEX fk_afs_class ON admission_fee_structures (class_id)');
            DB::statement('CREATE INDEX fk_afs_session_year ON admission_fee_structures (session_year_id)');
            DB::statement('CREATE INDEX fk_afs_created_by ON admission_fee_structures (created_by)');

            $this->reinsertSqliteRows($rows);
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function reinsertSqliteRows(iterable $rows): void
    {
        foreach ($rows as $row) {
            DB::table('admission_fee_structures')->insert([
                'id' => $row->id,
                'branch_id' => $row->branch_id,
                'class_id' => $row->class_id,
                'session_year_id' => $row->session_year_id,
                'structure_name' => $row->structure_name,
                'version' => $row->version,
                'status' => $row->status,
                'total_amount' => $row->total_amount,
                'includes_monthly_tuition' => $row->includes_monthly_tuition,
                'tuition_month_number' => $row->tuition_month_number,
                'notes' => $row->notes,
                'created_by' => $row->created_by,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }
    }
};
