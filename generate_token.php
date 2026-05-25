<?php

// 1. Set the Device Identity Hash copied from the Activation Page
$deviceIdentityHash = '2d371b41179672fb8693724a1844e2849d6dce456808bb9b4a5a39a0d973fcdb';

// 2. Set Expiry (e.g., valid for 1 year)
$expiry = strtotime('+1 year');

$header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
$payload = json_encode([
    'mac_hash' => $deviceIdentityHash,
    'exp' => $expiry
]);

$base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
$base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

$dataToSign = $base64UrlHeader . '.' . $base64UrlPayload;

// Load the private key
$privateKey = file_get_contents(__DIR__ . '/license-private.pem');

// Sign the data
openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);
$base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

// Output the final token
$token = $dataToSign . '.' . $base64UrlSignature;
echo "Your License Token:\n\n" . $token . "\n";
