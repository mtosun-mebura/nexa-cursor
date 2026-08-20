<?php

/**
 * Publieke NEXA Suite-prijzen (taxibedrijven).
 * Bron voor /prijzen, marketing-preview en de product-FAQ.
 */
return [
    'vat_note' => 'Prijzen exclusief btw. Standaardtermijn 12 maanden; daarna maandelijks opzegbaar.',
    'eyebrow' => 'Prijzen',
    'title' => 'Start klein. Groei mee als de ritten toenemen.',
    'subtitle' => 'Een vast maandbedrag voor het platform. Een eenmalig bedrag om jouw website live te zetten. Geen marktplaats-commissie.',

    'packages' => [
        [
            'name' => 'Start',
            'audience' => 'ZZP en tot 3 chauffeurs',
            'price' => '49',
            'period' => 'per maand',
            'badge' => 'Instap',
            'highlighted' => false,
            'cta_text' => 'Start aanvragen',
            'cta_url' => '/contact',
            'features' => [
                'Website met boekingsmodule',
                'Tot 3 chauffeur-accounts',
                'Online ritaanvragen',
                'Jouw logo en merkkleuren',
                'E-mailsupport',
            ],
        ],
        [
            'name' => 'Pro',
            'audience' => 'Dagelijks ritten, centrale + app',
            'price' => '99',
            'period' => 'per maand',
            'badge' => 'Aanbevolen',
            'highlighted' => true,
            'cta_text' => 'Pro aanvragen',
            'cta_url' => '/contact',
            'features' => [
                'Alles van Start',
                'Onbeperkt chauffeurs',
                'Dispatch: toewijzen en opnieuw uitzetten',
                'Chauffeur-app op iPhone en Android',
                'Betalen via Mollie (online of QR)',
                'Factuur als pdf naar de klant',
            ],
        ],
        [
            'name' => 'Business',
            'audience' => 'Taxi plus vast contractwerk',
            'price' => '179',
            'period' => 'per maand',
            'badge' => 'Contract erbij',
            'highlighted' => false,
            'cta_text' => 'Business aanvragen',
            'cta_url' => '/contact',
            'features' => [
                'Alles van Pro',
                'Contractvervoer: school, zorg, zakelijk, privé',
                'Contract-app voor ouder en opdrachtgever',
                'Afmeldingen van–tot',
                'Maandfactuur en SEPA',
                'Meerdere beheerders',
            ],
        ],
    ],

    'website' => [
        'title' => 'Website live zetten',
        'price_label' => '750',
        'price_prefix' => 'vanaf',
        'period' => 'eenmalig',
        'subtitle' => 'Jouw taxisite met boekingsknop, in jouw merkkleuren, op jouw domein. Inclusief website builder, SEO per pagina en Google Maps. Daarna alleen het maandabonnement.',
        'cta_text' => 'Website bespreken',
        'cta_url' => '/contact',
        'features' => [
            'Home, contact en boekingspagina',
            'Eigen website builder: pagina’s met configureerbare componenten',
            'Logo, kleuren en teksten in jouw merk',
            'Automatische SEO per pagina: titel, meta-omschrijving en sitemap voor Google Zoeken',
            'Google Maps: vestiging, adres en routebeschrijving op je site',
            'Live op jouw eigen domein',
            'Uitgebreider (meer pagina\'s of copy): vanaf € 1.250',
        ],
    ],

    'addons' => [
        [
            'name' => 'Extra vestiging',
            'price' => '+ € 49 / maand',
            'description' => 'Nog een merk of standplaats, met eigen site en ritten.',
        ],
        [
            'name' => 'AI-assistent',
            'price' => '+ € 29 / maand',
            'description' => 'Chat op jouw website: offerte, status en veelgestelde vragen.',
        ],
        [
            'name' => 'Vloot / maatwerk',
            'price' => 'vanaf € 249 / maand',
            'description' => 'Meerdere vestigingen, SLA en afspraken op jouw proces.',
        ],
    ],
];
