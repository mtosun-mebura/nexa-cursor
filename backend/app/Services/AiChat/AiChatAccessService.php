<?php

namespace App\Services\AiChat;

use App\DTO\AiChat\AiChatRequestContext;
use App\Enums\AiChat\AiChatIntent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class AiChatAccessService
{
    /**
     * Bepaalt of de ingelogde gebruiker live taxi-data mag opvragen (admin).
     */
    public function userMayQueryLiveData(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        foreach (['ai_chatbot.view', 'rides.view', 'vehicles.view', 'rides.update'] as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ingelogde klant in Mijn Taxi mag eigen ritten opvragen (geen admin/medewerker).
     */
    public function userMayQueryOwnRides(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($this->userMayQueryLiveData($user)) {
            return false;
        }

        if ($this->userHasKlantRole($user)) {
            return true;
        }

        return strtolower(trim((string) $user->email)) !== '';
    }

    public function resolveIntentAccess(AiChatIntent $intent, AiChatRequestContext $context): bool
    {
        if ($intent->allowsPublicRates()) {
            return false;
        }

        if ($intent->isCustomerOwnData()) {
            if (! $context->isMijnTaxiChannel()) {
                return false;
            }

            return $this->userMayQueryOwnRides($context->user);
        }

        if (! $intent->requiresLiveData()) {
            return false;
        }

        if (! $context->isAdminChannel()) {
            return false;
        }

        return $this->userMayQueryLiveData($context->user);
    }

    public function resolvePublicRatesAccess(AiChatIntent $intent): bool
    {
        return $intent->allowsPublicRates();
    }

    private function userHasKlantRole(User $user): bool
    {
        if (! $user->company_id) {
            return false;
        }

        $companyId = (int) $user->company_id;
        $pivot = DB::getTablePrefix().config('permission.table_names.model_has_roles');
        $rolesTable = DB::getTablePrefix().config('permission.table_names.roles');
        $teamKey = config('permission.column_names.team_foreign_key') ?: 'company_id';
        $morphTypes = array_values(array_unique(array_filter([
            User::class,
            (new User)->getMorphClass(),
        ])));

        return User::query()
            ->where('users.id', $user->id)
            ->whereExists(function ($sub) use ($companyId, $pivot, $rolesTable, $teamKey, $morphTypes) {
                $sub->select(DB::raw('1'))
                    ->from($pivot)
                    ->join($rolesTable, $rolesTable.'.id', '=', $pivot.'.role_id')
                    ->whereColumn($pivot.'.model_id', 'users.id')
                    ->whereIn($pivot.'.model_type', $morphTypes)
                    ->where(function ($q) use ($pivot, $teamKey, $companyId) {
                        $q->where($pivot.'.'.$teamKey, $companyId)
                            ->orWhere(function ($q2) use ($pivot, $teamKey, $companyId) {
                                $q2->whereNull($pivot.'.'.$teamKey)
                                    ->where('users.company_id', $companyId);
                            });
                    })
                    ->whereIn($rolesTable.'.guard_name', ['web', 'api'])
                    ->whereRaw('LOWER(TRIM('.$rolesTable.'.name)) = ?', ['klant']);
            })
            ->exists();
    }
}
