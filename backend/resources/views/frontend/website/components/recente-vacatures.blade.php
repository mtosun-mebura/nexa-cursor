{{-- Component: Recente Vacatures (container-custom, grid van vacaturekaarten). Altijd tonen; bij geen vacatures een lege staat. --}}
<section class="py-16 bg-gray-50 dark:bg-gray-900">
    <div class="container-custom">
        <div class="mb-12 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">Recente <span class="text-orange-500 dark:text-orange-400">Vacatures</span></h2>
            <p class="text-gray-600 dark:text-gray-300">Ontdek de nieuwste vacatures van topbedrijven</p>
        </div>
        @if(isset($jobs) && $jobs->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            @foreach($jobs as $job)
            @php
                $companyName = $job->company->name ?? 'Directe werkgever';
                $companyInitial = Str::upper(Str::substr($companyName, 0, 1));
            @endphp
            <article class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/50 p-6 hover:border-blue-500/50 transition-colors flex flex-col h-full shadow-sm dark:shadow-none">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-lg">{{ $companyInitial }}</span>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg text-gray-900 dark:text-white leading-tight">{{ $job->title }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $companyName }}</p>
                        </div>
                    </div>
                    <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-50">
                        @if($job->salary_min && $job->salary_max)
                            €{{ number_format($job->salary_min, 0, ',', '.') }}-{{ number_format($job->salary_max, 0, ',', '.') }}
                        @else
                            Salaris n.o.t.k.
                        @endif
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 flex-grow line-clamp-2">{{ Str::limit(strip_tags($job->description), 120) }}</p>
                <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                    <span class="text-sm text-gray-500 dark:text-gray-500">{{ $job->location ?? 'Locatie onbekend' }}</span>
                    <a href="{{ route('jobs.show', $job) }}" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">Details</a>
                </div>
            </article>
            @endforeach
        </div>
        <div class="text-center">
            <a href="{{ route('jobs.index') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg font-semibold bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600 transition-all">
                Bekijk alle vacatures
                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>
        @else
        <div class="text-center py-12 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/50">
            <p class="text-gray-600 dark:text-gray-400 mb-6">Er zijn momenteel geen recente vacatures.</p>
            <a href="{{ route('jobs.index') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg font-semibold bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600 transition-all">
                Bekijk alle vacatures
                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>
        @endif
    </div>
</section>
