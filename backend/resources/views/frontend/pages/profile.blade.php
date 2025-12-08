@extends('frontend.layouts.dashboard')

@section('title', 'Mijn Profiel - NEXA Skillmatching')

@section('content')
<section class="flex flex-wrap items-center justify-between gap-3">
  <div>
    <h1 class="text-2xl font-semibold leading-tight">Mijn Profiel</h1>
    <p class="text-sm text-muted dark:text-muted-dark">Beheer je persoonlijke informatie en vaardigheden.</p>
  </div>
  <div class="flex items-center gap-2">
    <span class="pill">{{ $profileCompleteness }}% compleet</span>
  </div>
</section>

<!-- Top Row: Profile Photo + Personal Information -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
  <!-- Profile Overview -->
  <div class="lg:col-span-1">
    <div class="card p-6 text-center">
      <!-- Profile Photo Container with Upload Button -->
      <div class="relative mx-auto mb-4" style="width: 300px; height: 300px;">
        <!-- Photo Container -->
        <div class="relative bg-gray-100 dark:bg-gray-800 rounded-full w-full h-full flex items-center justify-center overflow-hidden border-4 border-gray-300 dark:border-gray-600 shadow-lg"
             id="photo-container"
             style="display: flex; visibility: visible;"
             ondrop="handleDrop(event)"
             ondragover="handleDragOver(event)"
             ondragenter="handleDragEnter(event)"
             ondragleave="handleDragLeave(event)">

          <!-- Profile Image or Placeholder -->
          @if($user->photo_blob)
            <img id="profile-image"
                 src="{{ route('secure.photo', ['token' => $user->getPhotoToken()]) }}"
                 alt="Profile Photo"
                 class="absolute inset-0 w-full h-full object-cover cursor-move"
                 draggable="false"
                 style="transform: scale(1) translate(0px, 0px);">
          @else
            <!-- Default Avatar Icon -->
            <div class="w-full h-full flex items-center justify-center bg-gray-200 dark:bg-gray-700">
              <svg class="w-16 h-16 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
        </svg>
      </div>
          @endif

          <!-- Drag Overlay -->
          <div id="drag-overlay" class="absolute inset-0 bg-blue-500 bg-opacity-50 rounded-full flex items-center justify-center text-white font-semibold hidden">
            <div class="text-center">
              <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
              </svg>
              <div class="text-sm">Sleep foto hier</div>
            </div>
          </div>
        </div>

        <!-- Button Container -->
        <div class="absolute -bottom-2 left-0 right-0 flex justify-between px-2">
          <!-- Reset Button -->
          <button onclick="resetPhotoTransform()"
                  class="w-10 h-10 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-white border-2 border-gray-300 dark:border-gray-600 rounded-full flex items-center justify-center text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors z-10 shadow-lg"
                  title="Reset foto">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
          </button>

          <!-- Upload Button -->
          <button onclick="document.getElementById('photo-upload').click()"
                  class="w-10 h-10 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm hover:bg-blue-600 transition-colors z-10 shadow-lg"
                  title="Foto aanpassen">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
          </button>
        </div>
      </div>
      <input type="file" id="photo-upload" class="hidden" accept="image/*,.svg" onchange="uploadPhoto(this)">

      <!-- Photo editor instructions -->
      <div class="text-sm text-muted dark:text-muted-dark mb-4">
        <div class="flex flex-col items-center space-y-1">
          <div class="flex items-center space-x-4">
            <span>Sleep om te verplaatsen</span>
          </div>
          <div class="flex items-center space-x-4">
            <span>+/- = zoom in/uit</span>
          </div>
        </div>
      </div>

      <!-- Separator line -->
      <div class="w-full h-px bg-gray-200 dark:bg-gray-700 mb-2"></div>

      <h3 class="font-semibold text-lg mb-1">{{ $user->first_name ?? 'Gebruiker' }} {{ $user->last_name ?? '' }}</h3>
      <p class="text-sm text-muted dark:text-muted-dark mb-4">{{ $user->bio ?? 'Senior Developer' }}</p>

      <div class="space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-muted dark:text-muted-dark">Profiel compleet</span>
          <span class="font-medium">{{ $profileCompleteness }}%</span>
        </div>
        <div class="w-full bg-border dark:bg-border-dark rounded-full h-2">
          <div class="bg-brand-500 h-2 rounded-full" style="width: {{ $profileCompleteness }}%">        </div>
        </div>
      </div>
    </div>
  </div>
    <!-- Personal Information -->
    <div class="lg:col-span-2">
    <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-lg">Persoonlijke Informatie</h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">* verplicht</span>
            </div>
            <form id="profile-form" class="space-y-4">
                @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
                        <label class="text-sm font-medium text-muted dark:text-muted-dark">Voornaam <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" class="input mt-1" value="{{ $user->first_name ?? '' }}" placeholder="Voornaam" required>
          </div>
          <div>
                        <label class="text-sm font-medium text-muted dark:text-muted-dark">Achternaam <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" class="input mt-1" value="{{ $user->last_name ?? '' }}" placeholder="Achternaam" required>
          </div>
        </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
                        <label class="text-sm font-medium text-muted dark:text-muted-dark">E-mailadres <span class="text-red-500">*</span></label>
                        <input type="email" name="email" class="input mt-1" value="{{ $user->email ?? '' }}" placeholder="E-mailadres" required>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-muted dark:text-muted-dark">Geboortedatum</label>
                        <input type="text" name="date_of_birth" id="birth-date-input" class="input mt-1" value="{{ $user->date_of_birth ? \Carbon\Carbon::parse($user->date_of_birth)->format('d-m-Y') : '' }}" placeholder="dd-mm-jjjj" readonly onclick="showBirthDatePicker()">
                    </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-muted dark:text-muted-dark">Telefoonnummer</label>
                        <input type="tel" name="phone" class="input mt-1" value="{{ $user->phone ?? '' }}" placeholder="+31 6 12345678">
          </div>
          <div>
                        <label class="text-sm font-medium text-muted dark:text-muted-dark">Locatie <span class="text-red-500">*</span></label>
                        <input type="text" name="location" class="input mt-1" value="{{ $user->location ?? '' }}" placeholder="Amsterdam, Nederland">
          </div>
        </div>

                <div>
                    <label class="text-sm font-medium text-muted dark:text-muted-dark">Bio</label>
                    <textarea name="bio" class="input mt-1" rows="4" placeholder="Vertel iets over jezelf...">{{ $user->bio ?? '' }}</textarea>
                </div>

        <button type="submit" class="btn btn-primary">Opslaan</button>
      </form>
    </div>



        <!-- Add Skill Modal -->
        <div id="skill-modal" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-50">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4 relative border border-gray-200 dark:border-gray-700 shadow-xl">
                <button onclick="hideAddSkillModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <h3 class="text-lg font-semibold mb-4">Vaardigheid Toevoegen</h3>
                <form id="skill-form">
                    @csrf
                    <input type="hidden" id="skill-type" name="type">
                    <div class="mb-4">
                        <label class="text-sm font-medium text-muted dark:text-muted-dark">Naam</label>
                        <input type="text" name="name" class="input mt-1" placeholder="Vaardigheid naam" required>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="hideAddSkillModal()" class="btn btn-outline flex-1 flex items-center justify-center dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600">Annuleren</button>
                        <button type="submit" class="btn btn-primary flex-1 flex items-center justify-center">Toevoegen</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Experience Modal -->
        <div id="experience-modal" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-50 overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 w-[95vw] mx-4 relative max-h-[90vh] overflow-y-auto">
                <button onclick="hideAddExperienceModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <h3 class="text-lg font-semibold mb-4" id="experience-modal-title">Werkervaring Toevoegen</h3>
                <form id="experience-form">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted dark:text-muted-dark">Functietitel</label>
                            <input type="text" name="title" class="input mt-1" placeholder="Bijv. Senior Developer" required>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted dark:text-muted-dark">Bedrijf</label>
                            <input type="text" name="company" class="input mt-1" placeholder="Bedrijfsnaam" required>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-muted dark:text-muted-dark">Startdatum</label>
                                <input type="date" name="start_date" class="input mt-1" required>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted dark:text-muted-dark">Einddatum</label>
                                <input type="date" name="end_date" class="input mt-1" id="end-date">
                            </div>
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="current" id="current-job" class="mr-2" onchange="toggleEndDate()">
                                <span class="text-sm">Huidige functie</span>
                            </label>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted dark:text-muted-dark">Beschrijving</label>
                            <textarea name="description" class="input mt-1" rows="6" placeholder="Beschrijf je taken en verantwoordelijkheden..."></textarea>
                        </div>
                    </div>
                    <div class="flex gap-2 mt-6 justify-end">
                        <button type="button" onclick="hideAddExperienceModal()" class="btn btn-outline">Annuleren</button>
                        <button type="submit" class="btn btn-primary min-w-[120px]">Toevoegen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
  </div>
