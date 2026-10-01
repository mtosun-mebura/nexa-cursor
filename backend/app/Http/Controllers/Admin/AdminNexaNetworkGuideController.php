<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\NexaMarketplaceFeeCopy;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class AdminNexaNetworkGuideController extends Controller
{
    public function index(): View
    {
        $feePercent = NexaMarketplaceFeeCopy::percent();

        return view('admin.nexa-network.guide', [
            'feePercent' => $feePercent,
            'setupSteps' => $this->setupSteps($feePercent),
            'sections' => $this->sections($feePercent),
            'quickLinks' => $this->quickLinks(),
        ]);
    }

    private function safeRoute(string $name, mixed $parameters = [], string $fallback = '#'): string
    {
        if (! Route::has($name)) {
            return $fallback;
        }

        return route($name, $parameters);
    }

    /**
     * Numbered “do this here” map — exact menu path + deep link.
     *
     * @return list<array{
     *   title: string,
     *   menu: string,
     *   why: string,
     *   do: list<string>,
     *   links: list<array{label: string, url: string, primary?: bool}>
     * }>
     */
    private function setupSteps(int $feePercent): array
    {
        return [
            [
                'title' => 'Network aanzetten (per tenant)',
                'menu' => 'Chauffeur dispatch → NEXA Network',
                'why' => 'Zonder network aan krijgt geen enkele partner offers. Default = uit.',
                'do' => [
                    'Schakel naar de juiste tenant via de tenant-switcher.',
                    'Zet Network inschakelen aan.',
                    'Kies modus: Handmatig of Automatisch.',
                    'Opslaan.',
                ],
                'links' => [
                    [
                        'label' => 'Chauffeur dispatch: NEXA Network',
                        'url' => $this->safeRoute('admin.taxi.dispatch_settings.edit', [], url('/admin/taxi/dispatch-instellingen')).'#dispatch-nexa-network',
                        'primary' => true,
                    ],
                ],
            ],
            [
                'title' => 'Partners koppelen via invite-code',
                'menu' => 'Chauffeur dispatch → Network-partners (onder Opslaan)',
                'why' => 'Privacy: geen bedrijvenlijst. Alleen wie een code deelt, kan gekoppeld worden.',
                'do' => [
                    'Taxi B: kopieer “Jouw invite-code” en deel die (mail/WhatsApp) met Taxi A.',
                    'Taxi A: plak de code bij “Partner koppelen” → verzoek versturen.',
                    'Taxi B: accepteer onder “Inkomende verzoeken” (of zet auto-accept aan).',
                    'Daarna staat B bij A onder Actieve partners; A’s ritten mogen naar B.',
                ],
                'links' => [
                    [
                        'label' => 'Network-partners / invites',
                        'url' => $this->safeRoute('admin.taxi.dispatch_settings.edit', [], url('/admin/taxi/dispatch-instellingen')).'#dispatch-nexa-network-partners',
                        'primary' => true,
                    ],
                ],
            ],
            [
                'title' => 'Fee % (marketplace én network)',
                'menu' => 'Betalingen → NEXA Suite ritten → Instellingen',
                'why' => "Eén percentage voor beide modellen — nu {$feePercent}%. Geen aparte network-fee.",
                'do' => [
                    'Pas Fee over elke gereden NEXA Suite-rit (%) aan indien nodig.',
                    'Network gebruikt bewust hetzelfde percentage.',
                ],
                'links' => [
                    ['label' => 'Fee-instellingen', 'url' => $this->safeRoute('admin.nexa-suite-bookings.settings'), 'primary' => true],
                    ['label' => 'NEXA Suite ritten', 'url' => $this->safeRoute('admin.nexa-suite-bookings.index')],
                ],
            ],
            [
                'title' => 'Payout / KYB per taxi',
                'menu' => 'Betalingen → Payout onboarding',
                'why' => 'Zonder verified payout-identity geen uitbetaling van netto.',
                'do' => [
                    'Zorg dat owner (A) én partner (B) een enabled payout-identity hebben.',
                    'Provider account-ID + gemaskeerde bankbestemming.',
                ],
                'links' => [
                    ['label' => 'Payout onboarding', 'url' => $this->safeRoute('admin.payout-identities.index'), 'primary' => true],
                ],
            ],
            [
                'title' => 'Ritten controleren (owner / uitvoerder)',
                'menu' => 'Nexa Taxi → Ritten',
                'why' => 'Zie of network-claim klopt: company_id = A, fulfilling_company_id = B.',
                'do' => [
                    'Filter of open rit-detail.',
                    'Check badges Network / Settlement.',
                    'Bij review: knop Settlement vrijgeven op rit-detail.',
                ],
                'links' => [
                    [
                        'label' => 'Ritten',
                        'url' => $this->safeRoute('admin.taxi.ride_requests.index', [], url('/admin/taxi/ride_requests')),
                        'primary' => true,
                    ],
                ],
            ],
            [
                'title' => 'Geldflow & settlement-wachtrij',
                'menu' => 'Betalingen → Uitleg betalingen / Settlement-wachtrij',
                'why' => 'Platform collect: fee blijft bij NEXA, netto naar taxi(’s).',
                'do' => [
                    'Lees de geldflow (marketplace vs network-split).',
                    'Bekijk of verwerk de settlement-wachtrij (retry / force paid).',
                ],
                'links' => [
                    ['label' => 'Uitleg betalingen', 'url' => $this->safeRoute('admin.payment-flows.guide'), 'primary' => true],
                    ['label' => 'Settlement-wachtrij', 'url' => $this->safeRoute('admin.payment-flows.settlements')],
                ],
            ],
        ];
    }

    /**
     * @return list<array{id: string, title: string, summary: string, body: list<string>, links?: list<array{label: string, url: string}>}>
     */
    private function sections(int $feePercent): array
    {
        return [
            [
                'id' => 'modellen',
                'title' => 'Drie ritmodellen',
                'summary' => 'Zelfde chauffeur-app, verschillende eigenaar-/uitvoerdersemantiek.',
                'body' => [
                    'Tenant — klant boekt bij Taxi A; eigen vloot rijdt. company_id = A, fulfilling_company_id = leeg.',
                    'Marketplace (NEXA Suite) — klant boekt op nexasuite.nl/boek; dichtstbijzijnde centrale claimt. company_id wordt de claimer; geen fulfiller.',
                    'Network — klant hoort bij Taxi A; bij capaciteitsgebrek mag partner Taxi B uitvoeren. company_id blijft A; fulfilling_company_id = B.',
                ],
                'links' => [
                    ['label' => 'NEXA Suite ritten', 'url' => $this->safeRoute('admin.nexa-suite-bookings.index')],
                    ['label' => 'Fee-instellingen', 'url' => $this->safeRoute('admin.nexa-suite-bookings.settings')],
                ],
            ],
            [
                'id' => 'flow',
                'title' => 'E2E-flow (network)',
                'summary' => 'Van boeking tot settlement-eligible — complete ≠ uitbetaling.',
                'body' => [
                    '1. Klant boekt bij owner (Taxi A) via website of portaal.',
                    '2. Eigen vloot krijgt eerst offers (tenant dispatch).',
                    '3. Als network aan staat (modus manual/auto) én partner-IDs gevuld: partner-offers (Taxi B), zonder ownership te stelen.',
                    '4. Partner-chauffeur accepteert → fulfilling_company_id = B, company_id blijft A.',
                    '5. Rit starten/afronden in chauffeur-app; betaling via bestaande flow.',
                    '6. complete → status completed + settlement-evaluatie → hold / review / rejected (nooit direct eligible).',
                    '7. Cron taxi:release-settlement-holds promoveert due holds → settlement_eligible; review alleen via admin “Settlement vrijgeven”.',
                    '8. Platform-settlement cron maakt ledger + netto-payout; billing/earnings tellen alleen eligible/settled.',
                ],
                'links' => [
                    [
                        'label' => 'Chauffeur dispatch: network + partners',
                        'url' => $this->safeRoute('admin.taxi.dispatch_settings.edit', [], url('/admin/taxi/dispatch-instellingen')).'#dispatch-nexa-network',
                    ],
                    ['label' => 'Settlement-wachtrij', 'url' => $this->safeRoute('admin.payment-flows.settlements')],
                ],
            ],
            [
                'id' => 'partners',
                'title' => 'Partnerlijst via invite-code',
                'summary' => 'Geen directory van tenants. Alleen wie een code deelt, kan gekoppeld worden.',
                'body' => [
                    'Taxi B deelt invite-code → Taxi A plakt die → B accepteert (of auto-accept).',
                    'Daarna: A = owner, B mag A’s network-ritten uitvoeren.',
                    'Wederzijds is optioneel: als B ook A wil inzetten, deelt A een code en B plakt die.',
                    'Super-admin mag nog handmatig IDs zetten (support); tenants zien die lijst niet.',
                ],
                'links' => [
                    [
                        'label' => 'Network-partners / invites',
                        'url' => $this->safeRoute('admin.taxi.dispatch_settings.edit', [], url('/admin/taxi/dispatch-instellingen')).'#dispatch-nexa-network-partners',
                    ],
                ],
            ],
            [
                'id' => 'fee',
                'title' => 'Fee & chauffeur-weergave',
                'summary' => "Zelfde provisie als marketplace: nu {$feePercent}% over ritomzet.",
                'body' => [
                    "Chauffeur ziet bij marketplace/network: Klant betaalt €… · Owner = … · Executor = … · NEXA fee = €… ({$feePercent}%).",
                    'Fee-config: Betalingen → NEXA Suite ritten → Instellingen (fee_percent).',
                    'Network-split na fee: owner/fulfiller via env NEXA_NETWORK_OWNER_SHARE_PERCENT / NEXA_NETWORK_FULFILLER_SHARE_PERCENT.',
                    'Maandfactuur provisie = specificatie/naslag van reeds ingehouden fee (platform collect).',
                ],
                'links' => [
                    ['label' => 'Fee-instellingen', 'url' => $this->safeRoute('admin.nexa-suite-bookings.settings')],
                    ['label' => 'Uitleg betalingen', 'url' => $this->safeRoute('admin.payment-flows.guide')],
                ],
            ],
            [
                'id' => 'settlement',
                'title' => 'Settlement-gate',
                'summary' => 'Driver complete is een claim; uitbetaling vereist eligible.',
                'body' => [
                    'Hold (standaard 24u) — schone rit; auto-release via cron.',
                    'Review — cash, te snel, onwaarschijnlijke afstand, ontbrekende trip-start, of klant “probleem melden”.',
                    'Rejected — payment nog pending.',
                    'Admin rit-detail: Settlement-status + knop “Settlement vrijgeven” (hold/review).',
                ],
                'links' => [
                    [
                        'label' => 'Ritten-overzicht',
                        'url' => $this->safeRoute('admin.taxi.ride_requests.index', [], url('/admin/taxi/ride_requests')),
                    ],
                    ['label' => 'Settlement-wachtrij', 'url' => $this->safeRoute('admin.payment-flows.settlements')],
                ],
            ],
            [
                'id' => 'payout',
                'title' => 'Payout / KYB',
                'summary' => 'Geen raw IBAN/kaart in NEXA — alleen provider-metadata.',
                'body' => [
                    'Per tenant: Betalingen → Payout onboarding.',
                    'Opslaan: provider account-ID + gemaskeerde bestemming (***last4).',
                    'Bestemming wijzigen: wachtwoord + cooling-off; daarna opnieuw verifiëren.',
                    'Independent driver-payouts standaard uit (NEXA_ALLOW_INDEPENDENT_DRIVER_PAYOUTS).',
                ],
                'links' => [
                    ['label' => 'Payout onboarding', 'url' => $this->safeRoute('admin.payout-identities.index')],
                ],
            ],
        ];
    }

    /**
     * @return list<array{label: string, url: string, hint: string}>
     */
    private function quickLinks(): array
    {
        return [
            [
                'label' => 'Chauffeur dispatch: network + partners',
                'url' => $this->safeRoute('admin.taxi.dispatch_settings.edit', [], url('/admin/taxi/dispatch-instellingen')).'#dispatch-nexa-network-partners',
                'hint' => 'Invite-code delen / koppelen',
            ],
            [
                'label' => 'Fee %',
                'url' => $this->safeRoute('admin.nexa-suite-bookings.settings'),
                'hint' => 'Marketplace + network',
            ],
            [
                'label' => 'Payout onboarding',
                'url' => $this->safeRoute('admin.payout-identities.index'),
                'hint' => 'KYB / provider-metadata',
            ],
            [
                'label' => 'Uitleg betalingen',
                'url' => $this->safeRoute('admin.payment-flows.guide'),
                'hint' => 'Platform collect & splits',
            ],
            [
                'label' => 'Settlement-wachtrij',
                'url' => $this->safeRoute('admin.payment-flows.settlements'),
                'hint' => 'Ledgers + retry payout',
            ],
            [
                'label' => 'Ritten',
                'url' => $this->safeRoute('admin.taxi.ride_requests.index', [], url('/admin/taxi/ride_requests')),
                'hint' => 'Owner / fulfiller / settlement',
            ],
            [
                'label' => 'Bedrijven',
                'url' => $this->safeRoute('admin.companies.index'),
                'hint' => 'Tenant-IDs voor partners',
            ],
            [
                'label' => 'NEXA Suite ritten',
                'url' => $this->safeRoute('admin.nexa-suite-bookings.index'),
                'hint' => 'Marketplace fee & facturen',
            ],
        ];
    }
}
