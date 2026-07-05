@php
    $pages = $pages ?? \App\Support\AdminHandleiding::pages();
    $activeSlug = $activeSlug ?? null;
@endphp
<nav class="handleiding-nav kt-card w-full min-w-0" aria-label="Handleiding">
    <div class="kt-card-header border-b border-border">
        <h2 class="kt-card-title text-base mb-0">Inhoud</h2>
    </div>
    <div class="kt-card-content p-0">
        <ul class="divide-y divide-border">
            <li>
                <a href="{{ route('admin.handleiding.index') }}"
                   class="handleiding-nav-link flex items-start gap-3 px-4 py-3 text-sm transition-colors hover:bg-accent/50 {{ request()->routeIs('admin.handleiding.index') ? 'bg-accent/60 font-medium text-primary' : 'text-foreground' }}">
                    <i class="ki-filled ki-book-open text-base mt-0.5 shrink-0 text-muted-foreground"></i>
                    <span>
                        <span class="block">Overzicht</span>
                        <span class="block text-xs text-muted-foreground font-normal">Alle onderwerpen</span>
                    </span>
                </a>
            </li>
            @foreach($pages as $navSlug => $navPage)
                <li>
                    <a href="{{ route('admin.handleiding.show', $navSlug) }}"
                       class="handleiding-nav-link flex items-start gap-3 px-4 py-3 text-sm transition-colors hover:bg-accent/50 {{ $activeSlug === $navSlug ? 'bg-accent/60 font-medium text-primary' : 'text-foreground' }}">
                        <i class="ki-filled {{ $navPage['icon'] ?? 'ki-document' }} text-base mt-0.5 shrink-0 text-muted-foreground"></i>
                        <span>
                            <span class="block">{{ $navPage['title'] }}</span>
                            @if(!empty($navPage['estimated_minutes']))
                                <span class="block text-xs text-muted-foreground font-normal">± {{ $navPage['estimated_minutes'] }} min lezen</span>
                            @endif
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</nav>
