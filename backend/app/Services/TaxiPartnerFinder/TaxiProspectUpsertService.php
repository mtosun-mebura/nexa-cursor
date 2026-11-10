<?php

namespace App\Services\TaxiPartnerFinder;

use App\Models\TaxiProspect;
use App\Models\TaxiProspectOptOut;
use App\Models\TaxiProspectSource;
use Illuminate\Support\Facades\DB;

class TaxiProspectUpsertService
{
    public function __construct(
        protected TaxiProspectDeduplicator $deduplicator,
        protected TaxiProspectNormalizer $normalizer,
    ) {}

    /**
     * @return array{prospect: TaxiProspect, action: 'created'|'updated'|'duplicate'|'skipped_opt_out'|'fuzzy'}
     */
    public function upsert(TaxiProspectCandidate $candidate): array
    {
        $email = $this->normalizer->normalizeEmail($candidate->email);
        $phone = $this->normalizer->normalizePhone($candidate->phone);

        if ($this->isOptedOut($email, $phone)) {
            return [
                'prospect' => new TaxiProspect(['company_name' => $candidate->companyName]),
                'action' => 'skipped_opt_out',
            ];
        }

        $match = $this->deduplicator->findMatch($candidate);

        return DB::transaction(function () use ($candidate, $match, $email, $phone) {
            if ($match['match'] instanceof TaxiProspect) {
                $prospect = $this->mergeInto($match['match'], $candidate, $email, $phone);
                $this->touchSource($prospect, $candidate);
                $action = $match['fuzzy'] ? 'fuzzy' : 'updated';

                return ['prospect' => $prospect->fresh(['sources']) ?? $prospect, 'action' => $action];
            }

            $prospect = $this->createFrom($candidate, $email, $phone);
            $this->touchSource($prospect, $candidate);

            return ['prospect' => $prospect->fresh(['sources']) ?? $prospect, 'action' => 'created'];
        });
    }

    private function isOptedOut(?string $email, ?string $phone): bool
    {
        if ($email && TaxiProspectOptOut::query()->where('email', $email)->exists()) {
            return true;
        }
        if ($phone && TaxiProspectOptOut::query()->where('phone', $phone)->exists()) {
            return true;
        }

        return false;
    }

    private function createFrom(TaxiProspectCandidate $candidate, ?string $email, ?string $phone): TaxiProspect
    {
        $website = $this->normalizer->normalizeWebsite($candidate->website);
        $postal = $this->normalizer->normalizePostalCode($candidate->postalCode);
        $prospect = TaxiProspect::query()->create([
            'company_name' => trim($candidate->companyName),
            'trade_name' => $candidate->tradeName,
            'kvk_number' => $this->normalizer->normalizeKvk($candidate->kvkNumber),
            'street' => $candidate->street,
            'house_number' => $candidate->houseNumber,
            'postal_code' => $postal,
            'city' => $candidate->city,
            'municipality' => $candidate->municipality ?? $candidate->city,
            'province' => $candidate->province,
            'country' => $candidate->country ?: 'NL',
            'phone' => $phone,
            'email' => $email,
            'website' => $website,
            'website_domain' => $this->normalizer->websiteDomain($website),
            'google_place_id' => $candidate->googlePlaceId,
            'knv_member_status' => $candidate->knvMemberStatus ?? TaxiProspect::KNV_UNKNOWN,
            'verification_status' => 'unverified',
            'lead_status' => TaxiProspect::LEAD_DISCOVERED,
            'confidence_score' => max(1, min(100, $candidate->confidenceScore)),
            'normalized_name' => $this->normalizer->normalizeName($candidate->companyName),
            'normalized_postal_code' => $postal,
            'discovered_at' => now(),
            'last_verified_at' => now(),
        ]);
        $prospect->markContactReadyIfPossible();
        $prospect->save();

        return $prospect;
    }

    private function mergeInto(
        TaxiProspect $prospect,
        TaxiProspectCandidate $candidate,
        ?string $email,
        ?string $phone
    ): TaxiProspect {
        $fill = [];
        $map = [
            'trade_name' => $candidate->tradeName,
            'kvk_number' => $this->normalizer->normalizeKvk($candidate->kvkNumber),
            'street' => $candidate->street,
            'house_number' => $candidate->houseNumber,
            'postal_code' => $this->normalizer->normalizePostalCode($candidate->postalCode),
            'city' => $candidate->city,
            'municipality' => $candidate->municipality,
            'province' => $candidate->province,
            'phone' => $phone,
            'email' => $email,
            'website' => $this->normalizer->normalizeWebsite($candidate->website),
            'google_place_id' => $candidate->googlePlaceId,
        ];
        $authoritative = in_array($candidate->sourceType, [
            TaxiProspectSource::TYPE_KNV,
            TaxiProspectSource::TYPE_KNV_CSV,
            TaxiProspectSource::TYPE_KVK,
        ], true);
        foreach ($map as $field => $value) {
            if (! filled($value)) {
                continue;
            }
            if (blank($prospect->{$field}) || ($authoritative && in_array($field, ['phone', 'email', 'website', 'street', 'house_number', 'postal_code', 'city', 'province'], true))) {
                $fill[$field] = $value;
            }
        }
        if (filled($candidate->knvMemberStatus)) {
            $fill['knv_member_status'] = $candidate->knvMemberStatus;
        }
        if (isset($fill['website'])) {
            $fill['website_domain'] = $this->normalizer->websiteDomain($fill['website']);
        }
        if (isset($fill['postal_code'])) {
            $fill['normalized_postal_code'] = $fill['postal_code'];
        }
        $fill['last_verified_at'] = now();
        $fill['confidence_score'] = max(
            (int) $prospect->confidence_score,
            max(1, min(100, $candidate->confidenceScore))
        );
        $prospect->fill($fill);
        $prospect->markContactReadyIfPossible();
        $prospect->save();

        return $prospect;
    }

    private function touchSource(TaxiProspect $prospect, TaxiProspectCandidate $candidate): void
    {
        $ref = $candidate->externalReference ?: sha1(
            $candidate->sourceType.'|'.$candidate->companyName.'|'.($candidate->postalCode ?? '')
        );

        TaxiProspectSource::query()->updateOrCreate(
            [
                'source_type' => $candidate->sourceType,
                'external_reference' => $ref,
            ],
            [
                'taxi_prospect_id' => $prospect->id,
                'source_url' => $candidate->sourceUrl,
                'discovered_at' => now(),
                'last_checked_at' => now(),
                'raw_metadata' => $candidate->rawMetadata,
            ]
        );
    }
}
