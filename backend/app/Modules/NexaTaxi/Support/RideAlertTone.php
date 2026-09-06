<?php

namespace App\Modules\NexaTaxi\Support;

use App\Models\User;

final class RideAlertTone
{
    public const DEFAULT = 'classic';

    /** @var list<string> */
    public const KEYS = ['classic', 'chime', 'alert', 'soft', 'siren'];

    /**
     * @return array<string, array{label: string, hint: string}>
     */
    public static function options(): array
    {
        return [
            'classic' => [
                'label' => 'Klassiek',
                'hint' => 'Twee heldere piepjes',
            ],
            'chime' => [
                'label' => 'Bel',
                'hint' => 'Drie tonen, als een bel',
            ],
            'alert' => [
                'label' => 'Alert',
                'hint' => 'Korte, snelle piepjes',
            ],
            'soft' => [
                'label' => 'Zacht',
                'hint' => 'Lagere, rustige toon',
            ],
            'siren' => [
                'label' => 'Sirene',
                'hint' => 'Korte stijgende/dalende toon',
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

        return self::normalize($user->ride_alert_tone ?? null);
    }

    public static function saveFor(User $user, ?string $value): string
    {
        $tone = self::normalize($value);
        $user->forceFill(['ride_alert_tone' => $tone])->save();

        return $tone;
    }
}
