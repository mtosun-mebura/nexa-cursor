<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

class AdminExplanationCatalog
{
    /**
     * Bestaande uitleg- en werkwijzepagina's, gegroepeerd voor het super-admin-overzicht.
     *
     * @return list<array{title: string, intro: string, items: list<array{title: string, summary: string, url: string}>}>
     */
    public static function groups(): array
    {
        $pages = AdminHandleiding::pages();
        $used = [];

        $take = function (string $slug, ?string $summary = null, ?string $fragment = null) use ($pages, &$used): ?array {
            if (! isset($pages[$slug]) || isset($used[$slug])) {
                return null;
            }
            $used[$slug] = true;
            $page = $pages[$slug];

            return self::item(
                'admin.handleiding.show',
                (string) ($page['title'] ?? $slug),
                $summary ?? (string) ($page['summary'] ?? ''),
                ['slug' => $slug],
                $fragment
            );
        };

        $groups = [
            [
                'title' => 'App',
                'intro' => 'Native app en de webhandleidingen voor chauffeur en contract.',
                'items' => array_filter([
                    $take('app', 'Volledige werking van de native app: klant, chauffeur, contractant en contractouder.', 'klant'),
                    self::item('taxi.chauffeur.handleiding', 'Handleiding chauffeur (web)', 'Stappen van de chauffeur-webapp: inbox, starten en afronden.'),
                    self::item('taxi.contract.handleiding', 'Handleiding contract (web)', 'Stappen van het contractportaal: ritten, afmelden en navigatie.'),
                    $take('chauffeur-app'),
                ]),
            ],
            [
                'title' => 'Boeken en ritten',
                'intro' => 'Van boeking tot dispatch, inclusief de marktplaats-werkwijze.',
                'items' => array_filter([
                    self::item('admin.nexa-suite-bookings.procedure', 'Werkwijze marktplaats', 'Ophaalcode, actieve rit en uitbetaling voor NEXA Suite-ritten.'),
                    $take('ritten'),
                    $take('dispatch'),
                    $take('website-en-boekingen'),
                    $take('nexa-suite-ritten'),
                    $take('gps-tracker'),
                    $take('voertuigen-en-tarieven'),
                ]),
            ],
            [
                'title' => 'Betalen',
                'intro' => 'Hoe geld binnenkomt, wordt vastgehouden en wordt uitbetaald.',
                'items' => array_filter([
                    self::item('admin.payment-flows.guide', 'Uitleg betalingen', 'Platform-incasso, fee, ledger en uitbetaling voor marketplace en network.'),
                    self::item('admin.payment-flows.settlements', 'Settlement-wachtrij', 'Wachtrij van uitbetalingen die bij de betalingsuitleg hoort.'),
                    self::item('admin.payout-identities.index', 'Payout onboarding', 'Bankrekening koppelen zodat netto naar het taxibedrijf kan.'),
                    $take('betalingen'),
                    $take('facturen'),
                    $take('nexa-facturatie'),
                    self::item('admin.platform-billing.invoices.index', 'NEXA-facturen', 'Bovenaan staat de werkwijze van de platformfacturatie naar tenants.'),
                ]),
            ],
            [
                'title' => 'Contractvervoer',
                'intro' => 'Vaste ritten in de admin en in het portaal voor ouders en opdrachtgevers.',
                'items' => array_filter([
                    $take('contractvervoer'),
                    $take('contractportaal'),
                ]),
            ],
            [
                'title' => 'Platform en super-admin',
                'intro' => 'Tenant, network, website en pakketten. Alleen zichtbaar voor super-admins.',
                'items' => array_filter([
                    self::item('admin.uitleg', 'Uitlegoverzicht', 'Deze pagina: alle plekken met werkwijze of uitleg.'),
                    self::item('admin.nexa-network.guide', 'NEXA Network', 'Tenant, marketplace en network, met settlement-gate en fee.'),
                    self::item('admin.tenant-setup-checklist', 'Tenant configureren', 'Checklist voor DNS, Coolify en het koppelen van een nieuwe tenant.'),
                    self::item('admin.handleiding.index', 'Handleiding', 'Overzicht van alle handleiding-hoofdstukken voor de ingelogde admin.'),
                    $take('bedrijven'),
                    $take('frontend-en-ai'),
                    $take('nexa-network'),
                    $take('paketten'),
                ]),
            ],
        ];

        $rest = [];
        foreach ($pages as $slug => $page) {
            if (isset($used[$slug])) {
                continue;
            }
            $item = $take($slug);
            if ($item) {
                $rest[] = $item;
            }
        }

        if ($rest !== []) {
            $groups[] = [
                'title' => 'Overige handleiding',
                'intro' => 'Hoofdstukken die niet in de groepen hierboven vallen.',
                'items' => $rest,
            ];
        }

        $help = self::item('help', 'Help en FAQ', 'Publieke hulppagina. De tekst gaat over skillmatching, niet over de taxi-app.');
        if ($help) {
            $groups[] = [
                'title' => 'Publiek',
                'intro' => 'Uitleg buiten de admin.',
                'items' => [$help],
            ];
        }

        return array_values(array_filter($groups, fn (array $group) => $group['items'] !== []));
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{title: string, summary: string, url: string}|null
     */
    private static function item(string $route, string $title, string $summary, array $params = [], ?string $fragment = null): ?array
    {
        if (! Route::has($route)) {
            return null;
        }

        $url = route($route, $params);
        if ($fragment) {
            $url .= '#'.$fragment;
        }

        return [
            'title' => $title,
            'summary' => $summary,
            'url' => $url,
        ];
    }
}
