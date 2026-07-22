<?php

namespace Database\Seeders;

use App\Models\Shift;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first() ?? User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $shifts = [
            [
                'name' => 'Morning Student',
                'shift_name' => 'Morning Student',
                'type' => 'student',
                'in_time_start' => '09:00:00',
                'in_time_end' => '09:30:00',
                'late_time_end' => '09:40:00',
                'absent_time' => '09:41:00',
                'out_time' => '11:50:00',
                'user_id' => $admin->id,
            ],
            [
                'name' => 'Day Student',
                'shift_name' => 'Day Student',
                'type' => 'student',
                'in_time_start' => '11:30:00',
                'in_time_end' => '12:00:00',
                'late_time_end' => '12:05:00',
                'absent_time' => '12:06:00',
                'out_time' => '16:00:00',
                'user_id' => $admin->id,
            ],
            [
                'name' => 'Morning Staff',
                'shift_name' => 'Morning Staff',
                'type' => 'staff',
                'in_time_start' => '07:30:00',
                'in_time_end' => '08:30:00',
                'late_time_end' => '09:00:00',
                'absent_time' => '09:01:00',
                'out_time' => '12:00:00',
                'user_id' => $admin->id,
            ],
            [
                'name' => 'Day Staff',
                'shift_name' => 'Day Staff',
                'type' => 'staff',
                'in_time_start' => '11:30:00',
                'in_time_end' => '12:00:00',
                'late_time_end' => '12:30:00',
                'absent_time' => '12:31:00',
                'out_time' => '16:00:00',
                'user_id' => $admin->id,
            ]
        ];

        $createdShifts = [];
        foreach ($shifts as $s) {
            $createdShifts[$s['name']] = Shift::updateOrCreate(
                ['name' => $s['name']],
                $s
            );
        }

        // Migrate existing students from any old "Day Shift" or empty shifts to the new "Day Student" shift
        $dayStudentShift = $createdShifts['Day Student'];
        Student::whereNull('shift_id')
            ->orWhereIn('shift_id', function ($query) {
                $query->select('id')->from('shifts')
                    ->whereNull('name')
                    ->orWhereNotIn('name', ['Morning Student', 'Day Student', 'Morning Staff', 'Day Staff']);
            })
            ->update(['shift_id' => $dayStudentShift->id]);

        // Clean up old shifts
        Shift::whereNull('name')
            ->orWhereNotIn('name', ['Morning Student', 'Day Student', 'Morning Staff', 'Day Staff'])
            ->delete();
    }
}
