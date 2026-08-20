<?php

namespace App\Services\AiChat;

use App\Models\WebsitePage;
use App\Services\NexaContactAanvraagEmailTemplateService;
use App\Services\NexaPricingService;
use Illuminate\Support\Str;

/**
 * Antwoorden voor bezoekers van de NEXA SaaS-website, op basis van de publieke paginateksten.
 */
final class AiChatProductFaqService
{
    public function answer(string $message): string
    {
        $text = $this->normalize($message);
        if ($text === '') {
            return $this->fallback();
        }

        $contact = $this->contactLine();
        $pricing = app(NexaPricingService::class);

        $matchedPackages = $this->matchedPackageNames($text, $pricing);
        if (count($matchedPackages) === 1) {
            return $pricing->faqPackageAnswer($matchedPackages[0], $contact);
        }
        if (count($matchedPackages) > 1) {
            return $pricing->faqPackagesOverview($contact);
        }

        foreach ($this->topics($contact, $pricing) as $topic) {
            if ($this->matches($text, $topic['needles'])) {
                return $topic['answer'];
            }
        }

        $fromWebsite = $this->answerFromWebsitePages($message);
        if ($fromWebsite !== null) {
            return $fromWebsite;
        }

        return $this->fallback();
    }

    /**
     * @return list<array{needles: list<string>, answer: string}>
     */
    private function topics(string $contact, NexaPricingService $pricing): array
    {
        return [
            [
                'needles' => [
                    'contact', 'telefoon', 'telefoonnummer', 'e-mail', 'email', 'mailen',
                    'bereikbaar', 'wie moet ik', 'met wie', 'aanvraag', 'formulier',
                    'geinteresseerd', 'interesse', 'afspraak', 'gesprek', 'offerte aanvragen',
                    'plan een gesprek', 'teruggebeld', 'terugbellen', 'sales',
                ],
                'answer' => "Fijn dat je interesse hebt. Stuur een korte aanvraag via [het contactformulier](/contact); we kijken naar jouw ritten en nemen contact op over onboarding of een voorstel.\n\nJe kunt ons ook mailen: {$contact}.",
            ],
            [
                'needles' => [
                    'website live', 'live zetten', 'eenmalig', 'eenmalige', 'opzetkosten',
                    'setupkosten', 'setup kosten', 'websiteprijs', 'website prijs',
                ],
                'answer' => $pricing->faqWebsiteAnswer($contact),
            ],
            [
                'needles' => [
                    'wat kost', 'kosten', 'prijs', 'prijzen', 'tarief', 'tarieven', 'hoe duur',
                    'abonnement', 'licentie', 'maandelijks', 'investering', 'prijslijst',
                    'pakket', 'pakketten', 'welk pakket', 'welke pakketten', 'abonnementen',
                    'maandbedrag', 'maandprijs',
                ],
                'answer' => $pricing->faqPackagesOverview($contact),
            ],
            [
                'needles' => [
                    'nadeel', 'nadelen', 'voordeel', 'voordelen', 'waarom nexa', 'waarom kiezen',
                    'verschil', 'ten opzichte', 'herkenbaar', 'probleem', 'problemen',
                    'lost nexa', 'daarom',
                ],
                'answer' => "Veel taxibedrijven herkennen dit: klanten haken af als ze niet online kunnen boeken, chauffeurs sturen via WhatsApp en vaste ritten staan in Excel. NEXA Suite zet daar één platform tegenover, in jouw merk.\n\n**Wat het oplost**\n- Boekingsmodule op jouw website: 24/7 een rit vastleggen, minder gemiste calls.\n- Dispatch + chauffeur-app: accepteer, rijd en rond af — geen screenshots in WhatsApp.\n- Contractportaal: school, zorg of zakelijk; afmelden van–tot, de rit past zich aan.\n- Website, boeking en ritten in één systeem, klaar om te groeien.\n\nWil je dit voor jouw bedrijf nalopen? Stuur een aanvraag via [contact](/contact).",
            ],
            [
                'needles' => [
                    'contract-app', 'contract app', 'contractportaal', 'ouder', 'ouders',
                    'contractant', 'opdrachtgever', 'zorgcoordinator', 'zorgcoördinator',
                ],
                'answer' => "De contract-app is er voor wie de rit volgt: de ouder én de opdrachtgever.\n\n**Voor de ouder.** ’s Ochtends kijken of het kind meegaat, heen en terug in één oogopslag. Kind ziek of een vrije dag? Afmelden van–tot, zonder de centrale te bellen. De chauffeur rijdt die stop niet meer.\n\n**Voor de opdrachtgever.** School, zorgcoördinator of bedrijfscontact ziet de hele groep: wie gaat mee, wie is afgemeld, hoe de week eruitziet. Een studiedag of vakantieweek in één keer doorgeven.\n\nInstalleren gaat zonder App Store: openen in Safari of Chrome en toevoegen aan het startscherm. Werkt op iPhone, iPad en Android. Meer: [Contractvervoer](/contractvervoer). Interesse: [contact](/contact).",
            ],
            [
                'needles' => [
                    'contractvervoer', 'contract', 'leerlingenvervoer', 'school', 'zorgcontract',
                    'ziekenhuis', 'wmo', 'vast werk', 'vaste ritten', 'afmelden', 'opdrachtgever',
                    'maandfactuur', 'excel',
                ],
                'answer' => "Contractvervoer is voor vaste ritten zonder Excel: leerlingenvervoer, zorg- en ziekenhuisritten, zakelijk en privé.\n\nOuders en opdrachtgevers volgen de rit in de contract-app: vandaag, de week, heen/retour, afmelden van–tot. De chauffeur rijdt geen loze stop. Jij factureert per maand (pdf of e-mail, eventueel SEPA).\n\nDe app zetten ze zelf op het beginscherm van iPhone of Android — geen download in de App Store. Je start met taxi en voegt contractvervoer toe wanneer je klaar bent. Meer: [Contractvervoer](/contractvervoer). Interesse: [contact](/contact).",
            ],
            [
                'needles' => [
                    'beginscherm', 'startscherm', 'app store', 'google play', 'ios', 'iphone',
                    'ipad', 'android', 'installeren', 'als app', 'pwa',
                ],
                'answer' => "De chauffeur-app en de contract-app voel je als een echte app, zonder gedoe in de App Store of Google Play.\n\nOpen de app één keer in Safari (iPhone/iPad) of Chrome (Android) en kies “Zet op beginscherm” of “Toevoegen aan startscherm”. Daarna staat er een icoon klaar: tikken en je zit in het scherm.\n\nChauffeurs zien ritten, accepteren en ronden af. Ouders en opdrachtgevers zien vandaag en de week, en melden af van–tot. Updates komen vanzelf; jullie merk blijft op de voorgrond.\n\nMeer over de chauffeur-app: [Nexa Taxi](/taxi). Meer over de contract-app: [Contractvervoer](/contractvervoer).",
            ],
            [
                'needles' => [
                    'chauffeur-app', 'chauffeur app', 'chauffeursapp', 'in de auto', 'dispatch',
                    'toewijzen', 'accepteer', 'afronden',
                ],
                'answer' => "De chauffeur-app hoort bij Nexa Taxi: van dispatch tot het scherm in de auto.\n\nChauffeurs zetten hem op het beginscherm van iPhone of Android — één keer openen, “Zet op beginscherm”, klaar. Geen App Store, wel een icoon naast WhatsApp. In de auto: nieuwe ritten in de inbox, accepteren of afwijzen, starten, navigeren, stops, afronden. Betalen via QR-code of contant. Daarna kan de factuur direct als pdf naar het e-mailadres van de klant, met een betaald-indicatie — handig voor de administratie van de klant.\n\nGeen groepsapp die volloopt. Updates komen vanzelf. Meer: [Nexa Taxi](/taxi). Interesse: [contact](/contact).",
            ],
            [
                'needles' => [
                    'website builder', 'website-module', 'eigen website', 'eigen site', 'cms',
                    'merkkleuren', 'jouw domein', 'boekingsknop', 'pagina bouwen',
                ],
                'answer' => "Je website zit standaard bij NEXA Suite. Pagina’s, thema en SEO staan in hetzelfde platform als je ritten — geen extra CMS en geen extra bouwer.\n\n**Wat je krijgt**\n- Eigen website builder: componenten per pagina in- of uitschakelen en instellen (hero, formulier, galerij, prijzen).\n- Automatische SEO per pagina: unieke titel, meta-omschrijving en sitemap, zodat Google je taxisite kan vinden.\n- Google Maps: vestiging, adres en route op je site.\n- Jouw logo, kleuren en teksten. De boekingsmodule staat op dezelfde pagina: van “ik wil een taxi” naar een vastgelegde rit, in jouw merk.\n\nMeer: [Website](/website). Prijzen: [Prijzen](/prijzen). Interesse: [contact](/contact).",
            ],
            [
                'needles' => [
                    'boekingsmodule', 'online boeken', 'zelf boeken', '24/7', 'gemiste call',
                    'gemiste calls', 'telefoon overgaat',
                ],
                'answer' => "Met Nexa Taxi boeken klanten 24/7 op jouw eigen website: route, voertuig en tijd, met een directe prijsindicatie. Jij rijdt; de centrale loopt niet vol omdat de telefoon overgaat.\n\nDe rit gaat naar dispatch en de chauffeur-app. Alles in jouw merkkleuren, niet op een marktplaats.\n\nMeer: [Nexa Taxi](/taxi). Interesse: [contact](/contact).",
            ],
            [
                'needles' => [
                    'nexa taxi', 'taxi-applicatie', 'taxi applicatie', 'taxi app', 'ritten',
                    'voertuig', 'tarieven per', 'straatrit',
                ],
                'answer' => "Nexa Taxi is het product om ritten online te verkopen: van boeking tot chauffeur onderweg, in jouw merkkleuren.\n\n**In het kort**\n- Boeken op jouw website, 24/7, met prijsindicatie.\n- Dispatch vanuit de centrale: toewijzen, opnieuw uitzetten, overzicht per chauffeur.\n- Chauffeur-app op iPhone en Android: icoon op het beginscherm, inbox, rit starten, stops, afronden, betalen via QR of contant. Factuur als pdf naar de klant, met betaald-indicatie.\n- Jouw logo, kleuren en tarieven per voertuig.\n\nPakketten: Start, Pro en Business — vanaf € ".$pricing->startPrice()." per maand, plus website live zetten. Overzicht: [Prijzen](/prijzen). Meer: [Nexa Taxi](/taxi). Interesse: [contact](/contact).",
            ],
            [
                'needles' => [
                    'wat is nexa', 'nexa suite', 'platform', 'product', 'applicatie', 'applicaties',
                    'module', 'modules', 'hoe werkt', 'wat doen jullie', 'wat biedt',
                    'white-label', 'white label',
                ],
                'answer' => "NEXA Suite is het platform voor taxibedrijven: eigen website, online boeking, chauffeur-app en contractvervoer. Jouw merk. Jouw ritten.\n\nJe start waar het het meeste oplevert:\n- **Nexa Taxi** — online boeking, dispatch en chauffeur-app.\n- **Contractvervoer** — vaste ritten ernaast: school, zorg, ziekenhuis, zakelijk of privé.\n- **Website** — jouw taxisite op jouw domein, met boekingsknop; inbegrepen bij het maandpakket, live zetten eenmalig.\n\nDrie maandpakketten: Start, Pro en Business, vanaf € ".$pricing->startPrice()." per maand. Overzicht: [Prijzen](/prijzen). Interesse: [contact](/contact).",
            ],
        ];
    }