<!-- Bottom Row: Skills + Experience -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Skills -->
    <div class="card p-6 lg:col-start-1 lg:col-span-1">
      <h3 class="font-semibold text-lg mb-4">Vaardigheden</h3>
      <div class="space-y-4">
        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Technische Vaardigheden</label>
          <div class="flex flex-wrap gap-2 mt-2">
                    @foreach($user->skills->where('type', 'technical') as $skill)
                        <span class="pill flex items-center gap-1">
                {{ $skill->name }}
                <button onclick="removeSkill({{ $skill->id }})" class="text-red-500 hover:text-red-700 ml-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              </span>
                    @endforeach
                    <button onclick="showAddSkillModal('technical')" class="pill border-dashed border-2 border-border dark:border-border-dark text-muted dark:text-muted-dark">
              + Toevoegen
            </button>
          </div>
        </div>
        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Soft Skills</label>
          <div class="flex flex-wrap gap-2 mt-2">
                    @foreach($user->skills->where('type', 'soft') as $skill)
                        <span class="pill flex items-center gap-1">
                {{ $skill->name }}
                <button onclick="removeSkill({{ $skill->id }})" class="text-red-500 hover:text-red-700 ml-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              </span>
                    @endforeach
                    <button onclick="showAddSkillModal('soft')" class="pill border-dashed border-2 border-border dark:border-border-dark text-muted dark:text-muted-dark">
              + Toevoegen
            </button>
          </div>
        </div>

            <!-- CV Upload Section -->
            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                <label class="text-sm font-medium text-muted dark:text-muted-dark mb-2 block">Curriculum Vitae</label>
                <div id="cv-upload-area" class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-green-400 dark:hover:border-green-500 hover:bg-green-50 dark:hover:bg-green-900/10 transition-colors cursor-pointer group"
                     ondrop="handleCVDrop(event)"
                     ondragover="handleCVDragOver(event)"
                     ondragenter="handleCVDragEnter(event)"
                     ondragleave="handleCVDragLeave(event)"
                     ondragstart="event.preventDefault()"
                     onclick="document.getElementById('cv-upload').click()">

                    <!-- CV Upload Area -->
                    <div id="cv-upload-content">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            <span class="font-medium">Klik om CV te uploaden</span> of sleep bestand hierheen
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">
                            PDF, DOC, DOCX (max. 10MB)
                        </p>
                    </div>

                    <!-- Drag Overlay -->
                    <div id="cv-drag-overlay" class="absolute inset-0 bg-green-500 bg-opacity-50 rounded-lg flex items-center justify-center text-white font-semibold hidden">
                        Laat CV hier los
                    </div>
                </div>

                <input type="file" id="cv-upload" class="hidden" accept=".pdf,.doc,.docx" onchange="uploadCV(this)" multiple>

                <!-- CV Files List -->
                <div id="cv-files-list" class="mt-3 space-y-2">
                    @foreach($user->cvFiles as $cvFile)
                        <div class="cv-file-item flex items-center justify-start space-x-2" data-cv-id="{{ $cvFile->id }}">
                            <a href="{{ url('/file/' . str_replace('/', '--', $cvFile->file_path)) }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>{{ $cvFile->original_name }}</span>
                            </a>
                            <button onclick="removeCV({{ $cvFile->id }})" class="text-red-500 hover:text-red-700 ml-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
      </div>
    </div>

    <!-- Experience -->
    <div class="card p-6 lg:col-start-2 lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-lg">Werkervaring</h3>
            <button onclick="showAddExperienceModal()" class="w-8 h-8 bg-brand-500 text-white rounded-full flex items-center justify-center hover:bg-brand-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </button>
        </div>
      <div class="space-y-4">
            @forelse($user->experiences as $experience)
                <div class="border-l-2 border-brand-500 pl-4 py-2">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h4 class="font-medium">{{ $experience->title }}</h4>
                            <p class="text-sm text-muted dark:text-muted-dark">{{ $experience->company }} · {{ $experience->start_date->format('Y') }} - {{ $experience->current ? 'Heden' : ($experience->end_date ? $experience->end_date->format('Y') : 'Onbekend') }}</p>
                            @if($experience->description)
                                <p class="text-sm mt-1">{{ $experience->description }}</p>
                            @endif
        </div>
                        <div class="flex gap-2 ml-2">
                            <button onclick="editExperience({{ $experience->id }})" class="text-blue-500 hover:text-blue-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button onclick="removeExperience({{ $experience->id }})" class="text-red-500 hover:text-red-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
        </div>
                    </div>
                </div>
            @empty
                <p class="text-muted dark:text-muted-dark">Nog geen werkervaring toegevoegd.</p>
            @endforelse
        </div>
    </div>
</div>
</div>

<!-- Add Skill Modal -->
<div id="skill-modal" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-50">
  <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4 relative">
    <button onclick="hideAddSkillModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
    <h3 class="text-lg font-semibold mb-4">Vaardigheid Toevoegen</h3>
    <form id="skill-form">
      @csrf
      <input type="hidden" id="skill-type" name="type">
      <div class="mb-4">
        <label class="text-sm font-medium text-muted dark:text-muted-dark">Naam</label>
        <input type="text" name="name" class="input mt-1" placeholder="Vaardigheid naam" required>
      </div>
      <div class="flex gap-2">
        <button type="button" onclick="hideAddSkillModal()" class="btn btn-outline flex-1 flex items-center justify-center">Annuleren</button>
        <button type="submit" class="btn btn-primary flex-1 flex items-center justify-center">Toevoegen</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Experience Modal -->
<div id="experience-modal" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-50 overflow-hidden">
  <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 w-[95vw] mx-4 relative max-h-[90vh] overflow-y-auto">
    <button onclick="hideAddExperienceModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
    <h3 class="text-lg font-semibold mb-4" id="experience-modal-title">Werkervaring Toevoegen</h3>
    <form id="experience-form">
      @csrf
      <div class="space-y-4">
        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Functietitel</label>
          <input type="text" name="title" class="input mt-1" placeholder="Bijv. Senior Developer" required>
        </div>
        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Bedrijf</label>
          <input type="text" name="company" class="input mt-1" placeholder="Bedrijfsnaam" required>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-muted dark:text-muted-dark">Startdatum</label>
            <input type="date" name="start_date" class="input mt-1" required>
          </div>
          <div>
            <label class="text-sm font-medium text-muted dark:text-muted-dark">Einddatum</label>
            <input type="date" name="end_date" class="input mt-1" id="end-date">
          </div>
        </div>
        <div>
          <label class="flex items-center">
            <input type="checkbox" name="current" id="current-job" class="mr-2" onchange="toggleEndDate()">
            <span class="text-sm">Huidige functie</span>
          </label>
        </div>
        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Beschrijving</label>
          <textarea name="description" class="input mt-1" rows="6" placeholder="Beschrijf je taken en verantwoordelijkheden..."></textarea>
        </div>
      </div>
      <div class="flex gap-2 mt-6 justify-end">
        <button type="button" onclick="hideAddExperienceModal()" class="btn btn-outline">Annuleren</button>
        <button type="submit" class="btn btn-primary min-w-[120px]">Toevoegen</button>
      </div>
    </form>
  </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmation-modal" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-50">
  <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4 relative">
    <button onclick="hideConfirmationModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
    <div class="text-center">
      <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-red-100 dark:bg-red-900 mb-4">
        <svg class="h-10 w-10 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2" id="confirmation-title">Bevestiging</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-6" id="confirmation-message">Weet je zeker dat je dit wilt verwijderen?</p>
      <div class="flex gap-3 justify-center">
        <button onclick="hideConfirmationModal()" class="px-4 py-2 font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md transition-colors flex-1 flex items-center justify-center">
          Annuleren
        </button>
        <button onclick="confirmDelete()" class="px-4 py-2 font-medium text-white !bg-red-600 hover:!bg-red-700 rounded-md transition-colors flex-1 flex items-center justify-center" style="background-color: #dc2626 !important;">
          Verwijderen
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Success/Error Modal -->
<div id="message-modal" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-50">
  <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4 relative">
    <button onclick="hideMessageModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
    <div class="text-center">
      <div id="message-icon" class="mx-auto flex items-center justify-center h-16 w-16 rounded-full mb-4">
        <!-- Icon will be set dynamically -->
      </div>
      <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2" id="message-title">Bericht</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-6" id="message-text">Bericht tekst</p>
      <div class="flex justify-center">
        <button onclick="hideMessageModal()" class="px-6 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors">
          OK
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Birth Date Picker Modal -->
<div id="birth-date-picker-modal" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-50">
  <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4 relative">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Geboortedatum selecteren</h3>
      <button onclick="hideBirthDatePicker()" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-6">
      <!-- Month -->
      <div>
        <label class="text-sm font-medium text-muted dark:text-muted-dark mb-2 block">Maand</label>
        <select id="month-select" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 appearance-none cursor-pointer" onchange="updateDays()">
          <option value="">Maand</option>
          <option value="1">Januari</option>
          <option value="2">Februari</option>
          <option value="3">Maart</option>
          <option value="4">April</option>
          <option value="5">Mei</option>
          <option value="6">Juni</option>
          <option value="7">Juli</option>
          <option value="8">Augustus</option>
          <option value="9">September</option>
          <option value="10">Oktober</option>
          <option value="11">November</option>
          <option value="12">December</option>
        </select>
      </div>

      <!-- Day -->
      <div>
        <label class="text-sm font-medium text-muted dark:text-muted-dark mb-2 block">Dag</label>
        <select id="day-select" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 appearance-none cursor-pointer">
          <option value="">Dag</option>
        </select>
      </div>

      <!-- Year -->
      <div>
        <label class="text-sm font-medium text-muted dark:text-muted-dark mb-2 block">Jaar</label>
        <select id="year-select" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 appearance-none cursor-pointer" onchange="updateDays()">
          <option value="">Jaar</option>
        </select>
      </div>
    </div>

            <div class="flex gap-2 justify-end">
              <button onclick="hideBirthDatePicker()" class="btn btn-outline">Annuleren</button>
              <button onclick="selectBirthDate()" class="btn btn-primary">Opslaan</button>
            </div>
  </div>
