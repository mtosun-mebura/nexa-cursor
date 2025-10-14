@extends('frontend.layouts.dashboard')

@section('title', 'Instellingen - NEXA Skillmatching')

@section('content')
<section class="flex flex-wrap items-center justify-between gap-3">
  <div>
    <h1 class="text-2xl font-semibold leading-tight">Instellingen</h1>
    <p class="text-sm text-muted dark:text-muted-dark">Beheer je account instellingen en voorkeuren.</p>
  </div>
</section>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
  <!-- Account Settings -->
  <div class="space-y-6">
    <div class="card p-6">
      <h3 class="font-semibold text-lg mb-4">Account Instellingen</h3>

      <form id="account-form" class="space-y-4">
        @csrf
        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">E-mailadres</label>
          <input type="email" name="email" class="input mt-1" value="{{ Auth::user()->email ?? '' }}" placeholder="E-mailadres" required>
        </div>


        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Huidig wachtwoord</label>
          <input type="password" name="current_password" class="input mt-1" placeholder="Huidig wachtwoord">
        </div>

        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Nieuw wachtwoord</label>
          <input type="password" name="password" class="input mt-1" placeholder="Nieuw wachtwoord">
          <p class="text-xs text-muted dark:text-muted-dark mt-1">
            Minimaal 8 karakters, een combinatie van hoofdletters en kleine letters, minimaal één cijfer en één speciaal karakter.
          </p>
        </div>

        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Bevestig nieuw wachtwoord</label>
          <input type="password" name="password_confirmation" class="input mt-1" placeholder="Bevestig nieuw wachtwoord">
        </div>

        <button type="submit" class="btn btn-primary">Opslaan</button>
      </form>
    </div>


    <!-- Notification Settings -->
    <div class="card p-6">
      <h3 class="font-semibold text-lg mb-4">Notificaties</h3>
      <div class="space-y-4">
        <form id="notification-form" class="space-y-4">
          @csrf
          <div class="flex items-center justify-between">
            <div>
              <h4 class="font-medium">E-mail notificaties</h4>
              <p class="text-sm text-muted dark:text-muted-dark">Ontvang updates over nieuwe matches</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" name="email_notifications" class="sr-only peer" {{ Auth::user()->email_notifications ? 'checked' : '' }}>
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-500"></div>
            </label>
          </div>

          <div class="flex items-center justify-between">
            <div>
              <h4 class="font-medium">SMS notificaties</h4>
              <p class="text-sm text-muted dark:text-muted-dark">Ontvang belangrijke updates via SMS</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" name="sms_notifications" class="sr-only peer" {{ Auth::user()->sms_notifications ? 'checked' : '' }}>
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-500"></div>
            </label>
          </div>

          <div class="flex items-center justify-between">
            <div>
              <h4 class="font-medium">Push notificaties</h4>
              <p class="text-sm text-muted dark:text-muted-dark">Ontvang notificaties in je browser</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" name="push_notifications" class="sr-only peer" {{ Auth::user()->push_notifications ? 'checked' : '' }}>
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-500"></div>
            </label>
          </div>

        </form>
      </div>
    </div>

    <!-- Data Management -->
    <div class="card p-6">
      <h3 class="font-semibold text-lg mb-4">Gegevens Beheer</h3>
      <div class="space-y-4">
          <div>
              <h4 class="font-medium">Gegevens exporteren</h4>
              <p class="text-sm text-muted dark:text-muted-dark mb-3">Download een kopie van al je gegevens (GDPR).</p>
              <button id="export-data-btn" class="btn btn-outline">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                  Gegevens exporteren
              </button>
          </div>
      </div>
    </div>
  </div>

  <!-- Preferences -->
  <div class="space-y-6">
    <div class="card p-6">
      <h3 class="font-semibold text-lg mb-4">Job Voorkeuren</h3>
      <form id="job-preferences-form" class="space-y-4">
        @csrf
        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Gewenste locatie</label>
          <select name="preferred_location" class="select mt-1">
            <option value="">Selecteer locatie</option>
            <option value="Alkmaar" {{ Auth::user()->preferred_location == 'Alkmaar' ? 'selected' : '' }}>Alkmaar</option>
            <option value="Almelo" {{ Auth::user()->preferred_location == 'Almelo' ? 'selected' : '' }}>Almelo</option>
            <option value="Almere" {{ Auth::user()->preferred_location == 'Almere' ? 'selected' : '' }}>Almere</option>
            <option value="Alphen aan den Rijn" {{ Auth::user()->preferred_location == 'Alphen aan den Rijn' ? 'selected' : '' }}>Alphen aan den Rijn</option>
            <option value="Amersfoort" {{ Auth::user()->preferred_location == 'Amersfoort' ? 'selected' : '' }}>Amersfoort</option>
            <option value="Amstelveen" {{ Auth::user()->preferred_location == 'Amstelveen' ? 'selected' : '' }}>Amstelveen</option>
            <option value="Amsterdam" {{ Auth::user()->preferred_location == 'Amsterdam' ? 'selected' : '' }}>Amsterdam</option>
            <option value="Apeldoorn" {{ Auth::user()->preferred_location == 'Apeldoorn' ? 'selected' : '' }}>Apeldoorn</option>
            <option value="Arnhem" {{ Auth::user()->preferred_location == 'Arnhem' ? 'selected' : '' }}>Arnhem</option>
            <option value="Assen" {{ Auth::user()->preferred_location == 'Assen' ? 'selected' : '' }}>Assen</option>
            <option value="Breda" {{ Auth::user()->preferred_location == 'Breda' ? 'selected' : '' }}>Breda</option>
            <option value="Delft" {{ Auth::user()->preferred_location == 'Delft' ? 'selected' : '' }}>Delft</option>
            <option value="Den Haag" {{ Auth::user()->preferred_location == 'Den Haag' ? 'selected' : '' }}>Den Haag</option>
            <option value="Deventer" {{ Auth::user()->preferred_location == 'Deventer' ? 'selected' : '' }}>Deventer</option>
            <option value="Dordrecht" {{ Auth::user()->preferred_location == 'Dordrecht' ? 'selected' : '' }}>Dordrecht</option>
            <option value="Eindhoven" {{ Auth::user()->preferred_location == 'Eindhoven' ? 'selected' : '' }}>Eindhoven</option>
            <option value="Emmen" {{ Auth::user()->preferred_location == 'Emmen' ? 'selected' : '' }}>Emmen</option>
            <option value="Enschede" {{ Auth::user()->preferred_location == 'Enschede' ? 'selected' : '' }}>Enschede</option>
            <option value="Groningen" {{ Auth::user()->preferred_location == 'Groningen' ? 'selected' : '' }}>Groningen</option>
            <option value="Haarlem" {{ Auth::user()->preferred_location == 'Haarlem' ? 'selected' : '' }}>Haarlem</option>
            <option value="Helmond" {{ Auth::user()->preferred_location == 'Helmond' ? 'selected' : '' }}>Helmond</option>
            <option value="Hengelo" {{ Auth::user()->preferred_location == 'Hengelo' ? 'selected' : '' }}>Hengelo</option>
            <option value="Heerlen" {{ Auth::user()->preferred_location == 'Heerlen' ? 'selected' : '' }}>Heerlen</option>
            <option value="Hilversum" {{ Auth::user()->preferred_location == 'Hilversum' ? 'selected' : '' }}>Hilversum</option>
            <option value="Hoofddorp" {{ Auth::user()->preferred_location == 'Hoofddorp' ? 'selected' : '' }}>Hoofddorp</option>
            <option value="Hybride" {{ Auth::user()->preferred_location == 'Hybride' ? 'selected' : '' }}>Hybride</option>
            <option value="Katwijk" {{ Auth::user()->preferred_location == 'Katwijk' ? 'selected' : '' }}>Katwijk</option>
            <option value="Leeuwarden" {{ Auth::user()->preferred_location == 'Leeuwarden' ? 'selected' : '' }}>Leeuwarden</option>
            <option value="Leiden" {{ Auth::user()->preferred_location == 'Leiden' ? 'selected' : '' }}>Leiden</option>
            <option value="Lelystad" {{ Auth::user()->preferred_location == 'Lelystad' ? 'selected' : '' }}>Lelystad</option>
            <option value="Maastricht" {{ Auth::user()->preferred_location == 'Maastricht' ? 'selected' : '' }}>Maastricht</option>
            <option value="Nieuwegein" {{ Auth::user()->preferred_location == 'Nieuwegein' ? 'selected' : '' }}>Nieuwegein</option>
            <option value="Nijmegen" {{ Auth::user()->preferred_location == 'Nijmegen' ? 'selected' : '' }}>Nijmegen</option>
            <option value="Oss" {{ Auth::user()->preferred_location == 'Oss' ? 'selected' : '' }}>Oss</option>
            <option value="Purmerend" {{ Auth::user()->preferred_location == 'Purmerend' ? 'selected' : '' }}>Purmerend</option>
            <option value="Remote" {{ Auth::user()->preferred_location == 'Remote' ? 'selected' : '' }}>Remote</option>
            <option value="Roosendaal" {{ Auth::user()->preferred_location == 'Roosendaal' ? 'selected' : '' }}>Roosendaal</option>
            <option value="Rotterdam" {{ Auth::user()->preferred_location == 'Rotterdam' ? 'selected' : '' }}>Rotterdam</option>
            <option value="'s-Hertogenbosch" {{ Auth::user()->preferred_location == "'s-Hertogenbosch" ? 'selected' : '' }}>'s-Hertogenbosch</option>
            <option value="Schiedam" {{ Auth::user()->preferred_location == 'Schiedam' ? 'selected' : '' }}>Schiedam</option>
            <option value="Spijkenisse" {{ Auth::user()->preferred_location == 'Spijkenisse' ? 'selected' : '' }}>Spijkenisse</option>
            <option value="Tilburg" {{ Auth::user()->preferred_location == 'Tilburg' ? 'selected' : '' }}>Tilburg</option>
            <option value="Utrecht" {{ Auth::user()->preferred_location == 'Utrecht' ? 'selected' : '' }}>Utrecht</option>
            <option value="Veenendaal" {{ Auth::user()->preferred_location == 'Veenendaal' ? 'selected' : '' }}>Veenendaal</option>
            <option value="Venlo" {{ Auth::user()->preferred_location == 'Venlo' ? 'selected' : '' }}>Venlo</option>
            <option value="Vlaardingen" {{ Auth::user()->preferred_location == 'Vlaardingen' ? 'selected' : '' }}>Vlaardingen</option>
            <option value="Zeist" {{ Auth::user()->preferred_location == 'Zeist' ? 'selected' : '' }}>Zeist</option>
            <option value="Zoetermeer" {{ Auth::user()->preferred_location == 'Zoetermeer' ? 'selected' : '' }}>Zoetermeer</option>
            <option value="Zwolle" {{ Auth::user()->preferred_location == 'Zwolle' ? 'selected' : '' }}>Zwolle</option>
          </select>
        </div>

        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Maximale afstand</label>
          <select name="max_distance" class="select mt-1">
            <option value="">Alle afstanden</option>
            <option value="5" {{ Auth::user()->max_distance == 5 ? 'selected' : '' }}>Binnen 5 km</option>
            <option value="10" {{ Auth::user()->max_distance == 10 ? 'selected' : '' }}>Binnen 10 km</option>
            <option value="25" {{ Auth::user()->max_distance == 25 ? 'selected' : '' }}>Binnen 25 km</option>
            <option value="50" {{ Auth::user()->max_distance == 50 ? 'selected' : '' }}>Binnen 50 km</option>
            <option value="100" {{ Auth::user()->max_distance == 100 ? 'selected' : '' }}>Binnen 100 km</option>
          </select>
        </div>

        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Contract type</label>
          <select name="contract_type" class="select mt-1">
            <option value="">Selecteer contract type</option>
            <option value="Vast" {{ Auth::user()->contract_type == 'Vast' ? 'selected' : '' }}>Vast</option>
            <option value="Tijdelijk" {{ Auth::user()->contract_type == 'Tijdelijk' ? 'selected' : '' }}>Tijdelijk</option>
            <option value="ZZP" {{ Auth::user()->contract_type == 'ZZP' ? 'selected' : '' }}>ZZP</option>
            <option value="Stage" {{ Auth::user()->contract_type == 'Stage' ? 'selected' : '' }}>Stage</option>
            <option value="Traineeship" {{ Auth::user()->contract_type == 'Traineeship' ? 'selected' : '' }}>Traineeship</option>
          </select>
        </div>

        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Werkuren</label>
          <select name="work_hours" class="select mt-1">
            <option value="">Selecteer werkuren</option>
            <option value="Fulltime (40 uur)" {{ Auth::user()->work_hours == 'Fulltime (40 uur)' ? 'selected' : '' }}>Fulltime (40 uur)</option>
            <option value="Parttime (36 uur)" {{ Auth::user()->work_hours == 'Parttime (36 uur)' ? 'selected' : '' }}>Parttime (36 uur)</option>
            <option value="Parttime (32 uur)" {{ Auth::user()->work_hours == 'Parttime (32 uur)' ? 'selected' : '' }}>Parttime (32 uur)</option>
            <option value="Parttime (24 uur)" {{ Auth::user()->work_hours == 'Parttime (24 uur)' ? 'selected' : '' }}>Parttime (24 uur)</option>
            <option value="Parttime (16 uur)" {{ Auth::user()->work_hours == 'Parttime (16 uur)' ? 'selected' : '' }}>Parttime (16 uur)</option>
            <option value="Parttime (8 uur)" {{ Auth::user()->work_hours == 'Parttime (8 uur)' ? 'selected' : '' }}>Parttime (8 uur)</option>
          </select>
        </div>

        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Minimaal salaris / bruto uurtarief</label>
          <div class="relative mt-1">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium z-10 pointer-events-none" style="line-height: 1; padding-top: 14px; padding-left: 10px;">€</span>
            <input type="number" name="min_salary" class="input pl-8 pr-3" placeholder="4000" min="0" step="1" value="{{ Auth::user()->min_salary }}" style="padding-left: 1.4rem !important;">
          </div>
        </div>

        <button type="submit" class="btn btn-primary">Voorkeuren opslaan</button>
      </form>
    </div>

    <!-- Privacy Settings -->
    <div class="card p-6">
      <h3 class="font-semibold text-lg mb-4">Privacy</h3>
      <form id="privacy-form" class="space-y-4">
        @csrf
        <div class="flex items-center justify-between">
          <div>
            <h4 class="font-medium">Profiel zichtbaar</h4>
            <p class="text-sm text-muted dark:text-muted-dark">Maak je profiel zichtbaar voor werkgevers</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="profile_visible" class="sr-only peer" {{ Auth::user()->profile_visible ? 'checked' : '' }}>
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-500"></div>
          </label>
        </div>

        <div class="flex items-center justify-between">
          <div>
            <h4 class="font-medium">CV downloadbaar</h4>
            <p class="text-sm text-muted dark:text-muted-dark">Sta werkgevers toe je CV te downloaden</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="cv_downloadable" class="sr-only peer" {{ Auth::user()->cv_downloadable ? 'checked' : '' }}>
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-500"></div>
          </label>
        </div>
      </form>
    </div>

    <!-- Danger Zone -->
    <div class="card p-6 border-red-200 dark:border-red-800">
      <h3 class="font-semibold text-lg mb-4 text-red-600 dark:text-red-400">Gevaarlijke Zone</h3>
      <div class="space-y-4">
        <div>
          <h4 class="font-medium text-red-600 dark:text-red-400">Account verwijderen</h4>
          <p class="text-sm text-muted dark:text-muted-dark mb-3">Verwijder permanent je account en alle bijbehorende gegevens.</p>
          <button id="delete-account-btn" class="btn btn-outline border-red-300 text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Account verwijderen
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Message Modal -->
<div id="message-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4 relative">
    <button onclick="hideMessageModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
    <div class="flex items-center mb-4">
      <div id="message-icon" class="w-8 h-8 rounded-full flex items-center justify-center mr-3">
        <!-- Icon will be inserted here -->
      </div>
      <h3 id="message-title" class="text-lg font-semibold"></h3>
    </div>
    <p id="message-text" class="text-muted dark:text-muted-dark mb-4"></p>
    <button onclick="hideMessageModal()" class="btn btn-primary w-full flex items-center justify-center">Sluiten</button>
  </div>
