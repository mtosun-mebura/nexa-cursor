<?php

namespace App\Modules\NexaTaxi\Support;

use App\Models\User;

final class PwaAccent
{
    public const DEFAULT = 'orange';

    /** @var list<string> */
    public const KEYS = ['orange', 'yellow', 'blue', 'red', 'green', 'pink'];

    /**
     * @return array<string, array{hex: string, hover: string, rgb: string, on: string, label: string}>
     */
    public static function palettes(): array
    {
        return [
            'orange' => [
                'hex' => '#f97316',
                'hover' => '#ea580c',
                'rgb' => '249, 115, 22',
                'on' => '#ffffff',
                'label' => 'Oranje',
            ],
            'yellow' => [
                'hex' => '#eab308',
                'hover' => '#ca8a04',
                'rgb' => '234, 179, 8',
                'on' => '#422006',
                'label' => 'Geel',
            ],
            'blue' => [
                'hex' => '#2563eb',
                'hover' => '#1d4ed8',
                'rgb' => '37, 99, 235',
                'on' => '#ffffff',
                'label' => 'Blauw',
            ],
            'red' => [
                'hex' => '#ef4444',
                'hover' => '#dc2626',
                'rgb' => '239, 68, 68',
                'on' => '#ffffff',
                'label' => 'Rood',
            ],
            'green' => [
                'hex' => '#16a34a',
                'hover' => '#15803d',
                'rgb' => '22, 163, 74',
                'on' => '#ffffff',
                'label' => 'Groen',
            ],
            'pink' => [
                'hex' => '#ec4899',
                'hover' => '#db2777',
                'rgb' => '236, 72, 153',
                'on' => '#ffffff',
                'label' => 'Roze',
            ],
        ];
    }

    public static function normalize(?string $value): string
    {
        $key = strtolower(trim((string) $value));

        return in_array($key, self::KEYS, true) ? $key : self::DEFAULT;
    }

    public static function fromUser(?User $user): string
    {
        if (! $user) {
            return self::DEFAULT;
        }

        return self::normalize($user->pwa_accent ?? null);
    }

    public static function saveFor(User $user, ?string $value): string
    {
        $accent = self::normalize($value);
        $user->forceFill(['pwa_accent' => $accent])->save();

        return $accent;
    }
}
