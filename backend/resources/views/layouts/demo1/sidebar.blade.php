<!-- Sidebar -->
<div class="kt-sidebar bg-background border-e border-e-border fixed top-0 bottom-0 z-20 flex flex-col items-stretch shrink-0 [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]"
    data-kt-drawer="true" data-kt-drawer-class="kt-drawer kt-drawer-start top-0 bottom-0" id="sidebar">
    <div class="kt-sidebar-header flex items-center relative justify-between px-3 lg:px-6 shrink-0"
        id="sidebar_header">
        <a href="/demo1" class="flex items-center">
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
                <div class="kt-menu-item" data-kt-menu-item-toggle="accordion" data-kt-menu-item-trigger="click">
                    <div class="kt-menu-link flex grow cursor-pointer items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-element-11 text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            Dashboards
                        </span>
                        <span class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                            <span class="kt-menu-item-show:hidden inline-flex">
                                <i class="ki-filled ki-plus text-[11px]">
                                </i>
                            </span>
                            <span class="kt-menu-item-show:inline-flex hidden">
                                <i class="ki-filled ki-minus text-[11px]">
                                </i>
                            </span>
                        </span>
                    </div>
                    <div
                        class="kt-menu-accordion relative gap-1 ps-[10px] before:absolute before:bottom-0 before:start-[20px] before:top-0 before:border-s before:border-border">
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Light Sidebar
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/dashboards/dark-sidebar" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Dark Sidebar
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="kt-menu-item pt-2.25 pb-px">
                    <span
                        class="kt-menu-heading pe-[10px] ps-[10px] text-xs font-medium uppercase text-muted-foreground">
                        User
                    </span>
                </div>
                <div class="kt-menu-item" data-kt-menu-item-toggle="accordion" data-kt-menu-item-trigger="click">
                    <div class="kt-menu-link flex grow cursor-pointer items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-profile-circle text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            Public Profile
                        </span>
                        <span class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                            <span class="kt-menu-item-show:hidden inline-flex">
                                <i class="ki-filled ki-plus text-[11px]">
                                </i>
                            </span>
                            <span class="kt-menu-item-show:inline-flex hidden">
                                <i class="ki-filled ki-minus text-[11px]">
                                </i>
                            </span>
                        </span>
                    </div>
                    <div
                        class="kt-menu-accordion relative gap-1 ps-[10px] before:absolute before:bottom-0 before:start-[20px] before:top-0 before:border-s before:border-border">
                        <div class="kt-menu-item" data-kt-menu-item-toggle="accordion"
                            data-kt-menu-item-trigger="click">
                            <div class="kt-menu-link grow cursor-pointer gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px]"
                                tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-medium kt-menu-link-hover:!text-primary me-1 font-normal text-foreground">
                                    Profiles
                                </span>
                                <span
                                    class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                    <span class="kt-menu-item-show:hidden inline-flex">
                                        <i class="ki-filled ki-plus text-[11px]">
                                        </i>
                                    </span>
                                    <span class="kt-menu-item-show:inline-flex hidden">
                                        <i class="ki-filled ki-minus text-[11px]">
                                        </i>
                                    </span>
                                </span>
                            </div>
                            <div
                                class="kt-menu-accordion relative gap-1 ps-[22px] before:absolute before:bottom-0 before:start-[32px] before:top-0 before:border-s before:border-border">
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/public-profile/profiles/default" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Default
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/public-profile/profiles/creator" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Creator
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/public-profile/profiles/company" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Company
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/network/user-cards/nft" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            NFT
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/public-profile/profiles/blogger" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Blogger
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/public-profile/profiles/crm" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            CRM
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item flex-col-reverse" data-kt-menu-item-toggle="accordion"
                                    data-kt-menu-item-trigger="click">
                                    <div class="kt-menu-link grow cursor-pointer gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px]"
                                        tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span class="kt-menu-title text-2sm font-normal text-secondary-foreground">
                                            <span class="kt-menu-item-show:!flex hidden">
                                                Show less
                                            </span>
                                            <span class="kt-menu-item-show:hidden flex">
                                                Show 4 more
                                            </span>
                                        </span>
                                        <span
                                            class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                            <span class="kt-menu-item-show:hidden inline-flex">
                                                <i class="ki-filled ki-plus text-[11px]">
                                                </i>
                                            </span>
                                            <span class="kt-menu-item-show:inline-flex hidden">
                                                <i class="ki-filled ki-minus text-[11px]">
                                                </i>
                                            </span>
                                        </span>
                                    </div>
                                    <div class="kt-menu-accordion gap-1">
                                        <div class="kt-menu-item">
                                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                                href="/demo1/public-profile/profiles/gamer" tabindex="0">
                                                <span
                                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                                </span>
                                                <span
                                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                                    Gamer
                                                </span>
                                            </a>
                                        </div>
                                        <div class="kt-menu-item">
                                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                                href="/demo1/public-profile/profiles/feeds" tabindex="0">
                                                <span
                                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                                </span>
                                                <span
                                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                                    Feeds
                                                </span>
                                            </a>
                                        </div>
                                        <div class="kt-menu-item">
                                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                                href="/demo1/public-profile/profiles/plain" tabindex="0">
                                                <span
                                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                                </span>
                                                <span
                                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                                    Plain
                                                </span>
                                            </a>
                                        </div>
                                        <div class="kt-menu-item">
                                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                                href="/demo1/public-profile/profiles/modal" tabindex="0">
                                                <span
                                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                                </span>
                                                <span
                                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                                    Modal
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="kt-menu-item" data-kt-menu-item-toggle="accordion"
                            data-kt-menu-item-trigger="click">
                            <div class="kt-menu-link grow cursor-pointer gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px]"
                                tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-medium kt-menu-link-hover:!text-primary me-1 font-normal text-foreground">
                                    Projects
                                </span>
                                <span
                                    class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                    <span class="kt-menu-item-show:hidden inline-flex">
                                        <i class="ki-filled ki-plus text-[11px]">
                                        </i>
                                    </span>
                                    <span class="kt-menu-item-show:inline-flex hidden">
                                        <i class="ki-filled ki-minus text-[11px]">
                                        </i>
                                    </span>
                                </span>
                            </div>
                            <div
                                class="kt-menu-accordion relative gap-1 ps-[22px] before:absolute before:bottom-0 before:start-[32px] before:top-0 before:border-s before:border-border">
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/public-profile/projects/3-columns" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            3 Columns
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/public-profile/projects/2-columns" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            2 Columns
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/public-profile/works" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Works
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/account/members/teams" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Teams
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/public-profile/network" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Network
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/account/activity" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Activity
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item flex-col-reverse" data-kt-menu-item-toggle="accordion"
                            data-kt-menu-item-trigger="click">
                            <div class="kt-menu-link grow cursor-pointer gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px]"
                                tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span class="kt-menu-title text-2sm font-normal text-secondary-foreground">
                                    <span class="kt-menu-item-show:!flex hidden">
                                        Show less
                                    </span>
                                    <span class="kt-menu-item-show:hidden flex">
                                        Show 3 more
                                    </span>
                                </span>
                                <span
                                    class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                    <span class="kt-menu-item-show:hidden inline-flex">
                                        <i class="ki-filled ki-plus text-[11px]">
                                        </i>
                                    </span>
                                    <span class="kt-menu-item-show:inline-flex hidden">
                                        <i class="ki-filled ki-minus text-[11px]">
                                        </i>
                                    </span>
                                </span>
                            </div>
                            <div class="kt-menu-accordion gap-1">
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/public-profile/campaigns/card" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Campaigns - Card
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/public-profile/campaigns/list" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Campaigns - List
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/public-profile/empty" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Empty
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="kt-menu-item" data-kt-menu-item-toggle="accordion" data-kt-menu-item-trigger="click">
                    <div class="kt-menu-link flex grow cursor-pointer items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-setting-2 text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            My Account
                        </span>
                        <span
                            class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                            <span class="kt-menu-item-show:hidden inline-flex">
                                <i class="ki-filled ki-plus text-[11px]">
                                </i>
                            </span>
                            <span class="kt-menu-item-show:inline-flex hidden">
                                <i class="ki-filled ki-minus text-[11px]">
                                </i>
                            </span>
                        </span>
                    </div>
                    <div
                        class="kt-menu-accordion relative gap-1 ps-[10px] before:absolute before:bottom-0 before:start-[20px] before:top-0 before:border-s before:border-border">
                        <div class="kt-menu-item" data-kt-menu-item-toggle="accordion"
                            data-kt-menu-item-trigger="click">
                            <div class="kt-menu-link grow cursor-pointer gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px]"
                                tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-medium kt-menu-link-hover:!text-primary me-1 font-normal text-foreground">
                                    Account Home
                                </span>
                                <span
                                    class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                    <span class="kt-menu-item-show:hidden inline-flex">
                                        <i class="ki-filled ki-plus text-[11px]">
                                        </i>
                                    </span>
                                    <span class="kt-menu-item-show:inline-flex hidden">
                                        <i class="ki-filled ki-minus text-[11px]">
                                        </i>
                                    </span>
                                </span>
                            </div>
                            <div
                                class="kt-menu-accordion relative gap-1 ps-[22px] before:absolute before:bottom-0 before:start-[32px] before:top-0 before:border-s before:border-border">
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/network/get-started" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Get Started
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/home/user-profile" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            User Profile
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/home/company-profile" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Company Profile
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/home/settings-sidebar" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Settings - With Sidebar
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/home/settings-enterprise" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Settings - Enterprise
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/home/settings-plain" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Settings - Plain
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/home/settings-modal" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Settings - Modal
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="kt-menu-item" data-kt-menu-item-toggle="accordion"
                            data-kt-menu-item-trigger="click">
                            <div class="kt-menu-link grow cursor-pointer gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px]"
                                tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-medium kt-menu-link-hover:!text-primary me-1 font-normal text-foreground">
                                    Billing
                                </span>
                                <span
                                    class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                    <span class="kt-menu-item-show:hidden inline-flex">
                                        <i class="ki-filled ki-plus text-[11px]">
                                        </i>
                                    </span>
                                    <span class="kt-menu-item-show:inline-flex hidden">
                                        <i class="ki-filled ki-minus text-[11px]">
                                        </i>
                                    </span>
                                </span>
                            </div>
                            <div
                                class="kt-menu-accordion relative gap-1 ps-[22px] before:absolute before:bottom-0 before:start-[32px] before:top-0 before:border-s before:border-border">
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/billing/basic" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Billing - Basic
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/billing/enterprise" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Billing - Enterprise
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/billing/plans" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Plans
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/billing/history" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Billing History
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="kt-menu-item" data-kt-menu-item-toggle="accordion"
                            data-kt-menu-item-trigger="click">
                            <div class="kt-menu-link grow cursor-pointer gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px]"
                                tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-medium kt-menu-link-hover:!text-primary me-1 font-normal text-foreground">
                                    Security
                                </span>
                                <span
                                    class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                    <span class="kt-menu-item-show:hidden inline-flex">
                                        <i class="ki-filled ki-plus text-[11px]">
                                        </i>
                                    </span>
                                    <span class="kt-menu-item-show:inline-flex hidden">
                                        <i class="ki-filled ki-minus text-[11px]">
                                        </i>
                                    </span>
                                </span>
                            </div>
                            <div
                                class="kt-menu-accordion relative gap-1 ps-[22px] before:absolute before:bottom-0 before:start-[32px] before:top-0 before:border-s before:border-border">
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/network/get-started" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Get Started
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/security/overview" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Security Overview
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/security/allowed-ip-addresses" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Allowed IP Addresses
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/security/privacy-settings" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Privacy Settings
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/security/device-management" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Device Management
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/security/backup-and-recovery" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Backup & Recovery
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/security/current-sessions" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Current Sessions
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/security/security-log" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Security Log
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="kt-menu-item" data-kt-menu-item-toggle="accordion"
                            data-kt-menu-item-trigger="click">
                            <div class="kt-menu-link grow cursor-pointer gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px]"
                                tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-medium kt-menu-link-hover:!text-primary me-1 font-normal text-foreground">
                                    Members & Roles
                                </span>
                                <span
                                    class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                    <span class="kt-menu-item-show:hidden inline-flex">
                                        <i class="ki-filled ki-plus text-[11px]">
                                        </i>
                                    </span>
                                    <span class="kt-menu-item-show:inline-flex hidden">
                                        <i class="ki-filled ki-minus text-[11px]">
                                        </i>
                                    </span>
                                </span>
                            </div>
                            <div
                                class="kt-menu-accordion relative gap-1 ps-[22px] before:absolute before:bottom-0 before:start-[32px] before:top-0 before:border-s before:border-border">
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/members/team-starter" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Teams Starter
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/members/teams" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Teams
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/members/team-info" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Team Info
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/members/members-starter" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Members Starter
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/members/team-members" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Team Members
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/members/import-members" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Import Members
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/members/roles" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Roles
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/members/permissions-toggle" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Permissions - Toggler
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/members/permissions-check" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Permissions - Check
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/account/integrations" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Integrations
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/account/notifications" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Notifications
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/account/api-keys" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    API Keys
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item flex-col-reverse" data-kt-menu-item-toggle="accordion"
                            data-kt-menu-item-trigger="click">
                            <div class="kt-menu-link grow cursor-pointer gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px]"
                                tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span class="kt-menu-title text-2sm font-normal text-secondary-foreground">
                                    <span class="kt-menu-item-show:!flex hidden">
                                        Show less
                                    </span>
                                    <span class="kt-menu-item-show:hidden flex">
                                        Show 3 more
                                    </span>
                                </span>
                                <span
                                    class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                    <span class="kt-menu-item-show:hidden inline-flex">
                                        <i class="ki-filled ki-plus text-[11px]">
                                        </i>
                                    </span>
                                    <span class="kt-menu-item-show:inline-flex hidden">
                                        <i class="ki-filled ki-minus text-[11px]">
                                        </i>
                                    </span>
                                </span>
                            </div>
                            <div class="kt-menu-accordion gap-1">
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/appearance" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Appearance
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/invite-a-friend" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Invite a Friend
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/account/activity" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Activity
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="kt-menu-item" data-kt-menu-item-toggle="accordion" data-kt-menu-item-trigger="click">
                    <div class="kt-menu-link flex grow cursor-pointer items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-users text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            Network
                        </span>
                        <span
                            class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                            <span class="kt-menu-item-show:hidden inline-flex">
                                <i class="ki-filled ki-plus text-[11px]">
                                </i>
                            </span>
                            <span class="kt-menu-item-show:inline-flex hidden">
                                <i class="ki-filled ki-minus text-[11px]">
                                </i>
                            </span>
                        </span>
                    </div>
                    <div
                        class="kt-menu-accordion relative gap-1 ps-[10px] before:absolute before:bottom-0 before:start-[20px] before:top-0 before:border-s before:border-border">
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/network/get-started" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Get Started
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item" data-kt-menu-item-toggle="accordion"
                            data-kt-menu-item-trigger="click">
                            <div class="kt-menu-link grow cursor-pointer gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px]"
                                tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-medium kt-menu-link-hover:!text-primary me-1 font-normal text-foreground">
                                    User Cards
                                </span>
                                <span
                                    class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                    <span class="kt-menu-item-show:hidden inline-flex">
                                        <i class="ki-filled ki-plus text-[11px]">
                                        </i>
                                    </span>
                                    <span class="kt-menu-item-show:inline-flex hidden">
                                        <i class="ki-filled ki-minus text-[11px]">
                                        </i>
                                    </span>
                                </span>
                            </div>
                            <div
                                class="kt-menu-accordion relative gap-1 ps-[22px] before:absolute before:bottom-0 before:start-[32px] before:top-0 before:border-s before:border-border">
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/network/user-cards/mini-cards" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Mini Cards
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/network/user-table/team-crew" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Team Crew
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/network/user-cards/author" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Author
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/network/user-cards/nft" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            NFT
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/network/user-cards/social" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Social
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="kt-menu-item" data-kt-menu-item-toggle="accordion"
                            data-kt-menu-item-trigger="click">
                            <div class="kt-menu-link grow cursor-pointer gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px]"
                                tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-medium kt-menu-link-hover:!text-primary me-1 font-normal text-foreground">
                                    User Table
                                </span>
                                <span
                                    class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                    <span class="kt-menu-item-show:hidden inline-flex">
                                        <i class="ki-filled ki-plus text-[11px]">
                                        </i>
                                    </span>
                                    <span class="kt-menu-item-show:inline-flex hidden">
                                        <i class="ki-filled ki-minus text-[11px]">
                                        </i>
                                    </span>
                                </span>
                            </div>
                            <div
                                class="kt-menu-accordion relative gap-1 ps-[22px] before:absolute before:bottom-0 before:start-[32px] before:top-0 before:border-s before:border-border">
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/network/user-table/team-crew" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Team Crew
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/network/user-table/app-roster" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            App Roster
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/network/user-table/market-authors" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Market Authors
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/network/user-table/saas-users" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            SaaS Users
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/network/user-table/store-clients" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Store Clients
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/network/user-table/visitors" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Visitors
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="kt-menu-item">
                            <div class="kt-menu-label grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px]"
                                href="#" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span class="kt-menu-title text-2sm font-normal text-foreground">
                                    Cooperations
                                </span>
                                <span class="kt-menu-badge me-[-10px]">
                                    <span class="kt-badge kt-badge-sm text-accent-foreground/60">
                                        Soon
                                    </span>
                                </span>
                            </div>
                        </div>
                        <div class="kt-menu-item">
                            <div class="kt-menu-label grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px]"
                                href="#" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span class="kt-menu-title text-2sm font-normal text-foreground">
                                    Leads
                                </span>
                                <span class="kt-menu-badge me-[-10px]">
                                    <span class="kt-badge kt-badge-sm text-accent-foreground/60">
                                        Soon
                                    </span>
                                </span>
                            </div>
                        </div>
                        <div class="kt-menu-item">
                            <div class="kt-menu-label grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px]"
                                href="#" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span class="kt-menu-title text-2sm font-normal text-foreground">
                                    Donators
                                </span>
                                <span class="kt-menu-badge me-[-10px]">
                                    <span class="kt-badge kt-badge-sm text-accent-foreground/60">
                                        Soon
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="kt-menu-item" data-kt-menu-item-toggle="accordion" data-kt-menu-item-trigger="click">
                    <div class="kt-menu-link flex grow cursor-pointer items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-security-user text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            Authentication
                        </span>
                        <span
                            class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                            <span class="kt-menu-item-show:hidden inline-flex">
                                <i class="ki-filled ki-plus text-[11px]">
                                </i>
                            </span>
                            <span class="kt-menu-item-show:inline-flex hidden">
                                <i class="ki-filled ki-minus text-[11px]">
                                </i>
                            </span>
                        </span>
                    </div>
                    <div
                        class="kt-menu-accordion relative gap-1 ps-[10px] before:absolute before:bottom-0 before:start-[20px] before:top-0 before:border-s before:border-border">
                        <div class="kt-menu-item" data-kt-menu-item-toggle="accordion"
                            data-kt-menu-item-trigger="click">
                            <div class="kt-menu-link grow cursor-pointer gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px]"
                                tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-medium kt-menu-link-hover:!text-primary me-1 font-normal text-foreground">
                                    Classic
                                </span>
                                <span
                                    class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                    <span class="kt-menu-item-show:hidden inline-flex">
                                        <i class="ki-filled ki-plus text-[11px]">
                                        </i>
                                    </span>
                                    <span class="kt-menu-item-show:inline-flex hidden">
                                        <i class="ki-filled ki-minus text-[11px]">
                                        </i>
                                    </span>
                                </span>
                            </div>
                            <div
                                class="kt-menu-accordion relative gap-1 ps-[22px] before:absolute before:bottom-0 before:start-[32px] before:top-0 before:border-s before:border-border">
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/authentication/branded/sign-in" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Sign In
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/authentication/branded/sign-up" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Sign Up
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/authentication/branded/2fa" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            2FA
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/authentication/branded/reset-password/check-email" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Check Email
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item" data-kt-menu-item-toggle="accordion"
                                    data-kt-menu-item-trigger="click">
                                    <div class="kt-menu-link grow cursor-pointer gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px]"
                                        tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-medium kt-menu-link-hover:!text-primary me-1 font-normal text-foreground">
                                            Reset Password
                                        </span>
                                        <span
                                            class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                            <span class="kt-menu-item-show:hidden inline-flex">
                                                <i class="ki-filled ki-plus text-[11px]">
                                                </i>
                                            </span>
                                            <span class="kt-menu-item-show:inline-flex hidden">
                                                <i class="ki-filled ki-minus text-[11px]">
                                                </i>
                                            </span>
                                        </span>
                                    </div>
                                    <div
                                        class="kt-menu-accordion relative gap-1 ps-[22px] before:absolute before:bottom-0 before:start-[32px] before:top-0 before:border-s before:border-border">
                                        <div class="kt-menu-item">
                                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                                href="/demo1/authentication/branded/reset-password/enter-email"
                                                tabindex="0">
                                                <span
                                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                                </span>
                                                <span
                                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                                    Enter Email
                                                </span>
                                            </a>
                                        </div>
                                        <div class="kt-menu-item">
                                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                                href="/demo1/authentication/branded/reset-password/check-email"
                                                tabindex="0">
                                                <span
                                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                                </span>
                                                <span
                                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                                    Check Email
                                                </span>
                                            </a>
                                        </div>
                                        <div class="kt-menu-item">
                                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                                href="/demo1/authentication/branded/reset-password/change-password"
                                                tabindex="0">
                                                <span
                                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                                </span>
                                                <span
                                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                                    Change Password
                                                </span>
                                            </a>
                                        </div>
                                        <div class="kt-menu-item">
                                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                                href="/demo1/authentication/branded/reset-password/password-changed"
                                                tabindex="0">
                                                <span
                                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                                </span>
                                                <span
                                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                                    Password is Changed
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="kt-menu-item" data-kt-menu-item-toggle="accordion"
                            data-kt-menu-item-trigger="click">
                            <div class="kt-menu-link grow cursor-pointer gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px]"
                                tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-medium kt-menu-link-hover:!text-primary me-1 font-normal text-foreground">
                                    Branded
                                </span>
                                <span
                                    class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                    <span class="kt-menu-item-show:hidden inline-flex">
                                        <i class="ki-filled ki-plus text-[11px]">
                                        </i>
                                    </span>
                                    <span class="kt-menu-item-show:inline-flex hidden">
                                        <i class="ki-filled ki-minus text-[11px]">
                                        </i>
                                    </span>
                                </span>
                            </div>
                            <div
                                class="kt-menu-accordion relative gap-1 ps-[22px] before:absolute before:bottom-0 before:start-[32px] before:top-0 before:border-s before:border-border">
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/authentication/branded/sign-in" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Sign In
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/authentication/branded/sign-up" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Sign Up
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/authentication/branded/2fa" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            2FA
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/authentication/branded/reset-password/check-email" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Check Email
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item" data-kt-menu-item-toggle="accordion"
                                    data-kt-menu-item-trigger="click">
                                    <div class="kt-menu-link grow cursor-pointer gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px]"
                                        tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-medium kt-menu-link-hover:!text-primary me-1 font-normal text-foreground">
                                            Reset Password
                                        </span>
                                        <span
                                            class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                            <span class="kt-menu-item-show:hidden inline-flex">
                                                <i class="ki-filled ki-plus text-[11px]">
                                                </i>
                                            </span>
                                            <span class="kt-menu-item-show:inline-flex hidden">
                                                <i class="ki-filled ki-minus text-[11px]">
                                                </i>
                                            </span>
                                        </span>
                                    </div>
                                    <div
                                        class="kt-menu-accordion relative gap-1 ps-[22px] before:absolute before:bottom-0 before:start-[32px] before:top-0 before:border-s before:border-border">
                                        <div class="kt-menu-item">
                                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                                href="/demo1/authentication/branded/reset-password/enter-email"
                                                tabindex="0">
                                                <span
                                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                                </span>
                                                <span
                                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                                    Enter Email
                                                </span>
                                            </a>
                                        </div>
                                        <div class="kt-menu-item">
                                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                                href="/demo1/authentication/branded/reset-password/check-email"
                                                tabindex="0">
                                                <span
                                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                                </span>
                                                <span
                                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                                    Check Email
                                                </span>
                                            </a>
                                        </div>
                                        <div class="kt-menu-item">
                                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                                href="/demo1/authentication/branded/reset-password/change-password"
                                                tabindex="0">
                                                <span
                                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                                </span>
                                                <span
                                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                                    Change Password
                                                </span>
                                            </a>
                                        </div>
                                        <div class="kt-menu-item">
                                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                                href="/demo1/authentication/branded/reset-password/password-changed"
                                                tabindex="0">
                                                <span
                                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                                </span>
                                                <span
                                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                                    Password is Changed
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/authentication/welcome-message" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Welcome Message
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/authentication/account-deactivated" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Account Deactivated
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/authentication/error-404" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Error 404
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/authentication/error-500" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Error 500
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="kt-menu-item pt-2.25 pb-px">
                    <span
                        class="kt-menu-heading pe-[10px] ps-[10px] text-xs font-medium uppercase text-muted-foreground">
                        Apps
                    </span>
                </div>
                <div class="kt-menu-item" data-kt-menu-item-toggle="accordion" data-kt-menu-item-trigger="click">
                    <div class="kt-menu-link flex grow cursor-pointer items-center gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-users text-lg">
                            </i>
                        </span>
                        <span
                            class="kt-menu-title kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary text-sm font-medium text-foreground">
                            Store - Client
                        </span>
                        <span
                            class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                            <span class="kt-menu-item-show:hidden inline-flex">
                                <i class="ki-filled ki-plus text-[11px]">
                                </i>
                            </span>
                            <span class="kt-menu-item-show:inline-flex hidden">
                                <i class="ki-filled ki-minus text-[11px]">
                                </i>
                            </span>
                        </span>
                    </div>
                    <div
                        class="kt-menu-accordion relative gap-1 ps-[10px] before:absolute before:bottom-0 before:start-[20px] before:top-0 before:border-s before:border-border">
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/store-client/home" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Home
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/store-client/search-results-grid" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Search Results - Grid
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/store-client/search-results-list" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Search Results - List
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/store-client/product-details" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Product Details
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/store-client/shopping-cart" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Shopping Cart
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/store-client/wishlist" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Wishlist
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item" data-kt-menu-item-toggle="accordion"
                            data-kt-menu-item-trigger="click">
                            <div class="kt-menu-link grow cursor-pointer gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px]"
                                tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-medium kt-menu-link-hover:!text-primary me-1 font-normal text-foreground">
                                    Checkout
                                </span>
                                <span
                                    class="kt-menu-arrow me-[-10px] ms-1 w-[20px] shrink-0 justify-end text-muted-foreground">
                                    <span class="kt-menu-item-show:hidden inline-flex">
                                        <i class="ki-filled ki-plus text-[11px]">
                                        </i>
                                    </span>
                                    <span class="kt-menu-item-show:inline-flex hidden">
                                        <i class="ki-filled ki-minus text-[11px]">
                                        </i>
                                    </span>
                                </span>
                            </div>
                            <div
                                class="kt-menu-accordion relative gap-1 ps-[22px] before:absolute before:bottom-0 before:start-[32px] before:top-0 before:border-s before:border-border">
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/store-client/checkout/order-summary" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Order Summary
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/store-client/checkout/shipping-info" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Shipping Info
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/store-client/checkout/payment-method" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Payment Method
                                        </span>
                                    </a>
                                </div>
                                <div class="kt-menu-item">
                                    <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[5px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                        href="/demo1/store-client/checkout/order-placed" tabindex="0">
                                        <span
                                            class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                        </span>
                                        <span
                                            class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                            Order Placed
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/store-client/my-orders" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    My Orders
                                </span>
                            </a>
                        </div>
                        <div class="kt-menu-item">
                            <a class="kt-menu-link kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 grow items-center gap-[14px] border border-transparent py-[8px] pe-[10px] ps-[10px] hover:rounded-lg"
                                href="/demo1/store-client/order-receipt" tabindex="0">
                                <span
                                    class="kt-menu-bullet kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary relative -start-[3px] flex w-[6px] before:absolute before:top-0 before:size-[6px] before:-translate-y-1/2 before:rounded-full rtl:start-0 rtl:before:translate-x-1/2">
                                </span>
                                <span
                                    class="kt-menu-title text-2sm kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary font-normal text-foreground">
                                    Order Receipt
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="kt-menu-item">
                    <div class="kt-menu-label gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        href="" tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-setting text-lg">
                            </i>
                        </span>
                        <span class="kt-menu-title text-sm font-medium text-foreground">
                            Store - Admin
                        </span>
                        <span class="kt-menu-badge me-[-10px]">
                            <span class="kt-badge kt-badge-sm text-accent-foreground/60">
                                Soon
                            </span>
                        </span>
                    </div>
                </div>
                <div class="kt-menu-item">
                    <div class="kt-menu-label gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        href="" tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-python text-lg">
                            </i>
                        </span>
                        <span class="kt-menu-title text-sm font-medium text-foreground">
                            Store - Services
                        </span>
                        <span class="kt-menu-badge me-[-10px]">
                            <span class="kt-badge kt-badge-sm text-accent-foreground/60">
                                Soon
                            </span>
                        </span>
                    </div>
                </div>
                <div class="kt-menu-item">
                    <div class="kt-menu-label gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        href="" tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-artificial-intelligence text-lg">
                            </i>
                        </span>
                        <span class="kt-menu-title text-sm font-medium text-foreground">
                            AI Promt
                        </span>
                        <span class="kt-menu-badge me-[-10px]">
                            <span class="kt-badge kt-badge-sm text-accent-foreground/60">
                                Soon
                            </span>
                        </span>
                    </div>
                </div>
                <div class="kt-menu-item">
                    <div class="kt-menu-label gap-[10px] border border-transparent py-[6px] pe-[10px] ps-[10px]"
                        href="" tabindex="0">
                        <span class="kt-menu-icon w-[20px] items-start text-muted-foreground">
                            <i class="ki-filled ki-cheque text-lg">
                            </i>
                        </span>
                        <span class="kt-menu-title text-sm font-medium text-foreground">
                            Invoice Generator
                        </span>
                        <span class="kt-menu-badge me-[-10px]">
                            <span class="kt-badge kt-badge-sm text-accent-foreground/60">
                                Soon
                            </span>
                        </span>
                    </div>
                </div>
            </div>
            <!-- End of Sidebar Menu -->
        </div>
    </div>
</div>
<!-- End of Sidebar -->
