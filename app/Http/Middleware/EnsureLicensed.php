<?php

namespace App\Http\Middleware;

use App\Models\InstallationLicense;
use App\Services\DeviceFingerprint;
use App\Services\LicenseToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        // Cache the entire license verification result to avoid a DB query on every request.
        // The cache is file-based (CACHE_STORE=file), so it never touches MySQL.
        // TTL = 5 minutes. The cache is busted by LicenseController on activate / disable.
        $cacheKey = self::cacheKey($currentHash);

        $verificationResult = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($currentHash, $request) {
            $row = InstallationLicense::query()->first();

            if (! $row || ! is_string($row->token) || $row->token === '') {
                Log::notice('license_missing', [
                    'user_id' => $request->user()?->id,
                    'path' => $request->path(),
                    'device_hash' => $currentHash,
                ]);
                return ['status' => 'missing'];
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
                return ['status' => 'exception'];
            }

            if (! $result['valid']) {
                Log::notice('license_invalid', [
                    'user_id' => $request->user()?->id,
                    'path' => $request->path(),
                    'device_hash' => $currentHash,
                    'reason' => $result['reason'] ?? null,
                ]);
                return ['status' => 'invalid'];
            }

            $licensedHash = $result['payload']['mac_hash'] ?? null;
            if (! is_string($licensedHash) || $licensedHash === '' || ! hash_equals($licensedHash, $currentHash)) {
                Log::notice('license_mac_mismatch', [
                    'user_id' => $request->user()?->id,
                    'path' => $request->path(),
                    'device_hash' => $currentHash,
                    'licensed_hash' => $licensedHash,
                ]);
                return ['status' => 'mismatch'];
            }

            return ['status' => 'valid'];
        });

        $status = $verificationResult['status'] ?? 'invalid';

        if ($status === 'valid') {
            return $next($request);
        }

        // Bust the cache on non-valid outcome so the next request re-checks the DB.
        // This handles the case where a license was just activated in another tab/session.
        Cache::forget($cacheKey);

        if ($status === 'missing') {
            return redirect()->route('license.activate');
        }

        return redirect()->route('license.activate')->withErrors([
            'license' => 'Activation required: invalid license.',
        ]);
    }

    /**
     * File-cache key for a given device hash.
     * Call Cache::forget(EnsureLicensed::cacheKey($hash)) to bust the cache
     * immediately after activating or disabling a license.
     */
    public static function cacheKey(string $deviceHash): string
    {
        return 'license_check_' . $deviceHash;
    }
}
