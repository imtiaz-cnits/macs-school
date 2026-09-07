<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ClassTenMarksSeeder extends Seeder
{
    /**
     * Seed Class Ten 2nd Term marks and schedules.
     */
    public function run(): void
    {
        $sqlPath = database_path('seeders/class_ten_marks_dump.sql');
        if (File::exists($sqlPath)) {
            $sql = File::get($sqlPath);
            DB::unprepared($sql);
            $this->command->info('Class Ten 2nd Term marks imported successfully.');
        } else {
            $this->command->error('class_ten_marks_dump.sql not found at ' . $sqlPath);
        }
    }
}
