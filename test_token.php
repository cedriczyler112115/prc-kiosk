<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$deviceIdentityHash = app(\App\Services\DeviceFingerprint::class)->macHash(app(\App\Services\DeviceFingerprint::class)->currentMacAddress());

echo "Device Hash: " . $deviceIdentityHash . "\n";

$expiry = strtotime('+1 year');

$header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
$payload = json_encode([
    'mac_hash' => $deviceIdentityHash,
    'exp' => $expiry
]);

$base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
$base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

$dataToSign = $base64UrlHeader . '.' . $base64UrlPayload;

$privateKey = file_get_contents(__DIR__ . '/license-private.pem');

openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);
$base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

$token = $dataToSign . '.' . $base64UrlSignature;
echo "Generated Token: " . $token . "\n";

$result = app(\App\Services\LicenseToken::class)->verify($token);
print_r($result);
