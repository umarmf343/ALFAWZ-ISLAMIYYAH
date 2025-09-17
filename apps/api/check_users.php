<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Users in database:\n";
foreach(App\Models\User::all() as $user) {
    echo $user->id . ' - ' . $user->name . ' (' . $user->email . ')' . "\n";
}