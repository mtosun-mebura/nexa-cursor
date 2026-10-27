<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regressie: "Undefined variable $emailTemplate" in partials/formulier-preview.blade.php
 * op de create-pagina (daar bestaat nog geen template).
 */
class AdminEmailTemplateFormPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function create_page_renders_the_form_preview_without_an_existing_template(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('admin.email-templates.create'))
            ->assertOk()
            ->assertSee('id="formulier-preview-wrap"', false)
            ->assertSee('admin-form-preview-shell', false);
    }

    #[Test]
    public function edit_and_show_pages_still_render_with_their_template(): void
    {
        $admin = $this->superAdmin();
        $template = EmailTemplate::query()->create([
            'name' => 'Preview Regressietest',
            'subject' => 'Onderwerp',
            'type' => 'informatieaanvraag',
            'html_content' => '<p>inhoud</p>',
            'is_active' => true,
            'company_id' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.email-templates.edit', $template))
            ->assertOk()
            ->assertSee('admin-form-preview-shell', false);

        $this->actingAs($admin)
            ->get(route('admin.email-templates.show', $template))
            ->assertOk();
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        return $admin;
    }
}
