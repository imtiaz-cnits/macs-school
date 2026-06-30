<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Branch;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Shift;
use App\Models\SessionYear;

class PromotionTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        if (!$user) {
            $user = User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        // Create Current/Default Academic Setup if not exists
        $branch = Branch::where('branch_name', 'Main Branch')->first() ?? Branch::create(['branch_name' => 'Main Branch', 'user_id' => $user->id]);
        $classOne = Classes::where('class_name', 'Class One')->first() ?? Classes::create(['class_name' => 'Class One', 'user_id' => $user->id]);
        $sectionA = Section::where('section_name', 'Section A')->first() ?? Section::create(['section_name' => 'Section A', 'user_id' => $user->id]);
        $shiftMorning = Shift::where('shift_name', 'Morning Shift')->first() ?? Shift::create(['shift_name' => 'Morning Shift', 'user_id' => $user->id]);
        $session2026 = SessionYear::where('session_name', '2026')->first() ?? SessionYear::create(['session_name' => '2026', 'user_id' => $user->id]);

        // Create Next/Target Academic Setup for Promotion Testing
        $branchNext = Branch::where('branch_name', 'Second Branch')->first() ?? Branch::create(['branch_name' => 'Second Branch', 'user_id' => $user->id]);
        $classTwo = Classes::where('class_name', 'Class Two')->first() ?? Classes::create(['class_name' => 'Class Two', 'user_id' => $user->id]);
        $sectionB = Section::where('section_name', 'Section B')->first() ?? Section::create(['section_name' => 'Section B', 'user_id' => $user->id]);
        $shiftDay = Shift::where('shift_name', 'Day Shift')->first() ?? Shift::create(['shift_name' => 'Day Shift', 'user_id' => $user->id]);
        $session2027 = SessionYear::where('session_name', '2027')->first() ?? SessionYear::create(['session_name' => '2027', 'user_id' => $user->id]);
    }
}
