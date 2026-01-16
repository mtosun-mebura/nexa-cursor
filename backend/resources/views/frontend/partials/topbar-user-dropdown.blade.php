<!-- User -->
@auth
@if(auth()->check() && auth()->user())
<div class="shrink-0 user-dropdown-container" data-kt-dropdown="true" data-kt-dropdown-offset="10px, 10px" data-kt-dropdown-offset-rtl="-20px, 10px"
    data-kt-dropdown-placement="bottom-end" data-kt-dropdown-placement-rtl="bottom-start" data-kt-dropdown-trigger="click">
    <div class="shrink-0 cursor-pointer rounded-full border-2 border-green-500 overflow-hidden" data-kt-dropdown-toggle="true" style="width: 36px; height: 36px;">
        @if(auth()->user()->photo_blob)
            <img alt="{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}" 
                class="w-full h-full object-cover"
                src="{{ route('secure.photo', ['token' => auth()->user()->getPhotoToken()]) }}" />
        @else
            <img alt="{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}" 
                class="w-full h-full object-cover"
                src="{{ asset('assets/media/avatars/300-2.png') }}" />
        @endif
    </div>
    <div class="kt-dropdown-menu w-[250px]" data-kt-dropdown-menu="true">
        <div class="flex items-start gap-2 px-2.5 py-1.5">
            @if(auth()->user()->photo_blob)
                <div class="shrink-0 rounded-full border-2 border-green-500 overflow-hidden" style="width: 36px; height: 36px;">
                    <img alt="{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}" 
                        class="w-full h-full object-cover"
                        src="{{ route('secure.photo', ['token' => auth()->user()->getPhotoToken()]) }}" />
                </div>
            @else
                <div class="shrink-0 rounded-full border-2 border-green-500 overflow-hidden" style="width: 36px; height: 36px;">
                    <img alt="{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}" 
                        class="w-full h-full object-cover"
                        src="{{ asset('assets/media/avatars/300-2.png') }}" />
                </div>
            @endif
            <div class="flex flex-col gap-1.5">
                <span class="text-sm font-semibold leading-none text-foreground">
                    {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                </span>
                <a class="hover:text-primary text-xs font-medium leading-none text-secondary-foreground"
                    href="{{ route('profile') }}">
                    {{ auth()->user()->email }}
                </a>
            </div>
        </div>
        <ul class="kt-dropdown-menu-sub">
            <li>
                <div class="kt-dropdown-menu-separator">
                </div>
            </li>
            <li>
                <a class="kt-dropdown-menu-link" href="{{ route('dashboard') }}">
                    <i class="ki-filled ki-element-11">
                    </i>
                    Dashboard
                </a>
            </li>
            <li>
                <a class="kt-dropdown-menu-link" href="{{ route('profile') }}">
                    <i class="ki-filled ki-profile-circle">
                    </i>
                    Mijn Profiel
                </a>
            </li>
            @if(auth()->user() && auth()->user()->can('view-agenda'))
            <li>
                <a class="kt-dropdown-menu-link" href="{{ route('agenda') }}">
                    <i class="ki-filled ki-calendar">
                    </i>
                    Agenda
                </a>
            </li>
            @endif
            <li>
                <a class="kt-dropdown-menu-link" href="{{ route('applications') }}">
                    <i class="ki-filled ki-document">
                    </i>
                    Mijn Sollicitaties
                </a>
            </li>
            <li>
                <a class="kt-dropdown-menu-link" href="{{ route('settings') }}">
                    <i class="ki-filled ki-setting">
                    </i>
                    Instellingen
                </a>
            </li>
            <li>
                <div class="kt-dropdown-menu-separator">
                </div>
            </li>
        </ul>
        <div class="mb-2.5 flex flex-col gap-3.5 px-2.5 pt-1.5">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="kt-btn kt-btn-outline w-full justify-center">
                    Uitloggen
                </button>
            </form>
        </div>
    </div>
</div>
@endif
@endauth
<!-- End of User -->

