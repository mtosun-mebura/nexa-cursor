<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterProspect;
use App\Models\User;
use App\Services\NewsletterAiWriter;
use App\Services\NewsletterHtmlCompiler;
use App\Services\NewsletterLeadDiscoveryService;
use App\Services\NewsletterSendService;
use App\Services\UserRoleAssignmentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminNewsletterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        config([
            'services.openai.api_key' => null,
            'company-enrichment.google.enabled' => false,
            'company-enrichment.hunter.enabled' => false,
        ]);
    }

    #[Test]
    public function super_admin_can_open_newsletter_pages(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => null])
            ->get(route('admin.newsletters.index'))
            ->assertOk()
            ->assertSee('Nieuwsbrieven', false)
            ->assertSee('Designer', false)
            ->assertSee('Klantenlijst', false)
            ->assertDontSee('Kies links in de zijbalk een tenant', false);

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => null])
            ->get(route('admin.newsletters.create'))
            ->assertOk()
            ->assertSee('Aanmelden via contact', false)
            ->assertSee('Laat AI schrijven', false)
            ->assertDontSee('&amp;lt;!DOCTYPE', false)
            ->assertDontSee('Kies links in de zijbalk een tenant', false);

        $this->actingAs($admin)
            ->get(route('admin.newsletters.prospects'))
            ->assertOk()
            ->assertSee('Zoek bedrijven', false)
            ->assertSee('Bedrijven op de lijst', false);

        $this->actingAs($admin)
            ->get(route('admin.newsletters.send'))
            ->assertOk()
            ->assertSee('opt-inlijst', false);
    }

    #[Test]
    public function prospects_page_uses_client_datatable_with_filters(): void
    {
        $admin = $this->superAdmin();
        NewsletterProspect::query()->create([
            'company_name' => 'Taxi Datatable Test',
            'email' => 'datatable@taxi.test',
            'phone' => '0612345678',
            'city' => 'Zwolle',
            'province' => 'Overijssel',
            'source' => 'ai_web',
            'status' => NewsletterProspect::STATUS_SUBSCRIBED,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.newsletters.prospects'))
            ->assertOk()
            ->assertSee('Zoek op bedrijf, contact, e-mail, telefoon', false)
            ->assertSee('data-admin-datatable="true"', false)
            ->assertSee('data-admin-datatable-filter="province"', false)
            ->assertSee('Taxi Datatable Test', false)
            ->assertSee('datatable@taxi.test', false)
            ->assertSee('aria-label="Legenda bronnen"', false)
            ->assertSee('ki-technology-4', false)
            ->assertSee('data-kt-select-placeholder="Selecteer een provincie"', false)
            ->assertSee('data-kt-select-placeholder="Selecteer een status"', false)
            ->assertSee('data-kt-select-placeholder="Selecteer een bron"', false)
            ->assertSee('id="discover-elapsed"', false)
            ->assertSee('id="discover-form-error"', false)
            ->assertSee('discover-form-error" class="kt-alert kt-alert-danger mb-3 hidden"', false)
            ->assertDontSee('Geen bedrijven met een openbaar e-mailadres gevonden', false)
            ->assertSee('Vul een plaats in', false);
    }

    #[Test]
    public function prospects_page_ignores_stale_discover_error_query(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('admin.newsletters.prospects', ['discover_error' => 1]))
            ->assertOk()
            ->assertDontSee('Geen bedrijven met een openbaar e-mailadres gevonden', false);
    }

    #[Test]
    public function company_admin_cannot_open_newsletters(): void
    {
        [$user] = $this->companyAdmin();

        $this->actingAs($user)
            ->from(route('admin.customer-emails.index'))
            ->get(route('admin.newsletters.index'))
            ->assertRedirect(route('admin.customer-emails.index'));

        $this->actingAs($user)
            ->get(route('admin.customer-emails.index'))
            ->assertOk()
            ->assertDontSee(route('admin.newsletters.index'), false);
    }

    #[Test]
    public function ai_generate_fills_default_copy_without_api_key(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->postJson(route('admin.newsletters.generate'), ['brief' => 'contractvervoer'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('blocks.cta_url', '/contact')
            ->assertSee('Aanmelden via contact');
    }

    #[Test]
    public function html_compiler_includes_contact_cta_and_unsubscribe(): void
    {
        $prospect = NewsletterProspect::query()->create([
            'company_name' => 'Stadstaxi Utrecht',
            'email' => 'info@stadstaxi-utrecht.test',
            'province' => 'Utrecht',
            'status' => NewsletterProspect::STATUS_SUBSCRIBED,
        ]);
        $html = app(NewsletterHtmlCompiler::class)->compile(
            app(NewsletterAiWriter::class)->defaultBlocks(),
            $prospect
        );

        $this->assertStringContainsString('Aanmelden via contact', $html);
        $this->assertStringContainsString('/contact', $html);
        $this->assertStringContainsString('Beste Stadstaxi Utrecht', $html);
        $this->assertStringContainsString('Hier afmelden', $html);
        $this->assertStringContainsString($prospect->unsubscribeUrl(), $html);
        $this->assertStringContainsString('feature-taxi-booking', $html);
    }

    #[Test]
    public function html_compiler_uses_contact_name_in_greeting(): void
    {
        $prospect = NewsletterProspect::query()->create([
            'company_name' => 'Stadstaxi Utrecht',
            'first_name' => 'Jan',
            'middle_name' => 'van der',
            'last_name' => 'Berg',
            'email' => 'jan@stadstaxi-utrecht.test',
            'province' => 'Utrecht',
            'status' => NewsletterProspect::STATUS_SUBSCRIBED,
        ]);
        $html = app(NewsletterHtmlCompiler::class)->compile(
            app(NewsletterAiWriter::class)->defaultBlocks(),
            $prospect
        );

        $this->assertStringContainsString('Beste Jan van der Berg', $html);
        $this->assertStringNotContainsString('Beste Stadstaxi Utrecht', $html);
    }

    #[Test]
    public function discovery_saves_opt_in_prospects_from_web_search(): void
    {
        Http::fake([
            'lite.duckduckgo.com/*' => Http::response(
                '<a href="https://stadstaxi-utrecht.test/contact">Stadstaxi Utrecht</a>'
            ),
            'html.duckduckgo.com/*' => Http::response(
                '<a class="result__a" href="https://stadstaxi-utrecht.test/contact">Stadstaxi Utrecht</a>'
            ),
            'stadstaxi-utrecht.test/*' => Http::response(
                '<html><body>Mail ons: <a href="mailto:info@stadstaxi-utrecht.nl">info@stadstaxi-utrecht.nl</a> of bel <a href="tel:0302311111">030-231 11 11</a></body></html>'
            ),
        ]);

        $result = app(NewsletterLeadDiscoveryService::class)->discover('taxi', ['Utrecht']);

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('newsletter_prospects', [
            'email' => 'info@stadstaxi-utrecht.nl',
            'phone' => '0302311111',
            'company_name' => 'Stadstaxi Utrecht',
            'province' => 'Utrecht',
            'status' => NewsletterProspect::STATUS_SUBSCRIBED,
        ]);
    }

    #[Test]
    public function discovery_uses_ai_web_search_then_stores_email_and_phone(): void
    {
        config(['services.openai.api_key' => 'sk-test-newsletter']);
        Http::fake([
            'api.openai.com/*' => Http::response([
                'output_text' => json_encode([
                    'companies' => [[
                        'name' => 'Rijnstad Taxi',
                        'city' => 'Utrecht',
                        'province' => 'Utrecht',
                        'website' => 'https://rijnstad-taxi.test',
                        'email' => '',
                        'phone' => '',
                        'source_url' => 'https://rijnstad-taxi.test/contact',
                    ]],
                ], JSON_UNESCAPED_UNICODE),
            ]),
            '*duckduckgo.com/*' => Http::response('<html></html>'),
            '*rijnstad-taxi.test*' => Http::response(
                '<html><body>Contact: info@rijnstad-taxi.test tel 030-2121212</body></html>'
            ),
        ]);

        $result = app(NewsletterLeadDiscoveryService::class)->discover('taxi', ['Utrecht']);

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('newsletter_prospects', [
            'email' => 'info@rijnstad-taxi.test',
            'phone' => '0302121212',
            'company_name' => 'Rijnstad Taxi',
            'source' => 'ai_web',
        ]);
    }

    #[Test]
    public function discovery_uses_google_places_then_website_email(): void
    {
        config([
            'company-enrichment.google.enabled' => true,
            'company-enrichment.google.api_key' => 'test-places-key',
            'company-enrichment.google.requests_per_second' => 100,
            'company-enrichment.google.max_pages_per_query' => 1,
            'company-enrichment.crawler.delay_ms' => 0,
        ]);
        Http::fake([
            'places.googleapis.com/*' => Http::response([
                'places' => [[
                    'id' => 'ChIJ-test-enschede-taxi',
                    'displayName' => ['text' => 'Taxi Enschede Test'],
                    'formattedAddress' => 'Stationsplein 1, 7511 JD Enschede, Nederland',
                    'nationalPhoneNumber' => '053 123 45 67',
                    'websiteUri' => 'https://taxi-enschede.test',
                    'location' => ['latitude' => 52.2215, 'longitude' => 6.8937],
                    'addressComponents' => [
                        ['longText' => 'Enschede', 'types' => ['locality']],
                        ['longText' => 'Overijssel', 'types' => ['administrative_area_level_1']],
                        ['longText' => '7511 JD', 'types' => ['postal_code']],
                    ],
                ]],
            ]),
            'taxi-enschede.test/*' => Http::response(
                '<html><body>'
                .'<script type="application/ld+json">{"@type":"LocalBusiness","founder":{"@type":"Person","name":"Kees van Dijk"}}</script>'
                .'<a href="mailto:info@taxi-enschede.test">Mail ons</a>'
                .'</body></html>'
            ),
        ]);

        $result = app(NewsletterLeadDiscoveryService::class)->discover('taxi', ['Overijssel'], [
            'city' => 'Enschede',
            'radius_km' => 50,
            'max_results' => 10,
        ]);

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('newsletter_prospects', [
            'email' => 'info@taxi-enschede.test',
            'phone' => '0531234567',
            'company_name' => 'Taxi Enschede Test',
            'address' => 'Stationsplein 1, 7511 JD Enschede, Nederland',
            'google_place_id' => 'ChIJ-test-enschede-taxi',
            'source' => 'google_places',
            'city' => 'Enschede',
            'province' => 'Overijssel',
            'first_name' => 'Kees',
            'middle_name' => 'van',
            'last_name' => 'Dijk',
        ]);
    }

    #[Test]
    public function discovery_from_city_without_provinces(): void
    {
        config([
            'company-enrichment.google.enabled' => true,
            'company-enrichment.google.api_key' => 'test-places-key',
            'company-enrichment.google.requests_per_second' => 100,
            'company-enrichment.google.max_pages_per_query' => 1,
            'company-enrichment.crawler.delay_ms' => 0,
        ]);
        Http::fake([
            'places.googleapis.com/*' => Http::response([
                'places' => [[
                    'id' => 'ChIJ-city-only-taxi',
                    'displayName' => ['text' => 'Taxi Hengelo Test'],
                    'formattedAddress' => 'Markt 2, Hengelo',
                    'nationalPhoneNumber' => '074 111 22 33',
                    'websiteUri' => 'https://taxi-hengelo.test',
                    'addressComponents' => [
                        ['longText' => 'Hengelo', 'types' => ['locality']],
                        ['longText' => 'Overijssel', 'types' => ['administrative_area_level_1']],
                    ],
                ]],
            ]),
            'taxi-hengelo.test/*' => Http::response(
                '<html><body><a href="mailto:info@taxi-hengelo.test">Mail</a></body></html>'
            ),
        ]);

        $result = app(NewsletterLeadDiscoveryService::class)->discover('taxi', [], [
            'city' => 'Enschede',
            'radius_km' => 50,
            'max_results' => 10,
        ]);

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('newsletter_prospects', [
            'email' => 'info@taxi-hengelo.test',
            'google_place_id' => 'ChIJ-city-only-taxi',
            'source' => 'google_places',
        ]);
    }

    #[Test]
    public function discover_stream_reports_found_count(): void
    {
        config([
            'company-enrichment.google.enabled' => true,
            'company-enrichment.google.api_key' => 'test-places-key',
            'company-enrichment.google.max_pages_per_query' => 1,
            'company-enrichment.crawler.delay_ms' => 0,
        ]);
        Http::fake([
            'places.googleapis.com/*' => Http::response([
                'places' => [[
                    'id' => 'ChIJ-stream-count',
                    'displayName' => ['text' => 'Stream Taxi'],
                    'formattedAddress' => 'Markt 1, Enschede',
                    'nationalPhoneNumber' => '053 111 22 33',
                    'websiteUri' => 'https://stream-taxi.test',
                    'addressComponents' => [
                        ['longText' => 'Enschede', 'types' => ['locality']],
                        ['longText' => 'Overijssel', 'types' => ['administrative_area_level_1']],
                    ],
                ]],
            ]),
            'stream-taxi.test/*' => Http::response(
                '<html><body><a href="mailto:info@stream-taxi.test">Mail</a></body></html>'
            ),
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->withHeaders([
                'X-Discover-Stream' => '1',
                'Accept' => 'application/x-ndjson',
            ])
            ->post(route('admin.newsletters.discover'), [
                'branch' => 'taxi',
                'city' => 'Enschede',
                'provinces' => ['Overijssel'],
                'max_results' => 10,
            ]);

        $response->assertOk();
        $body = $response->streamedContent();
        $this->assertStringContainsString('"phase":"searching"', $body);
        $this->assertStringContainsString('"found":1', $body);
        $this->assertStringContainsString('"type":"complete"', $body);
        $this->assertStringContainsString('"saved":1', $body);
    }

    #[Test]
    public function discover_stream_works_with_city_and_no_provinces(): void
    {
        config([
            'company-enrichment.google.enabled' => true,
            'company-enrichment.google.api_key' => 'test-places-key',
            'company-enrichment.google.max_pages_per_query' => 1,
            'company-enrichment.crawler.delay_ms' => 0,
        ]);
        Http::fake([
            'places.googleapis.com/*' => Http::response([
                'places' => [[
                    'id' => 'ChIJ-city-stream',
                    'displayName' => ['text' => 'Plaats Taxi'],
                    'formattedAddress' => 'Markt 3, Enschede',
                    'nationalPhoneNumber' => '053 222 33 44',
                    'websiteUri' => 'https://plaats-taxi.test',
                    'addressComponents' => [
                        ['longText' => 'Enschede', 'types' => ['locality']],
                        ['longText' => 'Overijssel', 'types' => ['administrative_area_level_1']],
                    ],
                ]],
            ]),
            'plaats-taxi.test/*' => Http::response(
                '<html><body><a href="mailto:info@plaats-taxi.test">Mail</a></body></html>'
            ),
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->withHeaders([
                'X-Discover-Stream' => '1',
                'Accept' => 'application/x-ndjson',
            ])
            ->post(route('admin.newsletters.discover'), [
                'branch' => 'taxi',
                'city' => 'Enschede',
                'radius_km' => 40,
                'max_results' => 10,
            ]);

        $response->assertOk();
        $body = $response->streamedContent();
        $this->assertStringContainsString('"type":"complete"', $body);
        $this->assertStringContainsString('"saved":1', $body);
        $this->assertDatabaseHas('newsletter_prospects', [
            'email' => 'info@plaats-taxi.test',
        ]);
    }

    #[Test]
    public function discover_rejects_without_province_or_city(): void
    {
        $this->actingAs($this->superAdmin())
            ->from(route('admin.newsletters.prospects'))
            ->post(route('admin.newsletters.discover'), [
                'branch' => 'taxi',
            ])
            ->assertRedirect(route('admin.newsletters.prospects'))
            ->assertSessionHasErrors('city');
    }

    #[Test]
    public function discover_stream_returns_json_error_without_region(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->withHeaders([
                'X-Discover-Stream' => '1',
                'Accept' => 'application/x-ndjson',
            ])
            ->post(route('admin.newsletters.discover'), [
                'branch' => 'taxi',
            ]);

        $response->assertUnprocessable();
        $response->assertJsonPath(
            'message',
            'Kies minstens één provincie, of vul een plaats in. De straal wordt vanaf die plaats gebruikt.'
        );
    }

    #[Test]
    public function unsubscribed_prospect_is_skipped_on_next_send(): void
    {
        Mail::fake();
        $active = NewsletterProspect::query()->create([
            'company_name' => 'Actieve Taxi',
            'email' => 'actief@taxi.test',
            'province' => 'Utrecht',
            'status' => NewsletterProspect::STATUS_SUBSCRIBED,
        ]);
        $gone = NewsletterProspect::query()->create([
            'company_name' => 'Afgemelde Taxi',
            'email' => 'weg@taxi.test',
            'province' => 'Utrecht',
            'status' => NewsletterProspect::STATUS_SUBSCRIBED,
        ]);
        $gone->unsubscribe();

        $campaign = NewsletterCampaign::query()->create([
            'name' => 'Testcampagne',
            'subject' => 'NEXA voor taxibedrijven',
            'blocks' => app(NewsletterAiWriter::class)->defaultBlocks(),
            'status' => NewsletterCampaign::STATUS_DRAFT,
        ]);

        $stats = app(NewsletterSendService::class)->send($campaign);

        $this->assertSame(1, $stats['sent']);
        $this->assertDatabaseHas('newsletter_sends', [
            'campaign_id' => $campaign->id,
            'prospect_id' => $active->id,
            'status' => 'sent',
        ]);
        $this->assertDatabaseMissing('newsletter_sends', [
            'campaign_id' => $campaign->id,
            'prospect_id' => $gone->id,
        ]);
    }

    #[Test]
    public function public_unsubscribe_link_opts_out(): void
    {
        $prospect = NewsletterProspect::query()->create([
            'company_name' => 'Taxi Afmelden',
            'email' => 'afmelden@taxi.test',
            'status' => NewsletterProspect::STATUS_SUBSCRIBED,
        ]);

        $this->get(route('newsletter.unsubscribe', $prospect->unsubscribe_token))
            ->assertOk()
            ->assertSee('U bent afgemeld', false);

        $this->assertFalse($prospect->fresh()->isSubscribed());

        $this->post(route('newsletter.unsubscribe.one-click', $prospect->unsubscribe_token))
            ->assertOk();
    }

    #[Test]
    public function super_admin_can_store_a_campaign(): void
    {
        $admin = $this->superAdmin();
        $blocks = app(NewsletterAiWriter::class)->defaultBlocks();

        $this->actingAs($admin)
            ->post(route('admin.newsletters.store'), [
                'name' => 'Taxi werving mei',
                'subject' => 'Ritten in eigen systeem',
                'preview_text' => 'Geen commissie',
                'eyebrow' => 'Voor taxibedrijven',
                'title' => $blocks['title'],
                'intro' => $blocks['intro'],
                'hero_image' => $blocks['hero_image'],
                'cta_label' => 'Aanmelden via contact',
                'cta_url' => '/contact',
                'features' => $blocks['features'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('newsletter_campaigns', [
            'name' => 'Taxi werving mei',
            'subject' => 'Ritten in eigen systeem',
        ]);
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        return $admin;
    }

    /**
     * @return array{0: User, 1: Company}
     */
    private function companyAdmin(): array
    {
        $company = Company::query()->create([
            'name' => 'Newsletter Tenant '.uniqid(),
            'is_active' => true,
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['company-admin']);

        return [$user, $company];
    }
}
