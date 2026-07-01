<?php

namespace Tests\Unit;

use App\Services\InformatieaanvraagEmailHtmlNormalizer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InformatieaanvraagEmailHtmlNormalizerTest extends TestCase
{
    #[Test]
    public function test_adds_full_width_and_bgcolor_to_header_cell(): void
    {
        $html = '<table class="info-request-email-card"><tr><td class="info-request-email-header" style="background-color: #2563eb;">Title</td></tr></table>';

        $normalized = app(InformatieaanvraagEmailHtmlNormalizer::class)->normalize($html);

        $this->assertStringContainsString('width="100%"', $normalized);
        $this->assertStringContainsString('bgcolor="#2563eb"', $normalized);
    }

    #[Test]
    public function test_does_not_wrap_html_in_extra_div(): void
    {
        $html = '<!DOCTYPE html><html><body><table class="info-request-email-card"><tr><td class="info-request-email-body">Hi</td></tr></table></body></html>';

        $normalized = app(InformatieaanvraagEmailHtmlNormalizer::class)->normalize($html);

        $this->assertStringNotContainsString('<div class="info-request-email-body"', $normalized);
        $this->assertStringContainsString('<!DOCTYPE html>', $normalized);
    }

    #[Test]
    public function test_uses_separate_border_collapse_for_rounded_card_corners(): void
    {
        $html = '<table class="info-request-email-card" style="width: 100%; max-width: 600px; border-collapse: collapse; border-radius: 8px;"></table>';

        $normalized = app(InformatieaanvraagEmailHtmlNormalizer::class)->normalize($html);

        $this->assertStringContainsString('border-collapse: separate', $normalized);
        $this->assertStringNotContainsString('border-collapse: collapse', $normalized);
    }

    #[Test]
    public function test_aligns_field_labels_to_the_right(): void
    {
        $html = '<table class="info-request-fields"><tr><td class="info-request-field-label" style="text-align: left; width: 175px;"><strong>Voornaam:</strong></td></tr></table>';

        $normalized = app(InformatieaanvraagEmailHtmlNormalizer::class)->normalize($html);

        $this->assertStringContainsString('text-align: right', $normalized);
    }

    #[Test]
    public function test_reduces_spacing_between_intro_and_fields_table(): void
    {
        $html = <<<'HTML'
<table class="info-request-email-card"><tr><td class="info-request-email-body">
<p style="margin: 0 0 15px 0;">Er is een informatieaanvraag binnengekomen met de volgende gegevens:</p>
<table class="info-request-fields" style="margin: 20px 0;"></table>
</td></tr></table>
HTML;

        $normalized = app(InformatieaanvraagEmailHtmlNormalizer::class)->normalize($html);

        $this->assertStringContainsString('margin: 0', $normalized);
        $this->assertStringContainsString('<p style="margin: 0;">', $normalized);
    }

    #[Test]
    public function test_inserts_light_gray_divider_rows_between_field_rows(): void
    {
        $html = <<<'HTML'
<table class="info-request-fields" width="100%">
<tr class="info-request-field-row"><td class="info-request-field-label" style="border-bottom: 1px solid #ffffff;">Voornaam</td><td class="info-request-field-value" style="border-bottom: 1px solid #e5e7eb;">Jan</td></tr>
<tr class="info-request-field-row"><td class="info-request-field-label">Achternaam</td><td class="info-request-field-value">Jansen</td></tr>
</table>
HTML;

        $normalized = app(InformatieaanvraagEmailHtmlNormalizer::class)->normalize($html);

        $this->assertStringContainsString('info-request-field-divider', $normalized);
        $this->assertStringContainsString('background-color: #d1d5db', $normalized);
        $this->assertStringContainsString('bgcolor="#d1d5db"', $normalized);
        $this->assertStringNotContainsString('border-bottom: 1px solid #ffffff', $normalized);
    }

    #[Test]
    public function test_moves_loose_field_rows_and_dividers_into_nested_fields_table(): void
    {
        $html = <<<'HTML'
<table class="info-request-email-card" style="width: 100%; max-width: 600px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
<tr><td class="info-request-email-header" style="background-color: #2563eb;">Header</td></tr>
<tr><td class="info-request-email-body" style="background-color: #ffffff;"><p>Intro</p></td></tr>
<tr class="info-request-field-row"><td class="info-request-field-label"><strong>Voornaam:</strong></td><td class="info-request-field-value">Jan</td></tr>
<tr class="info-request-field-divider"><td colspan="2">divider</td></tr>
<tr class="info-request-field-row"><td class="info-request-field-label"><strong>Achternaam:</strong></td><td class="info-request-field-value">Jansen</td></tr>
<tr><td class="info-request-email-footer" style="background-color: #f9fafb;">Footer</td></tr>
</table>
HTML;

        $normalized = app(InformatieaanvraagEmailHtmlNormalizer::class)->normalize($html);

        $this->assertStringNotContainsString("</td></tr>\n<tr class=\"info-request-field-row\">", $normalized);
        $this->assertSame(1, substr_count($normalized, 'info-request-email-header'));
        $this->assertStringContainsString('class="info-request-fields"', $normalized);
        $this->assertStringContainsString('info-request-email-body', $normalized);
        $this->assertStringContainsString('Intro', $normalized);
    }

    #[Test]
    public function test_tags_legacy_inner_field_table_without_class(): void
    {
        $html = <<<'HTML'
<table class="info-request-email-card" style="width: 100%; max-width: 600px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
<tr><td class="info-request-email-body"><p>Intro</p><table role="presentation" style="width: 100%;"><tr class="info-request-field-row"><td class="info-request-field-label">Voornaam</td><td class="info-request-field-value">Jan</td></tr></table></td></tr>
</table>
HTML;

        $normalized = app(InformatieaanvraagEmailHtmlNormalizer::class)->normalize($html);

        $this->assertStringContainsString('class="info-request-fields"', $normalized);
        $this->assertStringContainsString('<colgroup><col style="width: 175px;"><col></colgroup>', $normalized);
    }

    #[Test]
    public function test_removes_table_layout_fixed_from_email_card(): void
    {
        $html = '<table class="info-request-email-card" style="width: 100%; max-width: 600px; table-layout: fixed;"></table>';

        $normalized = app(InformatieaanvraagEmailHtmlNormalizer::class)->normalize($html);

        $this->assertStringNotContainsString('table-layout: fixed', $normalized);
    }

    #[Test]
    public function test_removes_one_percent_width_from_value_column(): void
    {
        $html = <<<'HTML'
<table class="info-request-fields"><colgroup><col style="width: 175px;"><col style="width: 1%;"></colgroup><tr class="info-request-field-row"><td class="info-request-field-label">E-mailadres</td><td class="info-request-field-value">jan@example.com</td></tr></table>
HTML;

        $normalized = app(InformatieaanvraagEmailHtmlNormalizer::class)->normalize($html);

        $this->assertStringNotContainsString('width: 1%', $normalized);
    }
}
