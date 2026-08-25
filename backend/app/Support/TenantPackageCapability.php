<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Machine-readable functies per NEXA-pakket.
 *
 * Sleutels blijven stabiel zodat applicatiecode hierop kan toetsen, bijvoorbeeld:
 *   app(CompanyEntitlementService::class)->allows($company, TenantPackageCapability::MOLLIE_PAYMENTS)
 *   app(CompanyEntitlementService::class)->maxDrivers($company)
 */
final class TenantPackageCapability
{
    public const MAX_DRIVERS = 'max_drivers';

    public const WEBSITE_BOOKING = 'website_booking';

    public const MOLLIE_PAYMENTS = 'mollie_payments';

    public const INVOICE_PDF = 'invoice_pdf';

    public const DISPATCH = 'dispatch';

    public const DRIVER_APP = 'driver_app';

    public const CONTRACT_TRANSPORT = 'contract_transport';

    public const CONTRACT_PORTAL = 'contract_portal';

    public const MULTIPLE_ADMINS = 'multiple_admins';

    public const MONTHLY_INVOICE_SEPA = 'monthly_invoice_sepa';

    public const MAX_CONTRACT_CLIENTS = 'max_contract_clients';

    public const GPS_TRACKING = 'gps_tracking';

    public const TYPE_LIMIT = 'limit';

    public const TYPE_BOOL = 'bool';

    /**
     * @return list<array{key: string, type: string, label: string, hint: string, code: string}>
     */
    public static function definitions(): array
    {
        return [
            [
                'key' => self::MAX_DRIVERS,
                'type' => self::TYPE_LIMIT,
                'label' => 'Maximum chauffeurs',
                'hint' => 'Aantal chauffeur-accounts dat dit bedrijf mag aanmaken. Onbeperkt = geen limiet. Bij overschrijding krijgt de gebruiker een melding.',
                'code' => 'TenantPackageCapability::MAX_DRIVERS',
            ],
            [
                'key' => self::WEBSITE_BOOKING,
                'type' => self::TYPE_BOOL,
                'label' => 'Website met boekingsmodule',
                'hint' => 'Online ritaanvragen via de tenant-website.',
                'code' => 'TenantPackageCapability::WEBSITE_BOOKING',
            ],
            [
                'key' => self::MOLLIE_PAYMENTS,
                'type' => self::TYPE_BOOL,
                'label' => 'Betalen via Mollie',
                'hint' => 'Online betalen (boeking of QR in de chauffeur-app). Uit = Mollie mag niet worden aangezet voor dit pakket.',
                'code' => 'TenantPackageCapability::MOLLIE_PAYMENTS',
            ],
            [
                'key' => self::INVOICE_PDF,
                'type' => self::TYPE_BOOL,
                'label' => 'Factuur als pdf naar de klant',
                'hint' => 'Chauffeur of beheerder mag een ritfactuur als pdf naar de klant sturen.',
                'code' => 'TenantPackageCapability::INVOICE_PDF',
            ],
            [
                'key' => self::DISPATCH,
                'type' => self::TYPE_BOOL,
                'label' => 'Dispatch (toewijzen en opnieuw uitzetten)',
                'hint' => 'Ritten toewijzen aan chauffeurs vanuit de centrale.',
                'code' => 'TenantPackageCapability::DISPATCH',
            ],
            [
                'key' => self::DRIVER_APP,
                'type' => self::TYPE_BOOL,
                'label' => 'Chauffeur-app',
                'hint' => 'Inbox, rit starten en afronden in de chauffeur-app.',
                'code' => 'TenantPackageCapability::DRIVER_APP',
            ],
            [
                'key' => self::CONTRACT_TRANSPORT,
                'type' => self::TYPE_BOOL,
                'label' => 'Contractvervoer',
                'hint' => 'Vaste ritten: school, zorg, zakelijk of privé.',
                'code' => 'TenantPackageCapability::CONTRACT_TRANSPORT',
            ],
            [
                'key' => self::CONTRACT_PORTAL,
                'type' => self::TYPE_BOOL,
                'label' => 'Contractportaal (ouder / opdrachtgever)',
                'hint' => 'App voor afmelden van–tot en weekoverzicht.',
                'code' => 'TenantPackageCapability::CONTRACT_PORTAL',
            ],
            [
                'key' => self::MULTIPLE_ADMINS,
                'type' => self::TYPE_BOOL,
                'label' => 'Meerdere beheerders',
                'hint' => 'Meer dan één company-admin voor hetzelfde bedrijf.',
                'code' => 'TenantPackageCapability::MULTIPLE_ADMINS',
            ],
            [
                'key' => self::MONTHLY_INVOICE_SEPA,
                'type' => self::TYPE_BOOL,
                'label' => 'Maandfactuur en SEPA',
                'hint' => 'Periodieke facturatie en SEPA-mandaat voor contractvervoer (niet de automatische bankincasso).',
                'code' => 'TenantPackageCapability::MONTHLY_INVOICE_SEPA',
            ],
            [
                'key' => self::MAX_CONTRACT_CLIENTS,
                'type' => self::TYPE_LIMIT,
                'label' => 'Maximum contractklanten',
                'hint' => 'Actieve contractklanten (school, zorg, bedrijf, privé). 0 = geen. Business standaard 10; extra bundels en Vloot tel je per bedrijf bij de aanvullende modules.',
                'code' => 'TenantPackageCapability::MAX_CONTRACT_CLIENTS',
            ],
        ];
    }

