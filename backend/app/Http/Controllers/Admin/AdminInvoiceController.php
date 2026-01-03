<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceSetting;
use App\Models\PaymentReminder;
use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\JobMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AdminInvoiceController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403, 'Alleen super-admin heeft toegang tot facturen.');
        }

        $query = Invoice::with(['company', 'jobMatch']);

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
        $jobMatches = JobMatch::where('status', 'hired')
            ->with(['company', 'vacancy', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();
        $settings = InvoiceSetting::getSettings();

        return view('admin.invoices.create', compact('companies', 'jobMatches', 'settings'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'job_match_id' => 'nullable|exists:matches,id',
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

        $settings = InvoiceSetting::getSettings();
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

    public function show(Invoice $invoice)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        $invoice->load(['company', 'jobMatch.candidate', 'jobMatch.vacancy', 'reminders', 'payments']);
        $settings = InvoiceSetting::getSettings();

        return view('admin.invoices.show', compact('invoice', 'settings'));
    }

    public function edit(Invoice $invoice)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $jobMatches = JobMatch::where('status', 'hired')
            ->with(['company', 'vacancy', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();
        $settings = InvoiceSetting::getSettings();

        return view('admin.invoices.edit', compact('invoice', 'companies', 'jobMatches', 'settings'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'job_match_id' => 'nullable|exists:matches,id',
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
        ]);

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
        
        if (!$companyId) {
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

    public function sendReminder(Invoice $invoice)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        $company = $invoice->company;
        $email = $company->email ?? $company->contact_email ?? null;

        if (!$email) {
            return back()->with('error', 'Bedrijf heeft geen e-mailadres');
        }

        // Determine reminder type based on existing reminders
        $existingReminders = $invoice->reminders()->count();
        $reminderType = 'first';
        if ($existingReminders == 1) {
            $reminderType = 'second';
        } elseif ($existingReminders >= 2) {
            $reminderType = 'final';
        }

        // Create reminder record
        $reminder = PaymentReminder::create([
            'invoice_id' => $invoice->id,
            'company_id' => $company->id,
            'reminder_type' => $reminderType,
            'sent_to_email' => $email,
            'message' => 'Aanmaning voor factuur ' . $invoice->invoice_number,
            'sent_at' => now(),
        ]);

        // Send email (would use actual email template in production)
        // Mail::to($email)->send(new PaymentReminderMail($invoice, $reminder));

        return back()->with('success', 'Aanmaning verstuurd naar ' . $email);
    }

    public function generatePdf(Invoice $invoice)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        // PDF generation would be implemented here
        // For now, return a placeholder
        return response()->json([
            'message' => 'PDF generatie wordt geïmplementeerd',
            'invoice_number' => $invoice->invoice_number,
        ]);
    }

    public function paymentLinks(Invoice $invoice)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403);
        }

        $links = [
            'tikkie' => $invoice->getPaymentLink('tikkie'),
            'qr' => $invoice->getPaymentLink('qr'),
            'bank' => $invoice->getPaymentLink('bank'),
        ];

        return response()->json($links);
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

        $settings = InvoiceSetting::getSettings();
        $settings->update($validated);

        return redirect()->route('admin.invoices.settings')
            ->with('success', 'Factuurinstellingen bijgewerkt');
    }
}
