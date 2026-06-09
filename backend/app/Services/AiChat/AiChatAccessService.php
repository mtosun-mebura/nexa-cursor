<?php

namespace App\Services\AiChat;

use App\Enums\AiChat\AiChatIntent;
use App\Models\User;

final class AiChatAccessService
{
    /**
     * Bepaalt of de ingelogde gebruiker live taxi-data mag opvragen.
     * Frontend mag dit nooit zelf bepalen — uitsluitend server-side.
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

    public function resolveIntentAccess(AiChatIntent $intent, ?User $user, bool $isAdminChannel): bool
    {
        if ($intent->allowsPublicRates()) {
            return false;
        }

        if (! $intent->requiresLiveData()) {
            return false;
        }

        if (! $isAdminChannel) {
            return false;
        }

        return $this->userMayQueryLiveData($user);
    }

    public function resolvePublicRatesAccess(AiChatIntent $intent): bool
    {
        return $intent->allowsPublicRates();
    }
}
