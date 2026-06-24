<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
$reflection = new ReflectionClass($user);
foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
    if ($method->getDeclaringClass()->getName() !== 'Illuminate\Database\Eloquent\Model') {
        echo $method->getName() . "\n";
    }
}
