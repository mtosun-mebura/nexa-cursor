<?php

namespace App\Support;

use App\Models\User;

class UserAgendaColor
{
    /** @var list<string> */
    public const PALETTE = [
        '#3b82f6',
        '#10b981',
        '#f59e0b',
        '#ef4444',
        '#8b5cf6',
        '#ec4899',
        '#06b6d4',
        '#84cc16',
        '#f97316',
        '#6366f1',
    ];

    public static function resolved(?User $user): string
    {
        if ($user === null) {
            return self::UNASSIGNED;
        }

        $stored = trim((string) ($user->agenda_color ?? ''));
        if ($stored !== '' && preg_match('/^#[0-9A-Fa-f]{6}$/', $stored) === 1) {
            return strtolower($stored);
        }

        return self::defaultForUserId((int) $user->id);
    }

    public static function defaultForUserId(int $userId): string
    {
        if ($userId <= 0) {
            return self::UNASSIGNED;
        }

        return self::PALETTE[$userId % count(self::PALETTE)];
    }

    public const UNASSIGNED = '#60a5fa';

    public const COMPLETED = '#94a3b8';

    /**
     * @return array{color: string, state: 'completed'|'active'|'upcoming'}
     */
    public static function forRide(?User $driver, string $status): array
    {
        if ($status === 'completed') {
            return [
                'color' => self::COMPLETED,
                'state' => 'completed',
            ];
        }

        $base = self::resolved($driver);
        $isDefaultColor = $driver === null;

        $activeStatuses = [
            'accepted',
            'assigned',
            'offered',
            'pending_dispatch',
            'pending_payment',
        ];

        if (in_array($status, $activeStatuses, true)) {
            return [
                'color' => $isDefaultColor ? $base : self::lighten($base, 0.18),
                'state' => 'active',
            ];
        }

        return [
            'color' => $isDefaultColor ? $base : self::lighten($base, 0.38),
            'state' => 'upcoming',
        ];
    }

    public static function lighten(string $hex, float $mix = 0.35): string
    {
        $normalized = ltrim(strtolower(trim($hex)), '#');
        if (strlen($normalized) !== 6 || ! ctype_xdigit($normalized)) {
            return self::UNASSIGNED;
        }

        $r = hexdec(substr($normalized, 0, 2));
        $g = hexdec(substr($normalized, 2, 2));
        $b = hexdec(substr($normalized, 4, 2));

        $r = (int) round($r + (255 - $r) * $mix);
        $g = (int) round($g + (255 - $g) * $mix);
        $b = (int) round($b + (255 - $b) * $mix);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
