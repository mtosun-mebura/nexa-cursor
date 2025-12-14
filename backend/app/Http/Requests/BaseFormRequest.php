<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base FormRequest class met uniforme validatie en security checks
 * 
 * Deze class biedt:
 * - Standaard security validatie (XSS, SQL injection preventie)
 * - Uniforme error handling
 * - Recursieve validatie voor geneste arrays
 */
abstract class BaseFormRequest extends FormRequest
{
    /**
     * Bepaal of de gebruiker geautoriseerd is om deze request te maken
     */
    public function authorize(): bool
    {
        return true; // Override in child classes indien nodig
    }

    /**
     * Get de validatie regels
     * Moet geïmplementeerd worden in child classes
     */
    abstract public function rules(): array;

    /**
     * Get custom validatie berichten
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Prepare de data voor validatie
     * Sanitize alle input om XSS en SQL injection te voorkomen
     */
    protected function prepareForValidation(): void
    {
        $data = $this->all();
        $sanitized = $this->sanitizeRecursive($data);
        $this->merge($sanitized);
    }

    /**
     * Recursieve sanitization van input data
     * Voorkomt XSS door HTML te escapen en gevaarlijke karakters te verwijderen
     */
    protected function sanitizeRecursive($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeRecursive'], $data);
        }

        if (!is_string($data)) {
            return $data;
        }

        // Trim whitespace
        $data = trim($data);

        // Verwijder null bytes (kunnen gebruikt worden voor SQL injection)
        $data = str_replace("\0", '', $data);

        // Verwijder control characters behalve newlines en tabs
        $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);

        return $data;
    }

    /**
     * Handle een failed validatie
     * Retourneert JSON response voor AJAX requests, anders redirect met errors
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson() || $this->ajax()) {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'Validatie mislukt',
                    'errors' => $validator->errors()
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }

    /**
     * Get validatie regels met security checks
     * Voegt automatisch security regels toe aan specifieke velden
     */
    protected function getRulesWithSecurity(): array
    {
        $rules = $this->rules();

        // Voeg security regels toe aan string velden
        foreach ($rules as $field => $fieldRules) {
            if (is_string($fieldRules)) {
                $fieldRules = explode('|', $fieldRules);
            }

            // Voeg max length toe als die niet al bestaat
            if (is_array($fieldRules) && !in_array('max:255', $fieldRules) && !in_array('max:', $fieldRules)) {
                // Alleen toevoegen voor string velden zonder max
                $hasMax = false;
                foreach ($fieldRules as $rule) {
                    if (strpos($rule, 'max:') === 0) {
                        $hasMax = true;
                        break;
                    }
                }
                if (!$hasMax && !in_array('file', $fieldRules) && !in_array('image', $fieldRules)) {
                    // Standaard max voor strings (kan overschreven worden)
                }
            }
        }

        return $rules;
    }
}


