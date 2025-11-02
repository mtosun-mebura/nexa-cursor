@extends('frontend.layouts.dashboard')

@section('title', 'Mijn Favorieten - NEXA Skillmatching')

@section('content')
    <!-- Full width content outside the grid -->
    <div class="lg:col-span-12">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-muted dark:text-muted-dark">
                <li><a href="/" class="hover:text-primary dark:hover:text-primary-dark">Home</a></li>
                <li class="flex items-center">
                    <svg class="w-4 h-4 mx-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-primary dark:text-primary-dark">Mijn Favorieten</span>
                </li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="card p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Mijn Favorieten</h1>
                    <p class="text-muted dark:text-muted-dark">{{ $favorites->total() }} opgeslagen vacatures</p>
                </div>
            </div>
        </div>

    <!-- Favorites List -->
    @if($favorites->count() > 0)
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-1/4">
                                Vacature
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-1/8">
                                Bedrijf
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-1/12">
                                Locatie
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-1/12">
                                Type
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-1/8">
                                Salaris
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-1/12">
                                Ervaring
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-1/12">
                                Opgeslagen
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-1/8">
                                Acties
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($favorites as $favorite)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $favorite->title }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ Str::limit(strip_tags($favorite->description), 80) }}
                                    </div>
                                    <div class="flex flex-wrap gap-1 mt-2">
                                        @if($favorite->category)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ $favorite->category->name }}
                                            </span>
                                        @endif
                                        @if($favorite->remote_work)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                Remote
                                            </span>
                                        @endif
                                        @if($favorite->travel_expenses)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100">
                                                Reiskosten
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm text-gray-900 dark:text-gray-100">
                                    @if($favorite->company && $favorite->company->is_intermediary)
                                        {{ $favorite->company->name }}
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">Directe werkgever</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ $favorite->location }}
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $favorite->employment_type ?? 'Niet opgegeven' }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm text-gray-900 dark:text-gray-100">
                                    @if($favorite->salary_min && $favorite->salary_max)
                                        €{{ number_format($favorite->salary_min, 0, ',', '.') }} - €{{ number_format($favorite->salary_max, 0, ',', '.') }}
                                    @else
                                        Niet opgegeven
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ $favorite->experience_level ?? 'Niet opgegeven' }}
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm text-gray-900 dark:text-gray-100">
                                    @if($favorite->pivot && $favorite->pivot->created_at)
                                        {{ $favorite->pivot->created_at->format('d-m-Y') }}
                                    @else
                                        Onbekend
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('jobs.show', $favorite) }}" 
                                       class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        Details
                                    </a>
                                    <button onclick="removeFavorite({{ $favorite->id }})" 
                                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-red-600 hover:bg-red-700 transition-colors"
                                            title="Verwijder uit favorieten">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($favorites->hasPages())
            <div class="mt-6">
                {{ $favorites->links() }}
            </div>
        @endif
    @else
        <div class="card p-8 text-center">
            <div class="mx-auto w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Geen favorieten gevonden</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">Je hebt nog geen vacatures opgeslagen in je favorieten.</p>
            <a href="{{ route('jobs.index') }}" 
               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                Bekijk vacatures
            </a>
        </div>
    @endif
    </div>

<script>
async function removeFavorite(vacancyId) {
    if (!confirm('Weet je zeker dat je deze vacature uit je favorieten wilt verwijderen?')) {
        return;
    }

    try {
        const response = await fetch(`/favorites/${vacancyId}/toggle`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const data = await response.json();
        
        if (data.success) {
            // Reload the page to update the list
            location.reload();
        } else {
            alert('Er is een fout opgetreden: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Er is een fout opgetreden bij het verwijderen van de vacature.');
    }
}
</script>
@endsection