    private function contactLine(): string
    {
        return NexaContactAanvraagEmailTemplateService::RECIPIENT_EMAIL;
    }

    /**
     * @param  list<string>  $needles
     */
    private function matches(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function answerFromWebsitePages(string $message): ?string
    {
        $keywords = $this->keywords($message);
        if ($keywords === []) {
            return null;
        }

        $best = null;
        $bestScore = 0;
        foreach ($this->websiteChunks() as $chunk) {
            $score = $this->scoreChunk($chunk, $keywords);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $chunk;
            }
        }

        if (! is_array($best) || $bestScore < 8) {
            return null;
        }

        $title = trim((string) ($best['title'] ?? ''));
        $body = trim((string) ($best['text'] ?? ''));
        if ($title !== '' && str_starts_with(mb_strtolower($body), mb_strtolower($title))) {
            $body = trim(mb_substr($body, mb_strlen($title)));
        }
        $body = Str::limit($body !== '' ? $body : $title, 700, '…');
        if ($body === '') {
            return null;
        }

        $intro = $title !== '' && ! str_starts_with(mb_strtolower($body), mb_strtolower($title))
            ? $title."\n\n".$body
            : $body;

        return trim($intro."\n\nWil je dit voor jouw taxibedrijf bespreken? Stuur een aanvraag via [het contactformulier](/contact).");
    }

