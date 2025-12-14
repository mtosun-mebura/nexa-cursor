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
                'required',
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
            'role' => [
                'required',
                'string',
                'exists:roles,name',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Voornaam is verplicht.',
            'first_name.min' => 'Voornaam moet minimaal 2 karakters lang zijn.',
            'first_name.max' => 'Voornaam mag maximaal 255 karakters bevatten.',
            'first_name.regex' => 'Voornaam mag alleen letters, spaties, streepjes, apostrofs en punten bevatten.',
            'last_name.required' => 'Achternaam is verplicht.',
            'last_name.min' => 'Achternaam moet minimaal 2 karakters lang zijn.',
            'last_name.max' => 'Achternaam mag maximaal 255 karakters bevatten.',
            'last_name.regex' => 'Achternaam mag alleen letters, spaties, streepjes, apostrofs en punten bevatten.',
            'email.required' => 'E-mailadres is verplicht.',
            'email.unique' => 'Dit e-mailadres is al in gebruik.',
            'email.regex' => 'E-mailadres moet een geldig e-mailadres zijn.',
            'password.required' => 'Wachtwoord is verplicht.',
            'password.min' => 'Wachtwoord moet minimaal 8 karakters lang zijn.',
            'password.regex' => 'Wachtwoord moet minimaal 1 kleine letter, 1 hoofdletter en 1 cijfer bevatten.',
            'phone.regex' => 'Telefoonnummer moet een geldig Nederlands nummer zijn (bijv. 0612345678 of +31612345678).',
            'date_of_birth.before' => 'Geboortedatum moet in het verleden liggen.',
            'company_id.exists' => 'Het geselecteerde bedrijf bestaat niet.',
            'role.required' => 'Rol is verplicht.',
            'role.exists' => 'De geselecteerde rol bestaat niet.',
        ];
    }
}


