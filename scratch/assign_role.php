<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$imtiaz = App\Models\User::where('email', 'imtiaz@gmail.com')->first();
if ($imtiaz) {
    echo "Assigning role to Imtiaz Ahmed...\n";
    $role = \HasinHayder\Tyro\Models\Role::where('slug', 'super-admin')->first();
    if ($role) {
        $imtiaz->assignRole($role);
        echo "Done! Current roles for Imtiaz: " . implode(', ', $imtiaz->tyroRoleSlugs()) . "\n";
    } else {
        echo "Role 'super-admin' not found in database.\n";
    }
} else {
    echo "User imtiaz@gmail.com not found.\n";
}
