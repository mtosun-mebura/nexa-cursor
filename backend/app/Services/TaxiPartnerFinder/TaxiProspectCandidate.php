<?php

namespace App\Services\TaxiPartnerFinder;

final readonly class TaxiProspectCandidate
{
    /**
     * @param  array<string, mixed>  $rawMetadata
     */
    public function __construct(
        public string $companyName,
        public string $sourceType,
        public ?string $externalReference = null,
        public ?string $sourceUrl = null,
        public ?string $tradeName = null,
        public ?string $kvkNumber = null,
        public ?string $street = null,
        public ?string $houseNumber = null,
        public ?string $postalCode = null,
        public ?string $city = null,
        public ?string $municipality = null,
        public ?string $province = null,
        public string $country = 'NL',
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $website = null,
        public ?string $googlePlaceId = null,
        public ?string $knvMemberStatus = null,
        public int $confidenceScore = 40,
        public array $rawMetadata = [],
    ) {}
}