    /**
     * @return list<array{title: string, text: string}>
     */
    private function websiteChunks(): array
    {
        $pages = WebsitePage::query()
            ->whereNull('company_id')
            ->whereNull('module_name')
            ->where('is_active', true)
            ->get(['title', 'home_sections']);

        $chunks = [];
        foreach ($pages as $page) {
            $pageTitle = $this->plainText((string) ($page->title ?? ''));
            if ($pageTitle !== '') {
                $chunks[] = ['title' => $pageTitle, 'text' => $pageTitle];
            }

            $sections = $page->home_sections;
            if (! is_array($sections)) {
                continue;
            }

            foreach ($sections as $section) {
                if (! is_array($section)) {
                    continue;
                }

                $sectionTitle = $this->plainText((string) ($section['title'] ?? $section['section_title'] ?? ''));
                $subtitle = $this->plainText((string) ($section['subtitle'] ?? ''));
                if ($sectionTitle !== '' || $subtitle !== '') {
                    $chunks[] = [
                        'title' => $sectionTitle,
                        'text' => trim($sectionTitle."\n\n".$subtitle),
                    ];
                }

                if (isset($section['content']) && is_string($section['content'])) {
                    $content = $this->plainText($section['content']);
                    if ($content !== '') {
                        $chunks[] = ['title' => $sectionTitle, 'text' => $content];
                    }
                }

                foreach ($section['items'] ?? [] as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    $title = $this->plainText((string) ($item['title'] ?? $item['name'] ?? $item['caption'] ?? ''));
                    $description = $this->plainText((string) ($item['description'] ?? ''));
                    $features = [];
                    if (isset($item['features']) && is_array($item['features'])) {
                        foreach ($item['features'] as $feature) {
                            $featureText = $this->plainText((string) $feature);
                            if ($featureText !== '') {
                                $features[] = $featureText;
                            }
                        }
                    }
                    $text = $description;
                    if ($features !== []) {
                        $text = trim($text."\n".implode("\n", $features));
                    }
                    if ($title === '' && $text === '') {
                        continue;
                    }
                    $chunks[] = [
                        'title' => $title,
                        'text' => $title !== '' && $text !== '' ? $title."\n\n".$text : ($text !== '' ? $text : $title),
                    ];
                }

                $cons = $this->comparisonTexts($section, 'cons', 'left');
                $pros = $this->comparisonTexts($section, 'pros', 'right');
                if ($cons !== [] || $pros !== []) {
                    $leftHeading = $this->plainText((string) ($section['left_heading'] ?? 'Nadelen'));
                    $rightHeading = $this->plainText((string) ($section['right_heading'] ?? 'Voordelen'));
                    $parts = [];
                    if ($cons !== []) {
                        $parts[] = ($leftHeading !== '' ? $leftHeading : 'Nadelen').":\n- ".implode("\n- ", $cons);
                    }
                    if ($pros !== []) {
                        $parts[] = ($rightHeading !== '' ? $rightHeading : 'Voordelen').":\n- ".implode("\n- ", $pros);
                    }
                    $chunks[] = [
                        'title' => $sectionTitle !== '' ? $sectionTitle : 'Vergelijking',
                        'text' => implode("\n\n", $parts),
                    ];
                }
            }
        }

        return $chunks;
    }

