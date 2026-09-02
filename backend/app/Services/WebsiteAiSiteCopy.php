<?php

namespace App\Services;

use App\Models\Company;

/**
 * Bedrijfsspecifieke website-copy voor de AI-generator (fallback én aanvulling op OpenAI).
 */
class WebsiteAiSiteCopy
{
    public const BOOKING_COMPONENT = 'taxi.boekingsmodule_v2';

    /** @var list<string> */
    public const HOME_ANIMATED_COMPONENTS = [
        'landwind.stats_strip',
        'landwind.feature_checklist',
        'website.comparison_table',
        'vue_material.info_pills',
        'landwind.faq',
        'vue_material.quote_cards',
        self::BOOKING_COMPONENT,
    ];

    /**
     * @param  array<string, mixed>  $source
     * @return array{brand: array{name: string, tagline: string}, pages: list<array<string, mixed>>, footer: array{tagline: string, copyright: string}}
     */
    public function blueprint(Company $company, string $context, array $source, int $maxPages): array
    {
        $p = $this->profile($company, $context, $source);
        $pages = [
            $this->homePage($p),
            $this->aboutPage($p),
            $this->contactPage($p),
            $this->servicesPage($p),
            $this->ratesPage($p),
        ];

        return [
            'brand' => ['name' => $p['brand'], 'tagline' => $p['tagline']],
            'pages' => array_slice($pages, 0, max(1, $maxPages)),
            'footer' => [
                'tagline' => $p['tagline'],
                'copyright' => '© {year} '.$p['brand'].'. Alle rechten voorbehouden.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array{
     *     brand: string,
     *     city: string,
     *     phone: string,
     *     email: string,
     *     address: string,
     *     brief: string,
     *     tagline: string,
     *     source: string
     * }
     */
    public function profile(Company $company, string $context, array $source): array
    {
        $brand = trim((string) $company->name) ?: 'Ons taxibedrijf';
        $city = trim((string) ($company->city ?? '')) ?: 'Nederland';
        $phone = trim((string) ($company->phone ?? ''));
        $email = trim((string) ($company->email ?? ''));
        $street = trim(implode(' ', array_filter([
            trim((string) ($company->street ?? '')),
            trim((string) ($company->house_number ?? '')),
            trim((string) ($company->house_number_extension ?? '')),
        ])));
        $address = trim(implode(', ', array_filter([
            $street,
            trim((string) ($company->postal_code ?? '')),
            $city,
        ])));
        $brief = trim($context);
        if ($brief === '') {
            $brief = trim((string) ($company->description ?? ''));
        }
        if ($brief === '') {
            $brief = $brand.' verzorgt betrouwbaar personenvervoer in '.$city.' en de regio: luchthaven, zakelijk, privé en contractritten. Boeken gaat via de website, in uw merkkleuren, met live route en vaste afspraken.';
        }
        $sourceText = trim((string) ($source['summary'] ?? ''));
        $tagline = $this->firstSentence($brief) ?: ('Vervoer in '.$city.' waarop u kunt rekenen.');

        return [
            'brand' => $brand,
            'city' => $city,
            'phone' => $phone,
            'email' => $email,
            'address' => $address !== '' ? $address : $city,
            'brief' => $brief,
            'tagline' => mb_substr($tagline, 0, 140),
            'source' => $sourceText,
        ];
    }

    /**
     * @param  array<string, string>  $p
     * @return array<string, mixed>
     */
    public function homePage(array $p): array
    {
        $brand = $p['brand'];
        $city = $p['city'];
        $phone = $p['phone'];

        return [
            'slug' => 'home',
            'title' => $brand.' · Taxi '.$city,
            'menu_title' => 'Home',
            'page_type' => 'home',
            'show_in_menu' => true,
            'meta_description' => mb_substr($brand.' in '.$city.': boek een rit online. Luchthaven, zakelijk en privévervoer. '.$p['tagline'], 0, 160),
            'hero' => [
                'title' => 'Boek uw rit in '.$city,
                'title_highlight' => $city,
                'subtitle' => $brand.' brengt u op tijd, netjes en zonder gedoe. Vul ophaaladres en bestemming in: u ziet direct de route en kunt de rit vastleggen. '.$p['tagline'],
                'cta_primary_text' => 'Rit boeken',
                'cta_primary_url' => '#boek-rit',
                'cta_secondary_text' => 'Onze diensten',
                'cta_secondary_url' => '/diensten',
                'image_prompt' => 'Photorealistic premium taxi waiting at a Dutch city curb at golden hour, cinematic, no text no logos',
            ],
            'why_nexa' => [
                'title' => 'Waarom reizigers voor '.$brand.' kiezen',
                'subtitle' => 'Korte lijnen, ervaren chauffeurs en een boeking die op uw eigen site blijft. Geen marktplaats ertussen: u spreekt met het bedrijf zelf, van de eerste klik tot aankomst.',
            ],
            'text_block' => [
                'content' => $this->homeStoryHtml($p),
            ],
            'features' => [
                'section_title' => 'Wat u van ons mag verwachten',
                'items' => [
                    ['title' => 'Online boeken, live route', 'description' => 'Ophaaladres, bestemming en tijdstip in één flow. U ziet de route op de kaart en legt de rit vast zonder nabelen.', 'icon' => 'map-pin'],
                    ['title' => 'Op tijd, elke rit', 'description' => 'Chauffeurs kennen '.$city.' en de regio. We plannen buffer voor files en houden u op de hoogte als er iets wijzigt.', 'icon' => 'clock'],
                    ['title' => 'Zakelijk én privé', 'description' => 'Luchthaven, congres, diner of een vaste schoolrit: dezelfde kwaliteit, met factuur of directe betaling.', 'icon' => 'briefcase'],
                    ['title' => 'Altijd een aanspreekpunt', 'description' => ($phone !== '' ? 'Bel '.$phone.' of stuur een bericht. ' : '').'Geen chatrobot die u doorverwijst: u spreekt met de centrale van '.$brand.'.', 'icon' => 'chat-bubble-left-right'],
                ],
            ],
            'stats' => [
                'items' => [
                    ['value' => '24/7', 'label' => 'Online boekbaar'],
                    ['value' => $city, 'label' => 'Standplaats'],
                    ['value' => '100%', 'label' => 'Eigen chauffeurs'],
                    ['value' => $phone !== '' ? $phone : 'Direct', 'label' => 'Centrale'],
                ],
            ],
            'cta' => [
                'title' => 'Klaar om te vertrekken?',
                'subtitle' => 'Boek hieronder in twee minuten, of bel de centrale'.($phone !== '' ? ' op '.$phone : '').'. We staan klaar voor '.$city.' en omstreken.',
                'cta_primary_text' => 'Direct boeken',
                'cta_primary_url' => '#boek-rit',
                'cta_secondary_text' => 'Contact',
                'cta_secondary_url' => '/contact',
            ],
            'featured_services' => [
                'title' => 'Diensten van '.$brand,
                'subtitle' => 'Van een enkele rit tot vast contractvervoer. Alles via dezelfde boeking.',
                'animation_speed' => 'slow',
                'items' => [
                    ['icon' => 'truck', 'title' => 'Luchthavenvervoer', 'description' => 'Schiphol, Eindhoven, Rotterdam of Düsseldorf: we rekenen met inchecktijd, files en vluchtstatus. U stapt in, wij rijden.'],
                    ['icon' => 'briefcase', 'title' => 'Zakelijke ritten', 'description' => 'Account, maandfactuur en vaste chauffeurs voor management en gasten. Geen verrassingstarief achteraf.'],
                    ['icon' => 'user-group', 'title' => 'Contractvervoer', 'description' => 'School, zorg en terugkerende ritten. Ouders en opdrachtgevers zien status; afmeldingen van–tot zijn geregeld.'],
                    ['icon' => 'star', 'title' => 'Privé en evenementen', 'description' => 'Diner, bruiloft, concert of een dagje weg. Extra bagage of een grotere wagen: geef het aan bij het boeken.'],
                ],
            ],
            'components' => self::HOME_ANIMATED_COMPONENTS,
            'component_copy' => $this->homeComponentCopy($p),
        ];
    }

    /**
     * @param  array<string, string>  $p
     * @return array<string, mixed>
     */
    public function aboutPage(array $p): array
    {
        $brand = $p['brand'];
        $city = $p['city'];

        return [
            'slug' => 'over-ons',
            'title' => 'Over '.$brand,
            'menu_title' => 'Over ons',
            'page_type' => 'about',
            'show_in_menu' => true,
            'meta_description' => mb_substr('Leer '.$brand.' in '.$city.' kennen: wie we zijn, hoe we rijden en waarom klanten terugkomen.', 0, 160),
            'hero' => [
                'title' => $brand.' uit '.$city,
                'title_highlight' => $brand,
                'subtitle' => 'Een lokaal taxibedrijf met een moderne boeking. We kennen de stad, de ritten en de mensen achter de rit.',
                'cta_primary_text' => 'Rit boeken',
                'cta_primary_url' => '/#boek-rit',
                'cta_secondary_text' => 'Contact',
                'cta_secondary_url' => '/contact',
                'image_prompt' => 'Photorealistic Dutch taxi drivers and dispatcher in a small office, warm daylight, no text',
            ],
            'text_block' => [
                'content' => $this->aboutStoryHtml($p),
            ],
            'featured_services' => [
                'title' => 'Hoe wij werken',
                'subtitle' => 'Van websiteboeking tot chauffeur in de auto.',
                'animation_speed' => 'slow',
                'items' => [
                    ['icon' => 'map-pin', 'title' => 'U start op de site', 'description' => 'Geen app-store, geen extra accountverplichting voor een losse rit. De boeking leeft op de website van '.$brand.'.'],
                    ['icon' => 'bolt', 'title' => 'De rit landt bij dispatch', 'description' => 'De centrale ziet ophaal, bestemming en wensen direct. De juiste chauffeur krijgt de rit aangeboden.'],
                    ['icon' => 'shield-check', 'title' => 'U blijft op de hoogte', 'description' => 'Status terug naar u, zonder dat u de centrale hoeft te belasten voor “waar is de auto”.'],
                ],
            ],
            'components' => ['play.about_overlap', 'landwind.faq', 'vue_material.quote_cards'],
            'component_copy' => [
                'play.about_overlap' => [
                    'eyebrow' => 'Ons verhaal',
                    'title' => $brand.' is meer dan een auto voor de deur',
                    'subtitle' => 'Lokaal team, duidelijke afspraken, moderne boeking.',
                    'body' => $brand.' is geworteld in '.$city.'. Chauffeurs, planning en klantenservice werken vanuit hetzelfde ritoverzicht. De website is het voorportaal; de rit zelf blijft mensenwerk. '.$p['brief'],
                    'cta_label' => 'Boek een rit',
                    'cta_url' => '/#boek-rit',
                ],
                'landwind.faq' => $this->faqCopy($p, 'about'),
                'vue_material.quote_cards' => $this->quotesCopy($p),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $p
     * @return array<string, mixed>
     */
    public function contactPage(array $p): array
    {
        $brand = $p['brand'];
        $phone = $p['phone'];

        return [
            'slug' => 'contact',
            'title' => 'Contact · '.$brand,
            'menu_title' => 'Contact',
            'page_type' => 'contact',
            'show_in_menu' => true,
            'meta_description' => mb_substr('Neem contact op met '.$brand.' in '.$p['city'].'. Boek online of bel de centrale.', 0, 160),
            'hero' => [
                'title' => 'Bereik de centrale',
                'title_highlight' => 'centrale',
                'subtitle' => ($phone !== '' ? 'Bel '.$phone.' of boek hieronder. ' : 'Boek hieronder of stuur een bericht. ').'Vermeld datum, aantal personen en extra bagage, dan zetten we de rit scherp.',
                'cta_primary_text' => $phone !== '' ? 'Bel '.$phone : 'Rit boeken',
                'cta_primary_url' => $phone !== '' ? 'tel:'.preg_replace('/\s+/', '', $phone) : '#boek-rit',
                'cta_secondary_text' => 'Over ons',
                'cta_secondary_url' => '/over-ons',
                'image_prompt' => 'Photorealistic taxi dispatch desk with screens and a city map, daylight, no text',
            ],
            'text_block' => [
                'content' => '<p>U bereikt '.$this->e($brand).' het snelst via de boekingsmodule op deze pagina: ophaaladres, bestemming en tijdstip zijn genoeg voor een ritvoorstel.</p>'
                    .'<p>Voor contracten, maandfactuur of een offerte voor een evenement mailt u ons'.($p['email'] !== '' ? ' op '.$this->e($p['email']) : '').' of belt u de centrale'.($phone !== '' ? ' op '.$this->e($phone) : '').'. We reageren op werkdagen meestal dezelfde dag.</p>'
                    .'<p>Bezoekadres: '.$this->e($p['address']).'. Standplaats '.$this->e($p['city']).'.</p>',
            ],
            'components' => [self::BOOKING_COMPONENT, 'play.contact_split'],
            'component_copy' => [
                'play.contact_split' => [
                    'eyebrow' => 'Centrale',
                    'title' => 'Plan een rit of een kennismaking',
                    'subtitle' => 'Vragen over een luchthavenrit, een vast contract of een groepsvervoer? We denken mee.',
                    'address' => $p['address'],
                    'phone' => $phone !== '' ? $phone : 'via het boekingsformulier',
                    'email' => $p['email'] !== '' ? $p['email'] : 'via het contactformulier',
                    'hours' => 'Boeken kan 24/7 online. Centrale: ma–zo volgens dienstregeling.',
                    'cta_label' => 'Verstuur bericht',
                ],
            ],
        ];
    }

    /**
     * @param  array<string, string>  $p
     * @return array<string, mixed>
     */
    public function servicesPage(array $p): array
    {
        $brand = $p['brand'];

        return [
            'slug' => 'diensten',
            'title' => 'Diensten · '.$brand,
            'menu_title' => 'Diensten',
            'page_type' => 'custom',
            'show_in_menu' => true,
            'meta_description' => mb_substr('Diensten van '.$brand.': luchthaven, zakelijk, privé en contractvervoer in '.$p['city'].'.', 0, 160),
            'hero' => [
                'title' => 'Vervoer dat past bij de rit',
                'title_highlight' => 'past bij de rit',
                'subtitle' => 'Niet elke rit is hetzelfde. Kies het type vervoer; de boeking op de site blijft hetzelfde: duidelijk, met route en prijsafspraak.',
                'cta_primary_text' => 'Boek deze rit',
                'cta_primary_url' => '/#boek-rit',
                'cta_secondary_text' => 'Tarieven',
                'cta_secondary_url' => '/tarieven',
                'image_prompt' => 'Photorealistic luxury people carrier and sedan at a Dutch airport curb, golden hour, no text',
            ],
            'text_block' => [
                'content' => '<p>'.$this->e($brand).' rijdt luchthavenritten, zakelijke transfers, privévervoer en contractritten in '.$this->e($p['city']).' en de regio. U boekt alles via dezelfde module: één adres, één chauffeur, één afspraak.</p>'
                    .'<p>Voor groepen tot acht personen plannen we een grotere wagen. Voor zorg- of schoolvervoer maken we vaste routes en contactpersonen. '.$this->e($p['brief']).'</p>'
                    .'<p>Twijfelt u welk voertuig past? Kies bij het boeken het aantal personen en bagage; wij schalen op als dat nodig is.</p>',
            ],
            'featured_services' => [
                'title' => 'Onze ritten',
                'subtitle' => 'Kies wat u nodig heeft. De boeking volgt vanzelf.',
                'animation_speed' => 'slow',
                'items' => [
                    ['icon' => 'globe-alt', 'title' => 'Luchthaven', 'description' => 'Inclusief wachttijd-afspraak. We volgen de vlucht als u het nummer doorgeeft.'],
                    ['icon' => 'briefcase', 'title' => 'Zakelijk', 'description' => 'Vaste rekeningen, meerdere medewerkers, prioriteit in de ochtendspits.'],
                    ['icon' => 'heart', 'title' => 'Privé', 'description' => 'Avond, weekend, evenement. Stil, netjes, zonder omwegen die u niet vroeg.'],
                    ['icon' => 'calendar', 'title' => 'Contract', 'description' => 'Wekelijkse ritten met afmelden van–tot. Geschikt voor school en zorg.'],
                ],
            ],
            'components' => [self::BOOKING_COMPONENT, 'vue_material.info_pills', 'taxi.tarieven'],
            'component_copy' => [
                'vue_material.info_pills' => $this->pillsCopy($p),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $p
     * @return array<string, mixed>
     */
    public function ratesPage(array $p): array
    {
        return [
            'slug' => 'tarieven',
            'title' => 'Tarieven · '.$p['brand'],
            'menu_title' => 'Tarieven',
            'page_type' => 'custom',
            'show_in_menu' => true,
            'meta_description' => mb_substr('Tarieven van '.$p['brand'].' in '.$p['city'].'. Vaste ritprijs via de boeking of een offerte op maat.', 0, 160),
            'hero' => [
                'title' => 'Heldere ritprijzen',
                'title_highlight' => 'ritprijzen',
                'subtitle' => 'Geen verrassing achteraf. Vul de route in op de boekingspagina voor een prijs, of vraag een vaste prijs voor een reeks ritten.',
                'cta_primary_text' => 'Bereken mijn rit',
                'cta_primary_url' => '/#boek-rit',
                'cta_secondary_text' => 'Contact',
                'cta_secondary_url' => '/contact',
                'image_prompt' => 'Photorealistic close-up of a clean taxi dashboard at night, city bokeh, no text',
            ],
            'text_block' => [
                'content' => '<p>De prijs hangt af van afstand, tijdstip, voertuig en eventuele wachttijd. Via de boekingsmodule op de home van '.$this->e($p['brand']).' ziet u een voorstel voordat u bevestigt.</p>'
                    .'<p>Nachtritten, feestdagen en extra stops geven we vooraf aan. Contractritten rekenen we maandelijks, met een overzicht per rit.</p>'
                    .'<p>Voor een evenement of pendeldienst maken we een offerte. Bel de centrale'.($p['phone'] !== '' ? ' op '.$this->e($p['phone']) : '').' of gebruik het contactformulier.</p>',
            ],
            'components' => ['taxi.tarieven', self::BOOKING_COMPONENT, 'landwind.faq'],
            'component_copy' => [
                'landwind.faq' => $this->faqCopy($p, 'rates'),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $p
     * @return array<string, array<string, mixed>>
     */
    public function homeComponentCopy(array $p): array
    {
        return [
            'landwind.faq' => $this->faqCopy($p, 'home'),
            'landwind.stats_strip' => [
                'eyebrow' => 'In cijfers',
                'title' => $p['brand'].' in '.$p['city'],
                'subtitle' => 'Geen loze marketingcijfers: dit is hoe we de dienst inrichten.',
                'items' => [
                    ['value' => '24', 'suffix' => '/7', 'label' => 'Online te boeken'],
                    ['value' => '15', 'suffix' => ' min', 'label' => 'Gem. aannametijd'],
                    ['value' => '4', 'suffix' => '+', 'label' => 'Personen standaard'],
                    ['value' => '8', 'suffix' => '', 'label' => 'Personen op aanvraag'],
                ],
            ],
            'landwind.feature_checklist' => [
                'eyebrow' => 'Werkwijze',
                'title' => 'Van boeking tot chauffeur, zonder nabelen',
                'subtitle' => 'Zo loopt een rit bij '.$p['brand'].'.',
                'items' => [
                    ['text' => 'U vult ophaaladres, bestemming en tijdstip in op de site'],
                    ['text' => 'U ziet de route en bevestigt de rit'],
                    ['text' => 'De centrale wijst een chauffeur toe'],
                    ['text' => 'U ontvangt status tot aankomst'],
                ],
            ],
            'website.comparison_table' => [
                'title' => 'Zelf bellen of direct boeken?',
                'subtitle' => 'Wat '.$p['brand'].' anders doet dan losse belletjes en marktplaats-apps.',
                'left_heading' => 'Los bellen of een app',
                'right_heading' => 'Boeken bij '.$p['brand'],
                'cons' => [
                    ['text' => 'Wachten in de wachtrij, opnieuw uitleggen waar u heen moet'],
                    ['text' => 'Geen zicht op de route tot de auto er is'],
                    ['text' => 'Prijs pas duidelijk als de meter stopt'],
                    ['text' => 'Commissie of een extra platform tussen u en de chauffeur'],
                ],
                'pros' => [
                    ['text' => 'Zelf invullen, wanneer het u uitkomt, ook ’s nachts'],
                    ['text' => 'Route op de kaart voordat u bevestigt'],
                    ['text' => 'Afspraak vooraf, factuur mogelijk voor zakelijk'],
                    ['text' => 'U houdt de rit bij het lokale bedrijf in '.$p['city']],
                ],
            ],
            'vue_material.info_pills' => $this->pillsCopy($p),
            'vue_material.quote_cards' => $this->quotesCopy($p),
            self::BOOKING_COMPONENT => [
                'title' => 'Boek uw rit bij '.$p['brand'],
                'subtitle' => 'Ophaal, bestemming en tijdstip. De route verschijnt naast het formulier.',
            ],
        ];
    }

    /**
     * @param  array<string, string>  $p
     * @return array<string, mixed>
     */
    public function faqCopy(array $p, string $variant = 'home'): array
    {
        $brand = $p['brand'];
        $city = $p['city'];
        $items = [
            ['question' => 'Hoe boek ik een rit bij '.$brand.'?', 'answer' => 'Gebruik de boekingsmodule op de website. Vul ophaaladres, bestemming, datum en aantal personen in. U ziet de route en bevestigt de rit. Een account is niet verplicht voor een losse rit.'],
            ['question' => 'Rijden jullie ook naar Schiphol of andere luchthavens?', 'answer' => 'Ja. Geef vluchtnummer of gewenste aankomsttijd door, dan plannen we extra marge. We rijden vanuit '.$city.' naar de grote luchthavens in Nederland en grensregio’s.'],
            ['question' => 'Kan ik een rit zakelijk laten factureren?', 'answer' => 'Ja. Vraag een account aan via contact. Daarna boekt u met bedrijfsgegevens; u ontvangt een maandfactuur in plaats van losse pinbetalingen.'],
            ['question' => 'Wat als mijn vlucht of afspraak later is?', 'answer' => 'Geef het zo vroeg mogelijk door aan de centrale. We houden de chauffeur op de hoogte. Extra wachttijd stemmen we vooraf of ter plaatse af, zonder verrassing achteraf als we het weten.'],
            ['question' => 'Nemen jullie kinderstoelen of extra bagage mee?', 'answer' => 'Geef het aan bij het boeken onder opmerkingen. We plannen een passende wagen. Kindersystemen op aanvraag, zolang de voorraad en de planning het toelaten.'],
        ];
        if ($variant === 'rates') {
            $items[0] = ['question' => 'Hoe wordt de prijs berekend?', 'answer' => 'Op afstand, tijdstip en voertuig. Via de boeking ziet u een voorstel vóór bevestiging. Contractritten gaan per maandoverzicht.'];
        }

        return [
            'eyebrow' => 'FAQ',
            'title' => 'Veelgestelde vragen over '.$brand,
            'subtitle' => 'Kort en concreet, zoals we ook rijden.',
            'items' => $items,
        ];
    }

    /**
     * @param  array<string, string>  $p
     * @return array<string, mixed>
     */
    public function quotesCopy(array $p): array
    {
        $city = $p['city'];
        $brand = $p['brand'];

        return [
            'eyebrow' => 'Klanten',
            'title' => 'Wat reizigers teruggeven',
            'subtitle' => 'Echte ritten, geen stockquotes over “synergie”.',
            'items' => [
                ['quote' => 'De auto stond er eerder dan ik. Chauffeur kende de afrit, geen omweg via het centrum.', 'author' => 'Sanne de Vries', 'role' => 'Zakelijk, '.$city],
                ['quote' => 'Eindelijk boeken op de site van het taxibedrijf zelf, zonder extra app die commissie aftikt.', 'author' => 'Murat Kaya', 'role' => 'Luchthavenrit'],
                ['quote' => $brand.' belt als de vlucht later is. Dat scheelt een hoop gedoe bij de aankomsthal.', 'author' => 'Lotte Janssen', 'role' => 'Familievervoer'],
            ],
        ];
    }

    /**
     * @param  array<string, string>  $p
     * @return array<string, mixed>
     */
    public function pillsCopy(array $p): array
    {
        return [
            'eyebrow' => 'Kies uw rit',
            'title' => 'Wat wilt u laten rijden?',
            'subtitle' => 'Drie smaken, dezelfde boeking bij '.$p['brand'].'.',
            'items' => [
                ['label' => 'Luchthaven', 'title' => 'Op tijd bij de terminal', 'text' => 'We rekenen terug vanaf uw vlucht of incheck. Extra koffers? Kies het aantal personen en bagage in de module; we sturen een passende wagen.'],
                ['label' => 'Zakelijk', 'title' => 'De klant houdt de rit', 'text' => 'Geen marktplaats. Medewerkers boeken op de site van '.$p['brand'].', u krijgt overzicht en factuur. Ideaal voor ochtendspits en gastenvervoer.'],
                ['label' => 'Contract', 'title' => 'Elke week dezelfde afspraak', 'text' => 'School of zorg: vaste tijden, afmelden van–tot, contact met ouders of begeleiding. De rit blijft in hetzelfde systeem als de losse boekingen.'],
            ],
        ];
    }

    /**
     * @param  array<string, string>  $p
     */
    public function homeStoryHtml(array $p): string
    {
        $brand = $this->e($p['brand']);
        $city = $this->e($p['city']);
        $brief = $this->e($p['brief']);
        $extra = $p['source'] !== ''
            ? '<p>Wat we van uw huidige site meenemen: we herschrijven de feiten, niet de opmaak. Kern uit de oude teksten is verwerkt in deze pagina’s.</p>'
            : '';

        return '<p>'.$brand.' is het taxibedrijf voor '.$city.' dat u zelf laat boeken: op deze website, in eigen merkkleuren, met een live route. Geen extra platform dat de klant afpakt of commissie rekent over uw rit.</p>'
            .'<p>'.$brief.'</p>'
            .'<p>Chauffeurs kennen de stad, de centrale houdt de planning strak en u ziet waar de rit staat. Dat is NEXA Suite in het klein: boeking, rit en opvolging in één lijn.</p>'
            .$extra;
    }

    /**
     * @param  array<string, string>  $p
     */
    public function aboutStoryHtml(array $p): string
    {
        $brand = $this->e($p['brand']);
        $city = $this->e($p['city']);

        return '<p>'.$brand.' zit in '.$city.'. We zijn geen anonieme app: u belt of mailt dezelfde mensen die de ritten verdelen. Dat merkt u als een vlucht later is, als er een kinderstoel bij moet, of als een gast om 06:10 naar kantoor moet.</p>'
            .'<p>'.$this->e($p['brief']).'</p>'
            .'<p>De website is het startpunt. Achter de schermen werkt dispatch met dezelfde rit. Chauffeurs rijden met navigatie en ritgegevens op de telefoon. U hoeft dat niet te zien, maar u merkt wel dat niemand meer “wacht even, ik zoek het op” zegt.</p>'
            .'<p>Wilt u een vaste samenwerking? We zetten een account klaar, met factuur en eventueel meerdere boekers. Voor een losse rit is de module op de home genoeg.</p>';
    }

    private function firstSentence(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if ($text === '') {
            return '';
        }
        if (preg_match('/^(.{20,180}?[.!?])(\s|$)/u', $text, $m)) {
            return $m[1];
        }

        return mb_substr($text, 0, 140);
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
