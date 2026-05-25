<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$issuances = App\Models\Issuance::with('user')->get();
foreach ($issuances as $issuance) {
    echo 'ID: '.$issuance->id."\n";
    echo 'User ID: '.$issuance->user_id."\n";
    echo 'User Relation: '.($issuance->user ? 'Found' : 'NULL')."\n";
    if ($issuance->user) {
        echo 'User Name: '.$issuance->user->name."\n";
    }
    echo "-------------------\n";
}
