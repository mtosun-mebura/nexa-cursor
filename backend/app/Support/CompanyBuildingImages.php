<?php

namespace App\Support;

/**
 * Standaard gebouw-illustraties (wizard + bedrijfsprofiel).
 * Bestanden: public/assets/media/company-buildings/{1,2,3}.png (in git, alle omgevingen).
 */
final class CompanyBuildingImages
{
    public const ASSET_DIR = 'assets/media/company-buildings';

    /** @var list<int> */
    public const VALID_IDS = [1, 2, 3];

    /**
     * @return array<int, array{label: string, src: string}>
     */
    public static function options(): array
    {
        $labels = [
            1 => 'Oranje gevel',
            2 => 'Twee torens',
            3 => 'Wit minimalisme',
        ];

        $out = [];
        foreach (self::VALID_IDS as $id) {
            $out[$id] = [
                'label' => $labels[$id],
                'src' => self::url($id) ?? '',
            ];
        }

        return $out;
    }

    public static function isValidId(?int $id): bool
    {
        return $id !== null && in_array($id, self::VALID_IDS, true);
    }

    public static function url(int $id): ?string
    {
        if (! self::isValidId($id)) {
            return null;
        }

        return asset(self::ASSET_DIR.'/'.$id.'.png');
    }
}
