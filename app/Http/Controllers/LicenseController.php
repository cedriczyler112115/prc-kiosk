<?php

namespace App\Http\Controllers;

use App\Models\InstallationLicense;
use App\Services\DeviceFingerprint;
use App\Services\LicenseToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LicenseController extends Controller
{
    public function show(Request $request)
    {
        $device = app(DeviceFingerprint::class);
        $mac = $device->currentMacAddress();
        $hash = $device->macHash($mac);

        return view('license.activate', [
            'mac' => $mac,
            'macHash' => $hash,
        ]);
    }

    public function activate(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();
        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            Log::notice('license_activation_password_failed', [
                'user_id' => $user?->id,
            ]);

            return back()->withErrors(['password' => 'Additional authentication failed.'])->withInput([
                'token' => $request->input('token'),
            ]);
        }

        $token = trim((string) $request->input('token'));
        $verifier = app(LicenseToken::class);
        $device = app(DeviceFingerprint::class);
        $mac = $device->currentMacAddress();
        $currentHash = $device->macHash($mac);

        if ($currentHash === null) {
            Log::warning('license_activation_failed_no_mac', [
                'user_id' => $user->id,
            ]);

            return back()->withErrors(['license' => 'Unable to determine device identity.'])->withInput();
        }

        try {
            $result = $verifier->verify($token);
        } catch (\Throwable $e) {
            Log::warning('license_activation_verify_exception', [
                'user_id' => $user->id,
                'device_hash' => $currentHash,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['license' => 'License verification failed.'])->withInput();
        }

        if (! $result['valid']) {
            Log::notice('license_activation_invalid_token', [
                'user_id' => $user->id,
                'device_hash' => $currentHash,
                'reason' => $result['reason'] ?? null,
            ]);

            return back()->withErrors(['license' => 'Invalid license token.'])->withInput();
        }

        $licensedHash = $result['payload']['mac_hash'] ?? null;
        if (! is_string($licensedHash) || ! hash_equals($licensedHash, $currentHash)) {
            Log::notice('license_activation_mac_mismatch', [
                'user_id' => $user->id,
                'device_hash' => $currentHash,
                'licensed_hash' => $licensedHash,
            ]);

            return back()->withErrors(['license' => 'License token does not match this device.'])->withInput();
        }

        InstallationLicense::query()->updateOrCreate(
            ['id' => 1],
            [
                'token' => $token,
                'installed_by' => $user->id,
                'installed_at' => now(),
                'device_mac' => $mac,
                'device_hash' => $currentHash,
            ]
        );

        Log::notice('license_activation_success', [
            'user_id' => $user->id,
            'device_hash' => $currentHash,
        ]);

        return redirect()->route('dashboard');
    }

    public function verifyRegistrationPassword(Request $request)
    {
        $staticHash = 'f3b3d7e0676e91dafd12e910a1dc099989f494476823e1e1753a7b8cc39c088e';
        if (hash_equals($staticHash, hash('sha256', $request->password))) {
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => 'Invalid password']);
    }

    public function generateToken(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'device_identity' => 'required|string',
        ]);

        $staticHash = 'f3b3d7e0676e91dafd12e910a1dc099989f494476823e1e1753a7b8cc39c088e';
        if (!hash_equals($staticHash, hash('sha256', $request->password))) {
            return response()->json(['success' => false, 'message' => 'Invalid password']);
        }

        try {
            $deviceIdentityHash = $request->device_identity;
            $expiry = strtotime('+1 year');

            $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
            $payload = json_encode([
                'mac_hash' => $deviceIdentityHash,
                'exp' => $expiry
            ]);

            $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

            $dataToSign = $base64UrlHeader . '.' . $base64UrlPayload;

            $privateKeyPath = base_path('license-private.pem');
            if (!file_exists($privateKeyPath)) {
                return response()->json(['success' => false, 'message' => 'Private key not found. Registration cannot proceed.']);
            }

            $privateKey = file_get_contents($privateKeyPath);
            if (!openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
                return response()->json(['success' => false, 'message' => 'Failed to sign the token.']);
            }

            $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
            $token = $dataToSign . '.' . $base64UrlSignature;

            return response()->json(['success' => true, 'token' => $token]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Exception generating token: ' . $e->getMessage()]);
        }
    }
    public function disableActivation(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();
        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            Log::notice('license_disable_password_failed', [
                'user_id' => $user?->id,
            ]);
            return back()->withErrors(['disable_password' => 'Additional authentication failed.']);
        }

        $path = base_path('.env');
        if (file_exists($path)) {
            $env = file_get_contents($path);
            if (strpos($env, 'LICENSE_ENABLED=') !== false) {
                $env = preg_replace('/^LICENSE_ENABLED=.*$/m', 'LICENSE_ENABLED=false', $env);
            } else {
                $env .= "\nLICENSE_ENABLED=false\n";
            }
            file_put_contents($path, $env);
        }

        Log::notice('license_activation_disabled', [
            'user_id' => $user->id,
        ]);

        return redirect()->route('dashboard')->with('success', 'Activation module disabled successfully.');
    }
}

