@php
    $debugPanelEnabled = false;
    if (auth()->check() && auth()->user()->hasRole('super-admin')) {
        try {
            $envService = app(\App\Services\EnvService::class);
            $debugPanelEnabled = $envService->get('ADMIN_DEBUG_PANEL_ENABLED', 'false') === 'true';
        } catch (\Exception $e) {
            // Fallback to config if env service fails
            $debugPanelEnabled = config('app.debug', false);
        }
    }
@endphp

@if($debugPanelEnabled)
<div id="kt-debug-panel" class="fixed bottom-4 end-4 z-[9999] hidden">
    <div class="kt-card w-[400px] max-h-[600px] overflow-y-auto shadow-2xl border border-border">
        <div class="kt-card-header bg-primary">
            <div class="flex items-center justify-between">
                <h3 class="kt-card-title text-white">🔍 Rechten Debug</h3>
                <button onclick="toggleDebugPanel()" class="kt-btn kt-btn-icon kt-btn-sm text-white hover:bg-white/20">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
        </div>
        <div class="kt-card-body p-5 space-y-4">
            <!-- Current User Info -->
            <div>
                <h4 class="font-semibold text-sm mb-2 text-foreground">Ingelogde Gebruiker</h4>
                <div class="text-xs space-y-1 text-muted-foreground">
                    <div><strong>Naam:</strong> {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</div>
                    <div><strong>Email:</strong> {{ auth()->user()->email }}</div>
                    <div><strong>ID:</strong> {{ auth()->user()->id }}</div>
                </div>
            </div>

            <!-- User Roles -->
            <div>
                <h4 class="font-semibold text-sm mb-2 text-foreground">Rollen</h4>
                <div class="space-y-1">
                    @forelse(auth()->user()->roles as $role)
                        <div class="flex items-center justify-between p-2 bg-accent/30 rounded">
                            <span class="text-xs font-medium text-foreground">{{ $role->name }}</span>
                            <span class="kt-badge kt-badge-sm">{{ $role->guard_name }}</span>
                        </div>
                    @empty
                        <div class="text-xs text-muted-foreground">Geen rollen toegewezen</div>
                    @endforelse
                </div>
            </div>

            <!-- User Permissions -->
            <div>
                <h4 class="font-semibold text-sm mb-2 text-foreground">Rechten ({{ auth()->user()->getAllPermissions()->count() }})</h4>
                <div class="max-h-[200px] overflow-y-auto space-y-1">
                    @forelse(auth()->user()->getAllPermissions()->take(20) as $permission)
                        <div class="flex items-center justify-between p-1.5 bg-accent/20 rounded text-xs">
                            <span class="text-foreground">{{ $permission->name }}</span>
                            <span class="kt-badge kt-badge-xs">{{ $permission->guard_name }}</span>
                        </div>
                    @empty
                        <div class="text-xs text-muted-foreground">Geen rechten toegewezen</div>
                    @endforelse
                    @if(auth()->user()->getAllPermissions()->count() > 20)
                        <div class="text-xs text-muted-foreground italic">... en {{ auth()->user()->getAllPermissions()->count() - 20 }} meer</div>
                    @endif
                </div>
            </div>

            <!-- Current Route Info -->
            <div>
                <h4 class="font-semibold text-sm mb-2 text-foreground">Huidige Route</h4>
                <div class="text-xs space-y-1 text-muted-foreground">
                    <div><strong>Route:</strong> {{ request()->route()->getName() ?? 'N/A' }}</div>
                    <div><strong>URL:</strong> {{ request()->path() }}</div>
                    <div><strong>Method:</strong> {{ request()->method() }}</div>
                </div>
            </div>

            <!-- Required Permissions Check -->
            <div>
                <h4 class="font-semibold text-sm mb-2 text-foreground">Rechten Check</h4>
                <div class="space-y-1">
                    @php
                        $commonPermissions = [
                            'view-dashboard' => 'Dashboard bekijken',
                            'view-users' => 'Gebruikers bekijken',
                            'view-companies' => 'Bedrijven bekijken',
                            'view-vacancies' => 'Vacatures bekijken',
                            'view-matches' => 'Matches bekijken',
                            'view-interviews' => 'Interviews bekijken',
                            'view-notifications' => 'Notificaties bekijken',
                            'view-agenda' => 'Agenda bekijken',
                            'view-permissions' => 'Rechten bekijken',
                            'view-roles' => 'Rollen bekijken',
                        ];
                    @endphp
                    @foreach($commonPermissions as $permission => $label)
                        <div class="flex items-center justify-between p-1.5 rounded text-xs {{ auth()->user()->can($permission) ? 'bg-success/20' : 'bg-destructive/20' }}">
                            <span class="text-foreground">{{ $label }}</span>
                            @if(auth()->user()->can($permission))
                                <span class="kt-badge kt-badge-sm kt-badge-success">✓</span>
                            @else
                                <span class="kt-badge kt-badge-sm kt-badge-danger">✗</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Middleware Info -->
            <div>
                <h4 class="font-semibold text-sm mb-2 text-foreground">Middleware</h4>
                <div class="text-xs space-y-1 text-muted-foreground">
                    @if(request()->route())
                        @foreach(request()->route()->middleware() as $middleware)
                            <div class="p-1 bg-accent/20 rounded">{{ $middleware }}</div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Debug Toggle Button - Hidden when indicators are active, can be shown via console -->
<button onclick="toggleDebugPanel()" 
        class="fixed bottom-4 end-4 z-[9998] kt-btn kt-btn-primary kt-btn-icon size-12 rounded-full shadow-lg hidden"
        id="kt-debug-toggle"
        title="Rechten Debug">
    <i class="ki-filled ki-information text-xl"></i>
</button>

<script>
function toggleDebugPanel() {
    const panel = document.getElementById('kt-debug-panel');
    const toggle = document.getElementById('kt-debug-toggle');
    
    if (panel.classList.contains('hidden')) {
        panel.classList.remove('hidden');
        toggle.classList.add('opacity-50');
    } else {
        panel.classList.add('hidden');
        toggle.classList.remove('opacity-50');
    }
}

// Close panel when clicking outside
document.addEventListener('click', function(event) {
    const panel = document.getElementById('kt-debug-panel');
    const toggle = document.getElementById('kt-debug-toggle');
    
    if (panel && !panel.contains(event.target) && !toggle.contains(event.target)) {
        if (!panel.classList.contains('hidden')) {
            panel.classList.add('hidden');
            toggle.classList.remove('opacity-50');
        }
    }
});
</script>
@endif

