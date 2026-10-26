<?php

namespace App\Services\Monitoring;

use App\Models\ErrorLog;
use App\Models\User;
use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use App\Services\ChatUnreadCounter;
use App\Services\ModuleDatabaseService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Een herstelpoging per bekende foutregel. Alleen oplossen als de hercontrole slaagt.
 */
class MonitoringKnownErrorHealer
{
    private static bool $healing = false;

    public function __construct(
        private TaxiContractvervoerSchemaService $schema,
        private ChatUnreadCounter $unread,
        private ModuleDatabaseService $modules,
    ) {}

    public function heal(): void
    {
        if (self::$healing || ! Schema::hasTable('error_logs')) {
            return;
        }

        self::$healing = true;

        try {
            $logs = $this->pendingKnownErrors();
            $logs = $logs->sortBy(fn (ErrorLog $log): int => $this->rank((string) $log->message))->values();

            foreach ($logs as $log) {
                if ($this->alreadyAttempted($log)) {
                    continue;
                }

                $kind = $this->kind((string) $log->message);
                if ($kind === null) {
                    continue;
                }

                $this->markAttempted($log);

                $ok = false;
                try {
                    $ok = $this->repairAndRecheck($kind);
                } catch (Throwable) {
                    $ok = false;
                }

                if ($ok) {
                    $this->markResolved($log);
                }
            }
        } finally {
            self::$healing = false;
        }
    }

    public function kind(string $message): ?string
    {
        if (str_contains($message, 'candidate_id') && str_contains($message, 'chat_messages')) {
            return 'chat_unread';
        }

        if (str_contains($message, 'taxi:cancel-unaccepted-rides') && str_contains($message, 'exit code')) {
            return 'cancel_unaccepted';
        }

        if (str_contains($message, 'pg_type_typname_nsp_index') && str_contains($message, 'transport_customers')) {
            return 'transport_customers_type';
        }

        return null;
    }

    private function rank(string $message): int
    {
        return match ($this->kind($message)) {
            'transport_customers_type' => 0,
            'cancel_unaccepted' => 1,
            'chat_unread' => 2,
            default => 9,
        };
    }

    /**
     * @return \Illuminate\Support\Collection<int, ErrorLog>
     */
    private function pendingKnownErrors()
    {
        return ErrorLog::query()
            ->where('resolved', false)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('message', 'like', '%candidate_id%')
                        ->where('message', 'like', '%chat_messages%');
                })->orWhere(function ($query) {
                    $query->where('message', 'like', '%taxi:cancel-unaccepted-rides%')
                        ->where('message', 'like', '%exit code%');
                })->orWhere(function ($query) {
                    $query->where('message', 'like', '%pg_type_typname_nsp_index%')
                        ->where('message', 'like', '%transport_customers%');
                });
            })
            ->orderBy('id')
            ->get();
    }

    private function repairAndRecheck(string $kind): bool
    {
        return match ($kind) {
            'transport_customers_type' => $this->repairTransportCustomers(),
            'cancel_unaccepted' => $this->repairCancelUnacceptedRides(),
            'chat_unread' => $this->repairUnreadQuery(),
            default => false,
        };
    }

    private function repairTransportCustomers(): bool
    {
        $connection = $this->taxiConnection();
        $this->schema->ensureTransportCustomersTable($connection);

        return $this->schema->transportCustomersTableExists($connection)
            && ! $this->schema->orphanTransportCustomersTypeExists($connection);
    }

    private function repairCancelUnacceptedRides(): bool
    {
        if (! $this->cancelFixIsInPlace()) {
            return false;
        }

        $this->schema->ensureTransportCustomersTable($this->taxiConnection());

        try {
            $exit = Artisan::call('taxi:cancel-unaccepted-rides');
        } catch (Throwable) {
            return false;
        }

        return $exit === 0;
    }

    private function cancelFixIsInPlace(): bool
    {
        return $this->schema->transportCustomersCreatePlan(true, true) === 'skip'
            && $this->schema->transportCustomersCreatePlan(false, true) === 'drop_orphan_type_then_create';
    }

    private function repairUnreadQuery(): bool
    {
        if (! $this->unreadQuerySucceeds()) {
            return false;
        }

        return $this->unreadQuerySucceeds();
    }

    private function unreadQuerySucceeds(): bool
    {
        if (! Schema::hasTable('chats') || ! Schema::hasTable('chat_messages')) {
            return false;
        }

        try {
            $sql = $this->unread->unreadQuery(0, User::class)->toSql();
            if (! Schema::hasColumn('chats', 'candidate_id') && str_contains(strtolower($sql), 'candidate_id')) {
                return false;
            }

            $this->unread->countForUser(0, User::class);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function taxiConnection(): string
    {
        $this->modules->registerConnection('taxi');

        return $this->modules->getModuleConnectionName('taxi');
    }

    private function alreadyAttempted(ErrorLog $log): bool
    {
        $context = is_array($log->context) ? $log->context : [];

        return ($context['repair_attempted'] ?? false) === true;
    }

    private function markAttempted(ErrorLog $log): void
    {
        $context = is_array($log->context) ? $log->context : [];
        $context['repair_attempted'] = true;
        $log->forceFill(['context' => $context])->save();
    }

    private function markResolved(ErrorLog $log): void
    {
        if ($log->resolved) {
            return;
        }

        $log->forceFill([
            'resolved' => true,
            'resolved_at' => now(),
        ])->save();
    }
}