</div>

<script>
// Interactive photo editor variables
let isDragging = false;
let isResizing = false;
let startX, startY, startWidth, startHeight, startLeft, startTop;
let currentImage = null;
let currentScale = 1;
let currentTranslateX = 0;
let currentTranslateY = 0;

// Initialize photo editor when page loads
document.addEventListener('DOMContentLoaded', function() {
  initializePhotoEditor();
  setupRealTimeValidation();
  
  // Prevent default drag behavior on the entire page
  document.addEventListener('dragover', function(e) {
    e.preventDefault();
  });
  
  document.addEventListener('drop', function(e) {
    e.preventDefault();
  });

  // Add keyboard shortcuts
  document.addEventListener('keydown', function(e) {
    if (!currentImage) return;


    // Zoom in with + key
    if (e.key === '+' || e.key === '=') {
      e.preventDefault();
      currentScale = Math.min(3, currentScale + 0.1);
      updateImageTransform();
      savePhotoTransform();
    }

    // Zoom out with - key
    if (e.key === '-') {
      e.preventDefault();
      currentScale = Math.max(0.1, currentScale - 0.1);
      updateImageTransform();
      savePhotoTransform();
    }
  });

  // Add scroll zoom functionality
  document.addEventListener('wheel', function(e) {
    // Check if we're over the photo container
    const container = document.getElementById('photo-container');
    if (!container || !currentImage) return;

    const rect = container.getBoundingClientRect();
    const x = e.clientX;
    const y = e.clientY;

    // Only zoom if mouse is over the container and shift is pressed
    if (x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom && e.shiftKey) {
      e.preventDefault();
      e.stopPropagation();

      // Simple scroll zoom
      if (e.deltaY < 0) {
        // Scrolling up - zoom in
        currentScale = Math.min(3, currentScale + 0.1);
      } else if (e.deltaY > 0) {
        // Scrolling down - zoom out
        currentScale = Math.max(0.1, currentScale - 0.1);
      }

      updateImageTransform();
      savePhotoTransform(); // Save the transform

      console.log('Scroll zoom:', {
        deltaY: e.deltaY,
        newScale: currentScale,
        mouseOverContainer: true
      });
    }
  });
});

function initializePhotoEditor() {
  const container = document.getElementById('photo-container');
  const image = document.getElementById('profile-image');

  console.log('Initializing photo editor...');
  console.log('Container found:', !!container);
  console.log('Image found:', !!image);


  if (container) {
    // Always show the container
    container.style.display = 'flex';
    container.style.visibility = 'visible';
    container.style.width = '300px';
    container.style.height = '300px';
    container.style.borderRadius = '50%';
  }

  if (image) {
    currentImage = image;
    setupImageInteractions();

    // Load saved transform or use default
    loadPhotoTransform();
  }

  // Add container-specific scroll zoom
  if (container) {
    container.addEventListener('wheel', function(e) {
      if (!currentImage || !e.shiftKey) return;

      e.preventDefault();
      e.stopPropagation();

      // Simple scroll zoom
      if (e.deltaY < 0) {
        // Scrolling up - zoom in
        currentScale = Math.min(3, currentScale + 0.1);
      } else if (e.deltaY > 0) {
        // Scrolling down - zoom out
        currentScale = Math.max(0.1, currentScale - 0.1);
      }

      updateImageTransform();
      savePhotoTransform();

      console.log('Container scroll zoom:', {
        deltaY: e.deltaY,
        newScale: currentScale
      });
    });
  }

}

function setupImageInteractions() {
  if (!currentImage) {
    console.log('No current image found for setup');
    return;
  }

  console.log('Setting up image interactions for:', currentImage);

  // Remove existing event listeners to prevent duplicates
  currentImage.removeEventListener('mousedown', startDrag);
  currentImage.removeEventListener('touchstart', startDrag);

  // Make image draggable and resizable
  currentImage.addEventListener('mousedown', startDrag);
  currentImage.addEventListener('touchstart', startDrag);

  // Add resize handles
  addResizeHandles();

  console.log('Image interactions setup complete');
}

function updateImageTransform() {
  if (!currentImage) return;

  currentImage.style.transform = `scale(${currentScale}) translate(${currentTranslateX}px, ${currentTranslateY}px)`;
}

function resetPhotoTransform() {
  currentScale = 1;
  currentTranslateX = 0;
  currentTranslateY = 0;
  updateImageTransform();
  savePhotoTransform();
  console.log('Photo transform reset');
}

// DOM update functions for skills and experiences
function addSkillToDOM(skill) {
  // Find the correct skill container based on type
  const allSkillContainers = document.querySelectorAll('.flex.flex-wrap.gap-2');
  const container = skill.type === 'technical' ? allSkillContainers[0] : allSkillContainers[1];

  if (container) {
    const skillElement = document.createElement('span');
    skillElement.className = 'pill flex items-center gap-1';
    skillElement.innerHTML = `
      ${skill.name}
      <button onclick="removeSkill(${skill.id})" class="ml-1 text-red-500 hover:text-red-700">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    `;

    // Insert before the "Toevoegen" button
    const addButton = container.querySelector('button[onclick*="showAddSkillModal"]');
    if (addButton) {
      container.insertBefore(skillElement, addButton);
    } else {
      container.appendChild(skillElement);
    }

    console.log(`Added ${skill.type} skill: ${skill.name}`);
  } else {
    console.error('Could not find skill container for type:', skill.type);
  }
}

function removeSkillFromDOM(skillId) {
  const skillElement = document.querySelector(`button[onclick="removeSkill(${skillId})"]`)?.closest('span');
  if (skillElement) {
    console.log(`Removing skill with ID: ${skillId}`);
    skillElement.remove();
  } else {
    console.error(`Could not find skill element with ID: ${skillId}`);
  }
}

function addExperienceToDOM(experience) {
  // Find the experiences container - look for the card that contains "Werkervaring"
  const allCards = document.querySelectorAll('.card');
  let container = null;

  for (let card of allCards) {
    const heading = card.querySelector('h3');
    if (heading && heading.textContent.includes('Werkervaring')) {
      container = card.querySelector('.space-y-4');
      break;
    }
  }

  if (container) {
    const experienceElement = document.createElement('div');
    experienceElement.className = 'border-l-2 ' + (experience.current ? 'border-brand-500' : 'border-border dark:border-border-dark') + ' pl-4 relative';
    // Format dates properly
    const startDate = new Date(experience.start_date).getFullYear();
    const endDate = experience.current ? 'Heden' : (experience.end_date ? new Date(experience.end_date).getFullYear() : 'Onbekend');

    experienceElement.innerHTML = `
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <h4 class="font-medium">${experience.title}</h4>
          <p class="text-sm text-muted dark:text-muted-dark">${experience.company} · ${startDate} - ${endDate}</p>
          ${experience.description ? `<p class="text-sm mt-1">${experience.description}</p>` : ''}
        </div>
        <div class="flex gap-2 ml-2">
          <button onclick="editExperience(${experience.id})" class="text-blue-500 hover:text-blue-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
          </button>
          <button onclick="removeExperience(${experience.id})" class="text-red-500 hover:text-red-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
          </button>
        </div>
      </div>
    `;
    // Insert at the beginning to show newest first
    container.insertBefore(experienceElement, container.firstChild);
  }
}

function removeExperienceFromDOM(experienceId) {
  const experienceElement = document.querySelector(`button[onclick="removeExperience(${experienceId})"]`)?.closest('div[class*="border-l-2"]');
  if (experienceElement) {
    experienceElement.remove();
  }
}

function updateExperienceInDOM(experience) {
  // Find the existing experience element
  const experienceElement = document.querySelector(`button[onclick="editExperience(${experience.id})"]`)?.closest('div[class*="border-l-2"]');

  if (experienceElement) {
    // Update the content
    const titleElement = experienceElement.querySelector('h4');
    const companyElement = experienceElement.querySelector('p');

    if (titleElement) {
      titleElement.textContent = experience.title;
    }

    if (companyElement) {
      const startDate = new Date(experience.start_date).getFullYear();
      const endDate = experience.current ? 'Heden' : (experience.end_date ? new Date(experience.end_date).getFullYear() : 'Onbekend');
      companyElement.textContent = `${experience.company} · ${startDate} - ${endDate}`;
    }

    // Update description if it exists
    let descriptionElement = experienceElement.querySelector('.text-sm.mt-1');
    if (experience.description) {
      if (descriptionElement) {
        descriptionElement.textContent = experience.description;
      } else {
        // Add description if it doesn't exist
        const descriptionDiv = document.createElement('p');
        descriptionDiv.className = 'text-sm mt-1';
        descriptionDiv.textContent = experience.description;
        experienceElement.querySelector('.flex-1').appendChild(descriptionDiv);
      }
    } else if (descriptionElement) {
      descriptionElement.remove();
    }

    console.log('Updated experience in DOM:', experience.title);
  }
}

