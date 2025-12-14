<!-- User -->
@auth
<div class="shrink-0" data-kt-dropdown="true" data-kt-dropdown-offset="10px, 10px" data-kt-dropdown-offset-rtl="-20px, 10px"
    data-kt-dropdown-placement="bottom-end" data-kt-dropdown-placement-rtl="bottom-start" data-kt-dropdown-trigger="click">
    <div class="shrink-0 cursor-pointer" data-kt-dropdown-toggle="true">
        @if(auth()->user()->photo_blob)
            <img alt="{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}" 
                class="size-9 shrink-0 rounded-full border-2 border-green-500"
                src="{{ route('user.photo', auth()->id()) }}" />
        @else
            <img alt="{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}" 
                class="size-9 shrink-0 rounded-full border-2 border-green-500"
                src="{{ asset('assets/media/avatars/300-2.png') }}" />
        @endif
    </div>
    <div class="kt-dropdown-menu w-[250px]" data-kt-dropdown-menu="true">
        <div class="flex items-center justify-between gap-1.5 px-2.5 py-1.5">
            <div class="flex items-center gap-2">
                @if(auth()->user()->photo_blob)
                    <img alt="{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}" 
                        class="size-9 shrink-0 rounded-full border-2 border-green-500"
                        src="{{ route('user.photo', auth()->id()) }}" />
                @else
                    <img alt="{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}" 
                        class="size-9 shrink-0 rounded-full border-2 border-green-500"
                        src="{{ asset('assets/media/avatars/300-2.png') }}" />
                @endif
                <div class="flex flex-col gap-1.5">
                    <span class="text-sm font-semibold leading-none text-foreground">
                        {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                    </span>
                    <a class="hover:text-primary text-xs font-medium leading-none text-secondary-foreground"
                        href="{{ route('admin.profile') }}">
                        {{ auth()->user()->email }}
                    </a>
                </div>
            </div>
            @if(auth()->user()->hasRole('super-admin'))
                <span class="kt-badge kt-badge-sm kt-badge-primary kt-badge-outline">
                    Admin
                </span>
            @else
                <span class="kt-badge kt-badge-sm kt-badge-success kt-badge-outline">
                    User
                </span>
            @endif
        </div>
        <ul class="kt-dropdown-menu-sub">
            <li>
                <div class="kt-dropdown-menu-separator">
                </div>
            </li>
            <li>
                <a class="kt-dropdown-menu-link" href="{{ route('admin.dashboard') }}">
                    <i class="ki-filled ki-element-11">
                    </i>
                    Dashboard
                </a>
            </li>
            <li>
                <a class="kt-dropdown-menu-link" href="{{ route('admin.profile') }}">
                    <i class="ki-filled ki-profile-circle">
                    </i>
                    Mijn Profiel
                </a>
            </li>
            @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-agenda'))
            <li>
                <a class="kt-dropdown-menu-link" href="{{ route('admin.agenda.index') }}">
                    <i class="ki-filled ki-calendar">
                    </i>
                    Agenda
                </a>
            </li>
            @endif
            @if(auth()->user()->company_id)
            <li>
                <a class="kt-dropdown-menu-link" href="{{ route('admin.companies.show', auth()->user()->company_id) }}">
                    <i class="ki-filled ki-abstract-26">
                    </i>
                    Bedrijfsprofiel
                </a>
            </li>
            @endif
            <li>
                <div class="kt-dropdown-menu-separator">
                </div>
            </li>
        </ul>
        <div class="mb-2.5 flex flex-col gap-3.5 px-2.5 pt-1.5">
            <div class="flex items-center justify-between gap-2">
                <span class="flex items-center gap-2">
                    <i class="ki-filled ki-moon text-base text-muted-foreground">
                    </i>
                    <span class="text-2sm font-medium">
                        Dark Mode
                    </span>
                </span>
                <input class="kt-switch" data-kt-theme-switch-state="dark" data-kt-theme-switch-toggle="true"
                    name="check" type="checkbox" value="1" />
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="kt-btn kt-btn-outline w-full justify-center">
                    Uitloggen
                </button>
            </form>
        </div>
    </div>
</div>
@endauth
<!-- End of User -->
