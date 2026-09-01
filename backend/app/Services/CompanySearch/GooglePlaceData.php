<?php

namespace App\Services\CompanySearch;

final readonly class GooglePlaceData
{
    public function __construct(
        public string $placeId,
        public string $name,
        public ?string $formattedAddress = null,
        public ?string $phone = null,
        public ?string $website = null,
        public ?string $city = null,
        public ?string $region = null,
        public ?string $postalCode = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {}
}