// Confirmation modal functions
let pendingDeleteAction = null;

function showConfirmationModal(title, message, deleteAction) {
  document.getElementById('confirmation-title').textContent = title;
  document.getElementById('confirmation-message').textContent = message;
  document.getElementById('confirmation-modal').classList.remove('hidden');
  document.getElementById('confirmation-modal').classList.add('flex');
  pendingDeleteAction = deleteAction;

  // Add ESC key listener
  document.addEventListener('keydown', handleConfirmationModalEsc);
}

function hideConfirmationModal() {
  document.getElementById('confirmation-modal').classList.add('hidden');
  document.getElementById('confirmation-modal').classList.remove('flex');
  pendingDeleteAction = null;

  // Remove ESC key listener
  document.removeEventListener('keydown', handleConfirmationModalEsc);
}

function handleConfirmationModalEsc(e) {
  if (e.key === 'Escape' || e.key === 'Enter') {
    hideConfirmationModal();
  }
}

function confirmDelete() {
  if (pendingDeleteAction) {
    pendingDeleteAction();
  }
  hideConfirmationModal();
}

// Message modal functions
function showMessageModal(type, title, message) {
  const modal = document.getElementById('message-modal');
  const icon = document.getElementById('message-icon');
  const titleElement = document.getElementById('message-title');
  const messageElement = document.getElementById('message-text');

  // Set title and message
  titleElement.textContent = title;
  messageElement.textContent = message;

  // Set icon and colors based on type
  if (type === 'success') {
    icon.className = 'mx-auto flex items-center justify-center h-16 w-16 rounded-full mb-4 bg-green-100 dark:bg-green-900';
    icon.innerHTML = `
      <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
      </svg>
    `;
  } else if (type === 'error') {
    icon.className = 'mx-auto flex items-center justify-center h-16 w-16 rounded-full mb-4 bg-red-100 dark:bg-red-900';
    icon.innerHTML = `
      <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    `;
  }

  modal.classList.remove('hidden');
  modal.classList.add('flex');

  // Add ESC key listener
  document.addEventListener('keydown', handleMessageModalEsc);
}

function hideMessageModal() {
  const modal = document.getElementById('message-modal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');

  // Remove ESC key listener
  document.removeEventListener('keydown', handleMessageModalEsc);
}

function handleMessageModalEsc(e) {
  if (e.key === 'Escape' || e.key === 'Enter') {
    hideMessageModal();
  }
}

// CV Upload Functions
function handleCVDragOver(e) {
  e.preventDefault();
  e.stopPropagation();
  e.dataTransfer.dropEffect = 'copy';
}

function handleCVDragEnter(e) {
  e.preventDefault();
  e.stopPropagation();
  e.dataTransfer.dropEffect = 'copy';
  const uploadArea = document.getElementById('cv-upload-area');
  uploadArea.classList.remove('border-gray-300', 'dark:border-gray-600');
  uploadArea.classList.add('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
  document.getElementById('cv-drag-overlay').classList.remove('hidden');
}

function handleCVDragLeave(e) {
  e.preventDefault();
  e.stopPropagation();
  const uploadArea = document.getElementById('cv-upload-area');
  uploadArea.classList.remove('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
  uploadArea.classList.add('border-gray-300', 'dark:border-gray-600');
  document.getElementById('cv-drag-overlay').classList.add('hidden');
}

async function handleCVDrop(e) {
  e.preventDefault();
  e.stopPropagation();
  e.dataTransfer.dropEffect = 'copy';
  
  const uploadArea = document.getElementById('cv-upload-area');
  uploadArea.classList.remove('border-green-500', 'bg-green-50', 'dark:bg-green-900/20');
  uploadArea.classList.add('border-gray-300', 'dark:border-gray-600');
  document.getElementById('cv-drag-overlay').classList.add('hidden');

  const files = e.dataTransfer.files;
  if (files.length > 0) {
    // Upload all files
    for (let i = 0; i < files.length; i++) {
      await uploadCVFile(files[i]);
    }
  }
}

async function uploadCV(input) {
  const files = input.files;
  if (!files || files.length === 0) return;

  // Upload files one by one
  for (let i = 0; i < files.length; i++) {
    await uploadCVFile(files[i]);
  }
  
  // Reset input
  input.value = '';
}

async function uploadCVFile(file) {
  // Client-side validation
  const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
  if (!allowedTypes.includes(file.type)) {
    showMessageModal('error', 'Fout!', 'Alleen PDF, DOC en DOCX bestanden zijn toegestaan.');
    return;
  }

  if (file.size > 10 * 1024 * 1024) { // 10MB
    showMessageModal('error', 'Fout!', 'Het CV bestand mag maximaal 10MB groot zijn.');
    return;
  }

  const formData = new FormData();
  formData.append('cv', file);

  try {
    const response = await fetch('{{ route("profile.cv") }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: formData
    });

    const data = await response.json();

    if (data.success) {
      addCVToDisplay(data.filename, data.url, data.cv_id);
    } else {
      showMessageModal('error', 'Fout!', data.message);
    }
  } catch (error) {
    showMessageModal('error', 'Fout!', 'Er is een fout opgetreden bij het uploaden van het CV.');
  }
}

function addCVToDisplay(originalFilename, url, cvId) {
  const cvFilesList = document.getElementById('cv-files-list');
  
  const cvFileItem = document.createElement('div');
  cvFileItem.className = 'cv-file-item flex items-center justify-start space-x-2';
  cvFileItem.setAttribute('data-cv-id', cvId);
  cvFileItem.innerHTML = `
    <a href="${url}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium flex items-center space-x-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
      </svg>
      <span>${originalFilename}</span>
    </a>
    <button onclick="removeCV(${cvId})" class="text-red-500 hover:text-red-700 ml-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
      </svg>
    </button>
  `;

  cvFilesList.appendChild(cvFileItem);
}

function removeCV(cvId) {
  showCVRemoveModal(cvId);
}

let currentCVId = null;

function showCVRemoveModal(cvId) {
  currentCVId = cvId;
  const modal = document.getElementById('cv-remove-modal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  document.body.style.overflow = 'hidden';

  // Add ESC key listener
  document.addEventListener('keydown', handleCVRemoveModalEsc);
}

function hideCVRemoveModal() {
  const modal = document.getElementById('cv-remove-modal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
  document.body.style.overflow = '';

  // Remove ESC key listener
  document.removeEventListener('keydown', handleCVRemoveModalEsc);
}

function handleCVRemoveModalEsc(e) {
  if (e.key === 'Escape' || e.key === 'Enter') {
    hideCVRemoveModal();
  }
}

async function confirmCVRemove() {
  if (!currentCVId) return;
  
  try {
    const response = await fetch('{{ route("profile.cv.remove") }}', {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ cv_id: currentCVId })
    });

    const data = await response.json();

    if (data.success) {
      // Remove the CV file item from the list
      const cvFileItem = document.querySelector(`[data-cv-id="${currentCVId}"]`);
      if (cvFileItem) {
        cvFileItem.remove();
      }
      
      hideCVRemoveModal();
      showMessageModal('success', 'Succesvol!', 'CV succesvol verwijderd!');
    } else {
      showMessageModal('error', 'Fout!', data.message);
    }
  } catch (error) {
    showMessageModal('error', 'Fout!', 'Er is een fout opgetreden bij het verwijderen van het CV.');
  }
}

function resetCVDisplay() {
  const uploadArea = document.getElementById('cv-upload-area');
  
  // Remove any existing CV display elements
  const existingCVDisplay = uploadArea.querySelector('.cv-display');
  if (existingCVDisplay) {
    existingCVDisplay.remove();
  }
  
  // Remove any link sections outside the upload area
  const cvSection = uploadArea.closest('.mt-6');
  const linkSection = cvSection.querySelector('.cv-link-section');
  if (linkSection) {
    linkSection.remove();
  }
  
  uploadArea.innerHTML = `
    <div id="cv-upload-content">
      <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
      </svg>
      <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
        <span class="font-medium">Klik om CV te uploaden</span> of sleep bestand hierheen
      </p>
      <p class="text-xs text-gray-500 dark:text-gray-500">
        PDF, DOC, DOCX (max. 10MB)
      </p>
    </div>
  `;
}

// Field error highlighting function
function highlightFieldError(fieldName) {
  // Remove any existing error highlighting
  clearFieldErrors();

  // Find the field
  const field = document.querySelector(`input[name="${fieldName}"], textarea[name="${fieldName}"]`);
  if (field) {
    // Add error styling
    field.classList.add('border-red-500', 'ring-2', 'ring-red-200');
    field.classList.remove('border-gray-300', 'dark:border-gray-600');

    // Focus on the field
    setTimeout(() => {
      field.focus();
      field.select();
    }, 100);

    // Remove error styling after 5 seconds
    setTimeout(() => {
      clearFieldErrors();
    }, 5000);
  }
}

function clearFieldErrors() {
  // Remove error styling from all form fields
  const fields = document.querySelectorAll('input, textarea');
  fields.forEach(field => {
    field.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
    field.classList.add('border-gray-300', 'dark:border-gray-600');
  });
}

// Birth Date Picker functions
function showBirthDatePicker() {
  const modal = document.getElementById('birth-date-picker-modal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');

  // Add ESC key listener
  document.addEventListener('keydown', handleBirthDatePickerEsc);

  // Populate year dropdown (1900 to current year)
  const yearSelect = document.getElementById('year-select');
  const currentYear = new Date().getFullYear();

  yearSelect.innerHTML = '<option value="">Jaar</option>';
  for (let year = currentYear; year >= 1900; year--) {
    const option = document.createElement('option');
    option.value = year;
    option.textContent = year;
    yearSelect.appendChild(option);
  }

  // Check if there's an existing date and populate the fields
  const existingDate = document.getElementById('birth-date-input').value;
  if (existingDate) {
    const parts = existingDate.split('-');
    if (parts.length === 3) {
      // Set month first, then year, then update days
      document.getElementById('month-select').value = parts[1];
      document.getElementById('year-select').value = parts[2];
      updateDays(); // This will populate the days
      // Set the day value after a small delay to ensure the options are populated
      setTimeout(() => {
        document.getElementById('day-select').value = parts[0];
      }, 10);
    }
  }
}

function hideBirthDatePicker() {
  const modal = document.getElementById('birth-date-picker-modal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');

  // Remove ESC key listener
  document.removeEventListener('keydown', handleBirthDatePickerEsc);
}

function handleBirthDatePickerEsc(e) {
  if (e.key === 'Escape' || e.key === 'Enter') {
    hideBirthDatePicker();
  }
}

function updateDays() {
  const monthSelect = document.getElementById('month-select');
  const yearSelect = document.getElementById('year-select');
  const daySelect = document.getElementById('day-select');

  const month = parseInt(monthSelect.value);
  const year = parseInt(yearSelect.value);

  // Store the currently selected day value
  const currentDay = daySelect.value;

  // Clear days dropdown
  daySelect.innerHTML = '<option value="">Dag</option>';

  if (month && year) {
    const daysInMonth = new Date(year, month, 0).getDate();

    for (let day = 1; day <= daysInMonth; day++) {
      const option = document.createElement('option');
      option.value = day.toString().padStart(2, '0');
      option.textContent = day;
      daySelect.appendChild(option);
    }

    // Restore the selected day if it's still valid for the new month/year
    if (currentDay && parseInt(currentDay) <= daysInMonth) {
      daySelect.value = currentDay;
    }
  }
}

function selectBirthDate() {
  const month = document.getElementById('month-select').value;
  const day = document.getElementById('day-select').value;
  const year = document.getElementById('year-select').value;

  console.log('Birth date selection:', { month, day, year });

  if (month && day && year) {
    // Ensure day and month are zero-padded
    const paddedDay = day.padStart(2, '0');
    const paddedMonth = month.padStart(2, '0');
    const formattedDate = `${paddedDay}-${paddedMonth}-${year}`;

    console.log('Selected date:', formattedDate);
    document.getElementById('birth-date-input').value = formattedDate;
    hideBirthDatePicker();
  } else {
    showMessageModal('error', 'Validatie fout!', 'Selecteer een volledige datum (maand, dag en jaar)');
  }
}

// Real-time validation for required fields
function setupRealTimeValidation() {
  const requiredFields = ['first_name', 'last_name', 'email', 'location'];

  requiredFields.forEach(fieldName => {
    const input = document.querySelector(`input[name="${fieldName}"]`);
    if (input) {
      // Validate on blur (when user leaves the field)
      input.addEventListener('blur', function() {
        validateField(this);
      });

      // Clear error styling on input (when user starts typing)
      input.addEventListener('input', function() {
        if (this.classList.contains('border-red-500')) {
          this.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
          this.classList.add('border-gray-300', 'dark:border-gray-600');
        }
      });
    }
  });
}

function validateField(input) {
  const value = input.value.trim();
  const fieldName = input.name;

  // Clear existing error styling
  input.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
  input.classList.add('border-gray-300', 'dark:border-gray-600');

  // Check if field is empty
  if (!value) {
    input.classList.add('border-red-500', 'ring-2', 'ring-red-200');
    input.classList.remove('border-gray-300', 'dark:border-gray-600');
    return false;
  }

  // Additional email validation
  if (fieldName === 'email') {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(value)) {
      input.classList.add('border-red-500', 'ring-2', 'ring-red-200');
      input.classList.remove('border-gray-300', 'dark:border-gray-600');
      return false;
    }
  }

  return true;
}

