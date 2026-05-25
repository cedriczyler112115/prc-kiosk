<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class DeviceFingerprint
{
    public function currentMacAddress(): ?string
    {
        $override = config('services.licensing.mac_override');
        if (is_string($override) && trim($override) !== '') {
            return $this->normalizeFingerprint($override);
        }

        try {
            if (PHP_OS_FAMILY === 'Windows') {
                return $this->firstValidFingerprintFromWindows();
            }
            if (PHP_OS_FAMILY === 'Darwin') {
                return $this->firstValidFingerprintFromDarwin();
            }

            return $this->firstValidFingerprintFromLinux();
        } catch (\Throwable $e) {
            Log::warning('device_fingerprint_lookup_failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function macHash(?string $mac): ?string
    {
        $normalized = $mac ? $this->normalizeFingerprint($mac) : null;
        if (! $normalized) return null;
        return hash('sha256', $normalized);
    }

    private function normalizeFingerprint(string $fingerprint): ?string
    {
        $f = strtoupper(trim($fingerprint));
        $f = preg_replace('/[^0-9A-F\-]/', '', $f);
        if ($f === '') return null;
        return $f;
    }

    private function firstValidFingerprintFromLinux(): ?string
    {
        foreach (['/sys/class/dmi/id/product_uuid', '/etc/machine-id'] as $path) {
            if (is_file($path)) {
                $raw = trim((string) @file_get_contents($path));
                $fp = $this->normalizeFingerprint($raw);
                if ($fp && strlen($fp) > 10) return $fp;
            }
        }
        return null;
    }

    private function firstValidFingerprintFromDarwin(): ?string
    {
        $out = $this->execLines('ioreg -rd1 -c IOPlatformExpertDevice | awk \'/IOPlatformUUID/ { split($0, line, "\\\""); printf("%s\\n", line[4]); }\'');
        foreach ($out as $line) {
            $fp = $this->normalizeFingerprint($line);
            if ($fp && strlen($fp) > 10) return $fp;
        }
        return null;
    }

    private function firstValidFingerprintFromWindows(): ?string
    {
        $out = $this->execLines('wmic csproduct get uuid');
        foreach ($out as $line) {
            if (stripos(trim($line), 'uuid') !== false) continue;
            $fp = $this->normalizeFingerprint($line);
            if ($fp && strlen($fp) > 10) return $fp;
        }

        // Fallback to PowerShell
        $out = $this->execLines('powershell "(Get-CimInstance -Class Win32_ComputerSystemProduct).UUID"');
        foreach ($out as $line) {
            $fp = $this->normalizeFingerprint($line);
            if ($fp && strlen($fp) > 10) return $fp;
        }

        return null;
    }

    private function execLines(string $cmd): array
    {
        if (! function_exists('exec')) return [];
        $output = [];
        $redir = PHP_OS_FAMILY === 'Windows' ? ' 2>NUL' : ' 2>/dev/null';
        @exec($cmd.$redir, $output);
        if (! is_array($output)) return [];
        return array_map('strval', $output);
    }
}
