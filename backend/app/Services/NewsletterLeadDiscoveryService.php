<?php

namespace App\Services;

use App\Models\NewsletterProspect;
use App\Services\CompanySearch\CompanySearchData;
use App\Services\CompanySearch\ContactNameParser;
use App\Services\CompanySearch\DomainService;
use App\Services\CompanySearch\EmailExtractorService;
use App\Services\CompanySearch\GooglePlaceData;
use App\Services\CompanySearch\GooglePlacesCompanySearchService;
use App\Services\CompanySearch\HunterService;
use App\Services\CompanySearch\PhoneExtractorService;
use App\Services\CompanySearch\WebsiteCrawlerService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NewsletterLeadDiscoveryService
{
    public function __construct(
        protected GooglePlacesCompanySearchService $places,
        protected WebsiteCrawlerService $crawler,
        protected HunterService $hunter,
        protected EmailExtractorService $emails,
        protected PhoneExtractorService $phones,
        protected DomainService $domains,
        protected ContactNameParser $contactNames,
    ) {}

    /**
     * @param  list<string>  $provinces
     * @param  array{query?: string, city?: string, radius_km?: int, max_results?: int, on_progress?: callable}  $options
     * @return array{created: int, skipped: int, scanned: int, prospects: list<NewsletterProspect>}
     */
    public function discover(string $branch, array $provinces, array $options = []): array
    {
        @set_time_limit(180);
        $branch = trim($branch) !== '' ? trim($branch) : 'taxi';
        $provinces = array_values(array_filter(array_map('trim', $provinces)));
        $city = filled($options['city'] ?? null) ? trim((string) $options['city']) : '';
        if ($provinces === [] && $city === '') {
            return ['created' => 0, 'skipped' => 0, 'scanned' => 0, 'prospects' => []];
        }

        $searchRegions = $provinces !== [] ? $provinces : [$city];
        $cap = (int) config('company-enrichment.search.max_results_cap', 500);
        $defaultMax = (int) config('newsletter.max_discover_per_run', 300);
        $budget = max(1, min($cap, (int) ($options['max_results'] ?? $defaultMax)));
        $search = new CompanySearchData(
            industry: $branch,
            provinces: $provinces,
            query: trim((string) ($options['query'] ?? '')),
            city: $city !== '' ? $city : null,
            radiusKm: max(5, min(100, (int) ($options['radius_km'] ?? config('company-enrichment.search.default_radius_km', 50)))),
            maxResults: $budget,
        );

        Log::info('Company search started', [
            'industry' => $search->industry,
            'query' => $search->query,
            'provinces' => $search->provinces,
            'city' => $search->city,
            'max_results' => $search->maxResults,
        ]);

        $onProgress = $options['on_progress'] ?? null;
        $report = function (string $phase, int $found, int $saved = 0, int $skipped = 0) use ($onProgress): void {
            if (! is_callable($onProgress)) {
                return;
            }
            $onProgress([
                'type' => 'progress',
                'phase' => $phase,
                'found' => $found,
                'saved' => $saved,
                'skipped' => $skipped,
            ]);
        };

        $report('searching', 0);
        $candidates = $this->places->isEnabled()
            ? $this->candidatesFromPlaces($search, $report)
            : [];
        if ($candidates === []) {
            $report('searching', 0);
            $candidates = $this->searchWithAi($branch, $searchRegions);
            foreach ($searchRegions as $region) {
                $candidates = array_merge($candidates, $this->searchWeb($branch, $region));
                $report('searching', count($candidates));
            }
            $candidates = $this->mergeCandidates($candidates);
        }

        $report('enriching', count($candidates), 0, 0);

        $created = 0;
        $skipped = 0;
        $saved = [];
        $deadline = microtime(true) + (int) config('newsletter.discover_timeout_seconds', 120);

        foreach ($candidates as $candidate) {
            if ($created >= $budget || microtime(true) > $deadline) {
                break;
            }

            $candidate = $this->enrichCandidate($candidate);
            $email = strtolower(trim((string) ($candidate['email'] ?? '')));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL) || $this->emails->isJunk($email)) {
                $skipped++;
                $report('enriching', count($candidates), $created, $skipped);

                continue;
            }

            $phone = $this->phones->normalize((string) ($candidate['phone'] ?? ''));
            $website = $this->domains->normalizeWebsite((string) ($candidate['website'] ?? ''));
            $placeId = trim((string) ($candidate['google_place_id'] ?? ''));
            $existing = $this->findExisting($email, $placeId);
            if ($existing) {
                if ($existing->isSubscribed()) {
                    $this->fillMissing($existing, $candidate, $phone, $website !== '' ? $website : null);
                }
                $skipped++;
                $report('enriching', count($candidates), $created, $skipped);

                continue;
            }

            $prospect = NewsletterProspect::query()->create([
                'company_name' => Str::limit(trim((string) ($candidate['name'] ?? $branch)) ?: $branch, 120, ''),
                'first_name' => $candidate['first_name'] ?? null,
                'middle_name' => $candidate['middle_name'] ?? null,
                'last_name' => $candidate['last_name'] ?? null,
                'email' => $email,
                'phone' => $phone,
                'website' => $website !== '' ? $website : null,
                'address' => $this->limitAddress($candidate['address'] ?? null),
                'city' => $candidate['city'] ?? null,
                'province' => $candidate['province'] ?? ($provinces[0] ?? ($city !== '' ? $city : null)),
                'branch' => $branch,
                'google_place_id' => $placeId !== '' ? $placeId : null,
                'source' => (string) ($candidate['source'] ?? 'ai_web'),
                'source_url' => $candidate['source_url'] ?? ($website !== '' ? $website : null),
                'status' => NewsletterProspect::STATUS_SUBSCRIBED,
                'notes' => $candidate['snippet'] ?? null,
            ]);
            $saved[] = $prospect;
            $created++;
            $report('enriching', count($candidates), $created, $skipped);
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'scanned' => count($candidates),
            'prospects' => $saved,
        ];
    }

    /**
     * @param  callable(string $phase, int $found, int $saved=, int $skipped=): void  $report
     * @return list<array<string, string|null>>
     */
    private function candidatesFromPlaces(CompanySearchData $search, callable $report): array
    {
        $out = [];
        foreach ($this->places->search($search, function (int $found) use ($report): void {
            $report('searching', $found);
        }) as $place) {
            $out[] = $this->fromPlace($place);
        }

        return $out;
    }

    /**
     * @return array<string, string|null>
     */
    private function fromPlace(GooglePlaceData $place): array
    {
        return [
            'name' => $place->name,
            'email' => null,
            'phone' => $place->phone,
            'website' => $place->website,
            'address' => $place->formattedAddress,
            'city' => $place->city,
            'province' => $place->region,
            'google_place_id' => $place->placeId,
            'source' => 'google_places',
            'source_url' => $place->website,
            'snippet' => null,
        ];
    }

    /**
     * @param  array<string, string|null>  $candidate
     * @return array<string, string|null>
     */
    private function enrichCandidate(array $candidate): array
    {
        $website = $this->domains->normalizeWebsite((string) ($candidate['website'] ?? ''));
        if ($website !== '') {
            $candidate['website'] = $website;
        }

        if ($website !== '') {
            $contacts = $this->crawler->collectContacts($website);
            if (! filled($candidate['email']) && $contacts['email']) {
                $candidate['email'] = $contacts['email'];
                Log::info('Company email discovered', [
                    'domain' => $this->domains->host($website),
                    'source' => 'company_website',
                ]);
            }
            if (! filled($candidate['phone']) && $contacts['phone']) {
                $candidate['phone'] = $contacts['phone'];
            }
            $this->applyContactName($candidate, $contacts);
        }

        if ($website !== '' && $this->hunter->isEnabled() && (! filled($candidate['email']) || ! $this->hasContactName($candidate))) {
            $hunter = $this->hunter->lookup($website);
            if (! filled($candidate['email']) && filled($hunter['email'])) {
                $candidate['email'] = $hunter['email'];
                $candidate['source'] = ($candidate['source'] ?? 'google_places') === 'google_places'
                    ? 'google_places'
                    : ($candidate['source'] ?? 'hunter');
            }
            $this->applyContactName($candidate, $hunter);
        }

        if (! $this->hasContactName($candidate) && filled($candidate['email'] ?? null)) {
            $fromEmail = $this->contactNames->fromEmail((string) $candidate['email']);
            $this->applyContactName($candidate, $fromEmail ?? []);
        }

        return $candidate;
    }

    /**
     * @param  array<string, string|null>  $candidate
     * @param  array<string, mixed>  $names
     */
    private function applyContactName(array &$candidate, array $names): void
    {
        foreach (['first_name', 'middle_name', 'last_name'] as $field) {
            if (! filled($candidate[$field] ?? null) && filled($names[$field] ?? null)) {
                $candidate[$field] = trim((string) $names[$field]);
            }
        }
    }

    /**
     * @param  array<string, string|null>  $candidate
     */
    private function hasContactName(array $candidate): bool
    {
        return filled($candidate['first_name'] ?? null) || filled($candidate['last_name'] ?? null);
    }

    private function findExisting(string $email, string $placeId): ?NewsletterProspect
    {
        if ($placeId !== '') {
            $byPlace = NewsletterProspect::query()->where('google_place_id', $placeId)->first();
            if ($byPlace) {
                return $byPlace;
            }
        }

        return NewsletterProspect::query()->where('email', $email)->first();
    }

    /**
     * @param  array<string, string|null>  $candidate
     */
    private function fillMissing(NewsletterProspect $existing, array $candidate, ?string $phone, ?string $website): void
    {
        $dirty = false;
        if (! filled($existing->phone) && $phone) {
            $existing->phone = $phone;
            $dirty = true;
        }
        if (! filled($existing->website) && $website) {
            $existing->website = $website;
            $dirty = true;
        }
        if (! filled($existing->address) && filled($candidate['address'] ?? null)) {
            $existing->address = $this->limitAddress($candidate['address']);
            $dirty = true;
        }
        if (! filled($existing->google_place_id) && filled($candidate['google_place_id'] ?? null)) {
            $existing->google_place_id = $candidate['google_place_id'];
            $dirty = true;
        }
        foreach (['first_name', 'middle_name', 'last_name'] as $field) {
            if (! filled($existing->{$field}) && filled($candidate[$field] ?? null)) {
                $existing->{$field} = $candidate[$field];
                $dirty = true;
            }
        }
        if ($dirty) {
            $existing->save();
        }
    }

    private function limitAddress(?string $address): ?string
    {
        $address = trim((string) $address);

        return $address !== '' ? Str::limit($address, 180, '') : null;
    }

    /**
     * @param  list<string>  $provinces
     * @return list<array<string, string|null>>
     */
    private function searchWithAi(string $branch, array $provinces): array
    {
        $apiKey = config('services.openai.api_key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            return [];
        }

        $prompt = 'Zoek op het openbare Nederlandse internet naar '.trim($branch).'-bedrijven in deze regio: '
            .implode(', ', $provinces)
            .".\nGeef echte bedrijven met website, e-mailadres, telefoonnummer en waar zichtbaar de voornaam, tussenvoegsel en achternaam van de eigenaar of contactpersoon."
            ."\nVerzin geen e-mailadressen, telefoonnummers of namen. Sla een veld over als je het niet vond."
            ."\nAntwoord uitsluitend met JSON: {\"companies\":[{\"name\":\"\",\"city\":\"\",\"province\":\"\",\"website\":\"https://...\",\"email\":\"\",\"phone\":\"\",\"first_name\":\"\",\"middle_name\":\"\",\"last_name\":\"\",\"source_url\":\"\"}]}"
            ."\nMaximaal 30 bedrijven.";

        $payload = [
            'model' => config('services.openai.web_search_model', config('services.openai.model', 'gpt-4o-mini')),
            'tools' => [['type' => 'web_search']],
            'input' => $prompt,
        ];

        try {
            $response = Http::withToken($apiKey)
                ->timeout(90)
                ->acceptJson()
                ->post('https://api.openai.com/v1/responses', $payload);
            if ($response->status() === 400) {
                $payload['tools'] = [['type' => 'web_search_preview']];
                $response = Http::withToken($apiKey)
                    ->timeout(90)
                    ->acceptJson()
                    ->post('https://api.openai.com/v1/responses', $payload);
            }
            if (! $response->successful()) {
                return [];
            }
        } catch (\Throwable) {
            return [];
        }

        $text = $this->openAiOutputText($response->json() ?? []);
        $decoded = $this->decodeJsonObject($text);
        $rows = is_array($decoded['companies'] ?? null) ? $decoded['companies'] : [];
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            $website = $this->domains->normalizeWebsite((string) ($row['website'] ?? $row['source_url'] ?? ''));
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            if ($name === '' && $website === '' && $email === '') {
                continue;
            }
            $parsedName = $this->contactNames->fromParts(
                (string) ($row['first_name'] ?? ''),
                trim((string) ($row['middle_name'] ?? '').' '.(string) ($row['last_name'] ?? ''))
            );
            $out[] = [
                'name' => $name !== '' ? $name : ucfirst($branch),
                'website' => $website !== '' ? $website : null,
                'email' => $email !== '' ? $email : null,
                'phone' => $this->phones->normalize((string) ($row['phone'] ?? '')),
                'city' => trim((string) ($row['city'] ?? '')) ?: null,
                'province' => trim((string) ($row['province'] ?? '')) ?: ($provinces[0] ?? null),
                'first_name' => $parsedName['first_name'] ?? null,
                'middle_name' => $parsedName['middle_name'] ?? null,
                'last_name' => $parsedName['last_name'] ?? null,
                'source' => 'ai_web',
                'source_url' => $this->domains->normalizeWebsite((string) ($row['source_url'] ?? $website)) ?: $website,
                'snippet' => null,
            ];
        }

        return $out;
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function searchWeb(string $branch, string $province): array
    {
        $queries = [
            $branch.' bedrijf '.$province.' Nederland contact',
            'taxibedrijf '.$province.' telefoon e-mail',
        ];
        $city = config('newsletter.province_cities.'.$province);
        if (is_string($city) && $city !== '') {
            $queries[] = $branch.' '.$city.' contactgegevens';
        }

        $out = [];
        foreach ($queries as $query) {
            $out = array_merge($out, $this->searchDuckDuckGo($query, $province, $branch));
        }

        return $out;
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function searchDuckDuckGo(string $query, string $province, string $branch): array
    {
        $html = '';
        try {
            $lite = Http::timeout(15)
                ->withHeaders($this->browserHeaders())
                ->get('https://lite.duckduckgo.com/lite/', ['q' => $query]);
            if ($lite->successful()) {
                $html .= $lite->body();
            }
            $full = Http::timeout(15)
                ->withHeaders($this->browserHeaders())
                ->asForm()
                ->post('https://html.duckduckgo.com/html/', [
                    'q' => $query,
                    'kl' => 'nl-nl',
                ]);
            if ($full->successful()) {
                $html .= $full->body();
            }
        } catch (\Throwable) {
            return [];
        }

        $out = [];
        if (preg_match_all('/<a[^>]+href="(https?:[^"]+)"[^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $url = $this->unwrapDuckUrl(html_entity_decode(trim($match[1])));
                if ($url === '' || ! str_starts_with($url, 'http')) {
                    continue;
                }
                $host = strtolower((string) parse_url($url, PHP_URL_HOST));
                if ($this->isJunkHost($host)) {
                    continue;
                }
                $name = trim(html_entity_decode(strip_tags($match[2])));
                $name = preg_replace('/\s+/', ' ', $name) ?? $name;
                if ($name === '' || strlen($name) > 80) {
                    $name = ucfirst($branch).' '.$province;
                }
                $out[$host] = [
                    'name' => $name,
                    'website' => $url,
                    'email' => null,
                    'phone' => null,
                    'city' => is_string(config('newsletter.province_cities.'.$province)) ? config('newsletter.province_cities.'.$province) : null,
                    'province' => $province,
                    'source_url' => $url,
                    'snippet' => null,
                ];
            }
        }

        return array_values($out);
    }

    /**
     * @param  array<string, string|null>  $candidate
     * @return array<string, string|null>
     */
    private function enrichFromWebsite(array $candidate): array
    {
        $website = $this->cleanUrl((string) ($candidate['website'] ?? ''));
        if ($website === '') {
            return $candidate;
        }

        $pages = [$website];
        $home = $this->fetchHtml($website);
        if ($home !== '') {
            $candidate = $this->applyContactFromHtml($candidate, $home);
            foreach ($this->contactLinks($home, $website) as $link) {
                $pages[] = $link;
            }
        }

        $origin = $this->originOf($website);
        if ($origin !== '') {
            foreach (['/contact', '/contact.html', '/contactgegevens', '/over-ons', '/nl/contact'] as $path) {
                $pages[] = $origin.$path;
            }
        }

        $pages = array_values(array_unique(array_slice($pages, 0, 4)));
        foreach ($pages as $page) {
            if (filled($candidate['email']) && filled($candidate['phone'])) {
                break;
            }
            $html = $page === $website ? $home : $this->fetchHtml($page);
            if ($html === '') {
                continue;
            }
            $candidate = $this->applyContactFromHtml($candidate, $html);
        }

        return $candidate;
    }

    /**
     * @param  array<string, string|null>  $candidate
     * @return array<string, string|null>
     */
    private function applyContactFromHtml(array $candidate, string $html): array
    {
        if (! filled($candidate['email'])) {
            $email = $this->firstEmail($html);
            if ($email) {
                $candidate['email'] = $email;
            }
        }
        if (! filled($candidate['phone'])) {
            $phone = $this->firstPhone($html);
            if ($phone) {
                $candidate['phone'] = $phone;
            }
        }
        $title = $this->pageTitle($html);
        $name = trim((string) ($candidate['name'] ?? ''));
        $genericName = $name === '' || (bool) preg_match('/^taxi\s+[A-Za-z\-]+$/i', $name);
        if ($title && $genericName && ! preg_match('/^(contact|home|welkom)\b/i', $title)) {
            $candidate['name'] = Str::limit($title, 120, '');
        }

        return $candidate;
    }

    /**
     * @return list<string>
     */
    private function contactLinks(string $html, string $baseUrl): array
    {
        $out = [];
        if (! preg_match_all('/href=["\']([^"\']+)["\']/i', $html, $matches)) {
            return [];
        }
        foreach ($matches[1] as $href) {
            $href = html_entity_decode(trim($href));
            if (! preg_match('/contact|contactgegevens|over-ons/i', $href)) {
                continue;
            }
            $absolute = $this->absoluteUrl($href, $baseUrl);
            if ($absolute !== '') {
                $out[] = $absolute;
            }
        }

        return array_slice(array_values(array_unique($out)), 0, 2);
    }

    private function fetchHtml(string $url): string
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders($this->browserHeaders())
                ->get($url);
            if (! $response->successful()) {
                return '';
            }

            return $response->body();
        } catch (\Throwable) {
            return '';
        }
    }

    private function firstEmail(string $html): ?string
    {
        if (preg_match_all('/mailto:([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})/i', $html, $matches)) {
            foreach ($matches[1] as $email) {
                $email = strtolower($email);
                if (! $this->isJunkEmail($email)) {
                    return $email;
                }
            }
        }
        if (preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $html, $matches)) {
            foreach ($matches[0] as $email) {
                $email = strtolower($email);
                if (! $this->isJunkEmail($email)) {
                    return $email;
                }
            }
        }

        return null;
    }

    private function firstPhone(string $html): ?string
    {
        if (preg_match_all('/href=["\']tel:([^"\']+)["\']/i', $html, $matches)) {
            foreach ($matches[1] as $raw) {
                $phone = $this->normalizePhone(urldecode($raw));
                if ($phone) {
                    return $phone;
                }
            }
        }
        if (preg_match_all('/(?:\+|00)?31[\s\-\.(]*[1-9](?:[\s\-\.(]*\d){8}|0[1-9](?:[\s\-\.(]*\d){8}/', $html, $matches)) {
            foreach ($matches[0] as $raw) {
                $phone = $this->normalizePhone($raw);
                if ($phone) {
                    return $phone;
                }
            }
        }

        return null;
    }

    private function normalizePhone(string $value): ?string
    {
        return $this->phones->normalize($value);
    }

    private function pageTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $match)) {
            $title = trim(html_entity_decode(strip_tags($match[1])));

            return $title !== '' ? $title : null;
        }

        return null;
    }

    /**
     * @param  list<array<string, string|null>>  $list
     * @return list<array<string, string|null>>
     */
    private function mergeCandidates(array $list): array
    {
        $out = [];
        foreach ($list as $row) {
            $host = strtolower((string) parse_url((string) ($row['website'] ?? ''), PHP_URL_HOST));
            $email = strtolower((string) ($row['email'] ?? ''));
            $key = $email !== '' ? 'e:'.$email : ($host !== '' ? 'h:'.$host : 'n:'.strtolower((string) ($row['name'] ?? uniqid())));
            if (! isset($out[$key])) {
                $out[$key] = $row;

                continue;
            }
            foreach (['name', 'email', 'phone', 'website', 'city', 'province', 'source_url', 'first_name', 'middle_name', 'last_name'] as $field) {
                if (! filled($out[$key][$field] ?? null) && filled($row[$field] ?? null)) {
                    $out[$key][$field] = $row[$field];
                }
            }
        }

        return array_values($out);
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function openAiOutputText(array $json): string
    {
        if (isset($json['output_text']) && is_string($json['output_text'])) {
            return $json['output_text'];
        }
        $chunks = [];
        foreach ($json['output'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            foreach ($item['content'] ?? [] as $content) {
                if (! is_array($content)) {
                    continue;
                }
                $text = $content['text'] ?? $content['output_text'] ?? null;
                if (is_string($text) && $text !== '') {
                    $chunks[] = $text;
                }
            }
        }

        return implode("\n", $chunks);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonObject(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }
        if (preg_match('/\{.*\}/s', $text, $match)) {
            $decoded = json_decode($match[0], true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function unwrapDuckUrl(string $url): string
    {
        if (str_contains($url, 'uddg=')) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $target = urldecode((string) ($query['uddg'] ?? ''));
            if ($target !== '') {
                return $target;
            }
        }

        return $url;
    }

    private function cleanUrl(string $url): string
    {
        return $this->domains->normalizeWebsite($url);
    }

    private function originOf(string $url): string
    {
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        return $parts['scheme'].'://'.$parts['host'];
    }

    private function absoluteUrl(string $href, string $baseUrl): string
    {
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $this->cleanUrl($href);
        }
        $origin = $this->originOf($baseUrl);
        if ($origin === '') {
            return '';
        }
        if (str_starts_with($href, '/')) {
            return $origin.$href;
        }

        return $origin.'/'.ltrim($href, '/');
    }

    /**
     * @return array<string, string>
     */
    private function browserHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (compatible; NexaSuiteLeadBot/1.0; +https://nexasuite.nl)',
            'Accept' => 'text/html,application/xhtml+xml',
            'Accept-Language' => 'nl-NL,nl;q=0.9,en;q=0.8',
        ];
    }

    private function isJunkEmail(string $email): bool
    {
        return $this->emails->isJunk($email);
    }

    private function isJunkHost(string $host): bool
    {
        foreach ([
            'duckduckgo.', 'google.', 'googleusercontent.', 'facebook.', 'instagram.', 'linkedin.',
            'youtube.', 'wikipedia.', 'bing.', 'yahoo.', 'apple.com', 'microsoft.com', 'schema.org',
        ] as $needle) {
            if (str_contains($host, $needle)) {
                return true;
            }
        }

        return $host === '';
    }
}
