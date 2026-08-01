<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Classes;
use App\Models\Shift;
use Illuminate\Support\Facades\DB;

class StudentShiftUpdateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ১. ডাটাবেজ থেকে Morning এবং Day শিফট খুঁজে বের করা
        // নোট: আপনার Shifts টেবিলে নামগুলো যদি ভিন্ন থাকে (যেমন: 'Morning Student'), তবে এখানে তা পরিবর্তন করে নেবেন।
        $morningShift = Shift::where('name', 'Morning Student')->first();
        $dayShift = Shift::where('name', 'Day Student')->first();

        if (!$morningShift || !$dayShift) {
            $this->command->error('Morning or Day shift not found in the shifts table! Please ensure shift names match.');
            return;
        }

        // ২. মর্নিং শিফটের ক্লাসগুলোর নাম (আপনার ডাটাবেজের সঠিক এন্ট্রি অনুযায়ী অ্যারে সাজানো হয়েছে)
        $morningClassNames = ['Play', 'Nursery', '1', '2', '3', 'Class 1', 'Class 2', 'Class 3', 'One', 'Two', 'Three'];

        // ৩. ডে শিফটের ক্লাসগুলোর নাম
        $dayClassNames = ['4', '5', '6', '7', '8', '9', '10', 'Class 4', 'Class 5', 'Class 6', 'Class 7', 'Class 8', 'Class 9', 'Class 10'];

        // ৪. নামগুলো ব্যবহার করে Classes টেবিল থেকে নির্দিষ্ট ক্লাসগুলোর ID বের করা
        $morningClassIds = Classes::whereIn('class_name', $morningClassNames)->pluck('id');
        $dayClassIds = Classes::whereIn('class_name', $dayClassNames)->pluck('id');

        // ৫. ডাটাবেজ ট্রানজেকশনের মাধ্যমে সেফ বাল্ক আপডেট (Mass Update)
        DB::transaction(function () use ($morningClassIds, $morningShift, $dayClassIds, $dayShift) {
            
            // মর্নিং শিফটের স্টুডেন্টদের আপডেট
            $morningUpdated = Student::whereIn('class_id', $morningClassIds)
                                     ->update(['shift_id' => $morningShift->id]);

            // ডে শিফটের স্টুডেন্টদের আপডেট
            $dayUpdated = Student::whereIn('class_id', $dayClassIds)
                                 ->update(['shift_id' => $dayShift->id]);

            // টার্মিনালে সাকসেস মেসেজ দেখানো
            $this->command->info("Successfully updated {$morningUpdated} students to Morning Shift.");
            $this->command->info("Successfully updated {$dayUpdated} students to Day Shift.");
        });
    }
}