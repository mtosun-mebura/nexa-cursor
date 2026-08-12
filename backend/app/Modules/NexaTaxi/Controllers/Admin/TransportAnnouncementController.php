<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Controllers\Admin\Concerns\AuthorizesTaxiPermissions;
use App\Modules\NexaTaxi\Models\TransportAnnouncement;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use Illuminate\Http\Request;

class TransportAnnouncementController extends Controller
{
    use AuthorizesTaxiPermissions, TenantFilter, UsesModuleDatabase;

    public function store(Request $request, int $customerId)
    {
        $this->authorizeOrPermission('rides.update');

        $conn = $this->moduleConnection();
        app(TaxiContractvervoerSchemaService::class)->ensureContractPortalTables($conn);

        $customer = TransportCustomer::on($conn)->findOrFail($customerId);
        $this->assertCustomerInTenant($customer);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:2000'],
            'severity' => ['required', 'in:info,warning,critical'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        TransportAnnouncement::on($conn)->create([
            'company_id' => (int) $customer->company_id,
            'transport_customer_id' => (int) $customer->id,
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
            'severity' => $data['severity'],
            'starts_at' => $data['starts_at'] ?? now(),
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.taxi.transport_customers.show', $customer->id)
            ->with('success', 'Verstoring / melding geplaatst.');
    }

    public function update(Request $request, int $customerId, int $announcementId)
    {
        $this->authorizeOrPermission('rides.update');

        $conn = $this->moduleConnection();
        app(TaxiContractvervoerSchemaService::class)->ensureContractPortalTables($conn);

        $customer = TransportCustomer::on($conn)->findOrFail($customerId);
        $this->assertCustomerInTenant($customer);

        $announcement = TransportAnnouncement::on($conn)
            ->where('transport_customer_id', $customer->id)
            ->findOrFail($announcementId);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:2000'],
            'severity' => ['required', 'in:info,warning,critical'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $announcement->update([
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
            'severity' => $data['severity'],
            'starts_at' => $data['starts_at'] ?? $announcement->starts_at,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.taxi.transport_customers.show', $customer->id)
            ->with('success', 'Melding bijgewerkt.');
    }

    public function destroy(int $customerId, int $announcementId)
    {
        $this->authorizeOrPermission('rides.update');

        $conn = $this->moduleConnection();
        app(TaxiContractvervoerSchemaService::class)->ensureContractPortalTables($conn);

        $customer = TransportCustomer::on($conn)->findOrFail($customerId);
        $this->assertCustomerInTenant($customer);

        TransportAnnouncement::on($conn)
            ->where('transport_customer_id', $customer->id)
            ->whereKey($announcementId)
            ->delete();

        return redirect()
            ->route('admin.taxi.transport_customers.show', $customer->id)
            ->with('success', 'Melding verwijderd.');
    }

    private function assertCustomerInTenant(TransportCustomer $customer): void
    {
        $tenantId = $this->getTenantId();
        if ($tenantId && (int) $customer->company_id !== (int) $tenantId) {
            abort(404);
        }
    }
}
