<?php

namespace App\Services\CompanySearch;

final readonly class CompanySearchData
{
    /**
     * @param  list<string>  $provinces
     */
    public function __construct(
        public string $industry,
        public array $provinces,
        public string $query = '',
        public string $country = 'NL',
        public ?string $city = null,
        public int $radiusKm = 50,
        public int $maxResults = 80,
    ) {}

    /**
     * @return list<string>
     */
    public function searchTerms(): array
    {
        $terms = [];
        $industry = trim($this->industry);
        $query = trim($this->query);
        if ($industry !== '') {
            $terms[] = $industry;
        }
        if ($query !== '' && strcasecmp($query, $industry) !== 0) {
            $terms[] = $query;
        }

        return $terms !== [] ? array_values(array_unique($terms)) : ['bedrijf'];
    }
}
