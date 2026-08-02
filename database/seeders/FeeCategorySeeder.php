<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeeCategory;
use Illuminate\Support\Facades\DB;

class FeeCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // আপনার রিকোয়ারমেন্ট অনুযায়ী নতুন ফি ক্যাটাগরির তালিকা
        $categories = [
            'Monthly Fee',
            'Monthly Exam Fee',
            'Term Exam Fee',
            'Admission Fee',
            'Session Fee',
            'Registration Fee',
            'Fine',
            'Others',
            'Form Fill up Fee'
        ];

        // ডাটাবেজ ট্রানজেকশনের মাধ্যমে সেফ ইনসার্ট (Safe Insert)
        DB::transaction(function () use ($categories) {
            
            // CLI মোডে চালানোর জন্য ডিফল্ট সুপার অ্যাডমিন আইডি (1) সেট করা হলো
            $adminId = 1; 

            foreach ($categories as $category) {
                FeeCategory::firstOrCreate(
                    ['name' => $category], // চেক করবে এই নামে কোনো ক্যাটাগরি ইতোমধ্যে আছে কি না
                    [
                        'status'  => 'Active',
                        'user_id' => $adminId // 1364 Error ফিক্স করার জন্য কলামটি যুক্ত করা হলো
                    ] 
                );
            }
            
            $this->command->info('Fee Categories with User ID seeded successfully!');
        });
    }
}