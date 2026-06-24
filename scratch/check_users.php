<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (App\Models\User::all() as $u) {
    echo "Name: " . $u->name . " | Email: " . $u->email . " | Roles: " . implode(', ', $u->tyroRoleSlugs()) . "\n";
}
