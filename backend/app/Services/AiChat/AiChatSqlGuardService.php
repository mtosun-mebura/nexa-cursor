<?php

namespace App\Services\AiChat;

use App\Enums\AiChat\AiChatChannel;
use App\Enums\AiChat\AiChatIntent;
use RuntimeException;

final class AiChatSqlGuardService
{
    /**
     * @param  array{
     *     company_id: int,
     *     channel?: string,
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

            $channel = (string) ($claims['channel'] ?? '');
            if ($channel !== '' && $channel !== AiChatChannel::Public->value) {
                throw new RuntimeException('Publieke tarieven zijn alleen via het publieke kanaal toegestaan.');
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

        $this->assertChannelAllowsIntent($claims, $intent);
    }

    /**
     * @param  array{channel?: string, user_id?: ?int}  $claims
     */
    public function assertChannelAllowsIntent(array $claims, AiChatIntent $intent): void
    {
        $channel = (string) ($claims['channel'] ?? '');
        if ($channel === '') {
            throw new RuntimeException('SQL-token mist kanaal (channel).');
        }

        if ($intent === AiChatIntent::MijnRit) {
            if ($channel !== AiChatChannel::MijnTaxi->value) {
                throw new RuntimeException('Persoonlijke ritten zijn alleen toegestaan via het Mijn Taxi-kanaal.');
            }

            if ((int) ($claims['user_id'] ?? 0) <= 0) {
                throw new RuntimeException('user_id ontbreekt voor mijn_rit.');
            }

            return;
        }

        if ($channel !== AiChatChannel::Admin->value) {
            throw new RuntimeException('Operationele live data is alleen toegestaan via het admin-kanaal.');
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
