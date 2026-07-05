<?php

namespace Tests\Unit;

use App\Models\EmailTemplate;
use App\Models\InfoRequestFormField;
use Tests\TestCase;

class EmailTemplateFormFieldRequiredTest extends TestCase
{
    public function test_is_form_field_required_uses_template_override(): void
    {
        $template = new EmailTemplate(['form_field_required' => ['3' => false]]);
        $field = new InfoRequestFormField(['is_required' => true, 'name' => 'telefoonnummer']);
        $field->id = 3;

        $this->assertFalse($template->isFormFieldRequired($field));
    }

    public function test_is_form_field_required_falls_back_to_global_field_setting(): void
    {
        $template = new EmailTemplate(['form_field_required' => null]);
        $field = new InfoRequestFormField(['is_required' => true, 'name' => 'telefoonnummer']);
        $field->id = 3;

        $this->assertTrue($template->isFormFieldRequired($field));
    }

    public function test_validation_rules_for_form_field_makes_telefoon_required(): void
    {
        $template = new EmailTemplate(['form_field_required' => null]);
        $field = new InfoRequestFormField([
            'name' => 'telefoonnummer',
            'is_required' => true,
            'validation_rule' => 'tel',
        ]);
        $field->id = 4;

        $rules = $template->validationRulesForFormField($field);

        $this->assertContains('required', $rules);
        $this->assertNotContains('nullable', $rules);
    }

    public function test_validation_rules_for_form_field_respects_template_optional_override(): void
    {
        $template = new EmailTemplate(['form_field_required' => ['4' => false]]);
        $field = new InfoRequestFormField([
            'name' => 'telefoonnummer',
            'is_required' => true,
            'validation_rule' => 'tel',
        ]);
        $field->id = 4;

        $rules = $template->validationRulesForFormField($field);

        $this->assertNotContains('required', $rules);
        $this->assertContains('nullable', $rules);
    }
}
