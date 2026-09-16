<?php

namespace App\Support;

/**
 * Publieke website-copy: intern woord “tenant” niet tonen.
 */
class NexaPublicCopy
{
    /**
     * @param  array<string, mixed>  $sections
     * @return array<string, mixed>
     */
    public static function replaceTenantWordingIn(array $sections): array
    {
        foreach ($sections as $key => $value) {
            if (is_string($value)) {
                $sections[$key] = self::replaceTenantWording($value);
            } elseif (is_array($value)) {
                $sections[$key] = self::replaceTenantWordingIn($value);
            }
        }

        return $sections;
    }

    public static function replaceTenantWording(string $text): string
    {
        if ($text === '' || ! str_contains(mb_strtolower($text), 'tenant')) {
            return $text;
        }

        $replaced = strtr($text, [
            'Tenantomgevingen' => 'Klantomgevingen',
            'tenantomgevingen' => 'klantomgevingen',
            'Tenant-sites' => 'Klantwebsites',
            'tenant-sites' => 'klantwebsites',
            'Tenant-site' => 'Klantwebsite',
            'tenant-site' => 'klantwebsite',
            'Multi-tenant basis' => 'Meerdere klanten',
            'multi-tenant basis' => 'meerdere klanten',
            'Multi-tenant' => 'Meerdere klanten',
            'multi-tenant' => 'meerdere klanten',
            'Jouw tenant' => 'Jouw omgeving',
            'jouw tenant' => 'jouw omgeving',
            'of tenant' => '',
            'Per tenant' => 'Per klant',
            'per tenant' => 'per klant',
            'Extra tenants' => 'Extra klanten',
            'extra tenants' => 'extra klanten',
        ]);

        return (string) preg_replace_callback(
            '/\b(tenants?)\b/iu',
            static function (array $match): string {
                $word = $match[1];
                $plural = str_ends_with(mb_strtolower($word), 's');
                $upper = $word !== '' && mb_strtoupper(mb_substr($word, 0, 1)) === mb_substr($word, 0, 1);

                if ($plural) {
                    return $upper ? 'Klanten' : 'klanten';
                }

                return $upper ? 'Klant' : 'klant';
            },
            $replaced
        );
    }
}