</div>

<!-- Delete Account Confirmation Modal -->
<div id="delete-account-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4 relative">
    <div class="flex items-center mb-4">
      <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/20 flex items-center justify-center mr-4">
        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
        </svg>
      </div>
      <div>
        <h3 class="text-lg font-semibold text-red-600 dark:text-red-400">Account Verwijderen</h3>
        <p class="text-sm text-muted dark:text-muted-dark">Deze actie kan niet ongedaan worden gemaakt</p>
      </div>
    </div>
    
    <div class="mb-6">
      <p class="text-muted dark:text-muted-dark mb-4">
        Weet je zeker dat je je account wilt verwijderen? Deze actie is <strong>permanent</strong> en kan niet ongedaan worden gemaakt.
      </p>
      
      <div class="bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-4">
        <h4 class="font-medium text-red-800 dark:text-red-200 mb-2">Wat wordt er verwijderd:</h4>
        <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
          <li>• Je profiel en alle persoonlijke gegevens</li>
          <li>• Je CV en geüploade bestanden</li>
          <li>• Je job voorkeuren en instellingen</li>
          <li>• Je favoriete vacatures</li>
          <li>• Je match geschiedenis</li>
        </ul>
      </div>
      
      <div class="mb-4">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">
          Typ "VERWIJDER" om te bevestigen:
        </label>
        <input type="text" id="delete-confirmation-input" class="input w-full" placeholder="VERWIJDER">
      </div>
    </div>
    
    <div class="flex space-x-3">
      <button onclick="hideDeleteAccountModal()" class="btn btn-outline flex-1">
        Annuleren
      </button>
      <button id="confirm-delete-btn" class="btn bg-red-600 hover:bg-red-700 text-white flex-1" disabled>
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
        </svg>
        Account Verwijderen
      </button>
    </div>
  </div>
