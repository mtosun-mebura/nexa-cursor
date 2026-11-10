<?php

namespace App\Services\TaxiPartnerFinder;

use App\Models\TaxiProspect;
use App\Models\TaxiProspectSource;
use InvalidArgumentException;

class KnvCsvImportAdapter
{
    public function __construct(
        protected TaxiProspectNormalizer $normalizer,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('taxi-partner-finder.sources.knv_csv.enabled', true)
            && (bool) config('taxi-partner-finder.enabled', true);
    }

    /**
     * @return list<TaxiProspectCandidate>
     */
    public function parse(string $csvContents): array
    {
        if (! $this->isEnabled()) {
            throw new InvalidArgumentException('KNV CSV-import is uitgeschakeld.');
        }

        $csvContents = preg_replace('/^\xEF\xBB\xBF/', '', $csvContents) ?? $csvContents;
        $firstLine = strtok($csvContents, "\n") ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new InvalidArgumentException('Kon CSV niet openen.');
        }
        fwrite($handle, $csvContents);
        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter);
        if (! is_array($header) || $header === []) {
            fclose($handle);
            throw new InvalidArgumentException('CSV heeft geen kopregel.');
        }

        $map = $this->mapHeaders($header);
        if (! isset($map['company_name'])) {
            fclose($handle);
            throw new InvalidArgumentException('CSV mist kolom bedrijfsnaam / company_name.');
        }

        $candidates = [];
        $rowNum = 1;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNum++;
            if ($this->rowEmpty($row)) {
                continue;
            }
            $get = function (string $key) use ($map, $row): ?string {
                $idx = $map[$key] ?? null;
                if ($idx === null || ! array_key_exists($idx, $row)) {
                    return null;
                }
                $value = trim((string) $row[$idx]);

                return $value !== '' ? $value : null;
            };

            $company = $get('company_name');
            if ($company === null) {
                continue;
            }

            $streetParts = $this->normalizer->splitStreetLine($get('street') ?? $get('address'));
            $candidates[] = new TaxiProspectCandidate(
                companyName: $company,
                sourceType: TaxiProspectSource::TYPE_KNV_CSV,
                externalReference: $get('external_reference') ?: ('csv-row-'.$rowNum),
                sourceUrl: null,
                tradeName: $get('trade_name'),
                kvkNumber: $this->normalizer->normalizeKvk($get('kvk_number')),
                street: $streetParts['street'] ?? $get('street'),
                houseNumber: $streetParts['house_number'] ?? $get('house_number'),
                postalCode: $this->normalizer->normalizePostalCode($get('postal_code')),
                city: $get('city'),
                municipality: $get('municipality') ?? $get('city'),
                province: $get('province'),
                phone: $this->normalizer->normalizePhone($get('phone')),
                email: $this->normalizer->normalizeEmail($get('email')),
                website: $this->normalizer->normalizeWebsite($get('website')),
                knvMemberStatus: TaxiProspect::KNV_MEMBER,
                confidenceScore: 80,
                rawMetadata: [
                    'consulted_at' => now()->toIso8601String(),
                    'source_label' => 'KNV CSV-export',
                    'row' => $rowNum,
                ],
            );
        }
        fclose($handle);

        return $candidates;
    }

    /**
     * @param  list<string|null>  $header
     * @return array<string, int>
     */
    private function mapHeaders(array $header): array
    {
        $aliases = [
            'company_name' => ['company_name', 'company-name', 'bedrijfsnaam', 'naam', 'name', 'handelsnaam'],
            'trade_name' => ['trade_name', 'trade-name', 'handelsnaam'],
            'kvk_number' => ['kvk_number', 'kvk-number', 'kvk', 'kvk-nummer', 'kvknummer'],
            'street' => ['street', 'straat', 'adres', 'address'],
            'address' => ['address', 'adres'],
            'house_number' => ['house_number', 'house-number', 'huisnummer'],
            'postal_code' => ['postal_code', 'postal-code', 'postcode'],
            'city' => ['city', 'plaats', 'woonplaats'],
            'municipality' => ['municipality', 'gemeente'],
            'province' => ['province', 'provincie'],
            'phone' => ['phone', 'telefoon', 'tel', 'telefoonnummer'],
            'email' => ['email', 'e-mail', 'mail'],
            'website' => ['website', 'web', 'url'],
            'external_reference' => ['external_reference', 'external-reference', 'id', 'knv_id', 'knv-id', 'lid_id', 'lid-id'],
        ];

        $map = [];
        foreach ($header as $index => $label) {
            $key = $this->normalizeHeader((string) $label);
            foreach ($aliases as $field => $names) {
                if (in_array($key, $names, true) && ! isset($map[$field])) {
                    $map[$field] = $index;
                }
            }
        }

        return $map;
    }

    private function normalizeHeader(string $label): string
    {
        $label = mb_strtolower(trim($label));

        return str_replace([' ', '_'], ['-', '-'], $label);
    }

    /**
     * @param  list<string|null>  $row
     */
    private function rowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