// Profile completeness calculation and update
function calculateProfileCompleteness() {
  let completedFields = 0;
  let totalFields = 11; // 8 basic fields + 2 skill categories + 1 experience category

  // Check basic profile fields
  const basicFields = [
    document.querySelector('input[name="first_name"]')?.value,
    document.querySelector('input[name="last_name"]')?.value,
    document.querySelector('input[name="email"]')?.value,
    document.querySelector('input[name="phone"]')?.value,
    document.querySelector('input[name="location"]')?.value,
    document.querySelector('textarea[name="bio"]')?.value,
    document.querySelector('input[name="date_of_birth"]')?.value,
    document.getElementById('profile-image')?.src // Photo exists
  ];

  basicFields.forEach(field => {
    if (field && field.trim() !== '') {
      completedFields++;
    }
  });

  // Check skills (2 categories) - find skill containers by their position
  const allSkillContainers = document.querySelectorAll('.flex.flex-wrap.gap-2');
  const technicalSkills = allSkillContainers[0] ? allSkillContainers[0].querySelectorAll('.pill:not([onclick*="showAddSkillModal"])').length : 0;
  const softSkills = allSkillContainers[1] ? allSkillContainers[1].querySelectorAll('.pill:not([onclick*="showAddSkillModal"])').length : 0;

  if (technicalSkills > 0) completedFields++;
  if (softSkills > 0) completedFields++;

  // Check work experience - find the experiences container more reliably
  const experienceSection = Array.from(document.querySelectorAll('.card')).find(card => {
    const heading = card.querySelector('h3');
    return heading && heading.textContent.includes('Werkervaring');
  });

  const experiences = experienceSection ? experienceSection.querySelectorAll('.space-y-4 > div[class*="border-l-2"]').length : 0;
  if (experiences > 0) completedFields++;

  console.log('Profile completeness calculation:', {
    basicFields: completedFields,
    technicalSkills,
    softSkills,
    experiences,
    totalCompleted: completedFields,
    percentage: Math.round((completedFields / totalFields) * 100)
  });

  return Math.round((completedFields / totalFields) * 100);
}

function updateProfileCompleteness() {
  const percentage = calculateProfileCompleteness();

  // Find the percentage text element (the span with font-medium class in the profile completeness section)
  const percentageElement = document.querySelector('.space-y-2 .font-medium');
  const progressBar = document.querySelector('.bg-brand-500');

  if (percentageElement) {
    percentageElement.textContent = percentage + '%';
  }

  if (progressBar) {
    progressBar.style.width = percentage + '%';
  }

  // Update the pill in header
  const pillElement = document.querySelector('.pill');
  if (pillElement) {
    pillElement.textContent = percentage + '% compleet';
  }

  console.log('Profile completeness updated to:', percentage + '%');
}

function savePhotoTransform() {
  // Save transform to localStorage
  const transformData = {
    scale: currentScale,
    translateX: currentTranslateX,
    translateY: currentTranslateY
  };
  localStorage.setItem('photoTransform', JSON.stringify(transformData));
}

function loadPhotoTransform() {
  // Load transform from localStorage
  const savedTransform = localStorage.getItem('photoTransform');
  if (savedTransform) {
    try {
      const transformData = JSON.parse(savedTransform);
      currentScale = transformData.scale || 1;
      currentTranslateX = transformData.translateX || 0;
      currentTranslateY = transformData.translateY || 0;
      updateImageTransform();
    } catch (e) {
      console.log('Could not load saved transform:', e);
    }
  }
}

function addResizeHandles() {
  if (!currentImage) return;

  // Remove existing handles
  const existingHandles = document.querySelectorAll('.resize-handle');
  existingHandles.forEach(handle => handle.remove());

  // Add resize handles
  const handles = ['nw', 'ne', 'sw', 'se'];
  handles.forEach(direction => {
    const handle = document.createElement('div');
    handle.className = `resize-handle resize-${direction}`;
    handle.style.cssText = `
      position: absolute;
      width: 20px;
      height: 20px;
      background: #3b82f6;
      border: 3px solid white;
      border-radius: 50%;
      cursor: ${direction === 'nw' || direction === 'se' ? 'nw-resize' : 'ne-resize'};
      z-index: 20;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    `;

    // Position handles
    switch(direction) {
      case 'nw': handle.style.top = '-10px'; handle.style.left = '-10px'; break;
      case 'ne': handle.style.top = '-10px'; handle.style.right = '-10px'; break;
      case 'sw': handle.style.bottom = '-10px'; handle.style.left = '-10px'; break;
      case 'se': handle.style.bottom = '-10px'; handle.style.right = '-10px'; break;
    }

    handle.addEventListener('mousedown', (e) => startResize(e, direction));
    handle.addEventListener('touchstart', (e) => startResize(e, direction));

    currentImage.parentElement.appendChild(handle);
  });
}

