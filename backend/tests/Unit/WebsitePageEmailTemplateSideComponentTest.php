<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\WebsitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsitePageEmailTemplateSideComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_text_block_side_component_resolves_template_from_side_template_id(): void
    {
        $company = Company::query()->create(['name' => 'Test BV']);
        $template = EmailTemplate::query()->create([
            'name' => 'Contact',
            'subject' => 'Contact',
            'type' => 'informatieaanvraag',
            'html_content' => '<p>Test</p>',
            'company_id' => $company->id,
            'is_active' => true,
            'recipient_type' => 'email',
            'recipient_email' => 'info@example.com',
        ]);

        $homeSections = [
            'section_order' => ['text_block'],
            'text_block' => [
                'content' => '<p>Hallo</p>',
                'alignment' => 'left',
                'side_component_key' => 'email_template',
                'side_template_id' => $template->id,
            ],
        ];

        app()->instance('resolved_tenant_id', $company->id);

        $map = WebsitePage::emailTemplatesBySectionKeyForHomeSections($homeSections);

        $this->assertNotNull($map['email_template'] ?? null);
        $this->assertSame($template->id, $map['email_template']->id);
    }

    public function test_text_block_side_component_falls_back_to_single_company_template(): void
    {
        $company = Company::query()->create(['name' => 'Taxi BV']);
        $template = EmailTemplate::query()->create([
            'name' => 'Contact',
            'subject' => 'Contact',
            'type' => 'informatieaanvraag',
            'html_content' => '<p>Test</p>',
            'company_id' => $company->id,
            'is_active' => true,
            'recipient_type' => 'email',
            'recipient_email' => 'info@example.com',
        ]);

        $homeSections = [
            'section_order' => ['carousel', 'text_block'],
            'text_block' => [
                'content' => '<p>Openingstijden</p>',
                'alignment' => 'left',
                'side_component_key' => 'email_template',
            ],
        ];

        app()->instance('resolved_tenant_id', $company->id);

        $map = WebsitePage::emailTemplatesBySectionKeyForHomeSections($homeSections);

        $this->assertNotNull($map['email_template'] ?? null);
        $this->assertSame($template->id, $map['email_template']->id);
    }
}
