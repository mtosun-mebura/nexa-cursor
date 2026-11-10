<?php

namespace App\Services;

use App\Models\User;

/**
 * Bootstrap/seed/deploy mag accounts aanmaken, maar wachtwoorden van bestaande
 * gebruikers nooit overschrijven — ook niet “per ongeluk” via updateOrCreate.
 */
class DurableUserCredentials
{
    /**
     * @param  array<string, mixed>  $createAttributes  Alleen gebruikt als de gebruiker nog niet bestaat.
     */
    public function ensureByEmail(string $email, array $createAttributes, ?string $connection = null): User
    {
        $email = strtolower(trim($email));
        $query = $connection ? User::on($connection) : User::query();

        $existing = $query->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($existing !== null) {
            return $existing;
        }

        unset($createAttributes['id'], $createAttributes['email']);

        return $query->create(array_merge(['email' => $email], $createAttributes));
    }

    /**
     * Zet een wachtwoord alleen bij aanmaken. Bestaande hash blijft onaangeroerd.
     *
     * @param  array<string, mixed>  $attrs
     * @return array<string, mixed>
     */
    public function attributesForCreateOnly(User $user, array $attrs, ?string $password = null): array
    {
        if ($user->exists) {
            unset($attrs['password']);

            return $attrs;
        }

        if ($password !== null && $password !== '') {
            $attrs['password'] = $password;
        }

        return $attrs;
    }
}
