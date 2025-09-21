<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($jobs as $job)
        <div class="job-card">
            <!-- Job Header -->
            <div class="job-card-header">
                <div class="flex-1">
                    <h3 class="job-card-title">
                        <a href="{{ route('jobs.show', $job) }}" class="hover:underline">
                            {{ $job->title }}
                        </a>
                    </h3>
                    <p class="job-card-company">
                        {{ $job->company->name }}
                    </p>
                </div>
                <div class="flex-shrink-0 ml-4">
                    @if($job->company->logo)
                        <img src="{{ Storage::url($job->company->logo) }}" 
                             alt="{{ $job->company->name }}" 
                             class="w-12 h-12 rounded-lg object-cover">
                    @else
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                            <span class="text-blue-600 dark:text-blue-400 font-semibold text-lg">
                                {{ substr($job->company->name, 0, 1) }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Job Details -->
            <div class="space-y-3 mb-4">
                <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    {{ $job->location }}
                </div>
                
                <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                    {{ $job->salary_min ? '€' . number_format($job->salary_min, 0, ',', '.') . ' - €' . number_format($job->salary_max, 0, ',', '.') : 'Salaris op aanvraag' }}
                </div>
                
                <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    {{ $job->category->name }}
                </div>
            </div>
            
            <!-- Job Description -->
            <p class="job-card-description">
                {{ Str::limit(strip_tags($job->description), 120) }}
            </p>
            
            <!-- Job Footer -->
            <div class="job-card-footer">
                <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ $job->published_at->diffForHumans() }}
                </div>
                
                <a href="{{ route('jobs.show', $job) }}" 
                   class="btn btn-outline btn-sm hover-lift group">
                    Bekijk details
                    <svg class="ml-1 w-4 h-4 group-hover:translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
    @empty
        <div class="col-span-full text-center py-12">
            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Geen recente vacatures</h3>
            <p class="text-gray-600 dark:text-gray-400">Er zijn momenteel geen recente vacatures beschikbaar.</p>
        </div>
    @endforelse
</div>