</div>

<script>
// Message Modal Functions
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
    icon.innerHTML = `
      <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
      </svg>
    `;
    icon.className = 'w-8 h-8 rounded-full flex items-center justify-center mr-3 bg-green-500';
  } else {
    icon.innerHTML = `
      <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
      </svg>
    `;
    icon.className = 'w-8 h-8 rounded-full flex items-center justify-center mr-3 bg-red-500';
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

// Delete Account Modal Functions
function showDeleteAccountModal() {
  const modal = document.getElementById('delete-account-modal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  
  // Reset confirmation input
  document.getElementById('delete-confirmation-input').value = '';
  document.getElementById('confirm-delete-btn').disabled = true;
}

function hideDeleteAccountModal() {
  const modal = document.getElementById('delete-account-modal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}

function handleMessageModalEsc(e) {
  if (e.key === 'Escape' || e.key === 'Enter') {
    hideMessageModal();
  }
}

// Password validation function
function validatePassword(password) {
  const errors = [];

  // Check minimum length
  if (password.length < 8) {
    errors.push('minimaal 8 karakters');
  }

  // Check for uppercase letter
  if (!/[A-Z]/.test(password)) {
    errors.push('minimaal één hoofdletter');
  }

  // Check for lowercase letter
  if (!/[a-z]/.test(password)) {
    errors.push('minimaal één kleine letter');
  }

  // Check for digit
  if (!/\d/.test(password)) {
    errors.push('minimaal één cijfer');
  }

  // Check for special character
  if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
    errors.push('minimaal één speciaal karakter');
  }

  if (errors.length > 0) {
    return {
      isValid: false,
      message: `Wachtwoord moet bevatten: ${errors.join(', ')}.`
    };
  }

  return { isValid: true };
}

// Account Form Handler
document.getElementById('account-form').addEventListener('submit', async function(e) {
  e.preventDefault();

  const formData = new FormData(this);

  // Check if password fields are filled
  const currentPassword = formData.get('current_password');
  const newPassword = formData.get('password');
  const confirmPassword = formData.get('password_confirmation');

  let hasPasswordChange = currentPassword || newPassword || confirmPassword;

  // If password fields are filled, validate them
  if (hasPasswordChange) {
    if (!currentPassword) {
      showMessageModal('error', 'Fout!', 'Huidig wachtwoord is verplicht om het wachtwoord te wijzigen.');
      return;
    }
    if (!newPassword) {
      showMessageModal('error', 'Fout!', 'Nieuw wachtwoord is verplicht.');
      return;
    }
    if (newPassword !== confirmPassword) {
      showMessageModal('error', 'Fout!', 'Wachtwoord bevestiging komt niet overeen.');
      return;
    }
    // Validate password strength
    const passwordValidation = validatePassword(newPassword);
    if (!passwordValidation.isValid) {
      showMessageModal('error', 'Fout!', passwordValidation.message);
      return;
    }
  }

  try {
    // Update email first
    const emailResponse = await fetch('{{ route("settings.email") }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: new FormData(document.getElementById('account-form'))
    });

    const emailData = await emailResponse.json();

    if (!emailData.success) {
      showMessageModal('error', 'Fout!', emailData.message);
      return;
    }

    // Update password if provided
    if (hasPasswordChange) {
      const passwordResponse = await fetch('{{ route("settings.password") }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: new FormData(document.getElementById('account-form'))
      });

      const passwordData = await passwordResponse.json();

      if (!passwordData.success) {
        showMessageModal('error', 'Fout!', passwordData.message);
        return;
      }
    }

    // Success message
    const message = hasPasswordChange ? 'Account instellingen en wachtwoord succesvol gewijzigd!' : 'Account instellingen succesvol gewijzigd!';
    showMessageModal('success', 'Succesvol!', message);

    // Clear password fields
    document.querySelector('input[name="current_password"]').value = '';
    document.querySelector('input[name="password"]').value = '';
    document.querySelector('input[name="password_confirmation"]').value = '';

  } catch (error) {
    showMessageModal('error', 'Fout!', 'Er is een fout opgetreden bij het opslaan van de instellingen.');
  }
});

// Account Form Handler
document.getElementById('account-form').addEventListener('submit', async function(e) {
  e.preventDefault();

  const formData = new FormData(this);
  const submitButton = this.querySelector('button[type="submit"]');

  // Disable submit button to prevent double submission
  submitButton.disabled = true;
  submitButton.textContent = 'Opslaan...';

  try {
    // Determine which endpoint to use based on filled fields
    let endpoint = '';
    if (formData.get('current_password') && formData.get('password')) {
      endpoint = '{{ route("settings.password") }}';
    } else if (formData.get('email')) {
      endpoint = '{{ route("settings.email") }}';
    } else {
      showMessageModal('error', 'Fout!', 'Vul tenminste één veld in om op te slaan.');
      return;
    }

    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams(formData)
    });

    const data = await response.json();

    if (data.success) {
      showMessageModal('success', 'Succesvol!', data.message);
      // Clear password fields on success
      if (endpoint.includes('password')) {
        this.querySelector('input[name="current_password"]').value = '';
        this.querySelector('input[name="password"]').value = '';
        this.querySelector('input[name="password_confirmation"]').value = '';
      }
    } else {
      const errorMessage = typeof data.message === 'string' ? data.message : JSON.stringify(data.message);
      showMessageModal('error', 'Fout!', errorMessage || 'Er is een onbekende fout opgetreden.');
    }

  } catch (error) {
    console.error('Account update error:', error);
    showMessageModal('error', 'Fout!', 'Er is een fout opgetreden bij het opslaan van de account instellingen.');
  } finally {
    // Re-enable submit button
    submitButton.disabled = false;
    submitButton.textContent = 'Opslaan';
  }
});

