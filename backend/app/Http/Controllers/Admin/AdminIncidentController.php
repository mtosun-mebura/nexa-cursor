<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Incident;
use App\Models\IncidentComment;
use App\Services\IncidentNotifier;
use App\Support\IncidentCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminIncidentController extends Controller
{
    public function __construct(protected IncidentNotifier $notifier) {}

    public function index()
    {
        $user = auth()->user();
        abort_unless($this->canAccessIncidents($user), 403, 'Je hebt geen toegang tot incidenten.');

        return view('admin.incidents.index', [
            'bootstrap' => $this->bootstrapPayload($user),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $user = auth()->user();
        abort_unless($this->canAccessIncidents($user), 403);

        $query = Incident::query()
            ->with(['company:id,name', 'reporter:id,first_name,last_name,email'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! $user->isSuperAdmin()) {
            $query->where('company_id', $user->company_id);
        } elseif ($request->filled('company_id')) {
            $query->where('company_id', (int) $request->input('company_id'));
        }

        $archived = $request->boolean('archived');
        if ($archived) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        if ($request->filled('status') && in_array($request->input('status'), IncidentCatalog::statuses(), true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority') && in_array($request->input('priority'), IncidentCatalog::priorities(), true)) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('reference', 'like', '%'.$search.'%');
            });
        }

        $perPage = min(100, max(5, (int) $request->integer('per_page', 10)));
        $page = max(1, (int) $request->integer('page', 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $statsQuery = Incident::query()->whereNull('archived_at');
        if (! $user->isSuperAdmin()) {
            $statsQuery->where('company_id', $user->company_id);
        }
        $archivedQuery = Incident::query()->whereNotNull('archived_at');
        if (! $user->isSuperAdmin()) {
            $archivedQuery->where('company_id', $user->company_id);
        }

        return response()->json([
            'incidents' => $paginator->getCollection()->map(fn (Incident $incident) => $this->serializeIncident($incident, false))->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem() ?? 0,
                'to' => $paginator->lastItem() ?? 0,
            ],
            'stats' => [
                'open' => (clone $statsQuery)->where('status', IncidentCatalog::STATUS_OPEN)->count(),
                'in_progress' => (clone $statsQuery)->where('status', IncidentCatalog::STATUS_IN_PROGRESS)->count(),
                'resolved' => (clone $statsQuery)->where('status', IncidentCatalog::STATUS_RESOLVED)->count(),
                'total' => (clone $statsQuery)->count(),
                'archived' => $archivedQuery->count(),
            ],
        ]);
    }

    public function show(Incident $incident): JsonResponse
    {
        $this->assertCanView($incident);
        $incident->load(['company:id,name', 'reporter:id,first_name,last_name,email', 'resolvedBy:id,first_name,last_name,email']);
        if (auth()->user()?->isSuperAdmin()) {
            $incident->load(['comments.user:id,first_name,last_name,email']);
        }

        return response()->json([
            'incident' => $this->serializeIncident($incident, true),
        ]);
    }

    public function screenshot(Incident $incident, int $index)
    {
        $this->assertCanView($incident);

        $shot = ($incident->screenshots ?? [])[$index] ?? null;
        abort_unless(is_array($shot), 404);

        $path = (string) ($shot['path'] ?? '');
        abort_unless($path !== '' && Storage::disk('public')->exists($path), 404);

        $filename = (string) ($shot['name'] ?? basename($path));
        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return Storage::disk('public')->response($path, $filename, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        abort_unless($this->canCreateIncident($user), 403, 'Je hebt geen toegang om een incident in te dienen.');

        $validated = $request->validate([
            'kind' => ['required', Rule::in(IncidentCatalog::kinds())],
            'title' => ['required', 'string', 'max:160'],
            'page_url' => ['nullable', 'string', 'max:2048'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(IncidentCatalog::priorities())],
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')],
            'screenshots' => ['nullable', 'array', 'max:5'],
            'screenshots.*' => ['file', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif'],
        ]);

        $companyId = $user->isSuperAdmin()
            ? ($validated['company_id'] ?? null)
            : $user->company_id;

        abort_unless($user->isSuperAdmin() || (int) $companyId > 0, 403, 'Dit account is niet aan een bedrijf gekoppeld.');

        $incident = new Incident([
            'company_id' => $companyId,
            'reporter_user_id' => $user->id,
            'reference' => Incident::nextReference(),
            'kind' => $validated['kind'],
            'title' => trim($validated['title']),
            'page_url' => $this->normalizePageUrl($validated['page_url'] ?? null),
            'description' => trim($validated['description']),
            'priority' => $validated['priority'],
            'status' => IncidentCatalog::STATUS_OPEN,
        ]);
        $incident->save();

        $files = $this->uploadedScreenshotFiles($request);
        $screenshots = $this->storeScreenshots($incident, $files);
        if ($screenshots !== []) {
            $incident->update(['screenshots' => $screenshots]);
        }

        $this->notifier->notifySuperAdminsOfNewIncident($incident->fresh(['company', 'reporter']));

        return response()->json([
            'success' => true,
            'message' => 'Je incident is verstuurd. We nemen het zo snel mogelijk op.',
            'incident' => $this->serializeIncident($incident->fresh(['company', 'reporter']), true),
        ], 201);
    }

    public function archive(Request $request): JsonResponse
    {
        $user = auth()->user();
        abort_unless($this->canAccessIncidents($user), 403);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer'],
            'archived' => ['required', 'boolean'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));
        $query = Incident::query()->whereIn('id', $ids);
        if (! $user->isSuperAdmin()) {
            $query->where('company_id', $user->company_id);
        }

        $incidents = $query->get();
        abort_unless($incidents->count() === count($ids), 403, 'Je kunt niet alle geselecteerde incidenten wijzigen.');

        $archivedAt = $validated['archived'] ? now() : null;
        foreach ($incidents as $incident) {
            $incident->archived_at = $archivedAt;
            $incident->save();
        }

        return response()->json([
            'success' => true,
            'count' => $incidents->count(),
        ]);
    }

    public function update(Request $request, Incident $incident): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin(), 403, 'Alleen een super-admin kan incidenten afhandelen.');

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(IncidentCatalog::statuses())],
            'resolution_note' => ['sometimes', 'nullable', 'string', 'max:4000'],
        ]);

        if (! array_key_exists('status', $validated) && ! array_key_exists('resolution_note', $validated)) {
            throw ValidationException::withMessages([
                'status' => 'Geen wijziging ontvangen.',
            ]);
        }

        $wasHandled = $incident->isHandled();

        if (array_key_exists('status', $validated)) {
            $newStatus = $validated['status'];
            $incident->status = $newStatus;

            if (IncidentCatalog::isHandled($newStatus)) {
                $incident->resolved_by_user_id = $user->id;
                $incident->resolved_at = $incident->resolved_at ?? now();
            } else {
                $incident->resolved_by_user_id = null;
                $incident->resolved_at = null;
            }
        }

        if (array_key_exists('resolution_note', $validated)) {
            $note = trim((string) $validated['resolution_note']);
            $incident->resolution_note = $note !== '' ? $note : null;
        }

        $incident->save();

        if (! $wasHandled && $incident->isHandled()) {
            $this->notifier->notifyReporterIncidentHandled($incident->fresh(['reporter', 'resolvedBy']));
        }

        $incident->load(['resolvedBy:id,first_name,last_name,email']);

        $patch = $this->serializeStatusPatch($incident);
        if (array_key_exists('resolution_note', $validated)) {
            $patch['resolution_note'] = $incident->resolution_note;
        }

        return response()->json([
            'success' => true,
            'message' => array_key_exists('status', $validated) && $incident->isHandled() && ! $wasHandled
                ? 'Incident afgehandeld. De klant is op de hoogte gebracht.'
                : 'Opgeslagen.',
            'patch' => $patch,
        ]);
    }

    public function storeComment(Request $request, Incident $incident): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin(), 403, 'Alleen een super-admin kan intern commentaar plaatsen.');
        $this->assertCanView($incident);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $comment = $incident->comments()->create([
            'user_id' => $user->id,
            'body' => trim($validated['body']),
        ]);
        $comment->setRelation('user', $user);

        return response()->json([
            'success' => true,
            'comment' => $this->serializeComment($comment),
        ], 201);
    }

    public function destroyComment(Incident $incident, IncidentComment $comment): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin(), 403, 'Alleen een super-admin kan intern commentaar verwijderen.');
        $this->assertCanView($incident);
        abort_unless((int) $comment->incident_id === (int) $incident->id, 404);

        $comment->delete();

        return response()->json(['success' => true]);
    }

    private function canAccessIncidents($user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->canAccessAdminPanel() && (int) $user->company_id > 0;
    }

    private function canCreateIncident($user): bool
    {
        return $this->canAccessIncidents($user);
    }

    private function assertCanView(Incident $incident): void
    {
        $user = auth()->user();
        abort_unless($this->canAccessIncidents($user), 403);

        if ($user->isSuperAdmin()) {
            return;
        }

        abort_unless((int) $incident->company_id === (int) $user->company_id, 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function bootstrapPayload($user): array
    {
        return [
            'is_super_admin' => $user->isSuperAdmin(),
            'can_create' => $this->canCreateIncident($user),
            'user_name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: $user->email,
            'open_id' => request()->integer('open') ?: null,
            'csrf' => csrf_token(),
            'routes' => [
                'list' => route('admin.incidents.list'),
                'store' => route('admin.incidents.store'),
                'archive' => route('admin.incidents.archive'),
                'show' => url('/admin/incidents'),
                'update' => url('/admin/incidents'),
                'comments' => url('/admin/incidents'),
            ],
            'kinds' => IncidentCatalog::kindOptions(),
            'statuses' => IncidentCatalog::statusOptions(),
            'priorities' => IncidentCatalog::priorityOptions(),
            'companies' => $user->isSuperAdmin()
                ? Company::query()->orderBy('name')->get(['id', 'name'])->map(fn (Company $company) => [
                    'id' => $company->id,
                    'name' => $company->name,
                ])->values()->all()
                : [],
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    private function uploadedScreenshotFiles(Request $request): array
    {
        $files = $request->file('screenshots', []);
        if ($files instanceof UploadedFile) {
            $files = [$files];
        }
        if (! is_array($files) || $files === []) {
            $nested = $request->allFiles()['screenshots'] ?? [];
            $files = $nested instanceof UploadedFile ? [$nested] : (is_array($nested) ? $nested : []);
        }

        return array_values(array_filter(
            $files,
            static fn ($file) => $file instanceof UploadedFile
        ));
    }

    /**
     * @param  array<int, UploadedFile|null>  $files
     * @return list<array{path: string, url: string, name: string, size: int}>
     */
    private function storeScreenshots(Incident $incident, array $files): array
    {
        $stored = [];
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $path = $file->store('incidents/'.$incident->id, 'public');
            if (! $path) {
                continue;
            }
            $stored[] = [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'name' => $file->getClientOriginalName(),
                'size' => (int) $file->getSize(),
            ];
        }

        return $stored;
    }

    private function normalizePageUrl(?string $value): ?string
    {
        $url = trim((string) $value);
        if ($url === '') {
            return null;
        }

        if (! preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
            $url = 'https://'.$url;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw ValidationException::withMessages([
                'page_url' => 'Vul een geldige URL in, of laat het veld leeg.',
            ]);
        }

        return $url;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeIncident(Incident $incident, bool $full): array
    {
        $screenshots = [];
        foreach ($incident->screenshots ?? [] as $index => $shot) {
            if (! is_array($shot)) {
                continue;
            }
            $path = (string) ($shot['path'] ?? '');
            if ($path === '' && empty($shot['url'])) {
                continue;
            }
            $screenshots[] = [
                'path' => $path,
                'url' => $path !== ''
                    ? route('admin.incidents.screenshot', ['incident' => $incident, 'index' => (int) $index])
                    : ($shot['url'] ?? null),
                'name' => $shot['name'] ?? 'screenshot',
                'size' => (int) ($shot['size'] ?? 0),
            ];
        }

        $payload = [
            'id' => $incident->id,
            'reference' => $incident->reference,
            'kind' => $incident->kind,
            'kind_label' => IncidentCatalog::kindLabel((string) $incident->kind),
            'title' => $incident->title,
            'priority' => $incident->priority,
            'priority_label' => IncidentCatalog::priorityLabel((string) $incident->priority),
            'status' => $incident->status,
            'status_label' => IncidentCatalog::statusLabel((string) $incident->status),
            'is_handled' => $incident->isHandled(),
            'company' => $incident->company ? [
                'id' => $incident->company->id,
                'name' => $incident->company->name,
            ] : null,
            'reporter' => $incident->reporter ? [
                'id' => $incident->reporter->id,
                'name' => trim(($incident->reporter->first_name ?? '').' '.($incident->reporter->last_name ?? '')) ?: $incident->reporter->email,
                'email' => $incident->reporter->email,
            ] : null,
            'screenshot_count' => count($screenshots),
            'created_at' => $incident->created_at?->toIso8601String(),
            'created_at_human' => $incident->created_at?->diffForHumans(),
            'created_at_formatted' => $incident->created_at?->format('d-m-Y H:i'),
            'is_archived' => $incident->archived_at !== null,
        ];

        if (! $full) {
            return $payload;
        }

        $payload['description'] = $incident->description;
        $payload['page_url'] = $incident->page_url;
        $payload['screenshots'] = $screenshots;
        $payload['resolution_note'] = $incident->resolution_note;
        $payload['resolved_at'] = $incident->resolved_at?->toIso8601String();
        $payload['resolved_at_formatted'] = $incident->resolved_at?->format('d-m-Y H:i');
        $payload['resolved_by'] = $this->serializePerson($incident->resolvedBy);

        if (auth()->user()?->isSuperAdmin()) {
            $payload['comments'] = $incident->comments
                ->map(fn ($comment) => $this->serializeComment($comment))
                ->values()
                ->all();
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeStatusPatch(Incident $incident): array
    {
        return [
            'status' => $incident->status,
            'status_label' => IncidentCatalog::statusLabel((string) $incident->status),
            'is_handled' => $incident->isHandled(),
            'resolved_at' => $incident->resolved_at?->toIso8601String(),
            'resolved_at_formatted' => $incident->resolved_at?->format('d-m-Y H:i'),
            'resolved_by' => $this->serializePerson($incident->resolvedBy),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeComment($comment): array
    {
        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'user' => $this->serializePerson($comment->user),
            'created_at_formatted' => $comment->created_at?->format('d-m-Y H:i'),
        ];
    }

    /**
     * @return array{id: int, name: string, email?: string}|null
     */
    private function serializePerson($user): ?array
    {
        if (! $user) {
            return null;
        }

        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return [
            'id' => $user->id,
            'name' => $name !== '' ? $name : (string) $user->email,
            'email' => $user->email,
        ];
    }
}