function startDrag(e) {
  if (e.target.classList.contains('resize-handle')) return;

  isDragging = true;
  const containerRect = currentImage.parentElement.getBoundingClientRect();

  startX = (e.clientX || e.touches[0].clientX) - containerRect.left;
  startY = (e.clientY || e.touches[0].clientY) - containerRect.top;

  document.addEventListener('mousemove', drag);
  document.addEventListener('mouseup', stopDrag);
  document.addEventListener('touchmove', drag);
  document.addEventListener('touchend', stopDrag);

  e.preventDefault();
}

function drag(e) {
  if (!isDragging) return;

  const containerRect = currentImage.parentElement.getBoundingClientRect();
  const mouseX = (e.clientX || e.touches[0].clientX) - containerRect.left;
  const mouseY = (e.clientY || e.touches[0].clientY) - containerRect.top;

  // Calculate translation relative to center
  const deltaX = (mouseX - startX) / currentScale;
  const deltaY = (mouseY - startY) / currentScale;

  // Update translation
  currentTranslateX += deltaX;
  currentTranslateY += deltaY;

  // Constrain to container bounds (accounting for scale)
  const maxTranslate = 100; // Increased for larger container
  currentTranslateX = Math.max(-maxTranslate, Math.min(maxTranslate, currentTranslateX));
  currentTranslateY = Math.max(-maxTranslate, Math.min(maxTranslate, currentTranslateY));

  updateImageTransform();
  savePhotoTransform(); // Save the transform

  startX = mouseX;
  startY = mouseY;
}

function stopDrag() {
  isDragging = false;
  document.removeEventListener('mousemove', drag);
  document.removeEventListener('mouseup', stopDrag);
  document.removeEventListener('touchmove', drag);
  document.removeEventListener('touchend', stopDrag);
}

function startResize(e, direction) {
  isResizing = true;
  const containerRect = currentImage.parentElement.getBoundingClientRect();

  startX = e.clientX || e.touches[0].clientX;
  startY = e.clientY || e.touches[0].clientY;

  document.addEventListener('mousemove', (e) => resize(e, direction));
  document.addEventListener('mouseup', stopResize);
  document.addEventListener('touchmove', (e) => resize(e, direction));
  document.addEventListener('touchend', stopResize);

  e.preventDefault();
  e.stopPropagation();
}

function resize(e, direction) {
  if (!isResizing) return;

  const containerRect = currentImage.parentElement.getBoundingClientRect();
  const centerX = containerRect.left + containerRect.width / 2;
  const centerY = containerRect.top + containerRect.height / 2;

  const mouseX = e.clientX || e.touches[0].clientX;
  const mouseY = e.clientY || e.touches[0].clientY;

  // Calculate distance from center
  const distanceX = Math.abs(mouseX - centerX);
  const distanceY = Math.abs(mouseY - centerY);
  const maxDistance = Math.max(distanceX, distanceY);

  // Calculate new scale based on distance from center
  const baseDistance = 200; // Base distance for scale 1 (half of 400px)
  const newScale = Math.max(0.1, Math.min(3, maxDistance / baseDistance));

  currentScale = newScale;
  updateImageTransform();
  savePhotoTransform(); // Save the transform
}

function stopResize() {
  isResizing = false;
  document.removeEventListener('mousemove', resize);
  document.removeEventListener('mouseup', stopResize);
  document.removeEventListener('touchmove', resize);
  document.removeEventListener('touchend', stopResize);
}

// Drag and drop functionality
function handleDragOver(e) {
  e.preventDefault();
  e.dataTransfer.dropEffect = 'copy';
}

function handleDragEnter(e) {
  e.preventDefault();
  document.getElementById('drag-overlay').classList.remove('hidden');
}

function handleDragLeave(e) {
  e.preventDefault();
  document.getElementById('drag-overlay').classList.add('hidden');
}

function handleDrop(e) {
  e.preventDefault();
  document.getElementById('drag-overlay').classList.add('hidden');

  const files = e.dataTransfer.files;
  if (files.length > 0) {
    const file = files[0];
    if (file.type.startsWith('image/')) {
      // Create a temporary file input and trigger upload
      const tempInput = document.createElement('input');
      tempInput.type = 'file';
      tempInput.files = files;
      uploadPhoto(tempInput);
    }
  }
}

// Profile form submission
document.getElementById('profile-form').addEventListener('submit', async function(e) {
  e.preventDefault();

  // Clear any existing error styling
  clearFieldErrors();

  // Frontend validation for required fields
  const requiredFields = [
    { name: 'first_name', label: 'Voornaam' },
    { name: 'last_name', label: 'Achternaam' },
    { name: 'email', label: 'E-mailadres' },
    { name: 'location', label: 'Locatie' }
  ];

  let hasErrors = false;
  const errorMessages = [];

  requiredFields.forEach(field => {
    const input = document.querySelector(`input[name="${field.name}"]`);
    if (!input || !input.value || input.value.trim() === '') {
      hasErrors = true;
      errorMessages.push(`${field.label} is verplicht`);

      // Highlight the field with error
      if (input) {
        input.classList.add('border-red-500', 'ring-2', 'ring-red-200');
        input.classList.remove('border-gray-300', 'dark:border-gray-600');
      }
    }
  });

  // Additional email validation
  const emailInput = document.querySelector('input[name="email"]');
  if (emailInput && emailInput.value && emailInput.value.trim() !== '') {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(emailInput.value)) {
      hasErrors = true;
      errorMessages.push('E-mailadres moet een geldig e-mailadres zijn');
      emailInput.classList.add('border-red-500', 'ring-2', 'ring-red-200');
      emailInput.classList.remove('border-gray-300', 'dark:border-gray-600');
    }
  }

  if (hasErrors) {
    showMessageModal('error', 'Validatie fout!', errorMessages.join(', '));

    // Focus on the first error field
    const firstErrorField = document.querySelector('.border-red-500');
    if (firstErrorField) {
      firstErrorField.focus();
    }

    return;
  }

  const formData = new FormData(this);

  try {
    const response = await fetch('{{ route("profile.update") }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      },
      body: formData
    });

    const data = await response.json();

    if (data.success) {
      // Update the profile display immediately
      updateProfileDisplay(formData);
      // Update profile completeness
      updateProfileCompleteness();
      showMessageModal('success', 'Succesvol!', data.message);
    } else {
      // Handle validation errors with field highlighting
      let errorMessage = 'Er is een fout opgetreden';
      let fieldWithError = null;

      if (data.errors) {
        const errorMessages = [];
        if (data.errors.first_name) {
          errorMessages.push('Voornaam: ' + data.errors.first_name[0]);
          fieldWithError = 'first_name';
        }
        if (data.errors.last_name) {
          errorMessages.push('Achternaam: ' + data.errors.last_name[0]);
          fieldWithError = 'last_name';
        }
        if (data.errors.email) {
          errorMessages.push('E-mailadres: ' + data.errors.email[0]);
          fieldWithError = 'email';
        }
        if (data.errors.phone) {
          errorMessages.push('Telefoonnummer: ' + data.errors.phone[0]);
          fieldWithError = 'phone';
        }
        if (data.errors.location) {
          errorMessages.push('Locatie: ' + data.errors.location[0]);
          fieldWithError = 'location';
        }
        if (data.errors.bio) {
          errorMessages.push('Bio: ' + data.errors.bio[0]);
          fieldWithError = 'bio';
        }
        if (data.errors.date_of_birth) {
          errorMessages.push('Geboortedatum: ' + data.errors.date_of_birth[0]);
          fieldWithError = 'date_of_birth';
        }

        if (errorMessages.length > 0) {
          errorMessage = errorMessages.join(', ');
        } else {
          const errorList = Object.values(data.errors).flat();
          errorMessage = errorList.join(', ');
        }
      } else if (data.message) {
        errorMessage = data.message;
      }

      showMessageModal('error', 'Validatie fout!', errorMessage);

      // Highlight the field with error and focus on it
      if (fieldWithError) {
        highlightFieldError(fieldWithError);
      }
    }
  } catch (error) {
    console.error('Error:', error);
    showMessageModal('error', 'Fout!', 'Er is een fout opgetreden bij het opslaan van het profiel.');
  }
});

