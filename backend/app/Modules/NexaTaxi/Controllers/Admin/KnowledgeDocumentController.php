<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Models\KnowledgeDocument;
use App\Modules\NexaTaxi\Services\TaxiKnowledgeWebsiteImportService;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class KnowledgeDocumentController extends Controller
{
    use TenantFilter, UsesModuleDatabase;

    public function index()
    {
        $this->authorizeOrPermission('ai_chatbot.view');

        $conn = $this->moduleConnection();
        $this->ensureKnowledgeTableExists($conn);

        $documents = KnowledgeDocument::on($conn)
            ->orderByDesc('created_at')
            ->orderBy('title')
            ->get();
        $categoryLabels = KnowledgeDocument::categoryLabels();

        return view('taxi::admin.knowledge_documents.index', compact('documents', 'categoryLabels'));
    }

    public function show(KnowledgeDocument $knowledge_document)
    {
        $this->authorizeOrPermission('ai_chatbot.view');

        $categoryLabels = KnowledgeDocument::categoryLabels();

        return view('taxi::admin.knowledge_documents.show', [
            'document' => $knowledge_document,
            'categoryLabels' => $categoryLabels,
        ]);
    }

    public function create()
    {
        $this->authorizeOrPermission('ai_chatbot.create');

        $categoryLabels = KnowledgeDocument::categoryLabels();

        return view('taxi::admin.knowledge_documents.create', compact('categoryLabels'));
    }

    public function store(Request $request)
    {
        $this->authorizeOrPermission('ai_chatbot.create');

        $conn = $this->moduleConnection();
        $this->ensureKnowledgeTableExists($conn);

        $validated = $this->validateDocument($request);
        KnowledgeDocument::on($conn)->create($validated);

        return redirect()
            ->route('admin.taxi.knowledge_documents.index')
            ->with('success', 'Kennisdocument is toegevoegd.');
    }

    public function edit(KnowledgeDocument $knowledge_document)
    {
        $this->authorizeOrPermission('ai_chatbot.update');

        $categoryLabels = KnowledgeDocument::categoryLabels();

        return view('taxi::admin.knowledge_documents.edit', [
            'document' => $knowledge_document,
            'categoryLabels' => $categoryLabels,
        ]);
    }

    public function update(Request $request, KnowledgeDocument $knowledge_document)
    {
        $this->authorizeOrPermission('ai_chatbot.update');

        $validated = $this->validateDocument($request, $knowledge_document);
        $knowledge_document->update($validated);

        return redirect()
            ->route('admin.taxi.knowledge_documents.show', $knowledge_document)
            ->with('success', 'Kennisdocument is bijgewerkt.');
    }

    public function destroy(KnowledgeDocument $knowledge_document)
    {
        $this->authorizeOrPermission('ai_chatbot.delete');

        $knowledge_document->delete();

        return redirect()
            ->route('admin.taxi.knowledge_documents.index')
            ->with('success', 'Kennisdocument is verwijderd.');
    }

    public function generateFromWebsite(Request $request, TaxiKnowledgeWebsiteImportService $importService)
    {
        $this->authorizeOrPermission('ai_chatbot.create');

        $conn = $this->moduleConnection();
        $this->ensureKnowledgeTableExists($conn);

        $companyId = $this->resolveCompanyIdForAction();
        if ($companyId === null) {
            return redirect()
                ->route('admin.taxi.knowledge_documents.index')
                ->with('error', 'Selecteer eerst een tenant in de tenant-kiezer bovenaan.');
        }

        $stats = $importService->importForCompany($companyId, $conn);

        return redirect()
            ->route('admin.taxi.knowledge_documents.index')
            ->with('success', sprintf(
                'Website-inhoud geïmporteerd (diensten, contact, boekingsformulier en algemene vragen): %d toegevoegd, %d bijgewerkt, %d overgeslagen.',
                $stats['created'],
                $stats['updated'],
                $stats['skipped']
            ));
    }

    /**
     * @return array{title: string, content: string, category: string}
     */
    private function validateDocument(Request $request, ?KnowledgeDocument $existing = null): array
    {
        $categories = array_keys(KnowledgeDocument::categoryLabels());

        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'category' => ['required', 'string', Rule::in($categories)],
            'content' => 'required|string|max:16000',
        ], [
            'title.required' => 'Vul een titel in.',
            'category.required' => 'Kies een categorie.',
            'content.required' => 'Vul de inhoud in.',
            'content.max' => 'De inhoud is te lang.',
        ]);

        if ($this->plainContentLength($validated['content']) < 20) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'content' => 'De inhoud moet minimaal 20 tekens bevatten.',
            ]);
        }

        return $validated;
    }

    private function plainContentLength(string $html): int
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return mb_strlen(trim($text));
    }

    private function resolveCompanyIdForAction(): ?int
    {
        $user = auth()->user();
        if ($user->hasRole('super-admin')) {
            $tenantId = session('selected_tenant');

            return $tenantId ? (int) $tenantId : null;
        }

        return $user->company_id ? (int) $user->company_id : null;
    }

    private function ensureKnowledgeTableExists(string $connection): void
    {
        if (! Schema::connection($connection)->hasTable('knowledge_documents')) {
            abort(503, 'AI-kennistabellen ontbreken. Voer uit: php artisan modules:migrate taxi');
        }
    }

    private function authorizeOrPermission(string $ability): void
    {
        if (auth()->user()->hasRole('super-admin')) {
            return;
        }
        if (auth()->user()->can($ability)) {
            return;
        }

        $fallbacks = match ($ability) {
            'ai_chatbot.view' => ['rides.view', 'vehicles.view'],
            'ai_chatbot.create', 'ai_chatbot.update' => ['rides.update', 'vehicles.update'],
            'ai_chatbot.delete' => ['rides.delete', 'vehicles.delete'],
            default => [],
        };

        foreach ($fallbacks as $fallback) {
            if (auth()->user()->can($fallback)) {
                return;
            }
        }

        abort(403, 'Geen rechten voor deze actie.');
    }
}
