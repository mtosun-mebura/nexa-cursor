<?php

namespace App\Support;

/**
 * Heroicon-keuzelijsten voor admin-selects (features, dienstenblok, page builder).
 */
final class HeroiconSelectOptions
{
    /**
     * Icoon-id => label, gesorteerd op label (Nederlandse alfabetische volgorde).
     *
     * @param  array<int, string>  $excludeIds
     * @return array<string, string>
     */
    public static function sortedLabelsById(array $excludeIds = []): array
    {
        $icons = config('heroicons.icons', []);
        $options = [];

        foreach ($icons as $id => $data) {
            if (! is_string($id) || $id === '') {
                continue;
            }
            if (in_array($id, $excludeIds, true)) {
                continue;
            }
            if (! is_array($data) || ! isset($data['label'], $data['svg'])) {
                continue;
            }
            $options[$id] = (string) $data['label'];
        }

        uasort($options, static fn (string $a, string $b): int => strcasecmp($a, $b));

        return $options;
    }

    /**
     * @return array<int, string>
     */
    public static function sortedIconIds(array $excludeIds = []): array
    {
        return array_keys(self::sortedLabelsById($excludeIds));
    }
}
