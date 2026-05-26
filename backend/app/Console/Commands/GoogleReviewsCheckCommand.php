<?php

namespace App\Console\Commands;

use App\Services\GoogleReviewsService;
use Illuminate\Console\Command;

class GoogleReviewsCheckCommand extends Command
{
    protected $signature = 'google-reviews:check';

    protected $description = 'Toont hoeveel reviews (met min. 3 sterren) de Places API voor het geconfigureerde bedrijf teruggeeft (max 5).';

    public function handle(GoogleReviewsService $service): int
    {
        $this->info('Google Reviews – check voor geconfigureerd bedrijf');
        $this->newLine();

        $data = $service->getPlaceAndReviewsUnfiltered();
        $placeName = $data['place_name'] ?? '';
        $reviews = $data['reviews'] ?? [];
        $totalFromGoogle = (int) ($data['user_rating_count'] ?? 0);

        if ($placeName === '' && count($reviews) === 0) {
            $this->warn('Geen bedrijf geconfigureerd of geen data. Vul Place ID of bedrijfsnaam in onder Instellingen → Google Reviews.');
            return self::FAILURE;
        }

        $withMin3 = array_filter($reviews, fn ($r) => ((int) ($r['rating'] ?? 0)) >= 3);
        $countMin3 = count($withMin3);
        $countTotal = count($reviews);

        $this->table(
            ['Item', 'Waarde'],
            [
                ['Bedrijf', $placeName ?: '(naam onbekend)'],
                ['Totaal beoordelingen (Google)', $totalFromGoogle],
                ['Reviews opgehaald (Places API max 5)', $countTotal],
                ['Daarvan met minimaal 3 sterren', $countMin3],
            ]
        );

        $this->newLine();
        $this->comment('De Places API levert maximaal 5 reviews per plaats. Het getal "Totaal beoordelingen" is wat Google toont; we kunnen er maar 5 tonen.');

        return self::SUCCESS;
    }
}
