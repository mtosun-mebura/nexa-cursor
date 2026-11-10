<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * FormRequest voor het bijwerken van gebruikers
 * Met uniforme validatie en security checks
 */
class UpdateUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');
        return auth()->check() && (
            auth()->user()->hasRole('super-admin') || 
            auth()->user()->can('edit-users')
        );
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id ?? null;
        $canEditRoles = $this->canEditRolesForTarget();

        return [
            'first_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[\p{L}\s\-\'\.]+$/u',
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
                Rule::unique('users', 'email')->ignore($userId),
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'max:255',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
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
            'roles' => $canEditRoles
                ? [
                    'required',
                    'array',
                    'min:1',
                ]
                : [
                    'nullable',
                    'array',
                ],
            'roles.*' => [
                'string',
                'distinct',
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
            ],
            'agenda_color' => [
                'nullable',
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->canEditRolesForTarget()) {
                return;
            }
            $roles = $this->input('roles', []);
            if (! is_array($roles)) {
                return;
            }
            if (! auth()->user()->hasRole('super-admin') && in_array('super-admin', $roles, true)) {
                $validator->errors()->add('roles', __('Je mag geen super-admin rol toewijzen.'));
            }
        });
    }

    private function canEditRolesForTarget(): bool
    {
        $actor = auth()->user();
        $target = $this->route('user');

        return $actor && $target instanceof \App\Models\User && $actor->canEditRolesOf($target);
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





