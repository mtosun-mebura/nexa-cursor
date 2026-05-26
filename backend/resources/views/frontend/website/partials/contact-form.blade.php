@if(session('success'))
    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
        <p class="text-green-800 dark:text-green-200">{{ session('success') }}</p>
    </div>
@endif
@if(session('error'))
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
        <p class="text-red-800 dark:text-red-200">{{ session('error') }}</p>
    </div>
@endif

<form method="POST" action="{{ route('contact.submit') }}" class="space-y-6">
    @csrf
    <input type="text" name="website" style="display: none;" tabindex="-1" autocomplete="off">
    <input type="hidden" name="_submit_time" value="{{ time() }}">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="contact_first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Voornaam <span class="text-red-500">*</span></label>
            <input type="text" id="contact_first_name" name="first_name" value="{{ old('first_name') }}" required minlength="2" maxlength="255" class="input w-full @error('first_name') border-red-500 @enderror">
            @error('first_name')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="contact_last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Achternaam <span class="text-red-500">*</span></label>
            <input type="text" id="contact_last_name" name="last_name" value="{{ old('last_name') }}" required minlength="2" maxlength="255" class="input w-full @error('last_name') border-red-500 @enderror">
            @error('last_name')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="contact_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">E-mailadres <span class="text-red-500">*</span></label>
            <input type="email" id="contact_email" name="email" value="{{ old('email') }}" required maxlength="255" class="input w-full @error('email') border-red-500 @enderror">
            @error('email')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="contact_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Telefoon (optioneel)</label>
            <input type="tel" id="contact_phone" name="phone" value="{{ old('phone') }}" maxlength="15" class="input w-full @error('phone') border-red-500 @enderror">
            @error('phone')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="contact_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bericht <span class="text-red-500">*</span></label>
        <textarea id="contact_message" name="message" rows="5" minlength="10" maxlength="1000" required class="input w-full @error('message') border-red-500 @enderror">{{ old('message') }}</textarea>
        @error('message')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>

    <div>
        <button type="submit" class="btn btn-primary">Versturen</button>
    </div>
</form>
