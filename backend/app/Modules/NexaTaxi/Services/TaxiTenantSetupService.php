<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\GeneralSetting;
use App\Models\Notification;
use App\Models\User;
use App\Modules\NexaTaxi\Models\DefaultRate;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Support\NexaTaxiSchema;
use App\Services\ModuleDatabaseService;
use App\Services\TenantConfigAccessService;
use App\Support\TenantConfigCapability;
use Throwable;

/**
 * Standaard inrichtingsflow voor Nexa Taxi-tenants:
 * tarieven, voertuigen en (optioneel) eigen mailserver.
 */
class TaxiTenantSetupService
{
    public const NOTIFICATION_TYPE = 'taxi_setup';

    public const SESSION_PROMPT_KEY = 'taxi_setup_prompt';

    /** @var list<string> */
    public const BOOKING_COMPONENT_IDS = [
        'taxi.boekingsmodule',
        'taxi.boekingsmodule_v2',
        'taxi.algemene_boekingsmodule',
        'taxiroyaal.boekingsmodule',
    ];

    public function __construct(
        private readonly ModuleDatabaseService $moduleDb,
        private readonly TenantConfigAccessService $configAccess,
    ) {}

    public function appliesTo(?Company $company): bool
    {
        return $company !== null && $company->hasTaxiModule();
    }

    /**
     * @return array{
     *     applies: bool,
     *     needs_attention: bool,
     *     needs_login_prompt: bool,
     *     booking_allowed: bool,
     *     booking_block_message: string|null,
     *     steps: list<array<string, mixed>>,
     *     incomplete_count: int
     * }
     */
    public function status(Company $company, ?User $user = null): array
    {
        if (! $this->appliesTo($company)) {
            return [
                'applies' => false,
                'needs_attention' => false,
                'needs_login_prompt' => false,
                'booking_allowed' => true,
                'booking_block_message' => null,
                'steps' => [],
                'incomplete_count' => 0,
            ];
        }

        $hasVehicles = $this->hasVehicles($company);
        $hasRates = $this->hasRates($company);
        $hasMail = $this->hasOwnMailServer($company);

        $steps = [
            $this->step(
                key: 'vehicles',
                title: 'Voertuigen',
                description: 'Voeg minstens één actief voertuig toe. Zonder voertuig kan de boekingsmodule niet op de website.',
                done: $hasVehicles,
                required: true,
                url: $this->safeRoute('admin.taxi.vehicles.index'),
                button: 'Voertuigen openen',
            ),
            $this->step(
                key: 'rates',
                title: 'Tarieven',
                description: 'Stel de standaardtarieven in zodat klanten een juiste prijsindicatie zien.',
                done: $hasRates,
                required: true,
                url: $this->safeRoute('admin.taxi.tarieven.edit'),
                button: 'Tarieven openen',
            ),
            $this->step(
                key: 'mail',
                title: 'Mailserver',
                description: $hasMail
                    ? 'Eigen mailserver is ingesteld. Klantmails gaan uit namens jouw bedrijf.'
                    : 'Nog geen eigen mailserver. Uitgaande mail gaat nu via NEXA Suite; klanten zien die afzender.',
                done: $hasMail,
                required: false,
                url: $this->mailSetupUrl($company, $user),
                button: $hasMail
                    ? ($this->mailSetupUrl($company, $user) ? 'Mailserver bekijken' : null)
                    : ($this->mailSetupUrl($company, $user) ? 'Mailserver instellen' : null),
                warning: ! $hasMail,
            ),
        ];

        $incompleteRequired = collect($steps)->where('required', true)->where('done', false)->count();
        $incompleteAny = collect($steps)->where('done', false)->count();

        return [
            'applies' => true,
            'needs_attention' => $incompleteAny > 0,
            'needs_login_prompt' => $incompleteRequired > 0,
            'booking_allowed' => $hasVehicles,
            'booking_block_message' => $hasVehicles
                ? null
                : 'Voeg eerst minstens één voertuig toe voordat je de boekingsmodule op de website plaatst.',
            'steps' => $steps,
            'incomplete_count' => $incompleteAny,
            'company_name' => $company->name,
        ];
    }

    public function hasVehicles(Company $company): bool
    {
        $conn = $this->taxiConnectionOrNull();
        if ($conn === null) {
            return false;
        }

        try {
            return Vehicle::on($conn)
                ->where('company_id', $company->id)
                ->where('active', true)
                ->exists();
        } catch (Throwable) {
            return false;
        }
    }

    public function hasRates(Company $company): bool
    {
        $conn = $this->taxiConnectionOrNull();
        if ($conn === null) {
            return false;
        }

        try {
            foreach (DefaultRate::on($conn)->get() as $rate) {
                if ($this->rateLooksConfigured($rate->base_fare, $rate->min_fare, $rate->price_per_km, $rate->price_per_min)) {
                    return true;
                }
            }

            return Vehicle::on($conn)
                ->where('company_id', $company->id)
                ->get(['base_fare', 'min_fare', 'price_per_km', 'price_per_min'])
                ->contains(fn (Vehicle $vehicle) => $this->rateLooksConfigured(
                    $vehicle->base_fare,
                    $vehicle->min_fare,
                    $vehicle->price_per_km,
                    $vehicle->price_per_min
                ));
        } catch (Throwable) {
            return false;
        }
    }

    public function hasOwnMailServer(Company $company): bool
    {
        $host = trim((string) GeneralSetting::get('MAIL_HOST', '', (int) $company->id));

        return $host !== '';
    }

    public function isBookingModuleAllowed(Company $company): bool
    {
        return $this->hasVehicles($company);
    }

