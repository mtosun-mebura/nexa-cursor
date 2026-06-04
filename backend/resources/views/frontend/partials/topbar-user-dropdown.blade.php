<!-- User -->
@auth
@php
    $profileMenuLabel = $profileMenuLabel ?? 'Mijn Profiel';
    $profileMenuUrl = $profileMenuUrl ?? route('profile');
@endphp
@if(auth()->check() && auth()->user())
<div class="relative shrink-0 user-dropdown-container" x-data="{ userMenuOpen: false }" @keydown.escape.window="userMenuOpen = false">
    <button type="button"
            class="flex shrink-0 cursor-pointer rounded-full border-2 border-green-500 overflow-hidden focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
            style="width: 36px; height: 36px;"
            @click="userMenuOpen = !userMenuOpen"
            :aria-expanded="userMenuOpen"
            aria-haspopup="true"
            aria-label="Gebruikersmenu">
        @if(auth()->user()->photo_blob)
            <img alt="{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}"
                class="w-full h-full object-cover"
                src="{{ route('secure.photo', ['token' => auth()->user()->getPhotoToken()]) }}" />
        @else
            <img alt="{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}"
                @class([
                    'w-full h-full object-contain bg-black',
                    'opacity-50' => auth()->user()->defaultAvatarShouldAppearTransparent(),
                ])
                src="{{ asset(config('nexa.default_user_avatar')) }}" />
        @endif
    </button>
    <div x-show="userMenuOpen"
         x-cloak
         @click.outside="userMenuOpen = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-1"
         class="absolute right-0 top-full z-50 mt-2 w-64 origin-top-right rounded-lg border border-gray-700 !bg-[#111827] py-2 shadow-lg text-gray-100"
         role="menu">
        <div class="flex items-start gap-3 border-b border-gray-700 px-4 py-3">
            @if(auth()->user()->photo_blob)
                <div class="shrink-0 overflow-hidden rounded-full border-2 border-green-500" style="width: 40px; height: 40px;">
                    <img alt="{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}"
                        class="h-full w-full object-cover"
                        src="{{ route('secure.photo', ['token' => auth()->user()->getPhotoToken()]) }}" />
                </div>
            @else
                <div class="shrink-0 overflow-hidden rounded-full border-2 border-green-500" style="width: 40px; height: 40px;">
                    <img alt="{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}"
                        @class([
                            'h-full w-full object-contain bg-black',
                            'opacity-50' => auth()->user()->defaultAvatarShouldAppearTransparent(),
                        ])
                        src="{{ asset(config('nexa.default_user_avatar')) }}" />
                </div>
            @endif
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-gray-100">
                    {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                </p>
                <p class="mt-1 truncate text-xs font-medium text-gray-300">
                    {{ auth()->user()->email }}
                </p>
            </div>
        </div>
        <div class="py-1">
            @if($showSkillmatchingAppLinks ?? false)
            <a href="{{ route('dashboard') }}" class="flex w-full items-center px-4 py-2.5 text-sm font-medium text-gray-100 hover:bg-gray-800" role="menuitem">
                Dashboard
            </a>
            @endif
            <a href="{{ $profileMenuUrl }}" class="flex w-full items-center px-4 py-2.5 text-sm font-medium text-gray-100 hover:bg-gray-800" role="menuitem">
                {{ $profileMenuLabel }}
            </a>
            @if($showSkillmatchingAppLinks ?? false)
            <a href="{{ route('agenda') }}" class="flex w-full items-center px-4 py-2.5 text-sm font-medium text-gray-100 hover:bg-gray-800" role="menuitem">
                Agenda
            </a>
            <a href="{{ route('applications') }}" class="flex w-full items-center px-4 py-2.5 text-sm font-medium text-gray-100 hover:bg-gray-800" role="menuitem">
                Mijn Sollicitaties
            </a>
            <a href="{{ route('settings') }}" class="flex w-full items-center px-4 py-2.5 text-sm font-medium text-gray-100 hover:bg-gray-800" role="menuitem">
                Instellingen
            </a>
            @endif
        </div>
        <div class="border-t border-gray-700 px-4 py-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="w-full cursor-pointer select-none justify-center rounded-md border border-gray-600 bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-100 transition-colors hover:border-gray-500 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-[#111827]"
                >
                    Uitloggen
                </button>
            </form>
        </div>
    </div>
</div>
@endif
@endauth
<!-- End of User -->
