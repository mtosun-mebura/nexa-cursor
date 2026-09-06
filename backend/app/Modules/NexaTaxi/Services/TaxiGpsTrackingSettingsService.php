<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Hash;

class TaxiGpsTrackingSettingsService
{
    public const KEY_OFFLINE_CODE = 'taxi_gps_offline_view_code';

    public const KEY_APPEARANCE = 'taxi_gps_map_appearance';

    public const SESSION_UNLOCK_PREFIX = 'gps_tracking_offline_unlocked.';

    public const UNLOCK_TTL_SECONDS = 1800;

    public const MIN_CODE_LENGTH = 4;

    public const MAX_CODE_LENGTH = 8;

    public const MIN_REFRESH_SECONDS = 1;

    public const MAX_REFRESH_SECONDS = 30;

    public const CAR_STYLE_SEDAN = 'sedan';

    public const CAR_STYLE_VAN = 'van';

    public const CAR_STYLE_BUS = 'bus';

    public const COLOR_MODE_PER_VEHICLE = 'per_vehicle';

    public const COLOR_MODE_SINGLE = 'single';

    /**
     * @return list<string>
     */
    public static function carStyles(): array
    {
        return [self::CAR_STYLE_SEDAN, self::CAR_STYLE_VAN, self::CAR_STYLE_BUS];
    }

    /**
     * @return array<string, string>
     */
    public static function carStyleLabels(): array
    {
        return [
            self::CAR_STYLE_SEDAN => 'Auto',
            self::CAR_STYLE_VAN => 'Busje',
            self::CAR_STYLE_BUS => 'Bus',
        ];
    }

    public static function styleFromVehicleType(?string $type): string
    {
        return match (strtolower(trim((string) $type))) {
            'van' => self::CAR_STYLE_VAN,
            'bus' => self::CAR_STYLE_BUS,
            default => self::CAR_STYLE_SEDAN,
        };
    }

    /**
     * @return list<array{key: string, label: string, hex: string}>
     */
    public static function carColorPresets(): array
    {
        return [
            ['key' => 'orange', 'label' => 'Oranje', 'hex' => '#ea580c'],
            ['key' => 'blue', 'label' => 'Blauw', 'hex' => '#1d4ed8'],
            ['key' => 'green', 'label' => 'Groen', 'hex' => '#15803d'],
            ['key' => 'red', 'label' => 'Rood', 'hex' => '#dc2626'],
            ['key' => 'black', 'label' => 'Zwart', 'hex' => '#111827'],
            ['key' => 'silver', 'label' => 'Zilver', 'hex' => '#9ca3af'],
            ['key' => 'yellow', 'label' => 'Geel', 'hex' => '#ca8a04'],
            ['key' => 'white', 'label' => 'Wit', 'hex' => '#f8fafc'],
        ];
    }

    public function hasOfflineCode(?int $companyId): bool
    {
        if ($companyId === null || $companyId <= 0) {
            return false;
        }

        $hash = trim((string) GeneralSetting::get(self::KEY_OFFLINE_CODE, '', $companyId));

        return $hash !== '';
    }

    public function setOfflineCode(string $plain, int $companyId): void
    {
        GeneralSetting::set(self::KEY_OFFLINE_CODE, Hash::make($plain), $companyId);
    }

    public function codeMatches(string $plain, int $companyId): bool
    {
        $hash = trim((string) GeneralSetting::get(self::KEY_OFFLINE_CODE, '', $companyId));
        if ($hash === '') {
            return false;
        }

        return Hash::check($plain, $hash);
    }

    public function isOfflineUnlocked(?int $companyId): bool
    {
        if ($companyId === null || $companyId <= 0) {
            return false;
        }

        $until = (int) session($this->sessionKey($companyId), 0);

        return $until > time();
    }

    public function unlockOffline(int $companyId): void
    {
        session([
            $this->sessionKey($companyId) => time() + self::UNLOCK_TTL_SECONDS,
        ]);
    }

    public function lockOffline(int $companyId): void
    {
        session()->forget($this->sessionKey($companyId));
    }

    /**
     * @return array{
     *     car_style: string,
     *     car_color_mode: string,
     *     car_color: string,
     *     type_colors: array<string, string>,
     *     vehicle_colors: array<string, string>,
     *     plate_background: string,
     *     plate_text_color: string,
     *     plate_border_color: string,
     *     refresh_seconds: int
     * }
     */
    public function appearance(?int $companyId): array
    {
        $raw = $companyId ? GeneralSetting::get(self::KEY_APPEARANCE, '', $companyId) : '';
        $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];