    /**
     * @param  array{title: string, text: string}  $chunk
     * @param  list<string>  $keywords
     */
    private function scoreChunk(array $chunk, array $keywords): int
    {
        $title = mb_strtolower($chunk['title'] ?? '');
        $text = mb_strtolower($chunk['text'] ?? '');
        $score = 0;

        foreach ($keywords as $keyword) {
            if ($title !== '' && str_contains($title, $keyword)) {
                $score += 12;
            }
            if ($text !== '' && str_contains($text, $keyword)) {
                $score += 6;
            }
        }

        return $score;
    }

    /**
     * @return list<string>
     */
    private function keywords(string $message): array
    {
        $normalized = $this->normalize($message);
        $normalized = preg_replace('/[^\p{L}\p{N}\s-]+/u', ' ', $normalized) ?? $normalized;
        $parts = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stopWords = [
            'de', 'het', 'een', 'en', 'of', 'voor', 'naar', 'bij', 'ook', 'onze', 'uw', 'jij', 'jullie',
            'hebben', 'heeft', 'heb', 'kan', 'kunnen', 'wat', 'hoe', 'waar', 'wie', 'welke', 'welk',
            'zijn', 'is', 'ben', 'bent', 'mag', 'mogen', 'graag', 'alstublieft', 'alsjeblieft', 'nog',
            'meer', 'over', 'met', 'van', 'op', 'in', 'aan', 'er', 'die', 'dat', 'dit', 'deze', 'daar',
            'jullie', 'iets', 'vertel', 'uitleg',
        ];

        $keywords = [];
        foreach ($parts as $part) {
            $part = trim($part, '-');
            if ($part === '' || mb_strlen($part) < 3 || in_array($part, $stopWords, true)) {
                continue;
            }
            $keywords[] = $part;
        }

        return array_values(array_unique($keywords));
    }

