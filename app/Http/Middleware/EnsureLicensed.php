<?php

namespace App\Http\Middleware;

use App\Models\InstallationLicense;
use App\Services\DeviceFingerprint;
use App\Services\LicenseToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureLicensed
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('services.licensing.enabled', true)) {
            return $next($request);
        }

        if ($request->routeIs('license.*')) {
            return $next($request);
        }

        $device = app(DeviceFingerprint::class);
        $currentMac = $device->currentMacAddress();
        $currentHash = $device->macHash($currentMac);

        if ($currentHash === null) {
            Log::warning('license_check_failed_no_mac', [
                'user_id' => $request->user()?->id,
                'path' => $request->path(),
            ]);

            return redirect()->route('license.activate')->withErrors([
                'license' => 'Activation required: unable to determine device identity.',
            ]);
        }

        $row = InstallationLicense::query()->first();
        if (! $row || ! is_string($row->token) || $row->token === '') {
            Log::notice('license_missing', [
                'user_id' => $request->user()?->id,
                'path' => $request->path(),
                'device_hash' => $currentHash,
            ]);

            return redirect()->route('license.activate');
        }

        try {
            $result = app(LicenseToken::class)->verify($row->token);
        } catch (\Throwable $e) {
            Log::warning('license_verify_exception', [
                'user_id' => $request->user()?->id,
                'path' => $request->path(),
                'device_hash' => $currentHash,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('license.activate')->withErrors([
                'license' => 'Activation required: license verification failed.',
            ]);
        }

        if (! $result['valid']) {
            Log::notice('license_invalid', [
                'user_id' => $request->user()?->id,
                'path' => $request->path(),
                'device_hash' => $currentHash,
                'reason' => $result['reason'] ?? null,
            ]);

            return redirect()->route('license.activate')->withErrors([
                'license' => 'Activation required: invalid license.',
            ]);
        }

        $licensedHash = $result['payload']['mac_hash'] ?? null;
        if (! is_string($licensedHash) || $licensedHash === '' || ! hash_equals($licensedHash, $currentHash)) {
            Log::notice('license_mac_mismatch', [
                'user_id' => $request->user()?->id,
                'path' => $request->path(),
                'device_hash' => $currentHash,
                'licensed_hash' => $licensedHash,
            ]);

            return redirect()->route('license.activate')->withErrors([
                'license' => 'Activation required: license does not match this device.',
            ]);
        }

        return $next($request);
    }
}

