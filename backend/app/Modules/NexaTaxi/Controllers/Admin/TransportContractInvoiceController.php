<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Modules\NexaTaxi\Controllers\Admin\Concerns\AuthorizesTaxiPermissions;
use App\Modules\NexaTaxi\Models\TransportContract;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Services\ContractInvoiceService;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class TransportContractInvoiceController extends Controller
{
    use AuthorizesTaxiPermissions, TenantFilter, UsesModuleDatabase;

    public function generate(Request $request, int $customerId, int $contractId, ContractInvoiceService $invoices)
    {
        $this->authorizeOrPermission('rides.update');

        $data = $request->validate([
            'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'send_email' => ['boolean'],
        ]);

        $conn = $this->moduleConnection();
        $contract = $this->findContract($conn, $customerId, $contractId);

        try {
            $invoice = $invoices->generateMonthlyInvoice($conn, $contract, $data['period'], [
                'send_email' => $request->boolean('send_email'),
                'status' => $request->boolean('send_email') ? 'sent' : 'draft',
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('admin.taxi.transport_customers.contract_show', [$customerId, $contractId])
            ->with('success', 'Factuur '.$invoice->invoice_number.' aangemaakt.');
    }

    public function send(int $customerId, int $contractId, int $invoiceId, ContractInvoiceService $invoices)
    {
        $this->authorizeOrPermission('rides.update');

        $invoice = $this->findContractInvoice($customerId, $contractId, $invoiceId);

        try {
            $invoices->sendInvoiceToCustomer($invoice);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Factuur verzonden naar '.$invoice->customer_email.'.');
    }

    public function markPaid(int $customerId, int $contractId, int $invoiceId, ContractInvoiceService $invoices)
    {
        $this->authorizeOrPermission('rides.update');

        $invoice = $this->findContractInvoice($customerId, $contractId, $invoiceId);
        $invoices->markInvoicePaid($invoice);

        return back()->with('success', 'Factuur gemarkeerd als betaald.');
    }

    public function destroy(int $customerId, int $contractId, int $invoiceId, ContractInvoiceService $invoices)
    {
        $this->authorizeOrPermission('rides.update');

        $invoice = $this->findContractInvoice($customerId, $contractId, $invoiceId);

        try {
            $invoices->deleteDraftInvoice($invoice);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Conceptfactuur verwijderd. Je kunt nu opnieuw genereren met de bijgewerkte prijs.');
    }

    public function downloadPdf(
        int $customerId,
        int $contractId,
        int $invoiceId,
        InvoicePdfService $pdfService,
    ): Response {
        $this->authorizeOrPermission('rides.view');

        $invoice = $this->findContractInvoice($customerId, $contractId, $invoiceId);
        $bytes = $pdfService->renderPdfBytes($invoice);

        $filename = 'factuur-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoice->invoice_number).'.pdf';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function exportCsv(int $customerId, int $contractId, ContractInvoiceService $invoiceService)
    {
        $this->authorizeOrPermission('rides.view');

        $this->findContract($this->moduleConnection(), $customerId, $contractId);
        $rows = $invoiceService->invoicesForContract($contractId);

        $filename = 'contractfacturen-'.$contractId.'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = static function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Factuurnummer',
                'Periode',
                'Datum',
                'Vervaldatum',
                'Bedrag excl.',
                'BTW',
                'Totaal',
                'Status',
                'Betaald op',
            ], ';');

            foreach ($rows as $invoice) {
                fputcsv($handle, [
                    $invoice->invoice_number,
                    $invoice->billing_period,
                    $invoice->invoice_date?->format('Y-m-d'),
                    $invoice->due_date?->format('Y-m-d'),
                    number_format((float) $invoice->amount, 2, '.', ''),
                    number_format((float) $invoice->tax_amount, 2, '.', ''),
                    number_format((float) $invoice->total_amount, 2, '.', ''),
                    $invoice->status,
                    $invoice->paid_date?->format('Y-m-d'),
                ], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function findContract(string $conn, int $customerId, int $contractId): TransportContract
    {
        TransportCustomer::on($conn)->findOrFail($customerId);

        return TransportContract::on($conn)
            ->where('transport_customer_id', $customerId)
            ->findOrFail($contractId);
    }

    protected function findContractInvoice(int $customerId, int $contractId, int $invoiceId): Invoice
    {
        $this->findContract($this->moduleConnection(), $customerId, $contractId);

        $invoice = Invoice::query()->findOrFail($invoiceId);
        if (
            $invoice->module !== Invoice::MODULE_TAXI_CONTRACT
            || (int) $invoice->module_reference_id !== $contractId
        ) {
            abort(404);
        }

        return $invoice;
    }
}
