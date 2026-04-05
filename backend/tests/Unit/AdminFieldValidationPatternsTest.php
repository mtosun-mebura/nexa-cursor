<?php

namespace Tests\Unit;

use App\Support\AdminFieldValidationPatterns;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminFieldValidationPatternsTest extends TestCase
{
    #[Test]
    public function wizard_step1_evaluation_matches_laravel_rules_for_valid_payload(): void
    {
        $data = [
            'name' => 'Test BV',
            // Moet door email:rfc,dns komen (DNS-check); gmail heeft betrouwbare MX.
            'email' => 'support@gmail.com',
            'phone' => '0612345678',
            'postal_code' => '1234 AB',
            'house_number' => '12',
            'street' => 'Hoofdstraat',
            'city' => 'Amsterdam',
            'kvk_number' => '12345678',
            'website' => 'https://www.voorbeeld.nl',
        ];

        $rules = [
            'name' => 'required|string|max:255|min:2',
            'email' => 'required|email:rfc,dns|max:255',
            'phone' => ['required', 'string', 'max:20', 'regex:/^(\+31|0)[1-9][0-9]{8}$/'],
            'postal_code' => ['required', 'string', 'max:20', 'regex:/^[1-9][0-9]{3}\s?[A-Z]{2}$/i'],
            'house_number' => 'required|string|max:20|min:1',
            'street' => 'required|string|max:255|min:2',
            'city' => 'required|string|max:255|min:2',
            'kvk_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]{8}$/'],
            'website' => 'nullable|url:http,https|max:255',
        ];

        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->fails(), json_encode($validator->errors()->all()));

        foreach (array_keys($rules) as $field) {
            $this->assertSame(
                'valid',
                AdminFieldValidationPatterns::evaluateWizardStep1Field($field, (string) $data[$field]),
                'Field '.$field
            );
        }
    }

    #[Test]
    public function wizard_step1_phone_normalizes_spaces_like_client(): void
    {
        $this->assertSame('valid', AdminFieldValidationPatterns::evaluateWizardStep1Field('phone', '06 12 34 56 78'));
        $this->assertSame('valid', AdminFieldValidationPatterns::evaluateWizardStep1Field('phone', '+31 6 12 34 56 78'));
    }

    #[Test]
    public function wizard_step1_evaluation_marks_invalid_examples(): void
    {
        $this->assertSame('invalid', AdminFieldValidationPatterns::evaluateWizardStep1Field('name', 'X'));
        $this->assertSame('invalid', AdminFieldValidationPatterns::evaluateWizardStep1Field('email', 'geen-email'));
        $this->assertSame('invalid', AdminFieldValidationPatterns::evaluateWizardStep1Field('email', 'geen@punt'));
        $this->assertSame('invalid', AdminFieldValidationPatterns::evaluateWizardStep1Field('phone', '12345'));
        $this->assertSame('invalid', AdminFieldValidationPatterns::evaluateWizardStep1Field('postal_code', '0000AA'));
        $this->assertSame('invalid', AdminFieldValidationPatterns::evaluateWizardStep1Field('kvk_number', '123'));
        $this->assertSame('invalid', AdminFieldValidationPatterns::evaluateWizardStep1Field('website', 'ftp://x.nl'));
    }

    #[Test]
    public function wizard_step1_optional_empty_fields_are_neutral(): void
    {
        $this->assertSame('neutral', AdminFieldValidationPatterns::evaluateWizardStep1Field('kvk_number', ''));
        $this->assertSame('neutral', AdminFieldValidationPatterns::evaluateWizardStep1Field('website', ''));
    }

    #[Test]
    public function wizard_step1_required_empty_is_empty_state(): void
    {
        $this->assertSame('empty', AdminFieldValidationPatterns::evaluateWizardStep1Field('name', ''));
        $this->assertSame('empty', AdminFieldValidationPatterns::evaluateWizardStep1Field('email', '   '));
    }
}