// Job Preferences Form Handler
document.getElementById('job-preferences-form').addEventListener('submit', async function(e) {
  e.preventDefault();

  const formData = new FormData(this);
  const submitButton = this.querySelector('button[type="submit"]');

  // Disable submit button to prevent double submission
  submitButton.disabled = true;
  submitButton.textContent = 'Opslaan...';

  try {
    const response = await fetch('{{ route("settings.job-preferences") }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams(formData)
    });

    const data = await response.json();

    if (data.success) {
      showMessageModal('success', 'Succesvol!', data.message);
    } else {
      const errorMessage = typeof data.message === 'string' ? data.message : JSON.stringify(data.message);
      showMessageModal('error', 'Fout!', errorMessage || 'Er is een onbekende fout opgetreden.');
    }

  } catch (error) {
    console.error('Job preferences error:', error);
    showMessageModal('error', 'Fout!', 'Er is een fout opgetreden bij het opslaan van de job voorkeuren.');
  } finally {
    // Re-enable submit button
    submitButton.disabled = false;
    submitButton.textContent = 'Voorkeuren opslaan';
  }
});

// Auto-save notification preferences when checkboxes change
document.querySelectorAll('input[name="email_notifications"], input[name="sms_notifications"], input[name="push_notifications"]').forEach(checkbox => {
  // Ensure correct initial color based on checked state (override with inline style)
  const track = checkbox.closest('label')?.querySelector('div');
  if (track) {
    track.style.transition = 'background-color 150ms ease-in-out';
    track.style.backgroundColor = checkbox.checked ? '#10b981' : '';
  }

  checkbox.addEventListener('change', async function() {
    const formData = new FormData(document.getElementById('notification-form'));

    try {
      const response = await fetch('{{ route("settings.notifications") }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Accept': 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(formData)
      });

      const data = await response.json();

      if (data.success) {
        // Persist green when checked, clear when unchecked
        const label = this.closest('label');
        const trackEl = label.querySelector('div');
        trackEl.style.backgroundColor = this.checked ? '#10b981' : '';
      } else {
        const errorMessage = typeof data.message === 'string' ? data.message : JSON.stringify(data.message);
        showMessageModal('error', 'Fout!', errorMessage || 'Er is een onbekende fout opgetreden.');
        // Revert checkbox state on error
        this.checked = !this.checked;
        const label = this.closest('label');
        const trackEl = label.querySelector('div');
        trackEl.style.backgroundColor = this.checked ? '#10b981' : '';
      }

    } catch (error) {
      console.error('Notification preferences error:', error);
      showMessageModal('error', 'Fout!', 'Er is een fout opgetreden bij het opslaan van de notificatie instellingen.');
      // Revert checkbox state on error
      this.checked = !this.checked;
      const label = this.closest('label');
      const trackEl = label.querySelector('div');
      trackEl.style.backgroundColor = this.checked ? '#10b981' : '';
    }
  });
});

