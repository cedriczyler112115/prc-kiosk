<?php

namespace Tests\Unit;

use App\Services\LicenseToken;
use Tests\TestCase;

class LicenseTokenTest extends TestCase
{
    public function test_it_verifies_a_valid_rs256_token(): void
    {
        [$priv, $pub] = $this->generateRsaKeyPair();
        config(['services.licensing.public_key' => $pub]);

        $token = $this->makeToken($priv, [
            'mac_hash' => hash('sha256', 'aa:bb:cc:dd:ee:ff'),
            'exp' => time() + 3600,
        ]);

        $result = (new LicenseToken())->verify($token);
        $this->assertTrue($result['valid']);
        $this->assertSame(hash('sha256', 'aa:bb:cc:dd:ee:ff'), $result['payload']['mac_hash']);
    }

    public function test_it_rejects_expired_tokens(): void
    {
        [$priv, $pub] = $this->generateRsaKeyPair();
        config(['services.licensing.public_key' => $pub]);

        $token = $this->makeToken($priv, [
            'mac_hash' => hash('sha256', 'aa:bb:cc:dd:ee:ff'),
            'exp' => time() - 1,
        ]);

        $result = (new LicenseToken())->verify($token);
        $this->assertFalse($result['valid']);
        $this->assertSame('expired', $result['reason']);
    }

    public function test_it_rejects_bad_signatures(): void
    {
        [$priv, $pub] = $this->generateRsaKeyPair();
        config(['services.licensing.public_key' => $pub]);

        $token = $this->makeToken($priv, [
            'mac_hash' => hash('sha256', 'aa:bb:cc:dd:ee:ff'),
            'exp' => time() + 3600,
        ]);

        $parts = explode('.', $token);
        $payload = json_decode($this->b64urlDecode($parts[1]), true);
        $payload['mac_hash'] = hash('sha256', '11:22:33:44:55:66');
        $parts[1] = $this->b64urlEncode(json_encode($payload));
        $tampered = implode('.', $parts);

        $result = (new LicenseToken())->verify($tampered);
        $this->assertFalse($result['valid']);
        $this->assertSame('bad_signature', $result['reason']);
    }

    private function generateRsaKeyPair(): array
    {
        $conf = dirname(__DIR__).DIRECTORY_SEPARATOR.'openssl.cnf';
        if (is_file($conf)) {
            @putenv('OPENSSL_CONF='.$conf);
        }

        $res = openssl_pkey_new([
            'config' => $conf,
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $this->assertNotFalse($res);

        $priv = '';
        $ok = openssl_pkey_export($res, $priv, null, ['config' => $conf]);
        $this->assertTrue($ok);

        $details = openssl_pkey_get_details($res);
        $this->assertIsArray($details);
        $this->assertArrayHasKey('key', $details);

        return [$priv, $details['key']];
    }

    private function makeToken(string $privateKeyPem, array $payload): string
    {
        $header = ['alg' => 'RS256', 'typ' => 'LICENSE'];
        $h = $this->b64urlEncode(json_encode($header));
        $p = $this->b64urlEncode(json_encode($payload));
        $data = $h.'.'.$p;

        $sig = '';
        $ok = openssl_sign($data, $sig, $privateKeyPem, OPENSSL_ALGO_SHA256);
        $this->assertTrue($ok);

        return $data.'.'.$this->b64urlEncode($sig);
    }

    private function b64urlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function b64urlDecode(string $in): string
    {
        $b64 = strtr($in, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad > 0) $b64 .= str_repeat('=', 4 - $pad);
        $out = base64_decode($b64, true);
        $this->assertNotFalse($out);
        return $out;
    }
}
