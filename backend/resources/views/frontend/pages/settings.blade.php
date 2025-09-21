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
      <form class="space-y-4">
        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">E-mailadres</label>
          <input type="email" class="input mt-1" value="{{ Auth::user()->email ?? '' }}" placeholder="E-mailadres">
        </div>
        
        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Huidig wachtwoord</label>
          <input type="password" class="input mt-1" placeholder="Huidig wachtwoord">
        </div>
        
        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Nieuw wachtwoord</label>
          <input type="password" class="input mt-1" placeholder="Nieuw wachtwoord">
        </div>
        
        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Bevestig nieuw wachtwoord</label>
          <input type="password" class="input mt-1" placeholder="Bevestig nieuw wachtwoord">
        </div>
        
        <button type="submit" class="btn btn-primary">Wachtwoord wijzigen</button>
      </form>
    </div>

    <!-- Notification Settings -->
    <div class="card p-6">
      <h3 class="font-semibold text-lg mb-4">Notificaties</h3>
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h4 class="font-medium">E-mail notificaties</h4>
            <p class="text-sm text-muted dark:text-muted-dark">Ontvang updates over nieuwe matches</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" class="sr-only peer" checked>
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-300 dark:peer-focus:ring-brand-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-brand-600"></div>
          </label>
        </div>
        
        <div class="flex items-center justify-between">
          <div>
            <h4 class="font-medium">SMS notificaties</h4>
            <p class="text-sm text-muted dark:text-muted-dark">Ontvang belangrijke updates via SMS</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" class="sr-only peer">
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-300 dark:peer-focus:ring-brand-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-brand-600"></div>
          </label>
        </div>
        
        <div class="flex items-center justify-between">
          <div>
            <h4 class="font-medium">Push notificaties</h4>
            <p class="text-sm text-muted dark:text-muted-dark">Ontvang notificaties in je browser</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" class="sr-only peer" checked>
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-300 dark:peer-focus:ring-brand-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-brand-600"></div>
          </label>
        </div>
      </div>
    </div>
  </div>

  <!-- Preferences -->
  <div class="space-y-6">
    <div class="card p-6">
      <h3 class="font-semibold text-lg mb-4">Job Voorkeuren</h3>
      <form class="space-y-4">
        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Gewenste locatie</label>
          <select class="select mt-1">
            <option>Amsterdam</option>
            <option>Utrecht</option>
            <option>Rotterdam</option>
            <option>Den Haag</option>
            <option>Remote</option>
            <option>Hybride</option>
          </select>
        </div>
        
        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Contract type</label>
          <select class="select mt-1">
            <option>Vast</option>
            <option>Tijdelijk</option>
            <option>ZZP</option>
            <option>Stage</option>
          </select>
        </div>
        
        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Werkuren</label>
          <select class="select mt-1">
            <option>Fulltime (40 uur)</option>
            <option>Parttime (32 uur)</option>
            <option>Parttime (24 uur)</option>
            <option>Parttime (16 uur)</option>
          </select>
        </div>
        
        <div>
          <label class="text-sm font-medium text-muted dark:text-muted-dark">Minimaal salaris</label>
          <input type="number" class="input mt-1" placeholder="€ 4000" min="0" step="100">
        </div>
        
        <button type="submit" class="btn btn-primary">Voorkeuren opslaan</button>
      </form>
    </div>

    <!-- Privacy Settings -->
    <div class="card p-6">
      <h3 class="font-semibold text-lg mb-4">Privacy</h3>
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h4 class="font-medium">Profiel zichtbaar</h4>
            <p class="text-sm text-muted dark:text-muted-dark">Maak je profiel zichtbaar voor werkgevers</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" class="sr-only peer" checked>
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-300 dark:peer-focus:ring-brand-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-brand-600"></div>
          </label>
        </div>
        
        <div class="flex items-center justify-between">
          <div>
            <h4 class="font-medium">CV downloadbaar</h4>
            <p class="text-sm text-muted dark:text-muted-dark">Sta werkgevers toe je CV te downloaden</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" class="sr-only peer" checked>
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-300 dark:peer-focus:ring-brand-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-brand-600"></div>
          </label>
        </div>
      </div>
    </div>

    <!-- Danger Zone -->
    <div class="card p-6 border-red-200 dark:border-red-800">
      <h3 class="font-semibold text-lg mb-4 text-red-600 dark:text-red-400">Gevaarlijke Zone</h3>
      <div class="space-y-4">
        <div>
          <h4 class="font-medium text-red-600 dark:text-red-400">Account verwijderen</h4>
          <p class="text-sm text-muted dark:text-muted-dark mb-3">Verwijder permanent je account en alle bijbehorende gegevens.</p>
          <button class="btn btn-outline border-red-300 text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20">
            Account verwijderen
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