// Auto-save privacy preferences when checkboxes change
document.querySelectorAll('input[name="profile_visible"], input[name="cv_downloadable"]').forEach(checkbox => {
  // Ensure correct initial color based on checked state (override with inline style)
  const track = checkbox.closest('label')?.querySelector('div');
  if (track) {
    track.style.transition = 'background-color 150ms ease-in-out';
    track.style.backgroundColor = checkbox.checked ? '#10b981' : '';
  }

  checkbox.addEventListener('change', async function() {
    const formData = new FormData(document.getElementById('privacy-form'));

    try {
      const response = await fetch('{{ route("settings.privacy") }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Accept': 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(formData)
      });

      const data = await response.json();

      if (data.success) {
        // Persist green when checked, clear when unchecked
        const label = this.closest('label');
        const trackEl = label.querySelector('div');
        trackEl.style.backgroundColor = this.checked ? '#10b981' : '';
      } else {
        const errorMessage = typeof data.message === 'string' ? data.message : JSON.stringify(data.message);
        showMessageModal('error', 'Fout!', errorMessage || 'Er is een onbekende fout opgetreden.');
        // Revert checkbox state on error
        this.checked = !this.checked;
        const label = this.closest('label');
        const trackEl = label.querySelector('div');
        trackEl.style.backgroundColor = this.checked ? '#10b981' : '';
      }

    } catch (error) {
      console.error('Privacy preferences error:', error);
      showMessageModal('error', 'Fout!', 'Er is een fout opgetreden bij het opslaan van de privacy instellingen.');
      // Revert checkbox state on error
      this.checked = !this.checked;
      const label = this.closest('label');
      const trackEl = label.querySelector('div');
      trackEl.style.backgroundColor = this.checked ? '#10b981' : '';
    }
  });
});