// Update profile display function
function updateProfileDisplay(formData) {
  const firstName = formData.get('first_name');
  const lastName = formData.get('last_name');
  const bio = formData.get('bio');

  // Update the name in the profile overview
  const nameElement = document.querySelector('h3.font-semibold.text-lg.mb-1');
  if (nameElement) {
    nameElement.textContent = `${firstName} ${lastName}`;
  }

  // Update the bio in the profile overview
  const bioElement = document.querySelector('p.text-sm.text-muted.dark\\:text-muted-dark.mb-4');
  if (bioElement && bio) {
    bioElement.textContent = bio;
  }
}

// Photo upload
async function uploadPhoto(input) {
  if (!input.files[0]) return;

  const file = input.files[0];

  // Client-side validation
  const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
  if (!allowedTypes.includes(file.type)) {
    showMessageModal('error', 'Fout!', 'Alleen JPEG, PNG, JPG, GIF, WEBP en SVG bestanden zijn toegestaan.');
    input.value = '';
    return;
  }

  if (file.size > 5 * 1024 * 1024) { // 5MB
    showMessageModal('error', 'Fout!', 'De foto mag maximaal 5MB groot zijn.');
    input.value = '';
    return;
  }

  const formData = new FormData();
  formData.append('photo', file);

  try {
    console.log('Uploading file:', file.name, 'Size:', file.size, 'Type:', file.type);
    console.log('FormData contents:', Array.from(formData.entries()));

    const response = await fetch('{{ route("profile.photo") }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
        // Don't set Content-Type, let browser set it for FormData
      },
      body: formData
    });

    console.log('Response status:', response.status);
    const data = await response.json();
    console.log('Response data:', data);

    if (data.success) {
      // Update photo display immediately
      updatePhotoDisplay(data.photo_url);
      // Update profile completeness
      updateProfileCompleteness();
      showMessageModal('success', 'Succesvol!', data.message);

      // Force reinitialize the photo editor
      setTimeout(() => {
        initializePhotoEditor();
      }, 100);
    } else {
      console.error('Upload error:', data);
      showMessageModal('error', 'Fout!', 'Er is een fout opgetreden: ' + (data.message || 'Onbekende fout'));
    }
  } catch (error) {
    console.error('Upload error:', error);
    showMessageModal('error', 'Fout!', 'Er is een fout opgetreden bij het uploaden van de foto: ' + error.message);
  }
}

// Update photo display function
function updatePhotoDisplay(photoUrl) {
  console.log('Updating photo display with URL:', photoUrl);

  const photoContainer = document.getElementById('photo-container');
  if (photoContainer) {
    // Remove existing image and handles
    const existingImage = photoContainer.querySelector('#profile-image');
    const existingHandles = photoContainer.querySelectorAll('.resize-handle');

    if (existingImage) {
      existingImage.remove();
    }
    existingHandles.forEach(handle => handle.remove());

    // Create new image element
    const newImage = document.createElement('img');
    newImage.id = 'profile-image';
    // Add timestamp to break cache
    const separator = photoUrl.includes('?') ? '&' : '?';
    newImage.src = photoUrl + separator + 't=' + Date.now();
    newImage.alt = 'Profile Photo';
    newImage.className = 'absolute inset-0 w-full h-full object-cover cursor-move';
    newImage.draggable = false;
    
    // Add error handling for image load
    newImage.onerror = function() {
      console.error('Failed to load image:', photoUrl);
      // Remove the failed image and show default avatar
      newImage.remove();
      showDefaultAvatar();
    };
    
    newImage.onload = function() {
      console.log('Image loaded successfully:', photoUrl);
    };

    // Insert the new image
    photoContainer.insertBefore(newImage, photoContainer.firstChild);

    // Update current image reference
    currentImage = newImage;

    // Reset transform values for new image
    currentScale = 1;
    currentTranslateX = 0;
    currentTranslateY = 0;

    // Setup interactions for the new image
    setupImageInteractions();

    // Load saved transform for new image
    loadPhotoTransform();

    console.log('Photo display updated successfully');
  } else {
    console.error('Photo container not found');
  }
}

// Show default avatar when image fails to load
function showDefaultAvatar() {
  const photoContainer = document.getElementById('photo-container');
  if (photoContainer) {
    // Remove any existing image
    const existingImage = photoContainer.querySelector('#profile-image');
    if (existingImage) {
      existingImage.remove();
    }
    
    // Show default avatar (the fallback div should already be there)
    console.log('Showing default avatar');
  }
}

// Skill modals
function showAddSkillModal(type) {
  document.getElementById('skill-type').value = type;
  document.getElementById('skill-modal').classList.remove('hidden');
  document.getElementById('skill-modal').classList.add('flex');

  // Add ESC key listener
  document.addEventListener('keydown', handleSkillModalEsc);

  // Focus on the skill name input with multiple attempts
  const focusInput = () => {
    const skillNameInput = document.querySelector('#skill-modal input[name="name"]');
    if (skillNameInput) {
      skillNameInput.focus();
      skillNameInput.select();
      console.log('Focus set on skill name input');
    } else {
      console.log('Skill name input not found');
    }
  };

  // Try multiple times to ensure focus works
  setTimeout(focusInput, 100);
  setTimeout(focusInput, 300);
  setTimeout(focusInput, 500);
}

function handleSkillModalEsc(e) {
  if (e.key === 'Escape' || e.key === 'Enter') {
    hideAddSkillModal();
  }
}

function hideAddSkillModal() {
  document.getElementById('skill-modal').classList.add('hidden');
  document.getElementById('skill-modal').classList.remove('flex');
  document.getElementById('skill-form').reset();

  // Remove ESC key listener
  document.removeEventListener('keydown', handleSkillModalEsc);
}

document.getElementById('skill-form').addEventListener('submit', async function(e) {
  e.preventDefault();

  const formData = new FormData(this);

  try {
    const response = await fetch('{{ route("profile.skills.add") }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      },
      body: formData
    });

    const data = await response.json();

    if (data.success) {
      // Add skill to DOM without reload
      addSkillToDOM(data.skill);
      hideAddSkillModal();
      // Update profile completeness
      updateProfileCompleteness();
    } else {
      showMessageModal('error', 'Fout!', 'Er is een fout opgetreden: ' + (data.message || 'Onbekende fout'));
    }
  } catch (error) {
    console.error('Error:', error);
    showMessageModal('error', 'Fout!', 'Er is een fout opgetreden bij het toevoegen van de vaardigheid.');
  }
});

async function removeSkill(skillId) {
  showConfirmationModal(
    'Vaardigheid verwijderen',
    'Weet je zeker dat je deze vaardigheid wilt verwijderen?',
    async () => {
      try {
        const response = await fetch(`{{ route('profile.skills.remove', ':skillId') }}`.replace(':skillId', skillId), {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
          }
        });

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('Remove skill response:', data);

        if (data.success) {
          // Remove skill from DOM without reload
          removeSkillFromDOM(skillId);
          // Update profile completeness
          updateProfileCompleteness();
        } else {
          showMessageModal('error', 'Fout!', 'Er is een fout opgetreden: ' + (data.message || 'Onbekende fout'));
        }
      } catch (error) {
        console.error('Error removing skill:', error);
        showMessageModal('error', 'Fout!', 'Er is een fout opgetreden bij het verwijderen van de vaardigheid: ' + error.message);
      }
    }
  );
}

// Experience modals
let editingExperienceId = null;

function showAddExperienceModal() {
  editingExperienceId = null;
  document.getElementById('experience-modal-title').textContent = 'Werkervaring Toevoegen';

  // Reset button text to "Toevoegen" when adding new experience
  const submitButton = document.querySelector('#experience-modal button[type="submit"]');
  if (submitButton) {
    submitButton.textContent = 'Toevoegen';
    submitButton.classList.remove('min-w-[120px]');
    submitButton.classList.remove('flex-1');
  }

  // Ensure end date field is visible for new experiences
  const endDateInput = document.getElementById('end-date');
  const endDateContainer = endDateInput.closest('div');
  endDateContainer.style.display = 'block';
  endDateInput.disabled = false;

  // Prevent body scroll
  document.body.style.overflow = 'hidden';

  document.getElementById('experience-modal').classList.remove('hidden');
  document.getElementById('experience-modal').classList.add('flex');

  // Add ESC key listener
  document.addEventListener('keydown', handleExperienceModalEsc);

  // Focus on the job title input with multiple attempts
  const focusInput = () => {
    const jobTitleInput = document.querySelector('#experience-modal input[name="title"]');
    if (jobTitleInput) {
      jobTitleInput.focus();
      jobTitleInput.select();
      console.log('Focus set on job title input');
    } else {
      console.log('Job title input not found');
    }
  };

  // Try multiple times to ensure focus works
  setTimeout(focusInput, 100);
  setTimeout(focusInput, 300);
  setTimeout(focusInput, 500);
}

function editExperience(experienceId) {
  editingExperienceId = experienceId;
  document.getElementById('experience-modal-title').textContent = 'Werkervaring Bewerken';

  // Change button text to "Opslaan" when editing
  const submitButton = document.querySelector('#experience-modal button[type="submit"]');
  if (submitButton) {
    submitButton.textContent = 'Opslaan';
    submitButton.classList.add('min-w-[120px]');
    submitButton.classList.remove('flex-1');
  }

  // Prevent body scroll
  document.body.style.overflow = 'hidden';

  document.getElementById('experience-modal').classList.remove('hidden');
  document.getElementById('experience-modal').classList.add('flex');

  // Add ESC key listener
  document.addEventListener('keydown', handleExperienceModalEsc);

  // Load experience data into form
  loadExperienceData(experienceId);
}

