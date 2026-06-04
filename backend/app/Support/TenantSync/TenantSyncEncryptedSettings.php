<?php

namespace App\Support\TenantSync;

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Crypt;

final class TenantSyncEncryptedSettings
{
    private const PREFIX = 'enc:';

    public static function set(string $key, ?string $plain): void
    {
        if ($plain === null || trim($plain) === '') {
            GeneralSetting::set($key, '');

            return;
        }

        GeneralSetting::set($key, self::PREFIX.Crypt::encryptString(trim($plain)));
    }

    public static function get(string $key): ?string
    {
        $stored = trim((string) GeneralSetting::get($key, ''));
        if ($stored === '') {
            return null;
        }

        if (! str_starts_with($stored, self::PREFIX)) {
            return TenantSyncSecretInput::normalize($stored);
        }

        try {
            $plain = Crypt::decryptString(substr($stored, strlen(self::PREFIX)));

            return TenantSyncSecretInput::normalize($plain);
        } catch (\Throwable) {
            return null;
        }
    }
}