// Export Data Handler
document.getElementById('export-data-btn').addEventListener('click', async function() {
  const button = this;
  button.disabled = true;
  button.textContent = 'Exporteren...';

  try {
    const response = await fetch('{{ route("settings.export-data") }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
      }
    });

    if (response.ok) {
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'nexa-user-data-' + new Date().toISOString().split('T')[0] + '.json';
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
      showMessageModal('success', 'Succesvol!', 'Je gegevens zijn geëxporteerd.');
    } else {
      const data = await response.json();
      showMessageModal('error', 'Fout!', data.message || 'Er is een fout opgetreden bij het exporteren.');
    }

  } catch (error) {
    console.error('Export error:', error);
    showMessageModal('error', 'Fout!', 'Er is een fout opgetreden bij het exporteren van je gegevens.');
  } finally {
    button.disabled = false;
    button.innerHTML = `
      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
      </svg>
      Gegevens exporteren
    `;
  }
});

// Delete Account Handler
document.getElementById('delete-account-btn').addEventListener('click', function() {
  showDeleteAccountModal();
});

// Confirmation input handler
document.getElementById('delete-confirmation-input').addEventListener('input', function() {
  const confirmBtn = document.getElementById('confirm-delete-btn');
  if (this.value === 'VERWIJDER') {
    confirmBtn.disabled = false;
    confirmBtn.classList.remove('opacity-50', 'cursor-not-allowed');
  } else {
    confirmBtn.disabled = true;
    confirmBtn.classList.add('opacity-50', 'cursor-not-allowed');
  }
});

