<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * FormRequest voor het aanmaken van gebruikers
 * Met uniforme validatie en security checks
 */
class StoreUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (
            auth()->user()->hasRole('super-admin') ||
            auth()->user()->can('create-users')
        );
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        if ($this->input('password') === '') {
            $this->merge(['password' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[\p{L}\s\-\'\.]+$/u', // Alleen letters, spaties, streepjes, apostrofs en punten
            ],
            'last_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[\p{L}\s\-\'\.]+$/u',
            ],
            'email' => [
                'required',
                'max:255',
                'unique:users,email',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'max:255',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', // Minimaal 1 kleine letter, 1 hoofdletter, 1 cijfer
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^(\+31|0)[1-9][0-9]{8}$/',
            ],
            'date_of_birth' => [
                'nullable',
                'date',
                'before:today',
            ],
            'function' => [
                'nullable',
                'string',
                'max:255',
            ],
            'company_id' => [
                'nullable',
                'exists:companies,id',
            ],
            'roles' => [
                'required',
                'array',
                'min:1',
            ],
            'roles.*' => [
                'string',
                'distinct',
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
            ],
            'wizard_back_url' => [
                'nullable',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }
                    $parsed = parse_url($value);
                    if (! is_array($parsed) || empty($parsed['path']) || ! str_starts_with($parsed['path'], '/admin/')) {
                        $fail(__('Ongeldige terug-URL.'));

                        return;
                    }
                    if (isset($parsed['host']) && $parsed['host'] !== request()->getHost()) {
                        $fail(__('Ongeldige terug-URL.'));
                    }
                },
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $roles = $this->input('roles', []);
            if (! is_array($roles)) {
                return;
            }
            if (! auth()->user()->hasRole('super-admin') && in_array('super-admin', $roles, true)) {
                $validator->errors()->add('roles', __('Je mag geen super-admin rol toewijzen.'));
            }
        });
    }

    public function messages(): array
    {
        return [
            'first_name.required' => __('Voornaam is verplicht.'),
            'first_name.min' => __('Voornaam moet minimaal 2 karakters lang zijn.'),
            'first_name.max' => __('Voornaam mag maximaal 255 karakters bevatten.'),
            'first_name.regex' => __('Voornaam mag alleen letters, spaties, streepjes, apostrofs en punten bevatten.'),
            'last_name.required' => __('Achternaam is verplicht.'),
            'last_name.min' => __('Achternaam moet minimaal 2 karakters lang zijn.'),
            'last_name.max' => __('Achternaam mag maximaal 255 karakters bevatten.'),
            'last_name.regex' => __('Achternaam mag alleen letters, spaties, streepjes, apostrofs en punten bevatten.'),
            'email.required' => __('E-mailadres is verplicht.'),
            'email.unique' => __('Dit e-mailadres is al in gebruik.'),
            'email.regex' => __('E-mailadres moet een geldig e-mailadres zijn.'),
            'password.min' => __('Wachtwoord moet minimaal 8 karakters lang zijn.'),
            'password.regex' => __('Wachtwoord moet minimaal 1 kleine letter, 1 hoofdletter en 1 cijfer bevatten.'),
            'phone.regex' => __('Telefoonnummer moet een geldig Nederlands nummer zijn (bijv. 0612345678 of +31612345678).'),
            'date_of_birth.before' => __('Geboortedatum moet in het verleden liggen.'),
            'company_id.exists' => __('Het geselecteerde bedrijf bestaat niet.'),
            'roles.required' => __('Selecteer minimaal één rol.'),
            'roles.min' => __('Selecteer minimaal één rol.'),
            'roles.*.exists' => __('Een geselecteerde rol bestaat niet.'),
        ];
    }
}
