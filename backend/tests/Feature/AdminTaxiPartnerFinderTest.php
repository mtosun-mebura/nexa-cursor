<?php

namespace Tests\Feature;

use App\Models\TaxiProspect;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTaxiPartnerFinderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        config([
            'taxi-partner-finder.enabled' => true,
            'taxi-partner-finder.sources.knv.enabled' => true,
            'taxi-partner-finder.sources.knv_csv.enabled' => true,
        ]);
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        return $admin;
    }

    #[Test]
    public function company_admin_cannot_open_partner_finder(): void
    {
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('company-admin');

        $response = $this->actingAs($user)
            ->withSession(['selected_tenant' => null])
            ->get(route('admin.taxi-partner-finder.index'));

        $this->assertTrue(
            in_array($response->status(), [302, 403], true),
            'Company-admin mag Partner Finder niet openen, got '.$response->status()
        );
        $response->assertDontSee('KNV synchroniseren', false);
    }

    #[Test]
    public function super_admin_can_open_partner_finder(): void
    {
        $this->actingAs($this->superAdmin())
            ->withSession(['selected_tenant' => null])
            ->get(route('admin.taxi-partner-finder.index'))
            ->assertOk()
            ->assertSee('Taxi Partner Finder', false)
            ->assertSee('KNV-ledenoverzicht', false)
            ->assertDontSee('Kies links in de zijbalk een tenant', false);
    }

    #[Test]
    public function knv_sync_imports_fixture_html_without_duplicates(): void
    {
        $html = file_get_contents(base_path('tests/fixtures/knv-ledenoverzicht-sample.html'));
        $this->assertNotFalse($html);
        Http::fake([
            'www.knv.nl/*' => Http::response($html, 200, ['Content-Type' => 'text/html']),
        ]);

        $admin = $this->superAdmin();
        $this->actingAs($admin)
            ->withSession(['selected_tenant' => null])
            ->post(route('admin.taxi-partner-finder.sync-knv'))
            ->assertRedirect(route('admin.taxi-partner-finder.index'));

        $count = TaxiProspect::query()->count();
        $this->assertGreaterThanOrEqual(2, $count);

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => null])
            ->post(route('admin.taxi-partner-finder.sync-knv'))
            ->assertRedirect(route('admin.taxi-partner-finder.index'));

        $this->assertSame($count, TaxiProspect::query()->count());
        $this->assertTrue(TaxiProspect::query()->where('knv_member_status', 'member')->exists());
    }

    #[Test]
    public function csv_import_and_push_to_newsletter(): void
    {
        $csv = "bedrijfsnaam;email;plaats;provincie\nTaxi Bridge;bridge@taxi.test;Zwolle;Overijssel\n";
        $file = UploadedFile::fake()->createWithContent('knv.csv', $csv);

        $admin = $this->superAdmin();
        $this->actingAs($admin)
            ->withSession(['selected_tenant' => null])
            ->post(route('admin.taxi-partner-finder.import-csv'), [
                'csv' => $file,
                'push_newsletter' => '1',
            ])
            ->assertRedirect(route('admin.taxi-partner-finder.index'));

        $this->assertDatabaseHas('taxi_prospects', [
            'company_name' => 'Taxi Bridge',
            'email' => 'bridge@taxi.test',
        ]);
        $this->assertDatabaseHas('newsletter_prospects', [
            'email' => 'bridge@taxi.test',
            'source' => 'knv',
        ]);
    }
}
