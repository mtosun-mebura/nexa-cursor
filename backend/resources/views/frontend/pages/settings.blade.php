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
</script>
@endsection
