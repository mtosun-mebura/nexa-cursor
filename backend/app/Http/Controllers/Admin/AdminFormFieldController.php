<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InfoRequestFormField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminFormFieldController extends Controller
{
    private function authorizeFormFields(): void
    {
        if (! auth()->user()->hasRole('super-admin') && ! auth()->user()->can('edit-email-templates')) {
            abort(403, 'Je hebt geen rechten om formulier velden te beheren.');
        }
    }

    public function index(Request $request): View
    {
        $this->authorizeFormFields();

        $query = InfoRequestFormField::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('label', 'like', '%' . $search . '%');
            });
        }

        // Filter: Verplicht
        if ($request->filled('required')) {
            if ($request->required === '1') {
                $query->where('is_required', true);
            } elseif ($request->required === '0') {
                $query->where('is_required', false);
            }
        }

        // Filter: Validatie
        if ($request->filled('validation')) {
            if ($request->validation === 'none') {
                $query->where(function ($q) {
                    $q->whereNull('validation_rule')->orWhere('validation_rule', '');
                });
            } else {
                $query->where('validation_rule', $request->validation);
            }
        }

        // Sort
        $sortField = $request->get('sort', 'sort_order');
        $sortDirection = $request->get('direction', 'asc');
        if (! in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }
        $allowedSort = ['name', 'label', 'sort_order', 'created_at'];
        if (in_array($sortField, $allowedSort)) {
            $query->orderBy($sortField, $sortDirection)->orderBy('id', 'asc');
        } else {
            $query->ordered();
        }

        $fields = $query->get();

        // Stats
        $stats = [
            'total' => InfoRequestFormField::count(),
            'required' => InfoRequestFormField::where('is_required', true)->count(),
            'optional' => InfoRequestFormField::where('is_required', false)->count(),
        ];

        return view('admin.email-templates.form-fields.index', compact('fields', 'stats'));
    }

    public function create(): View
    {
        $this->authorizeFormFields();
        $field = new InfoRequestFormField(['sort_order' => InfoRequestFormField::max('sort_order') + 10]);
        return view('admin.email-templates.form-fields.edit', ['field' => $field, 'isCreate' => true]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeFormFields();
        $request->validate([
            'name' => 'required|string|max:100|regex:/^[a-z_]+$/|unique:info_request_form_fields,name',
            'label' => 'required|string|max:255',
            'is_required' => 'boolean',
            'validation_rule' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'name.regex' => 'De slug mag alleen kleine letters en underscores bevatten (geen cijfers).',
        ]);

        InfoRequestFormField::create([
            'name' => $request->name,
            'label' => $request->label,
            'is_required' => $request->boolean('is_required'),
            'validation_rule' => $request->filled('validation_rule') ? $request->validation_rule : null,
            'sort_order' => (int) ($request->sort_order ?? 0),
        ]);

        return redirect()->route('admin.email-templates.form-fields.index')
            ->with('success', 'Formulierveld toegevoegd.');
    }

    public function edit(InfoRequestFormField $info_request_form_field): View
    {
        $this->authorizeFormFields();
        return view('admin.email-templates.form-fields.edit', ['field' => $info_request_form_field, 'isCreate' => false]);
    }

    public function update(Request $request, InfoRequestFormField $info_request_form_field): RedirectResponse
    {
        $this->authorizeFormFields();
        $request->validate([
            'name' => 'required|string|max:100|regex:/^[a-z_]+$/|unique:info_request_form_fields,name,' . $info_request_form_field->id,
            'label' => 'required|string|max:255',
            'is_required' => 'boolean',
            'validation_rule' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'name.regex' => 'De slug mag alleen kleine letters en underscores bevatten (geen cijfers).',
        ]);

        $info_request_form_field->update([
            'name' => $request->name,
            'label' => $request->label,
            'is_required' => $request->boolean('is_required'),
            'validation_rule' => $request->filled('validation_rule') ? $request->validation_rule : null,
            'sort_order' => (int) ($request->sort_order ?? 0),
        ]);

        return redirect()->route('admin.email-templates.form-fields.index')
            ->with('success', 'Formulierveld bijgewerkt.');
    }

    public function destroy(InfoRequestFormField $info_request_form_field): RedirectResponse
    {
        $this->authorizeFormFields();
        $info_request_form_field->delete();
        return redirect()->route('admin.email-templates.form-fields.index')
            ->with('success', 'Formulierveld verwijderd.');
    }
}
