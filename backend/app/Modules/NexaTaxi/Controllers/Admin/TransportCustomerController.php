<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Models\TransportContract;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Models\TransportPaymentMandate;
use App\Modules\NexaTaxi\Models\TransportPassenger;
use App\Modules\NexaTaxi\Models\TransportGroup;
use App\Modules\NexaTaxi\Models\TransportIndividualBooking;
use App\Modules\NexaTaxi\Services\ContractInvoiceService;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use App\Rules\ValidIban;
use Illuminate\Http\Request;

class TransportCustomerController extends Controller
{
    use TenantFilter, UsesModuleDatabase;

    // -----------------------------------------------------------------------
    // Contractklanten
    // -----------------------------------------------------------------------

    public function index(Request $request)
    {
        $this->authorizeOrPermission('rides.view');

        $conn = $this->moduleConnection();
        $query = TransportCustomer::on($conn);
        $this->applyTenantFilter($query);

        if ($request->filled('search')) {
            $s = $request->string('search')->toString();
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('contact_email', 'like', "%{$s}%")
                    ->orWhere('debtor_number', 'like', "%{$s}%");
            });
        }

        if ($request->filled('active')) {
            $query->where('active', $request->string('active') === '1');
        }

        $customers = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('taxi::admin.transport_customers.index', compact('customers'));
    }

    public function create()
    {
        $this->authorizeOrPermission('rides.create');

        return view('taxi::admin.transport_customers.create');
    }

    public function store(Request $request)
    {
        $this->authorizeOrPermission('rides.create');

        $data = $request->validate([
            'name'                => ['required', 'string', 'max:200'],
            'contact_name'        => ['nullable', 'string', 'max:200'],
            'contact_email'       => ['nullable', 'email', 'max:200'],
            'contact_phone'       => ['nullable', 'string', 'max:50'],
            'debtor_number'       => ['nullable', 'string', 'max:50'],
            'billing_address'     => ['nullable', 'string', 'max:300'],
            'billing_city'        => ['nullable', 'string', 'max:100'],
            'billing_postal_code' => ['nullable', 'string', 'max:20'],
            'billing_country'     => ['nullable', 'string', 'max:100'],
            'notes'               => ['nullable', 'string'],
            'active'              => ['boolean'],
        ]);

        $conn = $this->moduleConnection();
        $companyId = $this->getTenantId();

        $customer = TransportCustomer::on($conn)->create(array_merge($data, [
            'company_id' => $companyId,
            'active'     => $request->boolean('active', true),
        ]));

        return redirect()->route('admin.taxi.transport_customers.show', $customer->id)
            ->with('success', 'Contractklant aangemaakt.');
    }

    public function show(int $id)
    {
        $this->authorizeOrPermission('rides.view');

        $conn = $this->moduleConnection();
        $customer = TransportCustomer::on($conn)->findOrFail($id);
        $contracts = TransportContract::on($conn)
            ->where('transport_customer_id', $customer->id)
            ->orderBy('start_date', 'desc')
            ->get();

        return view('taxi::admin.transport_customers.show', compact('customer', 'contracts'));
    }

    public function edit(int $id)
    {
        $this->authorizeOrPermission('rides.update');

        $conn = $this->moduleConnection();
        $customer = TransportCustomer::on($conn)->findOrFail($id);

        return view('taxi::admin.transport_customers.edit', compact('customer'));
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeOrPermission('rides.update');

        $data = $request->validate([
            'name'                => ['required', 'string', 'max:200'],
            'contact_name'        => ['nullable', 'string', 'max:200'],
            'contact_email'       => ['nullable', 'email', 'max:200'],
            'contact_phone'       => ['nullable', 'string', 'max:50'],
            'debtor_number'       => ['nullable', 'string', 'max:50'],
            'billing_address'     => ['nullable', 'string', 'max:300'],
            'billing_city'        => ['nullable', 'string', 'max:100'],
            'billing_postal_code' => ['nullable', 'string', 'max:20'],
            'billing_country'     => ['nullable', 'string', 'max:100'],
            'notes'               => ['nullable', 'string'],
            'active'              => ['boolean'],
        ]);

        $conn = $this->moduleConnection();
        $customer = TransportCustomer::on($conn)->findOrFail($id);
        $customer->update(array_merge($data, [
            'active' => $request->boolean('active'),
        ]));

        return redirect()->route('admin.taxi.transport_customers.show', $customer->id)
            ->with('success', 'Contractklant opgeslagen.');
    }

    public function destroy(int $id)
    {
        $this->authorizeOrPermission('rides.delete');

        $conn = $this->moduleConnection();
        $customer = TransportCustomer::on($conn)->findOrFail($id);

        // Soft-disable i.p.v. verwijderen; contracten + passagiers blijven bewaard.
        $customer->update(['active' => false]);

        return redirect()->route('admin.taxi.transport_customers.index')
            ->with('success', 'Contractklant gedeactiveerd.');
    }

    // -----------------------------------------------------------------------
    // Abonnementen (onder klant)
    // -----------------------------------------------------------------------

    public function contractCreate(int $customerId)
    {
        $this->authorizeOrPermission('rides.create');

        $conn = $this->moduleConnection();
        $customer = TransportCustomer::on($conn)->findOrFail($customerId);

        return view('taxi::admin.transport_customers.contract_create', compact('customer'));
    }

    public function contractStore(Request $request, int $customerId)
    {
        $this->authorizeOrPermission('rides.create');

        $this->mergeParsedContractDates($request);

        $data = $request->validate([
            'name'               => ['required', 'string', 'max:200'],
            'planning_color'     => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'status'             => ['required', 'in:active,paused,ended'],
            'start_date'         => ['nullable', 'date'],
            'end_date'           => ['nullable', 'date', 'after_or_equal:start_date'],
            'billing_model'      => ['required', 'in:fixed_monthly,per_ride,hybrid'],
            'monthly_amount'     => ['nullable', 'numeric', 'min:0'],
            'price_per_ride'     => ['nullable', 'numeric', 'min:0'],
            'invoice_day'        => ['required', 'integer', 'min:1', 'max:28'],
            'payment_terms_days' => ['required', 'integer', 'min:1', 'max:90'],
            'tax_rate'           => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $conn = $this->moduleConnection();
        $customer = TransportCustomer::on($conn)->findOrFail($customerId);

        $contract = TransportContract::on($conn)->create(array_merge($data, [
            'company_id'           => $customer->company_id,
            'transport_customer_id' => $customer->id,
        ]));

        return redirect()->route('admin.taxi.transport_customers.contract_show', [$customerId, $contract->id])
            ->with('success', 'Abonnement aangemaakt.');
    }

    public function contractShow(int $customerId, int $contractId, ContractInvoiceService $invoiceService)
    {
        $this->authorizeOrPermission('rides.view');

        $conn = $this->moduleConnection();
        $customer = TransportCustomer::on($conn)->findOrFail($customerId);
        $contract = TransportContract::on($conn)
            ->where('transport_customer_id', $customerId)
            ->findOrFail($contractId);

        $mandate = TransportPaymentMandate::on($conn)
            ->where('transport_contract_id', $contractId)
            ->latest()
            ->first();

        $passengerCount = TransportPassenger::on($conn)
            ->where('transport_contract_id', $contractId)
            ->where('active', true)
            ->count();

        $recentPassengers = TransportPassenger::on($conn)
            ->where('transport_contract_id', $contractId)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(5)
            ->get();

        $groupCount = TransportGroup::on($conn)
            ->where('transport_contract_id', $contractId)
            ->where('active', true)
            ->count();

        $recentGroups = TransportGroup::on($conn)
            ->where('transport_contract_id', $contractId)
            ->orderBy('name')
            ->limit(5)
            ->get();

        $individualBookingCount = TransportIndividualBooking::on($conn)
            ->where('transport_contract_id', $contractId)
            ->where('status', TransportIndividualBooking::STATUS_PLANNED)
            ->count();

        $recentIndividualBookings = TransportIndividualBooking::on($conn)
            ->where('transport_contract_id', $contractId)
            ->with('passenger')
            ->orderByDesc('pickup_at')
            ->limit(5)
            ->get();

        $contractInvoices = $invoiceService->invoicesForContract($contractId);
        $defaultInvoicePeriod = $invoiceService->previousBillingPeriod();

        return view('taxi::admin.transport_customers.contract_show', compact(
            'customer',
            'contract',
            'mandate',
            'passengerCount',
            'recentPassengers',
            'groupCount',
            'recentGroups',
            'individualBookingCount',
            'recentIndividualBookings',
            'contractInvoices',
            'defaultInvoicePeriod',
        ));
    }

    public function contractEdit(int $customerId, int $contractId)
    {
        $this->authorizeOrPermission('rides.update');

        $conn = $this->moduleConnection();
        $customer = TransportCustomer::on($conn)->findOrFail($customerId);
        $contract = TransportContract::on($conn)
            ->where('transport_customer_id', $customerId)
            ->findOrFail($contractId);

        return view('taxi::admin.transport_customers.contract_edit', compact('customer', 'contract'));
    }

    public function contractUpdate(Request $request, int $customerId, int $contractId)
    {
        $this->authorizeOrPermission('rides.update');

        $this->mergeParsedContractDates($request);

        $data = $request->validate([
            'name'               => ['required', 'string', 'max:200'],
            'planning_color'     => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'status'             => ['required', 'in:active,paused,ended'],
            'start_date'         => ['nullable', 'date'],
            'end_date'           => ['nullable', 'date', 'after_or_equal:start_date'],
            'billing_model'      => ['required', 'in:fixed_monthly,per_ride,hybrid'],
            'monthly_amount'     => ['nullable', 'numeric', 'min:0'],
            'price_per_ride'     => ['nullable', 'numeric', 'min:0'],
            'invoice_day'        => ['required', 'integer', 'min:1', 'max:28'],
            'payment_terms_days' => ['required', 'integer', 'min:1', 'max:90'],
            'tax_rate'           => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $conn = $this->moduleConnection();
        $contract = TransportContract::on($conn)
            ->where('transport_customer_id', $customerId)
            ->findOrFail($contractId);
        $contract->update($data);

        return redirect()->route('admin.taxi.transport_customers.contract_show', [$customerId, $contractId])
            ->with('success', 'Abonnement opgeslagen.');
    }

    private function mergeParsedContractDates(Request $request): void
    {
        $request->merge([
            'start_date' => parse_admin_date($request->input('start_date')),
            'end_date' => parse_admin_date($request->input('end_date')),
        ]);
    }

    private function authorizeOrPermission(string $ability): void
    {
        if (auth()->user()->hasRole('super-admin')) {
            return;
        }
        if (! auth()->user()->can($ability)) {
            abort(403, 'Geen rechten voor deze actie.');
        }
    }

    public function mandateSave(Request $request, int $customerId, int $contractId)
    {
        $this->authorizeOrPermission('rides.update');

        $request->merge([
            'signed_at' => parse_admin_date($request->input('signed_at')),
        ]);

        $data = $request->validate([
            'account_holder'   => ['required', 'string', 'max:200'],
            'iban'             => ['required', 'string', 'max:64', new ValidIban],
            'bic'              => ['nullable', 'string', 'max:64'],
            'mandate_reference' => ['nullable', 'string', 'max:64'],
            'status'           => ['required', 'in:pending,active,revoked'],
            'signed_at'        => ['nullable', 'date'],
        ]);

        $data['iban'] = normalize_iban($data['iban']);

        $conn = $this->moduleConnection();
        // Upsert: maximaal 1 mandaat per contract in MVP.
        TransportPaymentMandate::on($conn)->updateOrCreate(
            ['transport_contract_id' => $contractId],
            $data
        );

        return redirect()->route('admin.taxi.transport_customers.contract_show', [$customerId, $contractId])
            ->with('success', 'SEPA-mandaat opgeslagen.');
    }
}
