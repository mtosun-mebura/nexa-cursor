@extends('frontend.layouts.dashboard')

@section('title', $vacancy->title . ' - ' . $vacancy->company->name . ' | NEXA Skillmatching')

@section('content')
      <!-- Breadcrumb -->
      <nav class="mb-6">
          <ol class="flex items-center space-x-2 text-sm text-muted dark:text-muted-dark">
              <li><a href="/" class="hover:text-primary dark:hover:text-primary-dark">Home</a></li>
              <li class="flex items-center">
                  <svg class="w-4 h-4 mx-2" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                  </svg>
                  <a href="{{ route('vacature-matching') }}" class="hover:text-primary dark:hover:text-primary-dark">Vacature Matching</a>
              </li>
              <li class="flex items-center">
                  <svg class="w-4 h-4 mx-2" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                  </svg>
                  <span class="text-primary dark:text-primary-dark">{{ $vacancy->title }}</span>
              </li>
          </ol>
      </nav>

    <!-- Vacancy Header -->
    <div class="card p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $vacancy->title }}</h1>
                
                @if($vacancy->company && $vacancy->company->is_intermediary)
                    <p class="text-lg text-muted dark:text-muted-dark mb-4">{{ $vacancy->company->name }}</p>
                @endif
                
                <div class="flex flex-wrap gap-4 text-sm text-muted dark:text-muted-dark mb-4">
                    @if($vacancy->location)
                        <div class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                            </svg>
                            {{ $vacancy->location }}
                        </div>
                    @endif
                    
                    @if($vacancy->employment_type)
                        <div class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                            </svg>
                            {{ $vacancy->employment_type }}
                        </div>
                    @endif
                    
                    @if($vacancy->salary_range)
                        <div class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                            </svg>
                            {{ $vacancy->salary_range }}
                        </div>
                    @endif
                </div>
                
                <div class="flex flex-wrap gap-2">
                    @if($vacancy->category)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            {{ $vacancy->category->name }}
                        </span>
                    @endif
                    
                    @if($vacancy->remote_work)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-900">
                            Remote
                        </span>
                    @endif
                    
                    @if($vacancy->travel_expenses)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100">
                            Reiskosten
                        </span>
                    @endif
                </div>
            </div>
            
            <div class="flex flex-col gap-3 lg:min-w-[200px]">
                <button onclick="startApplication()" class="btn btn-primary w-full">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                    Direct Solliciteren
                </button>
                
                <button onclick="saveVacancy()" class="btn btn-outline w-full">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                    </svg>
                    Opslaan
                </button>
            </div>
        </div>
    </div>

      <!-- Content Grid - 2 blocks per row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Company Information - Only for intermediaries -->
        @if($vacancy->company && $vacancy->company->is_intermediary)
            <div class="card p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Over {{ $vacancy->company->name }}</h2>
                <div class="space-y-3">
                    @if($vacancy->company->email)
                        <div class="flex items-center gap-2 text-sm text-muted dark:text-muted-dark">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                            </svg>
                            {{ $vacancy->company->email }}
                        </div>
                    @endif
                    
                    @if($vacancy->company->phone)
                        <div class="flex items-center gap-2 text-sm text-muted dark:text-muted-dark">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                            </svg>
                            {{ $vacancy->company->phone }}
                        </div>
                    @endif
                    
                    @if($vacancy->company->website)
                        <div class="flex items-center gap-2 text-sm text-muted dark:text-muted-dark">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.083 9h1.946c.089-1.546.383-2.97.837-4.118A6.004 6.004 0 004.083 9zM10 2a8 8 0 100 16 8 8 0 000-16zm0 2c-.076 0-.232.032-.465.262-.238.234-.497.623-.737 1.182-.389.907-.673 2.142-.766 3.556h3.936c-.093-1.414-.377-2.649-.766-3.556-.24-.56-.5-.948-.737-1.182C10.232 4.032 10.076 4 10 4zm3.971 5c-.089-1.546-.383-2.97-.837-4.118A6.004 6.004 0 0115.917 9h-1.946zm-2.003 2H8.032c.093 1.414.377 2.649.766 3.556.24.56.5.948.737 1.182.233.23.389.262.465.262.076 0 .232-.032.465-.262.238-.234.498-.623.737-1.182.389-.907.673-2.142.766-3.556zm1.166 4.118c.454-1.147.748-2.572.837-4.118h1.946a6.004 6.004 0 01-2.783 4.118zm-6.268 0C6.412 13.97 6.118 12.546 6.03 11H4.083a6.004 6.004 0 002.783 4.118z" clip-rule="evenodd"></path>
                            </svg>
                            <a href="{{ $vacancy->company->website }}" target="_blank" class="text-primary dark:text-primary-dark hover:underline">
                                {{ $vacancy->company->website }}
                            </a>
                        </div>
                    @endif
                    
                    @if($vacancy->company->city)
                        <div class="flex items-center gap-2 text-sm text-muted dark:text-muted-dark">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                            </svg>
                            {{ $vacancy->company->city }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Job Description -->
        @if($vacancy->description)
            <div class="card p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Functieomschrijving</h2>
                <div class="prose prose-gray dark:prose-invert max-w-none">
                    {!! nl2br(e($vacancy->description)) !!}
                </div>
            </div>
        @endif

        <!-- Requirements -->
        @if($vacancy->requirements)
            <div class="card p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Eisen & Kwalificaties</h2>
                <div class="prose prose-gray dark:prose-invert max-w-none">
                    {!! nl2br(e($vacancy->requirements)) !!}
                </div>
            </div>
        @endif

        <!-- Offer -->
        @if($vacancy->offer)
            <div class="card p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Wat Wij Bieden</h2>
                <div class="prose prose-gray dark:prose-invert max-w-none">
                    {!! nl2br(e($vacancy->offer)) !!}
                </div>
            </div>
        @endif

        <!-- Application Instructions -->
        @if($vacancy->application_instructions)
            <div class="card p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Sollicitatie Instructies</h2>
                <div class="prose prose-gray dark:prose-invert max-w-none">
                    {!! nl2br(e($vacancy->application_instructions)) !!}
                </div>
            </div>
        @endif
      </div>

      <!-- Related Vacancies -->
      @if($relatedVacancies->count() > 0)
          <div class="card p-6 mt-6">
              <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Gerelateerde Vacatures</h2>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  @foreach($relatedVacancies as $related)
                      <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                          <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ $related->title }}</h3>
                          
                          @if($related->company->is_intermediary)
                              <p class="text-sm text-muted dark:text-muted-dark mb-2">{{ $related->company->name }}</p>
                          @endif
                          
                          <div class="flex flex-wrap gap-2 text-xs text-muted dark:text-muted-dark mb-3">
                              @if($related->location)
                                  <span>{{ $related->location }}</span>
                              @endif
                              @if($related->employment_type)
                                  <span>• {{ $related->employment_type }}</span>
                              @endif
                          </div>
                          
                          <a href="{{ route('frontend.vacancy-details', ['company' => $related->company->slug, 'vacancy' => $related->id]) }}" 
                             class="text-primary dark:text-primary-dark hover:underline text-sm font-medium">
                              Bekijk vacature →
                          </a>
                      </div>
                  @endforeach
              </div>
          </div>
      @endif