    /**
     * @return array<string, int|bool>
     */
    public static function defaultsForKey(string $key): array
    {
        return match ($key) {
            'start' => [
                self::MAX_DRIVERS => 3,
                self::WEBSITE_BOOKING => true,
                self::MOLLIE_PAYMENTS => false,
                self::INVOICE_PDF => true,
                self::DISPATCH => false,
                self::DRIVER_APP => false,
                self::CONTRACT_TRANSPORT => false,
                self::CONTRACT_PORTAL => false,
                self::MULTIPLE_ADMINS => false,
                self::MONTHLY_INVOICE_SEPA => false,
                self::MAX_CONTRACT_CLIENTS => 0,
            ],
            'pro' => [
                self::MAX_DRIVERS => 0,
                self::WEBSITE_BOOKING => true,
                self::MOLLIE_PAYMENTS => true,
                self::INVOICE_PDF => true,
                self::DISPATCH => true,
                self::DRIVER_APP => true,
                self::CONTRACT_TRANSPORT => false,
                self::CONTRACT_PORTAL => false,
                self::MULTIPLE_ADMINS => true,
                self::MONTHLY_INVOICE_SEPA => false,
                self::MAX_CONTRACT_CLIENTS => 0,
            ],
            'business' => [
                self::MAX_DRIVERS => 0,
                self::WEBSITE_BOOKING => true,
                self::MOLLIE_PAYMENTS => true,
                self::INVOICE_PDF => true,
                self::DISPATCH => true,
                self::DRIVER_APP => true,
                self::CONTRACT_TRANSPORT => true,
                self::CONTRACT_PORTAL => true,
                self::MULTIPLE_ADMINS => true,
                self::MONTHLY_INVOICE_SEPA => true,
                self::MAX_CONTRACT_CLIENTS => 10,
            ],
            default => [
                self::MAX_DRIVERS => 3,
                self::WEBSITE_BOOKING => true,
                self::MOLLIE_PAYMENTS => false,
                self::INVOICE_PDF => true,
                self::DISPATCH => false,
                self::DRIVER_APP => false,
                self::CONTRACT_TRANSPORT => false,
                self::CONTRACT_PORTAL => false,
                self::MULTIPLE_ADMINS => false,
                self::MONTHLY_INVOICE_SEPA => false,
                self::MAX_CONTRACT_CLIENTS => 0,
            ],
        };
    }

    public static function slugFromName(string $name): string
    {
        $slug = Str::slug($name);
        if ($slug === '') {
            $slug = 'pakket';
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, int|bool>
     */
    public static function normalize(array $raw, string $packageKey): array
    {
        $defaults = self::defaultsForKey($packageKey);
        $driversUnlimited = filter_var($raw['max_drivers_unlimited'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $out = [];
        foreach (self::definitions() as $definition) {
            $key = $definition['key'];
            $posted = $raw[$key] ?? null;
            if ($definition['type'] === self::TYPE_LIMIT) {
                if ($key === self::MAX_DRIVERS && $driversUnlimited) {
                    $out[$key] = 0;
                    continue;
                }
                if ($posted === null || $posted === '') {
                    $out[$key] = (int) $defaults[$key];
                    continue;
                }
                $out[$key] = max(0, (int) $posted);
                continue;
            }
            if ($posted === null) {
                $out[$key] = (bool) $defaults[$key];
                continue;
            }
            $out[$key] = filter_var($posted, FILTER_VALIDATE_BOOLEAN);
        }

        return $out;
    }
}
