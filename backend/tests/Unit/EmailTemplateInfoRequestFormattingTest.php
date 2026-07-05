<?php

namespace Tests\Unit;

use App\Models\EmailTemplate;
use App\Models\InfoRequestFormField;
use App\Services\EmailTemplateService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailTemplateInfoRequestFormattingTest extends TestCase
{
    #[Test]
    public function test_textarea_values_keep_line_breaks_as_br_tags(): void
    {
        $field = new InfoRequestFormField([
            'name' => 'omschrijving',
            'label' => 'Omschrijving / vraag',
            'validation_rule' => null,
        ]);

        $template = $this->createMock(EmailTemplate::class);
        $template->method('getOrderedFormFields')->willReturn(collect([$field]));

        $formatted = app(EmailTemplateService::class)->formatInformatieaanvraagVariables($template, [
            'OMSCHRIJVING' => "Regel 1\nRegel 2",
        ]);

        $this->assertSame("Regel 1<br>\nRegel 2", $formatted['OMSCHRIJVING']);
    }
}
