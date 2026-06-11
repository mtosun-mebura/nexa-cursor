<?php

namespace App\Services\AiChat;

use App\DTO\AiChat\AiChatRequestContext;
use App\Enums\AiChat\AiChatChannel;
use App\Models\GeneralSetting;
use App\Models\User;
use RuntimeException;

final class AiChatContextResolver
{
    public function __construct(
        private readonly AiChatAccessService $accessService,
    ) {}

    public function forPublicRequest(?string $module = null, ?string $sessionId = null): AiChatRequestContext
    {
        $companyId = GeneralSetting::resolveScopeCompanyId();
        if ($companyId === null) {
            throw new RuntimeException('Bedrijfscontext ontbreekt voor de AI-assistent.');
        }

        return new AiChatRequestContext(
            companyId: (int) $companyId,
            channel: AiChatChannel::Public,
            sessionId: $sessionId,
            module: strtolower(trim((string) ($module ?: 'taxi'))),
        );
    }

    public function forMijnTaxiRequest(User $user, ?string $module = null, ?string $sessionId = null): AiChatRequestContext
    {
        $companyId = GeneralSetting::resolveScopeCompanyId();
        if ($companyId === null && $user->company_id) {
            $companyId = (int) $user->company_id;
        }
        if ($companyId === null) {
            throw new RuntimeException('Bedrijfscontext ontbreekt voor de AI-assistent.');
        }

        return new AiChatRequestContext(
            companyId: (int) $companyId,
            channel: AiChatChannel::MijnTaxi,
            userId: (int) $user->id,
            user: $user,
            sessionId: $sessionId,
            module: strtolower(trim((string) ($module ?: 'taxi'))),
        );
    }

    public function forAdminRequest(User $user, ?string $module = null, ?string $sessionId = null): AiChatRequestContext
    {
        if (! $this->accessService->userMayQueryLiveData($user)) {
            // Admin-kanaal vereist minimaal view-rechten; anders 403 in controller.
        }

        $companyId = $this->resolveCompanyIdForUser($user);
        if ($companyId === null) {
            throw new RuntimeException('Selecteer eerst een tenant in de tenant-kiezer.');
        }

        return new AiChatRequestContext(
            companyId: $companyId,
            channel: AiChatChannel::Admin,
            userId: (int) $user->id,
            user: $user,
            sessionId: $sessionId,
            module: strtolower(trim((string) ($module ?: 'taxi'))),
        );
    }

    private function resolveCompanyIdForUser(User $user): ?int
    {
        if ($user->hasRole('super-admin')) {
            $tenantId = session('selected_tenant');

            return $tenantId ? (int) $tenantId : null;
        }

        return $user->company_id ? (int) $user->company_id : null;
    }
}
