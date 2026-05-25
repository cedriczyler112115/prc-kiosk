<?php

namespace Tests\Feature;

use App\Models\InstallationLicense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseActivationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_unlicensed_user_is_redirected_to_activation_page(): void
    {
        config(['services.licensing.enabled' => true]);
        config(['services.licensing.mac_override' => 'aa:bb:cc:dd:ee:ff']);
        config(['services.licensing.public_key' => $this->generateRsaKeyPair()[1]]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('license.activate'));
    }

    public function test_user_can_activate_with_valid_token_and_password(): void
    {
        [$priv, $pub] = $this->generateRsaKeyPair();
        config(['services.licensing.enabled' => true]);
        config(['services.licensing.public_key' => $pub]);
        config(['services.licensing.mac_override' => 'aa:bb:cc:dd:ee:ff']);

        $deviceHash = hash('sha256', 'aa:bb:cc:dd:ee:ff');
        $token = $this->makeToken($priv, [
            'mac_hash' => $deviceHash,
            'exp' => time() + 3600,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('license.activate.post'), [
                'token' => $token,
                'password' => 'password',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('installation_licenses', 1);
        $row = InstallationLicense::query()->first();
        $this->assertNotNull($row);
        $this->assertSame($deviceHash, $row->device_hash);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_activation_fails_with_wrong_password(): void
    {
        [$priv, $pub] = $this->generateRsaKeyPair();
        config(['services.licensing.enabled' => true]);
        config(['services.licensing.public_key' => $pub]);
        config(['services.licensing.mac_override' => 'aa:bb:cc:dd:ee:ff']);

        $token = $this->makeToken($priv, [
            'mac_hash' => hash('sha256', 'aa:bb:cc:dd:ee:ff'),
            'exp' => time() + 3600,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('license.activate.post'), [
                'token' => $token,
                'password' => 'wrong-password',
            ])
            ->assertSessionHasErrors(['password']);
    }

    public function test_device_change_forces_reactivation(): void
    {
        [$priv, $pub] = $this->generateRsaKeyPair();
        config(['services.licensing.enabled' => true]);
        config(['services.licensing.public_key' => $pub]);

        $user = User::factory()->create();

        config(['services.licensing.mac_override' => 'aa:bb:cc:dd:ee:ff']);
        $token = $this->makeToken($priv, [
            'mac_hash' => hash('sha256', 'aa:bb:cc:dd:ee:ff'),
            'exp' => time() + 3600,
        ]);

        $this->actingAs($user)
            ->post(route('license.activate.post'), [
                'token' => $token,
                'password' => 'password',
            ])
            ->assertRedirect(route('dashboard'));

        config(['services.licensing.mac_override' => '11:22:33:44:55:66']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('license.activate'));
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
}