<script>
function startApplication() {
    // Hier zou de AI chatbot flow gestart worden
    alert('AI Sollicitatie Assistant wordt gestart...\n\nDeze functie wordt binnenkort geïmplementeerd.');
}

async function saveVacancy() {
    console.log('Starting saveVacancy function');
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        console.log('CSRF Token:', csrfToken);
        
        if (!csrfToken) {
            alert('CSRF token niet gevonden. Pagina wordt herladen.');
            location.reload();
            return;
        }

        const url = '{{ route("favorites.toggle", $vacancy) }}';
        console.log('Request URL:', url);

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });

        console.log('Response status:', response.status);
        console.log('Response ok:', response.ok);

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Error response:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Response data:', data);
        
        if (data.success) {
            // Update button text and style
            const button = document.querySelector('button[onclick="saveVacancy()"]');
            if (data.isFavorited) {
                button.innerHTML = `
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                    </svg>
                    Opgeslagen
                `;
                button.classList.remove('btn-outline');
                button.classList.add('btn-primary');
            } else {
                button.innerHTML = `
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                    </svg>
                    Opslaan
                `;
                button.classList.remove('btn-primary');
                button.classList.add('btn-outline');
            }
            
            // Show success message
            alert(data.message);
        } else {
            alert('Er is een fout opgetreden: ' + (data.message || 'Onbekende fout'));
        }
    } catch (error) {
        console.error('Error details:', error);
        console.error('Error message:', error.message);
        alert('Er is een fout opgetreden bij het opslaan van de vacature. Controleer de console voor meer details.');
    }
}

// Check favorite status on page load
document.addEventListener('DOMContentLoaded', async function() {
    try {
        const response = await fetch('{{ route("favorites.check", $vacancy) }}');
        const data = await response.json();
        
        if (data.success && data.isFavorited) {
            const button = document.querySelector('button[onclick="saveVacancy()"]');
            button.innerHTML = `
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                </svg>
                Opgeslagen
            `;
            button.classList.remove('btn-outline');
            button.classList.add('btn-primary');
        }
    } catch (error) {
        console.error('Error checking favorite status:', error);
    }
});
</script>
@endsection
