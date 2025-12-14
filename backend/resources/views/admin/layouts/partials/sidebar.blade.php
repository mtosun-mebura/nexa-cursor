<!-- Sidebar -->
<div class="kt-sidebar bg-background border-e border-e-border fixed top-0 bottom-0 z-20 flex flex-col items-stretch shrink-0 [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]"
    data-kt-drawer="true" data-kt-drawer-class="kt-drawer kt-drawer-start top-0 bottom-0" id="sidebar">
    <div class="kt-sidebar-header flex items-center relative justify-center px-3 lg:px-6 shrink-0"
        id="sidebar_header">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center">
            <img class="default-logo h-[26px] w-auto max-w-[140px] object-contain" src="{{ asset('images/nexa-skillmatching-logo.png') }}" alt="Nexa Skillmatching" />
            <img class="small-logo h-[26px] w-auto max-w-[94px] object-contain" src="{{ asset('images/nexa-x-logo.png') }}" alt="Nexa" />
        </a>
        <button
            class="kt-btn kt-btn-outline kt-btn-icon absolute start-full top-2/4 size-[30px] -translate-x-2/4 -translate-y-2/4 rtl:translate-x-2/4"
            data-kt-toggle="body" data-kt-toggle-class="kt-sidebar-collapse" id="sidebar_toggle">
            <i
                class="ki-filled ki-black-left-line kt-toggle-active:rotate-180 rtl:translate rtl:kt-toggle-active:rotate-0 transition-all duration-300 rtl:rotate-180">
            </i>
        </button>
    </div>
    <div class="kt-sidebar-content flex shrink-0 grow py-5 pe-2" id="sidebar_content">
        <div class="kt-scrollable-y-hover flex shrink-0 grow pe-1 ps-2 lg:pe-3 lg:ps-5" data-kt-scrollable="true"
            data-kt-scrollable-dependencies="#sidebar_header" data-kt-scrollable-height="auto"
            data-kt-scrollable-offset="0px" data-kt-scrollable-wrappers="#sidebar_content" id="sidebar_scrollable">
            <!-- Sidebar Menu -->
            <div class="kt-menu flex grow flex-col gap-1" data-kt-menu="true" data-kt-menu-accordion-expand-all="false"
                id="sidebar_menu">

                <!-- Client API (Super Admin only) -->
                @if(auth()->user()->hasRole('super-admin'))
                @php
                    $companies = \App\Models\Company::orderBy('name')->get();
                    $selectedTenant = session('selected_tenant');
                    $selectedCompany = $selectedTenant ? \App\Models\Company::find($selectedTenant) : null;
                @endphp
                <div class="mb-2 tenant-switcher" data-kt-dropdown="true" data-kt-dropdown-placement="bottom-start" data-kt-dropdown-trigger="click" data-kt-dropdown-offset="0px, 5px">
                    <!-- Collapsed sidebar: icon-only toggle (opens same dropdown) -->
                    <button
                        class="tenant-toggle-icon kt-btn kt-btn-outline kt-btn-icon mx-auto"
                        type="button"
                        data-tenant-toggle-icon="true"
                        title="{{ $selectedCompany ? $selectedCompany->name : 'Alle Tenants' }}"
                        aria-label="Tenant kiezen">
                        <i class="ki-filled ki-abstract-26 text-base"></i>
                    </button>

                    <!-- Expanded sidebar: full button with icon + label -->
                    <button class="tenant-toggle-full w-full kt-btn kt-btn-outline justify-between flex-nowrap" type="button" data-kt-dropdown-toggle="true">
                        <span class="flex items-center gap-2 min-w-0">
                            <i class="ki-filled ki-abstract-26 text-base shrink-0"></i>
                            <span class="truncate">
                                @if($selectedCompany)
                                    {{ $selectedCompany->name }}
                                @else
                                    Alle Tenants
                                @endif
                            </span>
                        </span>
                        <i class="ki-filled ki-down text-xs ms-2"></i>
                    </button>
                    <div class="kt-dropdown-menu w-[250px]" data-kt-dropdown-menu="true">
                        <a href="#"
                           onclick="event.preventDefault(); switchTenant('');"
                           class="kt-dropdown-menu-link {{ !$selectedTenant ? 'kt-menu-item-active' : '' }}">
                            <span class="kt-menu-title">Alle Tenants</span>
                        </a>
                        <div class="kt-dropdown-menu-separator"></div>
                        @foreach($companies as $company)
                        <a href="#"
                           onclick="event.preventDefault(); switchTenant('{{ $company->id }}');"
                           class="kt-dropdown-menu-link {{ $selectedTenant == $company->id ? 'kt-menu-item-active' : '' }}">
                            <span class="kt-menu-title">{{ $company->name }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Dashboard -->
                <div class="kt-menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        href="{{ route('admin.dashboard') }}" tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-element-11 text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            Dashboard
                        </span>
                    </a>
                </div>

                <!-- Beheer -->
                <div class="kt-menu-item pt-2.25 pb-px">
                    <span
                        class="kt-menu-heading pe-[10px] ps-[10px] text-xs font-medium uppercase text-muted-foreground">
                        Beheer
                    </span>
                </div>

                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-companies'))
                <div class="kt-menu-item {{ request()->routeIs('admin.companies.*') ? 'active' : '' }}">
                    <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        href="{{ route('admin.companies.index') }}" tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-abstract-26 text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            Bedrijven
                        </span>
                    </a>
                </div>
                @endif

                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-users'))
                <div class="kt-menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        href="{{ route('admin.users.index') }}" tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-people text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            Gebruikers
                        </span>
                    </a>
                </div>
                @endif

                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-branches'))
                <div class="kt-menu-item {{ request()->routeIs('admin.branches.*') ? 'active' : '' }}">
                    <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        href="{{ route('admin.branches.index') }}" tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-tag text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            Branches
                        </span>
                    </a>
                </div>
                @endif

                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-vacancies'))
                <div class="kt-menu-item {{ request()->routeIs('admin.vacancies.*') ? 'active' : '' }}">
                    <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        href="{{ route('admin.vacancies.index') }}" tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-briefcase text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            Vacatures
                        </span>
                    </a>
                </div>
                @endif

                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-matches'))
                <div class="kt-menu-item {{ request()->routeIs('admin.matches.*') ? 'active' : '' }}">
                    <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        href="{{ route('admin.matches.index') }}" tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-abstract-38 text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            Matches
                        </span>
                    </a>
                </div>
                @endif

                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-interviews'))
                <div class="kt-menu-item {{ request()->routeIs('admin.interviews.*') ? 'active' : '' }}">
                    <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        href="{{ route('admin.interviews.index') }}" tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-calendar text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            Interviews
                        </span>
                    </a>
                </div>
                @endif

                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-agenda'))
                <div class="kt-menu-item {{ request()->routeIs('admin.agenda.*') ? 'active' : '' }}">
                    <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        href="{{ route('admin.agenda.index') }}" tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-calendar-2 text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            Agenda
                        </span>
                    </a>
                </div>
                @endif

                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-notifications'))
                <div class="kt-menu-item {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                    <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        href="{{ route('admin.notifications.index') }}" tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-notification-bing text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            Notificaties
                        </span>
                    </a>
                </div>
                @endif

                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-email-templates'))
                <div class="kt-menu-item {{ request()->routeIs('admin.email-templates.*') ? 'active' : '' }}">
                    <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        href="{{ route('admin.email-templates.index') }}" tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-sms text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            E-mail Templates
                        </span>
                    </a>
                </div>
                @endif

                <!-- Systeem -->
                @if(auth()->user()->hasRole('super-admin'))
                <div class="kt-menu-item pt-2.25 pb-px">
                    <span
                        class="kt-menu-heading pe-[10px] ps-[10px] text-xs font-medium uppercase text-muted-foreground">
                        Systeem
                    </span>
                </div>
                <div class="kt-menu-item {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'here show' : '' }}" 
                     data-kt-menu-item-toggle="accordion" data-kt-menu-item-trigger="click">
                    <div class="kt-menu-link flex grow cursor-pointer items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-setting-2 text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            Rollen & Rechten
                        </span>
                        <span class="kt-menu-arrow text-muted-foreground w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                            <span class="inline-flex kt-menu-item-show:hidden">
                                <i class="ki-filled ki-plus text-[11px]">
                                </i>
                            </span>
                            <span class="hidden kt-menu-item-show:inline-flex">
                                <i class="ki-filled ki-minus text-[11px]">
                                </i>
                            </span>
                        </span>
                    </div>
                    <div class="kt-menu-accordion relative gap-1 ps-[10px] before:absolute before:bottom-0 before:start-[20px] before:top-0 before:border-s before:border-border">
                        <div class="kt-menu-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                            <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                href="{{ route('admin.roles.index') }}" tabindex="0">
                                <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                </span>
                                <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                    Rollen
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                            <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                href="{{ route('admin.permissions.index') }}" tabindex="0">
                                <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                </span>
                                <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                    Rechten
                                </span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="kt-menu-item {{ request()->routeIs('admin.payments.*') || request()->routeIs('admin.invoices.*') || request()->routeIs('admin.payment-providers.*') ? 'here show' : '' }}" 
                     data-kt-menu-item-toggle="accordion" data-kt-menu-item-trigger="click">
                    <div class="kt-menu-link flex grow cursor-pointer items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-wallet text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            Betalingen
                        </span>
                        <span class="kt-menu-arrow text-muted-foreground w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                            <span class="inline-flex kt-menu-item-show:hidden">
                                <i class="ki-filled ki-plus text-[11px]"></i>
                            </span>
                            <span class="hidden kt-menu-item-show:inline-flex">
                                <i class="ki-filled ki-minus text-[11px]"></i>
                            </span>
                        </span>
                    </div>
                    <div class="kt-menu-accordion relative gap-1 ps-[10px] before:absolute before:bottom-0 before:start-[20px] before:top-0 before:border-s before:border-border">
                        <div class="kt-menu-item {{ request()->routeIs('admin.payments.index') ? 'active' : '' }}">
                            <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                href="{{ route('admin.payments.index') }}" tabindex="0">
                                <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary"></span>
                                <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                    Overzicht
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item {{ request()->routeIs('admin.payments.openstaand') ? 'active' : '' }}">
                            <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                href="{{ route('admin.payments.openstaand') }}" tabindex="0">
                                <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary"></span>
                                <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                    Openstaande betalingen
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item {{ request()->routeIs('admin.payments.voldaan') ? 'active' : '' }}">
                            <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                href="{{ route('admin.payments.voldaan') }}" tabindex="0">
                                <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary"></span>
                                <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                    Voldane betalingen
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item {{ request()->routeIs('admin.invoices.index') || request()->routeIs('admin.invoices.show') || request()->routeIs('admin.invoices.create') || request()->routeIs('admin.invoices.edit') ? 'active' : '' }}">
                            <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                href="{{ route('admin.invoices.index') }}" tabindex="0">
                                <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary"></span>
                                <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                    Facturen
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item {{ request()->routeIs('admin.payment-providers.*') ? 'active' : '' }}">
                            <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                href="{{ route('admin.payment-providers.index') }}" tabindex="0">
                                <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary"></span>
                                <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                    Betalingsproviders
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item {{ request()->routeIs('admin.invoices.settings') ? 'active' : '' }}">
                            <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                href="{{ route('admin.invoices.settings') }}" tabindex="0">
                                <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary"></span>
                                <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                    Instellingen
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="kt-menu-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        href="{{ route('admin.settings.index') }}" tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-setting text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            Instellingen
                        </span>
                    </a>
                </div>
                @endif

                <!-- Profile -->
                <div class="kt-menu-item pt-2.25 pb-px">
                    <span
                        class="kt-menu-heading pe-[10px] ps-[10px] text-xs font-medium uppercase text-muted-foreground">
                        Account
                    </span>
                </div>
                <div class="kt-menu-item {{ request()->routeIs('admin.profile') ? 'active' : '' }}">
                    <a class="kt-menu-link flex grow items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        href="{{ route('admin.profile') }}" tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-profile-circle text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            Mijn Profiel
                        </span>
                    </a>
                </div>

            </div>
            <!-- End of Sidebar Menu -->
        </div>
    </div>
</div>
<!-- End of Sidebar -->

<style>
    /* Tenant switcher: show icon-only toggle in collapsed sidebar */
    .tenant-toggle-icon { display: none; }
    .demo1.kt-sidebar-collapse .kt-sidebar:not(:hover) .tenant-toggle-full { display: none; }
    .demo1.kt-sidebar-collapse .kt-sidebar:not(:hover) .tenant-toggle-icon { display: inline-flex; }
    .demo1.kt-sidebar-collapse .kt-sidebar:not(:hover) .tenant-toggle-icon {
        width: 34px;
        height: 34px;
        align-items: center;
        justify-content: center;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebarEl = document.getElementById('sidebar');
    function isCollapsedMode() {
        return document.body.classList.contains('kt-sidebar-collapse') || document.documentElement.classList.contains('kt-sidebar-collapse');
    }
    function getTenantDropdownMenuOpen() {
        // Menu might be inside wrapper or rendered elsewhere; check both
        const wrapper = document.querySelector('.tenant-switcher[data-kt-dropdown="true"]');
        const inWrapper = wrapper ? wrapper.querySelector('.kt-dropdown-menu.open, [data-kt-dropdown-menu="true"].open') : null;
        return inWrapper || document.querySelector('.kt-dropdown-menu.open, [data-kt-dropdown-menu="true"].open');
    }
    function shouldCloseFromLeave(relatedTarget) {
        if (!isCollapsedMode()) return false;
        const menu = getTenantDropdownMenuOpen();
        // If the pointer is moving into the menu (or its children), do NOT close.
        if (menu && relatedTarget && menu.contains(relatedTarget)) return false;
        // If moving back into the sidebar, do NOT close.
        if (sidebarEl && relatedTarget && sidebarEl.contains(relatedTarget)) return false;
        // Otherwise close.
        return true;
    }

    function forceCloseDropdownEl(wrapper) {
        if (!wrapper) return;

        // Best effort: use KTDropdown API if available
        try {
            if (window.KTDropdown && typeof window.KTDropdown.getInstance === 'function') {
                const inst = window.KTDropdown.getInstance(wrapper);
                if (inst && typeof inst.hide === 'function') inst.hide();
                if (inst && typeof inst.close === 'function') inst.close();
            }
        } catch (e) {}

        // Fallback: remove open state + inline display
        wrapper.classList.remove('open');
        const toggle = wrapper.querySelector('[data-kt-dropdown-toggle="true"]');
        if (toggle) toggle.setAttribute('aria-expanded', 'false');

        const menus = wrapper.querySelectorAll('.kt-dropdown-menu, [data-kt-dropdown-menu="true"]');
        menus.forEach(function (menu) {
            menu.classList.remove('open');
            // If KT scripts set inline display, nuke it
            if (menu.style && menu.style.display) menu.style.display = '';
        });
    }

    function closeTenantDropdownIfOpen() {
        document.querySelectorAll('.tenant-switcher[data-kt-dropdown="true"]').forEach(forceCloseDropdownEl);
        // Also close any open dropdown menus globally (some implementations detach menus)
        document.querySelectorAll('.kt-dropdown-menu.open, [data-kt-dropdown-menu="true"].open').forEach(function (menu) {
            menu.classList.remove('open');
            if (menu.style && menu.style.display) menu.style.display = '';
        });
        document.querySelectorAll('.open[data-kt-dropdown-initialized]').forEach(function (w) {
            w.classList.remove('open');
        });
    }

    // Collapsed icon toggle should open the same dropdown as the full button
    document.querySelectorAll('.tenant-switcher [data-tenant-toggle-icon="true"]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const wrapper = btn.closest('.tenant-switcher');
            const fullToggle = wrapper ? wrapper.querySelector('.tenant-toggle-full[data-kt-dropdown-toggle="true"]') : null;
            if (fullToggle) {
                fullToggle.click();
            }
        });
    });

    // In collapsed sidebar mode the sidebar auto-collapses on hover-out via CSS.
    // Allow moving the cursor from sidebar -> dropdown menu; close only when leaving BOTH.
    if (sidebarEl) {
        sidebarEl.addEventListener('mouseleave', function (e) {
            if (shouldCloseFromLeave(e.relatedTarget)) closeTenantDropdownIfOpen();
        }, true);
        sidebarEl.addEventListener('pointerleave', function (e) {
            if (shouldCloseFromLeave(e.relatedTarget)) closeTenantDropdownIfOpen();
        }, true);
    }

    // When dropdown opens, attach hover listeners to the menu so it stays usable in collapsed mode
    (function observeTenantDropdownOpen() {
        const wrapper = document.querySelector('.tenant-switcher[data-kt-dropdown="true"]');
        if (!wrapper) return;
        const obs = new MutationObserver(function () {
            const menu = getTenantDropdownMenuOpen();
            if (!menu || menu.dataset.tenantHoverBound === '1') return;
            menu.dataset.tenantHoverBound = '1';

            menu.addEventListener('mouseleave', function (e) {
                if (shouldCloseFromLeave(e.relatedTarget)) closeTenantDropdownIfOpen();
            }, true);
            menu.addEventListener('pointerleave', function (e) {
                if (shouldCloseFromLeave(e.relatedTarget)) closeTenantDropdownIfOpen();
            }, true);
        });
        obs.observe(wrapper, { attributes: true, attributeFilter: ['class'] });
    })();

    // When sidebar collapses, force-close the dropdown so it doesn't stick out
    const sidebarToggle = document.getElementById('sidebar_toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            // Wait for the body class toggle to apply
            setTimeout(function () {
                if (isCollapsedMode()) {
                    closeTenantDropdownIfOpen();
                }
            }, 0);
        }, true);
    }

    // Click outside should close the pinned dropdown (prevents it floating over content)
    document.addEventListener('pointerdown', function (e) {
        if (!isCollapsedMode()) return;
        if (e.target.closest('.tenant-switcher') || e.target.closest('.kt-dropdown-menu') || e.target.closest('[data-kt-dropdown-menu="true"]')) return;
        closeTenantDropdownIfOpen();
    }, true);

    // Also observe direct class changes (e.g. programmatic toggles)
    const classObserver = new MutationObserver(function () {
        if (isCollapsedMode()) closeTenantDropdownIfOpen();
    });
    classObserver.observe(document.body, { attributes: true, attributeFilter: ['class'] });
    classObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
</script>

<script>
function switchTenant(tenantId) {
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
    formData.append('tenant_id', tenantId);
    
    fetch('{{ route('admin.tenant.switch') }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Redirect naar dashboard om company-profile te tonen
            // Gebruik redirect URL uit response als die beschikbaar is, anders gebruik dashboard route
            const redirectUrl = data.redirect || '{{ route("admin.dashboard") }}';
            window.location.href = redirectUrl;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Er is een fout opgetreden bij het wijzigen van de tenant.');
    });
}
</script>

