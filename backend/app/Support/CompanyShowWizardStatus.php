<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;

final class CompanyShowWizardStatus
{
    /**
     * @param  list<array{id: string, label: string, icon: string}>  $tabs
     * @return list<array{id: string, label: string, icon: string, done: bool, status_label: string}>
     */
    public static function decorateTabs(Company $company, array $tabs): array
    {
        return array_map(function (array $tab) use ($company): array {
            $done = self::isComplete($company, (string) ($tab['id'] ?? ''));
            $tab['done'] = $done;
            $tab['status_label'] = $done ? 'Wizardstap afgerond' : 'Wizardstap nog niet afgerond';

            return $tab;
        }, $tabs);
    }

    public static function isComplete(Company $company, string $tabId): bool
    {
        return match ($tabId) {
            'company-info' => self::companyInfoComplete($company),
            'company-contact' => self::contactComplete($company),
            'company-modules' => self::relationHasItems($company, 'modules'),
            'company-users-website' => self::relationHasItems($company, 'users'),
            'config-access' => self::companyAdminComplete($company),
            'company-domains' => self::relationHasItems($company, 'domains'),
            'company-locations' => self::relationHasItems($company, 'locations'),
            default => true,
        };
    }

    private static function companyInfoComplete(Company $company): bool
    {
        return filled($company->name)
            && filled($company->package_key)
            && filled($company->kvk_number)
            && filled($company->industry)
            && filled($company->street)
            && filled($company->house_number)
            && filled($company->postal_code)
            && filled($company->city);
    }

    private static function contactComplete(Company $company): bool
    {
        return filled($company->email)
            && filled($company->phone)
            && filled($company->contact_first_name)
            && filled($company->contact_last_name);
    }

    private static function companyAdminComplete(Company $company): bool
    {
        $email = strtolower(trim((string) $company->email));
        if ($email === '') {
            return false;
        }

        if ($company->relationLoaded('users')) {
            return $company->users->contains(
                fn (User $user): bool => strtolower((string) $user->email) === $email
            );
        }

        return $company->users()->whereRaw('LOWER(email) = ?', [$email])->exists();
    }

    private static function relationHasItems(Company $company, string $relation): bool
    {
        if ($company->relationLoaded($relation)) {
            return $company->getRelation($relation)->isNotEmpty();
        }

        return $company->{$relation}()->exists();
    }
}
