<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Controllers\Admin\Concerns\AuthorizesTaxiPermissions;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Models\TransportCustomerPortalUser;
use App\Modules\NexaTaxi\Models\TransportPassenger;
use App\Modules\NexaTaxi\Models\TransportPassengerGuardian;
use App\Modules\NexaTaxi\Services\TaxiAppFirstLoginService;
use App\Modules\NexaTaxi\Services\TaxiAppUserWelcomeService;
use App\Modules\NexaTaxi\Services\TaxiContractPortalAccessService;
use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use App\Services\CompanyEntitlementService;
use App\Support\TenantPackageCapability;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TransportCustomerPortalController extends Controller
{
    use AuthorizesTaxiPermissions, TenantFilter, UsesModuleDatabase;

    public function store(
        Request $request,
        int $customerId,
        TaxiContractPortalAccessService $access
    ): RedirectResponse {
        $this->authorizeOrPermission('rides.update');

        $conn = $this->moduleConnection();
        app(TaxiContractvervoerSchemaService::class)->ensureContractPortalTables($conn);

        $customer = $this->findCustomer($conn, $customerId);
        $companyId = (int) $customer->company_id;
        $company = Company::query()->find($companyId);
        app(CompanyEntitlementService::class)->assertAllows($company, TenantPackageCapability::CONTRACT_PORTAL);

        if ($request->input('existing_user_id') === '' || $request->input('existing_user_id') === '0') {
            $request->merge(['existing_user_id' => null]);
        }

        $userMode = $request->input('user_mode', 'new') === 'existing' ? 'existing' : 'new';
        $request->merge(['user_mode' => $userMode]);

        if ($userMode === 'new') {
            $request->merge(['existing_user_id' => null]);
        }

        $data = $request->validate([
            'user_mode' => ['required', Rule::in(['new', 'existing'])],
            'existing_user_id' => [
                Rule::requiredIf(fn () => $userMode === 'existing'),
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'email' => [
                Rule::requiredIf(fn () => $userMode === 'new'),
                'nullable',
                'email',
                'max:200',
            ],
            'first_name' => [
                Rule::requiredIf(fn () => $userMode === 'new'),
                'nullable',
                'string',
                'max:100',
            ],
            'last_name' => ['nullable', 'string', 'max:100'],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'max:100',
            ],
            'portal_role' => ['required', Rule::in([
                TransportCustomerPortalUser::ROLE_CONTRACTANT,
                TransportCustomerPortalUser::ROLE_CONTRACTOUDER,
            ])],
            'passenger_ids' => ['nullable', 'array'],
            'passenger_ids.*' => ['integer'],
        ]);

        $existingUserId = $userMode === 'existing' ? (int) ($data['existing_user_id'] ?? 0) : 0;
        $createdNewUser = false;

        if ($existingUserId > 0) {
            $user = User::query()
                ->where('id', $existingUserId)
                ->where('company_id', $companyId)
                ->first();

            if (! $user) {
                return back()->withErrors([
                    'existing_user_id' => 'Gebruiker niet gevonden voor dit bedrijf.',
                ])->withInput();
            }

            if (! $user->email_verified_at) {
                $user->email_verified_at = now();
                $user->save();
            }
        } else {
            $email = strtolower(trim((string) ($data['email'] ?? '')));
            if ($email === '') {
                return back()->withErrors([
                    'email' => 'E-mailadres is verplicht.',
                ])->withInput();
            }

            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
            $createdNewUser = false;

            if ($user) {
                if ((int) $user->company_id > 0 && (int) $user->company_id !== $companyId) {
                    return back()->withErrors([
                        'email' => 'Dit e-mailadres hoort bij een ander bedrijf.',
                    ])->withInput();
                }
                if (! $user->company_id) {
                    $user->company_id = $companyId;
                }
                $user->first_name = $data['first_name'];
                $user->last_name = $data['last_name'] ?? $user->last_name ?? '';
                if (! empty($data['password'])) {
                    $user->password = Hash::make($data['password']);
                }
                if (! $user->email_verified_at) {
                    $user->email_verified_at = now();
                }
                $user->save();
            } else {
                $firstLogin = app(TaxiAppFirstLoginService::class);
                try {
                    $user = User::create(array_merge([
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'] ?? '',
                        'email' => $email,
                        'password' => $firstLogin->unusablePasswordHash(),
                        'company_id' => $companyId,
                        'is_active' => true,
                    ], $firstLogin->provisionFlags()));
                    $createdNewUser = true;
                } catch (QueryException $e) {
                    if (str_contains(strtolower($e->getMessage()), 'users_email_unique')
                        || str_contains(strtolower($e->getMessage()), 'unique')) {
                        return back()->withErrors([
                            'email' => 'Dit e-mailadres is al in gebruik.',
                        ])->withInput();
                    }

                    throw $e;
                }
            }
        }

        $access->assignPortalRole($user, $data['portal_role'], $companyId);

        TransportCustomerPortalUser::on($conn)->updateOrCreate(
            [
                'transport_customer_id' => $customer->id,
                'user_id' => $user->id,
            ],
            [
                'company_id' => $companyId,
                'portal_role' => $data['portal_role'],
                'active' => true,
            ]
        );

        $this->syncGuardians(
            $conn,
            $companyId,
            $customer,
            $user,
            $data['passenger_ids'] ?? []
        );

        if (! empty($createdNewUser)) {
            $welcomeRole = $data['portal_role'] === TransportCustomerPortalUser::ROLE_CONTRACTANT
                ? TaxiAppFirstLoginService::ROLE_CONTRACTANT
                : TaxiAppFirstLoginService::ROLE_CONTRACTOUDER;
            app(TaxiAppUserWelcomeService::class)->send($user->fresh(), $welcomeRole);
        }

        return redirect()
            ->route('admin.taxi.transport_customers.show', $customer->id)
            ->with('success', ! empty($createdNewUser)
                ? 'Portaalgebruiker gekoppeld. Er is een welkomstmail verstuurd; eerste keer inloggen via een code in de app (/taxi/contract).'
                : 'Portaalgebruiker gekoppeld. App: /taxi/contract');
    }

    public function update(
        Request $request,
        int $customerId,
        int $portalUserId,
        TaxiContractPortalAccessService $access
    ): RedirectResponse {
        $this->authorizeOrPermission('rides.update');

        $conn = $this->moduleConnection();
        app(TaxiContractvervoerSchemaService::class)->ensureContractPortalTables($conn);
        $customer = $this->findCustomer($conn, $customerId);

        $link = TransportCustomerPortalUser::on($conn)
            ->where('transport_customer_id', $customer->id)
            ->where('id', $portalUserId)
            ->firstOrFail();

        $data = $request->validate([
            'portal_role' => ['required', Rule::in([
                TransportCustomerPortalUser::ROLE_CONTRACTANT,
                TransportCustomerPortalUser::ROLE_CONTRACTOUDER,
            ])],
            'active' => ['nullable', 'boolean'],
            'passenger_ids' => ['nullable', 'array'],
            'passenger_ids.*' => ['integer'],
        ]);

        $link->update([
            'portal_role' => $data['portal_role'],
            'active' => $request->boolean('active', true),
        ]);

        $user = User::find($link->user_id);
        if ($user) {
            $access->assignPortalRole($user, $data['portal_role'], (int) $customer->company_id);
            $this->syncGuardians(
                $conn,
                (int) $customer->company_id,
                $customer,
                $user,
                $data['passenger_ids'] ?? []
            );
        }

        return redirect()
            ->route('admin.taxi.transport_customers.show', $customer->id)
            ->with('success', 'Portaalgebruiker bijgewerkt.');
    }

    public function destroy(int $customerId, int $portalUserId): RedirectResponse
    {
        $this->authorizeOrPermission('rides.update');

        $conn = $this->moduleConnection();
        app(TaxiContractvervoerSchemaService::class)->ensureContractPortalTables($conn);
        $customer = $this->findCustomer($conn, $customerId);

        $link = TransportCustomerPortalUser::on($conn)
            ->where('transport_customer_id', $customer->id)
            ->where('id', $portalUserId)
            ->firstOrFail();

        TransportPassengerGuardian::on($conn)
            ->where('user_id', $link->user_id)
            ->where('company_id', $customer->company_id)
            ->whereIn('transport_passenger_id', $this->customerPassengerIds($conn, $customer))
            ->delete();

        $link->delete();

        return redirect()
            ->route('admin.taxi.transport_customers.show', $customer->id)
            ->with('success', 'Portaalgebruiker ontkoppeld.');
    }

    private function findCustomer(string $conn, int $customerId): TransportCustomer
    {
        $query = TransportCustomer::on($conn)->where('id', $customerId);
        $this->applyTenantFilter($query);

        return $query->firstOrFail();
    }

    /**
     * @return list<int>
     */
    private function customerPassengerIds(string $conn, TransportCustomer $customer): array
    {
        return TransportPassenger::on($conn)
            ->where('company_id', $customer->company_id)
            ->whereHas('contract', fn ($q) => $q->where('transport_customer_id', $customer->id))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Sla aangevinkte cliënten op voor deze portaalgebruiker.
     *
     * @param  list<int|string>  $passengerIds
     */
    private function syncGuardians(
        string $conn,
        int $companyId,
        TransportCustomer $customer,
        User $user,
        array $passengerIds
    ): void {
        $allowed = $this->customerPassengerIds($conn, $customer);

        TransportPassengerGuardian::on($conn)
            ->where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->whereIn('transport_passenger_id', $allowed)
            ->delete();

        $selected = array_values(array_unique(array_map('intval', $passengerIds)));
        $selected = array_values(array_intersect($selected, $allowed));

        foreach ($selected as $passengerId) {
            TransportPassengerGuardian::on($conn)->create([
                'company_id' => $companyId,
                'transport_passenger_id' => $passengerId,
                'user_id' => $user->id,
            ]);
        }
    }
}
