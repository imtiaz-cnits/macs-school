<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Classes;
use Illuminate\Support\Facades\DB;

class MoveStudentsToNurserySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // আপনার দেওয়া স্পেসিফিক স্টুডেন্ট আইডিগুলোর তালিকা
        $studentIdentities = [
            '24120P00057', '24120P00058', '25010P00059', '25010P00060',
            '24120P00061', '24120P00062', '24120P00063', '24120P00064',
            '25010P00065', '24120P00066', '25010P00067', '24110P00068',
            '25010P00069', '25010P00070', '25010P00071', '25120N00072',
            '25120N00073', '25120N00074', '25120N00075', '25120N00076',
            '26010N00077', '26010N00078', '26010N00079', '26010N00080',
            '26010N00081', '26010N00082', '26010N00083', '26010N00084',
            '26010N00085', '26010N00086', '26010N00087', '26010N00088',
            '26010N00089', '26010N00090', '26010N00091'
        ];

        // ডাটাবেজ থেকে Nursery ক্লাসের ID খুঁজে বের করা
        $nurseryClass = Classes::where('class_name', 'Nursery')->first();

        if (!$nurseryClass) {
            $this->command->error("Nursery class not found in the classes table! Please check the exact class name.");
            return;
        }

        // ডাটাবেজ ট্রানজেকশনের মাধ্যমে সেফ বাল্ক আপডেট
        DB::transaction(function () use ($studentIdentities, $nurseryClass) {
            $updatedCount = Student::whereIn('student_identity', $studentIdentities)
                                   ->update([
                                       'class_id' => $nurseryClass->id
                                   ]);

            $this->command->info("Success: {$updatedCount} students have been successfully moved to Nursery.");
        });
    }
}