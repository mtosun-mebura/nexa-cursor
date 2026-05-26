<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceSetting;
use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\JobMatch;
use App\Modules\NexaTaxi\Models\RidePayment;
use App\Services\InvoicePdfService;
use App\Services\InvoiceReminderService;
use App\Support\AdminReturnUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AdminInvoiceController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403, 'Alleen super-admin heeft toegang tot facturen.');
        }

        $with = ['company'];
        if ($this->matchesTableExists()) {
            $with[] = 'jobMatch';
        }
        $query = Invoice::with($with);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('company', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(25);
        $settings = InvoiceSetting::getSettings();
        
        // Status statistieken
        $invoiceStats = [
            'draft' => Invoice::where('status', 'draft')->count(),
            'in_progress' => Invoice::where('status', 'in_progress')->count(),
            'sent' => Invoice::where('status', 'sent')->count(),
            'paid' => Invoice::where('status', 'paid')->count(),
            'overdue' => Invoice::where('status', 'overdue')->count(),
            'cancelled' => Invoice::where('status', 'cancelled')->count(),
            'total' => Invoice::count(),
        ];

        return view('admin.invoices.index', compact('invoices', 'settings', 'invoiceStats'));
    }

    public function create(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $settings = InvoiceSetting::getSettings();
        $skillmatchingAvailable = $this->matchesTableExists();

        return view('admin.invoices.create', [
            'companies' => $companies,
            'jobMatches' => $this->loadJobMatchesForForm(),
            'settings' => $settings,
            'skillmatchingAvailable' => $skillmatchingAvailable,
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            ...$this->jobMatchValidationRules(),
            'amount' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'status' => 'required|in:draft,in_progress,sent,paid,overdue,cancelled',
            'currency' => 'required|string|size:3',
            'notes' => 'nullable|string',
            'is_partial' => 'boolean',
            'parent_invoice_number' => 'nullable|string',
            'partial_number' => 'nullable|integer|min:1',
        ]);

        if (! $this->matchesTableExists()) {
            $validated['job_match_id'] = null;
        }

        $settings = InvoiceSetting::getSettingsForCompany((int) $validated['company_id']);
        $taxRate = $validated['tax_rate'] ?? $settings->default_tax_rate;
        $amount = $validated['amount'];
        
        // Gebruik de waarden uit het formulier (die al berekend zijn door JavaScript)
        $taxAmount = $validated['tax_amount'];
        $totalAmount = $validated['total_amount'];
        
        // Verwijder display values als die bestaan
        unset($validated['tax_amount_display'], $validated['total_amount_display']);

        // Generate invoice number
        $invoiceNumber = $settings->generateInvoiceNumber(
            $validated['is_partial'] ?? false,
            $validated['parent_invoice_number'] ?? null,
            $validated['partial_number'] ?? null
        );

        $company = Company::findOrFail($validated['company_id']);
        
        // Parse dates from dd-MM-yyyy format
        $invoiceDate = now();
        if (isset($validated['invoice_date']) && !empty($validated['invoice_date'])) {
            try {
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $validated['invoice_date'])) {
                    $invoiceDate = \Carbon\Carbon::createFromFormat('d-m-Y', $validated['invoice_date']);
                } else {
                    $invoiceDate = \Carbon\Carbon::parse($validated['invoice_date']);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to parse invoice_date', ['input' => $validated['invoice_date'], 'error' => $e->getMessage()]);
            }
        }
        
        $dueDate = $invoiceDate->copy()->addDays($settings->payment_terms_days);
        if (isset($validated['due_date']) && !empty($validated['due_date'])) {
            try {
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $validated['due_date'])) {
                    $dueDate = \Carbon\Carbon::createFromFormat('d-m-Y', $validated['due_date']);
                } else {
                    $dueDate = \Carbon\Carbon::parse($validated['due_date']);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to parse due_date', ['input' => $validated['due_date'], 'error' => $e->getMessage()]);
            }
        }

        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'company_id' => $validated['company_id'],
            'job_match_id' => $validated['job_match_id'] ?? null,
            'amount' => $amount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'currency' => 'EUR',
            'status' => 'draft',
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'is_partial' => $validated['is_partial'] ?? false,
            'parent_invoice_number' => $validated['parent_invoice_number'] ?? null,
            'partial_number' => $validated['partial_number'] ?? null,
            'company_details' => [
                'name' => $company->name,
                'email' => $company->email,
                'address' => $company->address,
                'city' => $company->city,
                'postal_code' => $company->postal_code,
                'country' => $company->country,
                'vat_number' => $company->vat_number ?? null,
            ],
            'line_items' => [
                [
                    'description' => 'Match fee',
                    'quantity' => 1,
                    'price' => $amount,
                    'total' => $amount,
                ]
            ],
        ]);

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', 'Factuur aangemaakt');
    }

    public function show(Request $request, Invoice $invoice)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        $relations = ['company', 'reminders', 'payments'];
        if ($this->matchesTableExists()) {
            $relations[] = 'jobMatch.candidate';
            $relations[] = 'jobMatch.vacancy';
        }
        $invoice->load($relations);
        $settings = InvoiceSetting::getSettings();
        $defaultReminderEmail = app(InvoiceReminderService::class)->defaultRecipientEmail($invoice);
        $paymentTermsDays = InvoiceSetting::paymentTermsDaysForInvoice($invoice);
        $invoiceBackUrl = AdminReturnUrl::fromRequest(
            $request->query('return'),
            route('admin.invoices.index')
        );

        return view('admin.invoices.show', compact(
            'invoice',
            'settings',
            'defaultReminderEmail',
            'paymentTermsDays',
            'invoiceBackUrl'
        ));
    }

    public function edit(Invoice $invoice)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $settings = InvoiceSetting::getSettings();
        $skillmatchingAvailable = $this->matchesTableExists();

        return view('admin.invoices.edit', [
            'invoice' => $invoice,
            'companies' => $companies,
            'jobMatches' => $this->loadJobMatchesForForm(),
            'settings' => $settings,
            'skillmatchingAvailable' => $skillmatchingAvailable,
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            ...$this->jobMatchValidationRules(),
            'amount' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'status' => 'required|in:draft,in_progress,sent,paid,overdue,cancelled',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'is_partial' => 'boolean',
            'parent_invoice_number' => 'nullable|string|max:255',
            'partial_number' => 'nullable|integer|min:1',
            'line_item_description' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
        ]);

        if (! $this->matchesTableExists()) {
            $validated['job_match_id'] = null;
        }

        $settings = InvoiceSetting::getSettings();
        $taxRate = $validated['tax_rate'] ?? $settings->default_tax_rate;
        $amount = $validated['amount'];
        
        // Gebruik de waarden uit het formulier (die al berekend zijn door JavaScript)
        $taxAmount = $validated['tax_amount'];
        $totalAmount = $validated['total_amount'];
        
        // Verwijder display values als die bestaan
        unset($validated['tax_amount_display'], $validated['total_amount_display']);

        // Handle is_partial: if checkbox is checked, value is 1, otherwise use hidden input value (0)
        $validated['is_partial'] = $request->has('is_partial') && $request->input('is_partial') == '1' ? true : false;

        // Handle line items - update description
        $lineItemDescription = $request->input('line_item_description', 'Match fee');
        $validated['line_items'] = [
            [
                'description' => $lineItemDescription,
                'quantity' => 1,
                'price' => $validated['amount'],
                'total' => $validated['amount'],
            ]
        ];

        $company = Company::findOrFail($validated['company_id']);
        $validated['company_details'] = $company->toArray();
        
        // Parse dates from dd-MM-yyyy format
        if (isset($validated['invoice_date']) && !empty($validated['invoice_date'])) {
            try {
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $validated['invoice_date'])) {
                    $validated['invoice_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $validated['invoice_date'])->format('Y-m-d');
                } else {
                    $validated['invoice_date'] = \Carbon\Carbon::parse($validated['invoice_date'])->format('Y-m-d');
                }
            } catch (\Exception $e) {
                \Log::error('Failed to parse invoice_date', ['input' => $validated['invoice_date'], 'error' => $e->getMessage()]);
            }
        }
        
        if (isset($validated['due_date']) && !empty($validated['due_date'])) {
            try {
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $validated['due_date'])) {
                    $validated['due_date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $validated['due_date'])->format('Y-m-d');
                } else {
                    $validated['due_date'] = \Carbon\Carbon::parse($validated['due_date'])->format('Y-m-d');
                }
            } catch (\Exception $e) {
                \Log::error('Failed to parse due_date', ['input' => $validated['due_date'], 'error' => $e->getMessage()]);
            }
        }

        $invoice->update($validated);

        return redirect()->route('admin.invoices.show', $invoice->id)
            ->with('success', 'Factuur succesvol bijgewerkt.');
    }

    public function getMatchesForCompany(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        $companyId = $request->input('company_id');
        
        if (! $companyId || ! $this->matchesTableExists()) {
            return response()->json([]);
        }

        // Get matches for company - only include 'accepted' and 'hired' status
        $matches = JobMatch::whereIn('status', ['hired', 'accepted'])
            ->whereHas('vacancy', function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->with(['vacancy', 'candidate'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($matches->map(function($match) {
            $candidateName = 'N/A';
            if ($match->candidate) {
                $candidateName = trim(($match->candidate->first_name ?? '') . ' ' . ($match->candidate->last_name ?? ''));
                if (empty($candidateName)) {
                    $candidateName = 'N/A';
                }
            }
            
            $vacancyTitle = $match->vacancy->title ?? 'N/A';
            
            return [
                'id' => $match->id,
                'text' => $candidateName . ' (' . $vacancyTitle . ')'
            ];
        }));
    }

    public function sendReminder(Request $request, Invoice $invoice, InvoiceReminderService $reminders)
    {
        if (! auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        try {
            $reminder = $reminders->send($invoice, $validated['email']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', 'Aanmaning verstuurd naar '.$reminder->sent_to_email);
    }

    public function downloadPdf(Invoice $invoice, InvoicePdfService $pdfService): Response
    {
        if (! auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        try {
            $result = $pdfService->generateAndStore($invoice->fresh());
            $bytes = $result['bytes'];
        } catch (\Throwable $e) {
            abort(500, 'PDF kon niet worden gemaakt: '.$e->getMessage());
        }

        $filename = 'factuur-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoice->invoice_number).'.pdf';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function paymentLinks(Invoice $invoice)
    {
        if (! auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        $amountLabel = '€'.number_format((float) $invoice->total_amount, 2, ',', '.');

        $links = [
            [
                'key' => 'tikkie',
                'label' => 'Tikkie',
                'url' => $invoice->getPaymentLink('tikkie'),
                'hint' => 'Placeholder-link (nog geen Tikkie-koppeling)',
            ],
            [
                'key' => 'qr',
                'label' => 'QR-betaling',
                'url' => $invoice->getPaymentLink('qr'),
                'hint' => 'Generieke betaalpagina op dit platform',
            ],
            [
                'key' => 'bank',
                'label' => 'Bankoverschrijving',
                'url' => $invoice->getPaymentLink('bank'),
                'hint' => 'Instructiepagina bankbetaling',
            ],
        ];

        $mollie = $this->openMollieCheckoutForInvoice($invoice);
        if ($mollie) {
            array_unshift($links, [
                'key' => 'mollie',
                'label' => 'Mollie (openstaande ritbetaling)',
                'url' => $mollie,
                'hint' => 'Actieve betaallink van de taxirit',
            ]);
        }

        return response()->json([
            'invoice_number' => $invoice->invoice_number,
            'amount_label' => $amountLabel,
            'status' => $invoice->status,
            'is_paid' => $invoice->status === 'paid',
            'links' => $links,
        ]);
    }

    protected function openMollieCheckoutForInvoice(Invoice $invoice): ?string
    {
        if ($invoice->module !== Invoice::MODULE_TAXI || ! $invoice->module_reference_id) {
            return null;
        }

        if (! Schema::hasTable('ride_payments')) {
            return null;
        }

        $payment = RidePayment::query()
            ->where('ride_request_id', (int) $invoice->module_reference_id)
            ->where('status', RidePayment::STATUS_OPEN)
            ->whereNotNull('checkout_url')
            ->orderByDesc('id')
            ->first();

        return $payment?->checkout_url;
    }

    public function settings()
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403, 'Alleen super-admin heeft toegang tot factuurinstellingen.');
        }

        $settings = InvoiceSetting::getSettings();
        
        // Load companies with their locations for the dropdown
        $companies = Company::where('is_active', true)
            ->with(['locations' => function($query) {
                $query->where('is_active', true)
                      ->orderBy('is_main', 'desc')
                      ->orderBy('name');
            }])
            ->orderBy('name')
            ->get();
        
        // Prepare companies data for JavaScript
        $companiesData = $companies->map(function($company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'street' => $company->street ?? '',
                'house_number' => $company->house_number ?? '',
                'house_number_extension' => $company->house_number_extension ?? '',
                'postal_code' => $company->postal_code ?? '',
                'city' => $company->city ?? '',
                'country' => $company->country ?? '',
                'email' => $company->email ?? '',
                'phone' => $company->phone ?? '',
                'kvk_number' => $company->kvk_number ?? '',
                'locations' => $company->locations->map(function($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name,
                        'street' => $location->street ?? '',
                        'house_number' => $location->house_number ?? '',
                        'house_number_extension' => $location->house_number_extension ?? '',
                        'postal_code' => $location->postal_code ?? '',
                        'city' => $location->city ?? '',
                        'country' => $location->country ?? '',
                        'email' => $location->email ?? '',
                        'phone' => $location->phone ?? '',
                    ];
                })->toArray(),
            ];
        })->keyBy('id');

        return view('admin.invoices.settings', compact('settings', 'companies', 'companiesData'));
    }

    public function updateSettings(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'invoice_number_prefix' => 'required|string|max:10',
            'invoice_number_format' => 'required|string|max:100',
            'next_invoice_number' => 'required|integer|min:1',
            'current_year' => 'required|integer|min:2020|max:2100',
            'company_id' => 'nullable|exists:companies,id',
            'location_id' => 'nullable|exists:company_locations,id',
            'company_name' => 'nullable|string|max:255',
            'company_address' => 'nullable|string|max:255',
            'company_city' => 'nullable|string|max:100',
            'company_postal_code' => 'nullable|string|max:20',
            'company_country' => 'nullable|string|max:100',
            'company_vat_number' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|string|max:50',
            'bank_account' => 'nullable|string|max:50',
            'default_tax_rate' => 'required|numeric|min:0|max:100',
            'default_amount' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'required|integer|min:1|max:365',
            'invoice_footer_text' => 'nullable|string',
            'logo_path' => 'nullable|string|max:255',
        ]);

        $companyId = isset($validated['company_id']) ? (int) $validated['company_id'] : null;
        $settings = InvoiceSetting::getSettingsForCompany($companyId > 0 ? $companyId : null);
        $settings->update($validated);

        if ($companyId <= 0 && isset($validated['payment_terms_days'])) {
            InvoiceSetting::query()
                ->whereNotNull('company_id')
                ->where('payment_terms_days', 30)
                ->update(['payment_terms_days' => (int) $validated['payment_terms_days']]);
        }

        return redirect()->route('admin.invoices.settings')
            ->with('success', 'Factuurinstellingen bijgewerkt');
    }

    protected function matchesTableExists(): bool
    {
        return Schema::hasTable('matches');
    }

    /**
     * @return array<string, string>
     */
    protected function jobMatchValidationRules(): array
    {
        if ($this->matchesTableExists()) {
            return ['job_match_id' => 'nullable|exists:matches,id'];
        }

        return ['job_match_id' => 'nullable|integer'];
    }

    protected function loadJobMatchesForForm(): Collection
    {
        if (! $this->matchesTableExists()) {
            return collect();
        }

        return JobMatch::query()
            ->where('status', 'hired')
            ->with(['company', 'vacancy', 'candidate'])
            ->orderByDesc('created_at')
            ->get();
    }
}
