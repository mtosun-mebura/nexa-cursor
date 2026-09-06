<?php

namespace App\Services\AiWebsite;

final class AiScalar
{
    public static function string(mixed $value): string
    {
        if (is_string($value) || is_numeric($value) || is_bool($value)) {
            return trim((string) $value);
        }
        if ($value instanceof \Stringable) {
            return trim((string) $value);
        }
        if (! is_array($value) || $value === []) {
            return '';
        }
        if (array_is_list($value)) {
            return self::string($value[0] ?? '');
        }

        return self::string(
            $value['id']
            ?? $value['title']
            ?? $value['name']
            ?? $value['value']
            ?? $value['text']
            ?? $value['slug']
            ?? reset($value)
        );
    }

    /**
     * @return list<string>
     */
    public static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            $one = self::string($value);

            return $one !== '' ? [$one] : [];
        }
        $out = [];
        foreach ($value as $item) {
            $s = self::string($item);
            if ($s !== '') {
                $out[] = $s;
            }
        }

        return array_values($out);
    }

    /**
     * @return list<string>
     */
    public static function componentIds(mixed $components): array
    {
        $out = [];
        foreach (self::stringList($components) as $id) {
            $id = strtolower($id);
            $id = preg_replace('/^component:/', '', $id) ?? $id;
            if ($id !== '') {
                $out[] = $id;
            }
        }

        return array_values(array_unique($out));
    }
}
