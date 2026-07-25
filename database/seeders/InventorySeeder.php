<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InventoryItem;
use App\Models\InventoryLog;
use Carbon\Carbon;

use Illuminate\Support\Facades\Schema;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing records to allow clean re-run
        Schema::disableForeignKeyConstraints();
        InventoryLog::truncate();
        InventoryItem::truncate();
        Schema::enableForeignKeyConstraints();

        $today = Carbon::today('Asia/Dhaka')->format('Y-m-d');

        $items = [
            // Assets
            [
                'name' => 'Ceiling Fan (Orient 56")',
                'type' => 'asset',
                'class_id' => null,
                'description' => 'Orient 56 inch Ceiling Fan for classrooms',
                'current_quantity' => 24,
                'unit' => 'pcs',
                'initial_remarks' => 'Initial inventory setup for active classrooms'
            ],
            [
                'name' => 'Student Wooden Bench',
                'type' => 'asset',
                'class_id' => null,
                'description' => 'Standard high/low benches for student seating',
                'current_quantity' => 120,
                'unit' => 'pcs',
                'initial_remarks' => 'Counted from main inventory register'
            ],
            [
                'name' => 'LED Tube Light 20W',
                'type' => 'asset',
                'class_id' => null,
                'description' => 'Super Star energy efficient tube lights',
                'current_quantity' => 35,
                'unit' => 'pcs',
                'initial_remarks' => 'Setup count for active lighting fixtures'
            ],
            [
                'name' => 'Teacher Chair (Plastic Cushion)',
                'type' => 'asset',
                'class_id' => null,
                'description' => 'RFL plastic cushion chairs for teachers',
                'current_quantity' => 15,
                'unit' => 'pcs',
                'initial_remarks' => 'Purchased for staff room usage'
            ],

            // Stationery
            [
                'name' => 'A4 Offset Paper 80GSM',
                'type' => 'stationery',
                'class_id' => null,
                'description' => 'Double A brand offset white paper reams',
                'current_quantity' => 45,
                'unit' => 'ream',
                'initial_remarks' => 'Procured for exam sheet printing'
            ],
            [
                'name' => 'Whiteboard Marker (Black)',
                'type' => 'stationery',
                'class_id' => null,
                'description' => 'Deli dry-erase whiteboard marker pens',
                'current_quantity' => 24,
                'unit' => 'pcs',
                'initial_remarks' => 'Distributed to staff rooms'
            ],
            [
                'name' => 'Official Register Ledger Book',
                'type' => 'stationery',
                'class_id' => null,
                'description' => '200-page official accounting ledger book',
                'current_quantity' => 8,
                'unit' => 'pcs',
                'initial_remarks' => 'Initial bookkeeping stock setup'
            ],

            // Books
            [
                'name' => 'Class 10 NCTB English Textbook',
                'type' => 'book',
                'class_id' => 12, // Ten
                'description' => 'NCTB English for Today textbook for Class 10',
                'current_quantity' => 80,
                'unit' => 'copies',
                'initial_remarks' => 'Received from education board warehouse'
            ],
            [
                'name' => 'Class 9 NCTB General Math Textbook',
                'type' => 'book',
                'class_id' => 11, // Nine
                'description' => 'NCTB General Mathematics textbook for Class 9',
                'current_quantity' => 65,
                'unit' => 'copies',
                'initial_remarks' => 'Board books stock allotment'
            ],
            [
                'name' => 'Arabian Nights Storybook Collection',
                'type' => 'book',
                'class_id' => null, // Storybook / general library
                'description' => 'Storybooks collection for general school library',
                'current_quantity' => 12,
                'unit' => 'copies',
                'initial_remarks' => 'Purchased from book fair for library'
            ]
        ];

        foreach ($items as $itemData) {
            $initialQty = $itemData['current_quantity'];
            $remarks = $itemData['initial_remarks'];
            unset($itemData['initial_remarks']);

            // Create item
            $item = InventoryItem::create($itemData);

            // Create transaction log
            InventoryLog::create([
                'inventory_item_id' => $item->id,
                'user_id' => 1, // Admin / first user
                'type' => 'in',
                'quantity' => $initialQty,
                'date' => $today,
                'remarks' => $remarks
            ]);
        }
    }
}