    public function isBookingComponentId(string $componentId): bool
    {
        $id = strtolower(trim($componentId));
        if (str_starts_with($id, 'component:')) {
            $id = substr($id, strlen('component:'));
        }

        return in_array($id, self::BOOKING_COMPONENT_IDS, true);
    }

    public function isBookingSectionKey(string $sectionKey): bool
    {
        return $this->isBookingComponentId($sectionKey);
    }

    /**
     * @param  list<string>  $sectionOrder
     */
    public function sectionOrderContainsBooking(array $sectionOrder): bool
    {
        foreach ($sectionOrder as $key) {
            if (is_string($key) && $this->isBookingSectionKey($key)) {
                return true;
            }
        }

        return false;
    }

    public function markPromptPending(): void
    {
        session()->put(self::SESSION_PROMPT_KEY, true);
    }

    public function consumePrompt(): bool
    {
        return (bool) session()->pull(self::SESSION_PROMPT_KEY, false);
    }

    public function syncNotification(User $user, Company $company): void
    {
        $status = $this->status($company, $user);
        if (! $status['needs_attention']) {
            $this->clearNotification($user, $company);

            return;
        }

        $title = 'Nexa Taxi inrichten';
        $message = $this->notificationMessage($status);
        $actionUrl = route('admin.dashboard', ['taxi_setup' => 1]);

        $payload = [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'type' => self::NOTIFICATION_TYPE,
            'category' => 'warning',
            'title' => $title,
            'message' => $message,
            'priority' => 'high',
            'action_url' => $actionUrl,
            'data' => json_encode([
                'incomplete_count' => $status['incomplete_count'],
                'steps' => collect($status['steps'])->map(fn (array $step) => [
                    'key' => $step['key'],
                    'title' => $step['title'],
                    'done' => $step['done'],
                    'url' => $step['url'],
                    'button' => $step['button'],
                ])->values()->all(),
            ]),
        ];

        // Eén notificatie is genoeg (ook als die al gelezen is). Daarna volstaat
        // de gele banner; geen nieuwe melding bij elke page refresh.
        $existing = Notification::query()
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->where('type', self::NOTIFICATION_TYPE)
            ->latest('id')
            ->first();

        if ($existing) {
            if ($existing->read_at === null) {
                $existing->fill($payload)->save();
            }

            Notification::query()
                ->where('user_id', $user->id)
                ->where('company_id', $company->id)
                ->where('type', self::NOTIFICATION_TYPE)
                ->where('id', '!=', $existing->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return;
        }

        Notification::query()->create($payload);
    }

    public function clearNotification(User $user, Company $company): void
    {
        Notification::query()
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->where('type', self::NOTIFICATION_TYPE)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * @param  array{steps: list<array<string, mixed>>, incomplete_count: int}  $status
     */
    private function notificationMessage(array $status): string
    {
        $missing = [];
        foreach ($status['steps'] as $step) {
            if (! empty($step['done'])) {
                continue;
            }
            $missing[] = (string) $step['title'];
        }

        if ($missing === []) {
            return 'Open het stappenplan om je Nexa Taxi-inrichting te controleren.';
        }

        $list = $this->joinDutch($missing);

        return 'Nog in te richten: '.$list.'. Open het stappenplan voor directe links.';
    }

    /**
     * @return array{
     *     key: string,
     *     title: string,
     *     description: string,
     *     done: bool,
     *     required: bool,
     *     warning: bool,
     *     url: string|null,
     *     button: string|null
     * }
     */
    private function step(
        string $key,
        string $title,
        string $description,
        bool $done,
        bool $required,
        ?string $url,
        ?string $button,
        bool $warning = false,
    ): array {
        return [
            'key' => $key,
            'title' => $title,
            'description' => $description,
            'done' => $done,
            'required' => $required,
            'warning' => $warning,
            'url' => $url,
            'button' => $button,
        ];
    }

    private function rateLooksConfigured(mixed ...$values): bool
    {
        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if ((float) $value > 0) {
                return true;
            }
        }

        return false;
    }

    private function mailSetupUrl(Company $company, ?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        if ($user->isSuperAdmin() || $user->hasRole('super-admin')) {
            return $this->safeRoute('admin.settings.index');
        }

        if ($this->configAccess->can($user, $company, TenantConfigCapability::MAIL)) {
            $step = TenantConfigCapability::wizardStepFor(TenantConfigCapability::MAIL);
            if ($step !== null) {
                return $this->safeRoute('admin.companies.wizard.step', [$company, $step]);
            }
        }

        return null;
    }

    private function safeRoute(string $name, mixed $parameters = []): ?string
    {
        try {
            if (! \Illuminate\Support\Facades\Route::has($name)) {
                return null;
            }

            return route($name, $parameters);
        } catch (Throwable) {
            return null;
        }
    }

    private function taxiConnectionOrNull(): ?string
    {
        try {
            if (! $this->moduleDb->supportsModuleDatabases()) {
                $conn = $this->moduleDb->getModuleConnectionName('taxi');

                return NexaTaxiSchema::coreTablesExist($conn) ? $conn : null;
            }

            $this->moduleDb->ensureModuleStorageReady('taxi');
            $conn = $this->moduleDb->getModuleConnectionName('taxi');

            return NexaTaxiSchema::coreTablesExist($conn) ? $conn : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  list<string>  $items
     */
    private function joinDutch(array $items): string
    {
        $items = array_values($items);
        $count = count($items);
        if ($count === 0) {
            return '';
        }
        if ($count === 1) {
            return $items[0];
        }
        if ($count === 2) {
            return $items[0].' en '.$items[1];
        }

        return implode(', ', array_slice($items, 0, -1)).' en '.$items[$count - 1];
    }
}
