<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AdminCompanyController;
use App\Models\Company;
use App\Models\User;
use App\Services\UserRoleAssignmentService;
use App\Services\WebsiteBuilderService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class CompanyFaviconTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function company_edit_shows_favicon_upload(): void
    {
        $company = Company::query()->create([
            'name' => 'Favicon Taxi',
            'email' => 'favicon@example.com',
            'phone' => '0612345678',
            'street' => 'Teststraat',
            'house_number' => '1',
            'postal_code' => '1234AB',
            'city' => 'Amsterdam',
            'is_active' => true,
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.companies.edit', $company))
            ->assertOk()
            ->assertSee('Favicon (website)', false)
            ->assertSee('id="company-form-favicon-input"', false)
            ->assertSee('name="favicon"', false);
    }

    #[Test]
    public function company_favicon_upload_is_stored_on_company(): void
    {
        $company = Company::query()->create([
            'name' => 'Favicon Taxi BV',
            'email' => 'favicon2@example.com',
            'phone' => '0612345678',
            'street' => 'Teststraat',
            'house_number' => '1',
            'postal_code' => '1234AB',
            'city' => 'Amsterdam',
            'is_active' => true,
        ]);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $file = UploadedFile::fake()->createWithContent('favicon.png', $png)->mimeType('image/png');

        $request = Request::create('/admin/companies/'.$company->id, 'POST', [], [], [
            'favicon' => $file,
        ]);
        $data = [];
        $method = new ReflectionMethod(AdminCompanyController::class, 'applyFaviconFromRequest');
        $method->setAccessible(true);
        $controller = app(AdminCompanyController::class);
        $args = [$request, &$data];
        $method->invokeArgs($controller, $args);

        $this->assertArrayHasKey('favicon_blob', $data);
        $this->assertNotEmpty($data['favicon_blob']);
        $this->assertSame('image/png', $data['favicon_mime_type']);

        $company->update($data);
        $company->refresh();
        $this->assertTrue($company->hasFavicon());
    }

    #[Test]
    public function tenant_website_branding_uses_company_favicon(): void
    {
        $company = Company::query()->create([
            'name' => 'Favicon Brand BV',
            'email' => 'favicon-brand@example.com',
            'phone' => '0612345678',
            'street' => 'Teststraat',
            'house_number' => '1',
            'postal_code' => '1234AB',
            'city' => 'Amsterdam',
            'is_active' => true,
            'favicon_blob' => base64_encode('png-bytes'),
            'favicon_mime_type' => 'image/png',
        ]);

        // Platform/settings-favicon mag company-upload niet overrulen.
        \App\Models\GeneralSetting::set('favicon', 'settings/platform-favicon.png', null);
        \App\Models\GeneralSetting::set('favicon', 'settings/tenant-favicon.png', (int) $company->id);
        \Illuminate\Support\Facades\Storage::disk('public')->put('settings/platform-favicon.png', 'x');
        \Illuminate\Support\Facades\Storage::disk('public')->put('settings/tenant-favicon.png', 'y');

        app()->instance('resolved_tenant_id', (int) $company->id);

        $meta = app(WebsiteBuilderService::class)->publicFaviconMeta((int) $company->id);
        $this->assertStringContainsString('/brand/company/'.$company->id.'/favicon', $meta['url']);
        $this->assertSame('image/png', $meta['type']);

        $branding = app(WebsiteBuilderService::class)->getSiteBranding(null, false, (int) $company->id);
        $this->assertNotEmpty($branding['favicon_url']);
        $this->assertStringContainsString('/brand/company/'.$company->id.'/favicon', (string) $branding['favicon_url']);
        $this->assertStringNotContainsString('settings/', (string) $branding['favicon_url']);
    }

    #[Test]
    public function company_favicon_can_be_removed(): void
    {
        $company = Company::query()->create([
            'name' => 'Favicon Remove BV',
            'email' => 'favicon3@example.com',
            'phone' => '0612345678',
            'street' => 'Teststraat',
            'house_number' => '1',
            'postal_code' => '1234AB',
            'city' => 'Amsterdam',
            'is_active' => true,
            'favicon_blob' => base64_encode('fake'),
            'favicon_mime_type' => 'image/png',
        ]);

        $request = Request::create('/admin/companies/'.$company->id, 'POST', [
            'remove_favicon' => '1',
        ]);
        $data = [];
        $method = new ReflectionMethod(AdminCompanyController::class, 'applyFaviconFromRequest');
        $method->setAccessible(true);
        $controller = app(AdminCompanyController::class);
        $args = [$request, &$data];
        $method->invokeArgs($controller, $args);

        $company->update($data);
        $company->refresh();
        $this->assertFalse($company->hasFavicon());
        $this->assertNull($company->favicon_blob);
    }

    #[Test]
    public function public_favicon_route_serves_company_blob_for_tenant(): void
    {
        $company = Company::query()->create([
            'name' => 'Favicon Serve BV',
            'email' => 'favicon4@example.com',
            'phone' => '0612345678',
            'street' => 'Teststraat',
            'house_number' => '1',
            'postal_code' => '1234AB',
            'city' => 'Amsterdam',
            'is_active' => true,
            'favicon_blob' => base64_encode('icon-bytes'),
            'favicon_mime_type' => 'image/png',
        ]);

        app()->instance('resolved_tenant_id', (int) $company->id);
        app()->instance('resolved_tenant', $company);

        $this->actingAs($this->superAdmin())
            ->get(route('frontend.company-brand.favicon', $company))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['company_id' => null]);
        app(UserRoleAssignmentService::class)->syncWebRoles($user, ['super-admin']);

        return $user->fresh();
    }
}
