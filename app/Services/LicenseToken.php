<?php

namespace App\Services;

class LicenseToken
{
    public function verify(string $token): array
    {
        $publicKey = (string) config('services.licensing.public_key', '');
        if (trim($publicKey) === '') {
            return ['valid' => false, 'reason' => 'missing_public_key'];
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return ['valid' => false, 'reason' => 'bad_format'];
        }

        [$h, $p, $s] = $parts;
        $headerJson = $this->b64urlDecode($h);
        $payloadJson = $this->b64urlDecode($p);
        $sig = $this->b64urlDecodeRaw($s);
        if ($headerJson === null || $payloadJson === null || $sig === null) {
            return ['valid' => false, 'reason' => 'bad_encoding'];
        }

        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);
        if (! is_array($header) || ! is_array($payload)) {
            return ['valid' => false, 'reason' => 'bad_json'];
        }

        if (($header['alg'] ?? null) !== 'RS256') {
            return ['valid' => false, 'reason' => 'unsupported_alg'];
        }

        $data = $h.'.'.$p;
        $ok = openssl_verify($data, $sig, $publicKey, OPENSSL_ALGO_SHA256);
        if ($ok !== 1) {
            return ['valid' => false, 'reason' => 'bad_signature'];
        }

        $now = time();
        $exp = $payload['exp'] ?? null;
        if (! is_int($exp) && ! (is_string($exp) && ctype_digit($exp))) {
            return ['valid' => false, 'reason' => 'missing_exp'];
        }
        $expInt = (int) $exp;
        if ($expInt <= $now) {
            return ['valid' => false, 'reason' => 'expired'];
        }

        $macHash = $payload['mac_hash'] ?? null;
        if (! is_string($macHash) || trim($macHash) === '') {
            return ['valid' => false, 'reason' => 'missing_mac_hash'];
        }

        return ['valid' => true, 'payload' => $payload, 'header' => $header];
    }

    private function b64urlDecode(?string $in): ?string
    {
        $raw = $this->b64urlDecodeRaw($in);
        if ($raw === null) return null;
        return $raw;
    }

    private function b64urlDecodeRaw(?string $in): ?string
    {
        if (! is_string($in) || $in === '') return null;
        $b64 = strtr($in, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad > 0) $b64 .= str_repeat('=', 4 - $pad);
        $out = base64_decode($b64, true);
        return $out === false ? null : $out;
    }
}

