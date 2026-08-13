<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Student;
use App\Services\ZktecoService;

$updates = [
    ['sid' => '26010P00040', 'card' => '71'],
    ['sid' => '26020400278', 'card' => '310'],
    ['sid' => '23010500425', 'card' => '457'],
    ['sid' => '23010N00195', 'card' => '226'],
    ['sid' => '25120300227', 'card' => '258'],
    ['sid' => '26050100538', 'card' => '570'],
];

echo "=== Step 1: Updating card_number in DB ===\n";
foreach ($updates as $u) {
    $student = Student::where('student_identity', $u['sid'])->first();
    if ($student) {
        $student->update(['card_number' => $u['card']]);
        echo "OK: {$student->student_name} ({$u['sid']}) -> card: {$u['card']}\n";
    } else {
        echo "NOT FOUND: {$u['sid']}\n";
    }
}

echo "\n=== Step 2: Uploading ONLY these 6 to device ===\n";
$zkService = app(ZktecoService::class);
$ip = $zkService->getIp();
$port = $zkService->getPort();
$mode = $zkService->getMode();

if ($mode === 'simulation') {
    echo "Simulation mode. Skipped.\n";
    exit;
}

try {
    $zk = new \Jmrashed\Zkteco\Lib\ZKTeco($ip, $port);
    if (!$zk->connect()) {
        echo "Cannot connect to device {$ip}:{$port}\n";
        exit;
    }

    $zk->disableDevice();
    $count = 0;

    foreach ($updates as $u) {
        $student = Student::where('student_identity', $u['sid'])->first();
        if (!$student) {
            echo "SKIP: {$u['sid']} not found\n";
            continue;
        }

        $uid = $student->id;
        $userid = $student->id;
        $cleanName = substr(preg_replace('/[^A-Za-z0-9\s]/', '', $student->student_name), 0, 24);
        if (empty($cleanName)) $cleanName = "Student " . $student->id;

        $cardno = $student->card_number ? preg_replace('/[^0-9]/', '', $student->card_number) : 0;
        if (empty($cardno)) $cardno = 0;

        $zk->setUser($uid, $userid, $cleanName, '', 0, $cardno);
        echo "UPLOADED: {$student->student_name} (ID:{$uid} Card:{$cardno})\n";
        $count++;
    }

    $zk->enableDevice();
    $zk->disconnect();
    echo "\n=== Done! {$count} students uploaded to device ===\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
