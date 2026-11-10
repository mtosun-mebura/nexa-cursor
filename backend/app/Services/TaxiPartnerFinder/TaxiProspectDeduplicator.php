<?php

namespace App\Services\TaxiPartnerFinder;

use App\Models\TaxiProspect;
use App\Models\TaxiProspectSource;

class TaxiProspectDeduplicator
{
    public function __construct(
        protected TaxiProspectNormalizer $normalizer,
    ) {}

    /**
     * @return array{match: ?TaxiProspect, reason: ?string, fuzzy: bool}
     */
    public function findMatch(TaxiProspectCandidate $candidate): array
    {
        $kvk = $this->normalizer->normalizeKvk($candidate->kvkNumber);
        if ($kvk !== null) {
            $byKvk = TaxiProspect::query()->where('kvk_number', $kvk)->first();
            if ($byKvk) {
                return ['match' => $byKvk, 'reason' => 'kvk_number', 'fuzzy' => false];
            }
        }

        if (filled($candidate->googlePlaceId)) {
            $byPlace = TaxiProspect::query()->where('google_place_id', $candidate->googlePlaceId)->first();
            if ($byPlace) {
                return ['match' => $byPlace, 'reason' => 'google_place_id', 'fuzzy' => false];
            }
        }

        if (filled($candidate->externalReference) && filled($candidate->sourceType)) {
            $source = TaxiProspectSource::query()
                ->where('source_type', $candidate->sourceType)
                ->where('external_reference', $candidate->externalReference)
                ->first();
            if ($source?->prospect) {
                return ['match' => $source->prospect, 'reason' => 'source_reference', 'fuzzy' => false];
            }
        }

        $domain = $this->normalizer->websiteDomain($candidate->website);
        $postal = $this->normalizer->normalizePostalCode($candidate->postalCode);
        if ($domain && $postal) {
            $byDomainAddress = TaxiProspect::query()
                ->where('website_domain', $domain)
                ->where('normalized_postal_code', $postal)
                ->first();
            if ($byDomainAddress) {
                return ['match' => $byDomainAddress, 'reason' => 'domain_postal', 'fuzzy' => false];
            }
        }

        $normalizedName = $this->normalizer->normalizeName($candidate->companyName);
        if ($normalizedName !== '' && $postal) {
            $byNamePostal = TaxiProspect::query()
                ->where('normalized_name', $normalizedName)
                ->where('normalized_postal_code', $postal)
                ->first();
            if ($byNamePostal) {
                return ['match' => $byNamePostal, 'reason' => 'name_postal', 'fuzzy' => false];
            }
        }

        if ($normalizedName !== '' && filled($candidate->city)) {
            $fuzzy = TaxiProspect::query()
                ->where('normalized_name', $normalizedName)
                ->where('city', $candidate->city)
                ->first();
            if ($fuzzy) {
                return ['match' => $fuzzy, 'reason' => 'fuzzy_name_city', 'fuzzy' => true];
            }
        }

        return ['match' => null, 'reason' => null, 'fuzzy' => false];
    }
}