function handleExperienceModalEsc(e) {
  if (e.key === 'Escape' || e.key === 'Enter') {
    hideAddExperienceModal();
  }
}

function hideAddExperienceModal() {
  // Restore body scroll
  document.body.style.overflow = '';

  document.getElementById('experience-modal').classList.add('hidden');
  document.getElementById('experience-modal').classList.remove('flex');
  document.getElementById('experience-form').reset();
  editingExperienceId = null;

  // Remove ESC key listener
  document.removeEventListener('keydown', handleExperienceModalEsc);
}

async function loadExperienceData(experienceId) {
  try {
    // Fetch fresh data from the server
    const response = await fetch(`{{ route('profile.experiences.show', ':id') }}`.replace(':id', experienceId), {
      method: 'GET',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      const data = await response.json();
      if (data.success) {
        const experience = data.experience;

        // Fill all form fields
        document.querySelector('#experience-modal input[name="title"]').value = experience.title || '';
        document.querySelector('#experience-modal input[name="company"]').value = experience.company || '';
        document.querySelector('#experience-modal input[name="start_date"]').value = experience.start_date || '';
        document.querySelector('#experience-modal input[name="end_date"]').value = experience.end_date || '';
        document.querySelector('#experience-modal textarea[name="description"]').value = experience.description || '';

        // Handle current job checkbox
        const currentCheckbox = document.getElementById('current-job');
        if (experience.current) {
          currentCheckbox.checked = true;
          toggleEndDate(); // Hide end date field when current job is checked
        } else {
          currentCheckbox.checked = false;
          toggleEndDate(); // Show end date field when current job is NOT checked
        }

        console.log('Loaded experience data:', experience);
      }
    } else {
      console.error('Failed to load experience data');
    }
  } catch (error) {
    console.error('Error loading experience data:', error);
    // Fallback: try to extract from DOM
    const experienceElement = document.querySelector(`button[onclick="editExperience(${experienceId})"]`)?.closest('div[class*="border-l-2"]');

    if (experienceElement) {
      const titleElement = experienceElement.querySelector('h4');
      if (titleElement) {
        document.querySelector('#experience-modal input[name="title"]').value = titleElement.textContent;
      }
    }
  }
}

function toggleEndDate() {
  const endDateInput = document.getElementById('end-date');
  const currentJobCheckbox = document.getElementById('current-job');
  const endDateContainer = endDateInput.closest('div');

  if (currentJobCheckbox.checked) {
    // Hide the end date field completely when "Huidige functie" is checked
    endDateContainer.style.display = 'none';
    endDateInput.value = '';
    endDateInput.disabled = true;
  } else {
    // Show the end date field when "Huidige functie" is NOT checked
    endDateContainer.style.display = 'block';
    endDateInput.disabled = false;
  }
}

document.getElementById('experience-form').addEventListener('submit', async function(e) {
  e.preventDefault();

  // Get form element and create FormData from it
  const form = document.getElementById('experience-form');
  const formData = new FormData(form);

  // Ensure current checkbox is properly handled
  const currentCheckbox = document.getElementById('current-job');
  if (currentCheckbox && currentCheckbox.checked) {
    formData.set('current', '1');
  } else {
    formData.set('current', '0');
  }

  // Debug: Log all form inputs
  console.log('All form inputs:');
  const inputs = form.querySelectorAll('input, textarea, select');
  inputs.forEach(input => {
    console.log(`${input.name}: "${input.value}"`);
  });

  // Special debug for birth date
  const birthDateInput = document.getElementById('birth-date-input');
  if (birthDateInput) {
    console.log('Birth date input value:', birthDateInput.value);
  }

  // Debug: Log FormData
  console.log('FormData contents:');
  for (let [key, value] of formData.entries()) {
    console.log(`${key}: "${value}"`);
  }

  try {
    let url, method;

    if (editingExperienceId) {
      // Editing existing experience
      url = `{{ route('profile.experiences.update', ':id') }}`.replace(':id', editingExperienceId);
      method = 'PUT';
    } else {
      // Adding new experience
      url = '{{ route("profile.experiences.add") }}';
      method = 'POST';
    }

    // Try JSON approach first
    const jsonData = {
      title: formData.get('title'),
      company: formData.get('company'),
      start_date: formData.get('start_date'),
      end_date: formData.get('end_date'),
      description: formData.get('description'),
      current: formData.get('current'),
      _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    };

    console.log('JSON data being sent:', jsonData);

    const response = await fetch(url, {
      method: method,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(jsonData)
    });

    // Debug: Log response
    console.log('Response status:', response.status);
    const responseText = await response.text();
    console.log('Response text:', responseText);

    let data;
    try {
      data = JSON.parse(responseText);
    } catch (e) {
      console.error('Failed to parse JSON response:', e);
      showMessageModal('error', 'Fout!', 'Er is een fout opgetreden bij het verwerken van de response.');
      return;
    }

    if (data.success) {
      if (editingExperienceId) {
        // Update existing experience in DOM
        updateExperienceInDOM(data.experience);
      } else {
        // Add new experience to DOM
        addExperienceToDOM(data.experience);
      }
      hideAddExperienceModal();
      // Update profile completeness
      updateProfileCompleteness();
    } else {
      // Handle validation errors more gracefully
      let errorMessage = 'Er is een fout opgetreden';
      let fieldWithError = null;

      if (data.errors) {
        // Format validation errors with field names
        const errorMessages = [];
        if (data.errors.first_name) {
          errorMessages.push('Voornaam: ' + data.errors.first_name[0]);
          fieldWithError = 'first_name';
        }
        if (data.errors.last_name) {
          errorMessages.push('Achternaam: ' + data.errors.last_name[0]);
          fieldWithError = 'last_name';
        }
        if (data.errors.email) {
          errorMessages.push('E-mailadres: ' + data.errors.email[0]);
          fieldWithError = 'email';
        }
        if (data.errors.phone) {
          errorMessages.push('Telefoonnummer: ' + data.errors.phone[0]);
          fieldWithError = 'phone';
        }
        if (data.errors.location) {
          errorMessages.push('Locatie: ' + data.errors.location[0]);
          fieldWithError = 'location';
        }
        if (data.errors.bio) {
          errorMessages.push('Bio: ' + data.errors.bio[0]);
          fieldWithError = 'bio';
        }
        if (data.errors.date_of_birth) {
          errorMessages.push('Geboortedatum: ' + data.errors.date_of_birth[0]);
          fieldWithError = 'date_of_birth';
        }

        // Fallback to original errors if no specific mapping
        if (errorMessages.length === 0) {
          const errorList = Object.values(data.errors).flat();
          errorMessages.push(...errorList);
        }

        errorMessage = errorMessages.join(', ');
      } else if (data.message) {
        errorMessage = data.message;
      }

      showMessageModal('error', 'Validatie fout!', errorMessage);

      // Highlight the field with error and focus on it
      if (fieldWithError) {
        highlightFieldError(fieldWithError);
      }
    }
  } catch (error) {
    console.error('Error:', error);
    showMessageModal('error', 'Fout!', 'Er is een fout opgetreden bij het opslaan van de werkervaring.');
  }
});

async function removeExperience(experienceId) {
  showConfirmationModal(
    'Werkervaring verwijderen',
    'Weet je zeker dat je deze werkervaring wilt verwijderen?',
    async () => {
      try {
        const response = await fetch(`{{ route('profile.experiences.remove', ':experienceId') }}`.replace(':experienceId', experienceId), {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
          }
        });

        const data = await response.json();

        if (data.success) {
          // Remove experience from DOM without reload
          removeExperienceFromDOM(experienceId);
          // Update profile completeness
          updateProfileCompleteness();
        } else {
          showMessageModal('error', 'Fout!', 'Er is een fout opgetreden: ' + (data.message || 'Onbekende fout'));
        }
      } catch (error) {
        console.error('Error:', error);
        showMessageModal('error', 'Fout!', 'Er is een fout opgetreden bij het verwijderen van de werkervaring.');
      }
    }
  );
}
</script>

<!-- CV Remove Confirmation Modal -->
<div id="cv-remove-modal" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-50">
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4 relative border border-gray-200 dark:border-gray-700 shadow-xl">
        <button onclick="hideCVRemoveModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/20 mb-4">
                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>

            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                CV verwijderen
            </h3>

            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Weet je zeker dat je het CV wilt verwijderen? Deze actie kan niet ongedaan worden gemaakt.
            </p>

            <div class="flex space-x-3">
                <button onclick="hideCVRemoveModal()" class="flex-1 px-4 py-2 font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md transition-colors">
                    Annuleren
                </button>
                <button onclick="confirmCVRemove()" class="flex-1 px-4 py-2 font-medium text-white !bg-red-600 hover:!bg-red-700 rounded-md transition-colors" style="background-color: #dc2626 !important;">
                    Verwijderen
                </button>
      </div>
    </div>
  </div>
</div>
@endsection
