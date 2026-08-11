<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Student;

$updates = [
    ['student_id' => '26010P00040', 'card_number' => '71'],
    ['student_id' => '26020400278', 'card_number' => '310'],
    ['student_id' => '23010500425', 'card_number' => '457'],
    ['student_id' => '23010N00195', 'card_number' => '226'],
    ['student_id' => '25120360227', 'card_number' => '258'],
    ['student_id' => '26050100538', 'card_number' => '570'],
];

foreach ($updates as $u) {
    $student = Student::where('student_identity', $u['student_id'])->first();
    if ($student) {
        $student->update(['card_number' => $u['card_number']]);
        echo "OK: {$student->student_name} (ID: {$u['student_id']}) -> card_number: {$u['card_number']}\n";
    } else {
        echo "NOT FOUND: student_id {$u['student_id']}\n";
    }
}

echo "\nDone! Now run: php artisan zkteco:sync-users\n";