        return $this->normalizeAppearance(is_array($decoded) ? $decoded : []);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     car_style: string,
     *     car_color_mode: string,
     *     car_color: string,
     *     type_colors: array<string, string>,
     *     vehicle_colors: array<string, string>,
     *     plate_background: string,
     *     plate_text_color: string,
     *     plate_border_color: string,
     *     refresh_seconds: int
     * }
     */
    public function setAppearance(array $input, int $companyId): array
    {
        $normalized = $this->normalizeAppearance($input);
        GeneralSetting::set(self::KEY_APPEARANCE, json_encode($normalized), $companyId);

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     car_style: string,
     *     car_color_mode: string,
     *     car_color: string,
     *     type_colors: array<string, string>,
     *     vehicle_colors: array<string, string>,
     *     plate_background: string,
     *     plate_text_color: string,
     *     plate_border_color: string,
     *     refresh_seconds: int
     * }
     */
    public function normalizeAppearance(array $input): array
    {
        $style = strtolower(trim((string) ($input['car_style'] ?? self::CAR_STYLE_SEDAN)));
        if ($style === 'hatchback' || ! in_array($style, self::carStyles(), true)) {
            $style = self::CAR_STYLE_SEDAN;
        }

        $mode = strtolower(trim((string) ($input['car_color_mode'] ?? self::COLOR_MODE_SINGLE)));
        if (! in_array($mode, [self::COLOR_MODE_PER_VEHICLE, self::COLOR_MODE_SINGLE], true)) {
            $mode = self::COLOR_MODE_SINGLE;
        }

        $fallbackColor = $this->normalizeHex($input['car_color'] ?? null, '#ea580c');
        $typeColors = $this->normalizeTypeColors($input['type_colors'] ?? [], $fallbackColor);

        return [
            'car_style' => $style,
            'car_color_mode' => $mode,
            'car_color' => $typeColors[self::CAR_STYLE_SEDAN],
            'type_colors' => $typeColors,
            'vehicle_colors' => $this->normalizeVehicleColors($input['vehicle_colors'] ?? []),
            'plate_background' => $this->normalizeHex($input['plate_background'] ?? null, '#f7e125'),
            'plate_text_color' => $this->normalizeHex($input['plate_text_color'] ?? null, '#111827'),
            'plate_border_color' => $this->normalizeHex($input['plate_border_color'] ?? null, '#111827'),
            'refresh_seconds' => max(self::MIN_REFRESH_SECONDS, min(self::MAX_REFRESH_SECONDS, (int) ($input['refresh_seconds'] ?? 1))),
        ];
    }

    /**
     * @param  array<string, mixed>  $appearance
     */
    public function colorForStyle(array $appearance, string $style): string
    {
        $style = in_array($style, self::carStyles(), true) ? $style : self::CAR_STYLE_SEDAN;
        $colors = is_array($appearance['type_colors'] ?? null) ? $appearance['type_colors'] : [];
        $hex = $colors[$style] ?? $appearance['car_color'] ?? '#ea580c';

        return $this->normalizeHex($hex, '#ea580c');
    }

    /**
     * @param  mixed  $raw
     * @return array<string, string>
     */
    private function normalizeTypeColors(mixed $raw, string $fallback): array
    {
        $input = is_array($raw) ? $raw : [];
        $out = [];
        foreach (self::carStyles() as $style) {
            $out[$style] = $this->normalizeHex($input[$style] ?? null, $fallback);
        }

        return $out;
    }

    /**
     * @param  mixed  $raw
     * @return array<string, string>
     */
    private function normalizeVehicleColors(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $palette = TaxiGpsTrackingService::MARKER_COLORS;
        $out = [];
        foreach ($raw as $id => $hex) {
            $vehicleId = (int) $id;
            if ($vehicleId <= 0) {
                continue;
            }
            $fallback = $palette[abs($vehicleId) % count($palette)];
            $out[(string) $vehicleId] = $this->normalizeHex($hex, $fallback);
        }
        ksort($out, SORT_NUMERIC);

        return $out;
    }

    private function normalizeHex(mixed $value, string $fallback): string
    {
        $hex = strtoupper(ltrim(trim((string) $value), '#'));
        if (preg_match('/^[A-F0-9]{6}$/', $hex) !== 1) {
            return $fallback;
        }

        return '#'.strtolower($hex);
    }

    private function sessionKey(int $companyId): string
    {
        return self::SESSION_UNLOCK_PREFIX.$companyId;
    }
}
