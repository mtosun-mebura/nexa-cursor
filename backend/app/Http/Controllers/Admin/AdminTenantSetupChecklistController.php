<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Support\Admin\AdminTenantScope;
use App\Support\CoolifyVpsPublicIp;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminTenantSetupChecklistController extends Controller
{
    public function index(AdminTenantScope $tenantScope): View
    {
        $vpsIp = CoolifyVpsPublicIp::get();
        $company = $this->resolveExampleCompany($tenantScope);
        $example = $this->domainExampleFromCompany($company);

        return view('admin.tenant-setup-checklist.index', [
            'steps' => $this->steps($vpsIp, $example, $company),
            'vpsIp' => $vpsIp,
            'exampleCompany' => $company,
            'exampleDomain' => $example['domain'],
        ]);
    }

    private function resolveExampleCompany(AdminTenantScope $tenantScope): ?Company
    {
        $id = $tenantScope->selectedTenantId();
        if ($id === null) {
            return null;
        }

        return Company::query()->find($id);
    }

    /**
     * @return array{domain: string, www: string, slug_host: string, label: string}
     */
    private function domainExampleFromCompany(?Company $company): array
    {
        $label = $company?->name ? trim((string) $company->name) : 'Taxi Nexa';
        $slug = $company?->slug
            ? Str::slug((string) $company->slug)
            : Str::slug($label);
        if ($slug === '') {
            $slug = 'taxinexa';
        }

        // Hostnaam-voorbeeld: bedrijfsnaam aaneen zonder spaties/tekens + .nl (Taxi Nexa → taxinexa.nl).
        $base = strtolower(preg_replace('/[^a-z0-9]+/i', '', $label) ?: 'taxinexa');
        $domain = $base.'.nl';

        return [
            'domain' => $domain,
            'www' => 'www.'.$domain,
            'slug_host' => $slug.'.nexasuite.nl',
            'label' => $label,
        ];
    }

    /**
     * @param  array{domain: string, www: string, slug_host: string, label: string}  $example
     * @return list<array{id: string, title: string, summary: string, items: list<array{text?: string, before?: string, link_url?: string, link_label?: string, after?: string}>, tip?: string}>
     */
    private function steps(string $vpsIp, array $example, ?Company $company): array
    {
        $wizardUrl = route('admin.companies.wizard.start');
        $companiesUrl = route('admin.companies.index');
        $settingsUrl = route('admin.settings.index');
        $settingsFromChecklistUrl = route('admin.settings.index', ['from' => 'tenant-setup']);
        $upgradeUrl = route('admin.settings.upgrade.index');
        $domainsUrl = $company
            ? route('admin.companies.show', $company).'#company-domains'
            : $companiesUrl;
        $wizardDomainUrl = $company
            ? route('admin.companies.wizard.step', [$company, 3])
            : $wizardUrl;

        $domain = $example['domain'];
        $www = $example['www'];
        $slugHost = $example['slug_host'];
        $label = $example['label'];

        return [
            [
                'id' => 'create-tenant',
                'title' => 'Nieuwe tenant-wizard starten',
                'summary' => 'Doorloop de Nieuwe tenant-wizard. Bij de stap Domein vul je later het custom domein in (na DNS en Coolify).',
                'items' => [
                    [
                        'before' => 'Open de ',
                        'link_url' => $wizardUrl,
                        'link_label' => 'Nieuwe tenant-wizard',
                        'after' => ' (Dashboard → Bedrijven → Nieuwe tenant).',
                    ],
                    [
                        'text' => 'Doorloop stap Bedrijf en Vestigingen volledig.',
                    ],
                    [
                        'before' => 'Bij stap ',
                        'link_url' => $wizardDomainUrl,
                        'link_label' => 'Domein',
                        'after' => ': vul bij Hostnaam bijvoorbeeld '.$domain.' (van “'.$label.'”: naam aaneen + .nl) en eventueel '.$www.'.',
                    ],
                    [
                        'text' => 'Noteer de slug: die wordt '.$slugHost.' en werkt al zonder custom domein.',
                    ],
                ],
                'tip' => 'Zonder custom domein kun je de tenant al testen op https://'.$slugHost.'.',
            ],
            [
                'id' => 'dns-a',
                'title' => 'DNS A-records bij de registrar',
                'summary' => 'Laat apex en www naar de Coolify-VPS wijzen. Zonder dit faalt Coolify/SSL.',
                'items' => [
                    [
                        'text' => 'Open DNS bij de registrar (bijv. one.com, Hostinger).',
                    ],
                    [
                        'before' => 'A-record voor het apex-domein ('.$domain.') → ',
                        'after' => $vpsIp.'.',
                    ],
                    [
                        'text' => 'A-record voor www ('.$www.') → '.$vpsIp.'.',
                    ],
                    [
                        'text' => 'Optioneel: A-record * (wildcard) → '.$vpsIp.', alleen als je subdomeinen op dat merk wilt.',
                    ],
                    [
                        'before' => 'IP wijzigen kan onder ',
                        'link_url' => $upgradeUrl,
                        'link_label' => 'Upgrade (VPS-IP)',
                        'after' => '.',
                    ],
                    [
                        'text' => 'Wacht tot DNS is doorgevoerd (vaak enkele minuten, soms tot een uur).',
                    ],
                ],
                'tip' => 'Controleer met dig +short '.$www.' — het moet '.$vpsIp.' tonen.',
            ],
            [
                'id' => 'dns-mx',
                'title' => 'MX-records (en mail-authenticatie)',
                'summary' => 'Nodig als de tenant e-mail wil ontvangen/verzenden vanaf het eigen domein.',
                'items' => [
                    [
                        'text' => 'Zet MX-records volgens de mailprovider van de klant (one.com, Google Workspace, Microsoft 365, …).',
                    ],
                    [
                        'text' => 'Voeg SPF (TXT) toe zoals de mailprovider voorschrijft.',
                    ],
                    [
                        'text' => 'Voeg DKIM (TXT) toe als de provider dat levert.',
                    ],
                    [
                        'text' => 'Optioneel: DMARC (TXT) voor strengere mailbeveiliging.',
                    ],
                    [
                        'before' => 'In Nexa Suite: tenant-mailserver of SMTP-provider instellen onder ',
                        'link_url' => $settingsFromChecklistUrl,
                        'link_label' => 'Configuraties / Instellingen',
                        'after' => '.',
                    ],
                ],
                'tip' => 'MX/SPF/DKIM raken de website niet; je kunt dit parallel doen, maar wacht niet met Coolify tot mail “perfect” is.',
            ],
            [
                'id' => 'coolify-domains',
                'title' => 'Domeinen toevoegen in Coolify',
                'summary' => 'Traefik moet de Host kennen. Alleen DNS + Nexa Suite is niet genoeg — anders zie je “no available server”.',
                'items' => [
                    [
                        'text' => 'Open Coolify (panel) → Projects → Nexa Suite-app (backend).',
                    ],
                    [
                        'text' => 'Ga naar Domains.',
                    ],
                    [
                        'text' => 'Voeg toe: https://'.$domain.':8000 en https://'.$www.':8000 (poort 8000 verplicht).',
                    ],
                    [
                        'text' => 'Laat nexasuite.nl / www.nexasuite.nl staan; voeg géén *.nexasuite.nl als los Domain toe.',
                    ],
                    [
                        'text' => 'Klik Save / Apply.',
                    ],
                ],
                'tip' => 'Geen spaties achter hostnames. Poort :8000 moet in Coolify staan.',
            ],
            [
                'id' => 'coolify-https',
                'title' => 'HTTPS / Let’s Encrypt activeren',
                'summary' => 'Zonder geldig certificaat toont de browser “Niet beveiligd” of TRAEFIK DEFAULT CERT.',
                'items' => [
                    [
                        'text' => 'Controleer dat de domeinen met https:// in Coolify staan (dat triggert Let’s Encrypt).',
                    ],
                    [
                        'text' => 'Zet tijdelijk Redirect HTTP → HTTPS uit als het certificaat niet komt (ACME-challenge moet HTTP bereiken).',
                    ],
                    [
                        'text' => 'Klik Redeploy / Deploy op de Nexa-app (rechtsboven of tab Deployments).',
                    ],
                    [
                        'text' => 'Wacht 1–2 minuten. Controleer het slotje: issuer moet Let’s Encrypt zijn, niet TRAEFIK DEFAULT CERT.',
                    ],
                    [
                        'text' => 'Zet Redirect HTTP → HTTPS daarna weer aan.',
                    ],
                ],
                'tip' => $slugHost.' deelt het wildcard-certificaat; '.$domain.' heeft altijd een eigen certificaat nodig.',
            ],
            [
                'id' => 'nexa-domains',
                'title' => 'Domeinen afronden in Nexa Suite',
                'summary' => 'Laravel moet weten welke tenant bij welk Host-header hoort (wizard stap Domein of via Bedrijven).',
                'items' => [
                    [
                        'before' => 'Open ',
                        'link_url' => $domainsUrl,
                        'link_label' => $company ? 'Domeinen van '.$label : 'Bedrijven',
                        'after' => $company ? ' en vul bij Hostnaam bijvoorbeeld '.$domain.' in.' : ', kies de tenant en ga naar Domeinen.',
                    ],
                    [
                        'before' => 'Of hervat de ',
                        'link_url' => $wizardDomainUrl,
                        'link_label' => 'Nieuwe tenant-wizard (stap Domein)',
                        'after' => '.',
                    ],
                    [
                        'text' => 'Voeg apex toe ('.$domain.') en www ('.$www.').',
                    ],
                    [
                        'text' => 'Zet het gewenste domein op primair.',
                    ],
                    [
                        'text' => 'Laat '.$slugHost.' staan als fallback/admin-URL.',
                    ],
                ],
                'tip' => 'Voor “'.$label.'” is het Hostnaam-voorbeeld '.$domain.' (naam aaneen zonder spaties + .nl).',
            ],
            [
                'id' => 'whatsapp-tenant',
                'title' => 'WhatsApp-contactnummer van de tenant',
                'summary' => 'Zet het juiste WhatsApp-nummer voor de website-widget en boekingsmeldingen van deze tenant.',
                'items' => [
                    [
                        'text' => 'Selecteer de tenant in de zijbalk als dat nog niet gebeurd is.',
                    ],
                    [
                        'before' => 'Open ',
                        'link_url' => route('admin.settings.index', ['from' => 'tenant-setup', 'open' => 'whatsapp']).'#whatsapp',
                        'link_label' => 'Configuraties → WhatsApp (tenant)',
                        'after' => '.',
                    ],
                    [
                        'text' => 'Vul het widget-telefoonnummer in — dat is het contactnummer op de website.',
                    ],
                    [
                        'text' => 'Vul eventueel het WhatsApp-nummer bedrijf in voor boekingsmeldingen (dispatch).',
                    ],
                    [
                        'text' => 'Zet “Widget tonen op frontend” aan als bezoekers via WhatsApp moeten kunnen chatten.',
                    ],
                    [
                        'text' => 'Sla op met “WhatsApp tenant opslaan”.',
                    ],
                ],
                'tip' => 'De Business API (token / Phone Number ID) blijft platform-breed onder Algemene configuraties; per tenant alleen de contactnummers.',
            ],
            [
                'id' => 'verify',
                'title' => 'Eindcontrole',
                'summary' => 'Pas klaar als website én (indien van toepassing) mail kloppen.',
                'items' => [
                    [
                        'text' => 'Open https://'.$www.' — site laadt, geldig Let’s Encrypt-certificaat.',
                    ],
                    [
                        'text' => 'Open https://'.$domain.' — zelfde tenant, eventueel redirect naar www of apex.',
                    ],
                    [
                        'text' => 'Open https://'.$slugHost.' — blijft werken.',
                    ],
                    [
                        'before' => 'Stuur een testmail; SMTP kun je controleren onder ',
                        'link_url' => $settingsFromChecklistUrl,
                        'link_label' => 'Instellingen',
                        'after' => '.',
                    ],
                    [
                        'text' => 'Controleer op de website dat het WhatsApp-icoon het juiste contactnummer opent.',
                    ],
                ],
            ],
        ];
    }
}