// Confirm delete button handler
document.getElementById('confirm-delete-btn').addEventListener('click', function() {
  const confirmationInput = document.getElementById('delete-confirmation-input');
  if (confirmationInput.value === 'VERWIJDER') {
    deleteAccount();
  } else {
    showMessageModal('error', 'Fout!', 'Typ "VERWIJDER" om te bevestigen.');
  }
});

async function deleteAccount() {
  const confirmBtn = document.getElementById('confirm-delete-btn');
  const originalBtn = document.getElementById('delete-account-btn');
  
  // Disable confirm button and show loading
  confirmBtn.disabled = true;
  confirmBtn.innerHTML = `
    <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    Verwijderen...
  `;
  
  try {
    const response = await fetch('{{ route("settings.delete-account") }}', {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
      }
    });
    
    const data = await response.json();
    
    if (data.success) {
      hideDeleteAccountModal();
      showMessageModal('success', 'Account verwijderd', 'Je account is succesvol verwijderd. Je wordt doorgestuurd naar de homepage.');
      setTimeout(() => {
        window.location.href = '/';
      }, 2000);
    } else {
      showMessageModal('error', 'Fout!', data.message || 'Er is een fout opgetreden bij het verwijderen van je account.');
      // Reset button
      confirmBtn.disabled = false;
      confirmBtn.innerHTML = `
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
        </svg>
        Account Verwijderen
      `;
    }
    
  } catch (error) {
    console.error('Delete account error:', error);
    showMessageModal('error', 'Fout!', 'Er is een fout opgetreden bij het verwijderen van je account.');
    
    // Reset button
    confirmBtn.disabled = false;
    confirmBtn.innerHTML = `
      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
      </svg>
      Account Verwijderen
    `;
  }
}
</script>
@endsection