    /**
     * @param  array<string, mixed>  $section
     * @return list<string>
     */
    private function comparisonTexts(array $section, string $listKey, string $rowSide): array
    {
        $out = [];
        $raw = $section[$listKey] ?? null;
        if (is_array($raw)) {
            foreach ($raw as $item) {
                $text = '';
                if (is_string($item)) {
                    $text = $this->plainText($item);
                } elseif (is_array($item)) {
                    $text = $this->plainText((string) ($item['text'] ?? ''));
                }
                if ($text !== '') {
                    $out[] = $text;
                }
            }
        }
        if ($out !== []) {
            return $out;
        }
        foreach ($section['rows'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $text = $this->plainText((string) ($row[$rowSide] ?? ''));
            if ($text !== '') {
                $out[] = $text;
            }
        }

        return $out;
    }

    private function fallback(): string
    {
        return "Ik help je met vragen over NEXA Suite: de taxi-applicatie, contractvervoer, prijzen en pakketten, je eigen website en hoe je contact opneemt.\n\nStel gerust een concrete vraag, of stuur een aanvraag via [het contactformulier](/contact) — e-mail: ".$this->contactLine().'.';
    }

    /**
     * @return list<string>
     */
    private function matchedPackageNames(string $text, NexaPricingService $pricing): array
    {
        $names = [];
        foreach ($pricing->packageNames() as $name) {
            $needle = mb_strtolower($name);
            if ($needle === '' || mb_strlen($needle) < 3) {
                continue;
            }
            if (preg_match('/\b'.preg_quote($needle, '/').'\b/u', $text) === 1) {
                $names[] = $name;
            }
        }

        return $names;
    }

    private function normalize(string $message): string
    {
        $text = mb_strtolower(trim($message));
        $text = str_replace(['ë', 'ï', 'é', 'è', 'ö', 'ü'], ['e', 'i', 'e', 'e', 'o', 'u'], $text);

        return preg_replace('/\s+/u', ' ', $text) ?? $text;
    }

    private function plainText(string $value): string
    {
        $value = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n\n", $value) ?? $value;
        $value = preg_replace('/<br\s*\/?>/i', "\n", $value) ?? $value;
        $value = preg_replace('/<\/li>/i', "\n", $value) ?? $value;

        return trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');
    }
}
