<?php

namespace App\Services\AiChat;

use App\Enums\AiChat\AiChatIntent;
use RuntimeException;

final class AiChatSqlGuardService
{
    /**
     * @param  array{
     *     company_id: int,
     *     user_id: ?int,
     *     intent: string,
     *     allow_live_data: bool,
     *     allow_public_rates: bool,
     *     exp: int
     * }  $claims
     */
    public function assertMayExecute(array $claims, AiChatIntent $intent): void
    {
        if ($intent === AiChatIntent::Tarieven) {
            if (($claims['allow_public_rates'] ?? false) !== true) {
                throw new RuntimeException('Alleen publieke tarieven mogen worden opgevraagd.');
            }

            return;
        }

        if (($claims['allow_live_data'] ?? false) !== true) {
            throw new RuntimeException('Live data queries zijn niet toegestaan.');
        }

        if (($claims['company_id'] ?? 0) <= 0) {
            throw new RuntimeException('company_id ontbreekt in SQL-context.');
        }

        if (($claims['exp'] ?? 0) < now()->timestamp) {
            throw new RuntimeException('SQL-token is verlopen.');
        }
    }

    /**
     * Extra laag: company_id uit request mag nooit afwijken van token.
     */
    public function assertCompanyMatches(array $claims, int $requestedCompanyId): void
    {
        if ((int) $claims['company_id'] !== $requestedCompanyId) {
            throw new RuntimeException('company_id mismatch — query geblokkeerd.');
        }
    }
}
