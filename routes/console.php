<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('license:device', function () {
    $fp = app(\App\Services\DeviceFingerprint::class);
    $mac = $fp->currentMacAddress();
    $hash = $fp->macHash($mac);

    $this->line('mac='.$mac);
    $this->line('mac_hash='.$hash);
})->purpose('Print device MAC and MAC hash for licensing');

Artisan::command('license:sign {mac_hash} {--days=365} {--private-key=}', function () {
    $macHash = (string) $this->argument('mac_hash');
    $days = (int) $this->option('days');
    if ($days <= 0) $days = 365;

    $path = (string) $this->option('private-key');
    if (trim($path) === '') {
        $path = (string) env('LICENSE_PRIVATE_KEY_PATH', '');
    }

    if (trim($path) === '' || ! is_file($path)) {
        $this->error('Missing private key file. Provide --private-key or set LICENSE_PRIVATE_KEY_PATH.');
        return 1;
    }

    $privateKeyPem = (string) file_get_contents($path);
    if (trim($privateKeyPem) === '') {
        $this->error('Private key file is empty.');
        return 1;
    }

    $header = ['alg' => 'RS256', 'typ' => 'LICENSE'];
    $payload = [
        'mac_hash' => $macHash,
        'exp' => time() + ($days * 86400),
    ];

    $h = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
    $p = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    $data = $h.'.'.$p;

    $sig = '';
    $ok = openssl_sign($data, $sig, $privateKeyPem, OPENSSL_ALGO_SHA256);
    if ($ok !== true) {
        $this->error('Signing failed.');
        return 1;
    }

    $s = rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');
    $this->line($data.'.'.$s);
    return 0;
})->purpose('Generate a signed license token for a device MAC hash');
