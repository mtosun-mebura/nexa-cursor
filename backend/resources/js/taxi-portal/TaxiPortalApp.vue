<script setup lang="ts">
import { computed, ref } from 'vue'

type TabKey = 'rides' | 'profile' | 'invoices'

const tab = ref<TabKey>('rides')

const mountEl = document.getElementById('taxi-portal-app')
const logoAlt = mountEl?.dataset.logoAlt || 'Nexa'
const logoHref = mountEl?.dataset.logoHref || '#'
const logoLight = mountEl?.dataset.logoLight || '/metronic-v9.4.13/demo1/assets/media/app/default-logo.svg'
const logoDark = mountEl?.dataset.logoDark || '/metronic-v9.4.13/demo1/assets/media/app/default-logo-dark.svg'

const title = computed(() => {
  switch (tab.value) {
    case 'rides':
      return 'Mijn ritten'
    case 'profile':
      return 'Mijn gegevens'
    case 'invoices':
      return 'Facturen'
  }
})

const rides = ref([
  {
    id: 1024,
    from: 'Schiphol',
    to: 'Amsterdam',
    at: '2026-06-02 10:30',
    status: 'Gepland',
    amount: '€ 52,00',
  },
  {
    id: 1008,
    from: 'Amsterdam',
    to: 'Utrecht',
    at: '2026-05-30 18:10',
    status: 'Voltooid',
    amount: '€ 84,50',
  },
])

const profile = ref({
  first_name: 'Jan',
  last_name: 'Jansen',
  email: 'jan@voorbeeld.nl',
  phone: '0612345678',
})
</script>

<template>
  <div
    class="flex grow min-h-[calc(100vh-(4rem+1px)-var(--portal-footer-h))] md:min-h-[calc(100vh-(5rem+1px)-var(--portal-footer-h))] w-full bg-white dark:!bg-[#111827]"
    style="--portal-footer-h: 44px; --portal-sidebar-w: 188px;"
  >
    <!-- Sidebar -->
    <aside
      id="taxi_portal_sidebar"
      class="kt-sidebar w-[var(--portal-sidebar-w)] bg-white dark:!bg-[#111827] border-e border-gray-200 dark:!border-gray-600 fixed top-[calc(4rem+1px)] md:top-[calc(5rem+1px)] z-20 hidden lg:flex flex-col items-stretch shrink-0 [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]"
      style="bottom: var(--portal-footer-h);"
      data-kt-drawer="true"
      data-kt-drawer-class="kt-drawer kt-drawer-start top-0 bottom-0"
    >
      <div class="kt-sidebar-content flex grow shrink-0 py-5 pe-2 bg-white dark:!bg-[#111827]" id="sidebar_content">
        <div
          id="sidebar_scrollable"
          class="kt-scrollable-y-hover grow shrink-0 flex ps-2 lg:ps-5 pe-1 lg:pe-3 bg-white dark:!bg-[#111827]"
          data-kt-scrollable="true"
          data-kt-scrollable-height="auto"
          data-kt-scrollable-offset="0px"
          data-kt-scrollable-wrappers="#sidebar_content"
        >
          <nav class="kt-menu flex flex-col grow gap-1" data-kt-menu="true" data-kt-menu-accordion-expand-all="false" id="sidebar_menu">
            <div class="kt-menu-item" :class="tab === 'rides' ? 'active' : ''">
              <button
                type="button"
                class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                @click="tab = 'rides'"
              >
                <span class="kt-menu-icon items-start text-muted-foreground w-[20px]"><i class="ki-filled ki-calendar text-lg"></i></span>
                <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                  Ritten
                </span>
              </button>
            </div>

            <div class="kt-menu-item" :class="tab === 'invoices' ? 'active' : ''">
              <button
                type="button"
                class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                @click="tab = 'invoices'"
              >
                <span class="kt-menu-icon items-start text-muted-foreground w-[20px]"><i class="ki-filled ki-document text-lg"></i></span>
                <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                  Facturen
                </span>
              </button>
            </div>

            <div class="kt-menu-item" :class="tab === 'profile' ? 'active' : ''">
              <button
                type="button"
                class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                @click="tab = 'profile'"
              >
                <span class="kt-menu-icon items-start text-muted-foreground w-[20px]"><i class="ki-filled ki-profile-circle text-lg"></i></span>
                <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                  Mijn gegevens
                </span>
              </button>
            </div>
          </nav>
        </div>
      </div>
    </aside>
    <!-- /Sidebar -->

    <div
      class="kt-wrapper flex grow flex-col bg-white dark:!bg-[#111827] lg:ms-[var(--portal-sidebar-w)]"
    >
      <main class="grow">
        <div class="container-custom pt-2.5 pb-10">
          <div class="flex items-center justify-between mb-4 md:mb-6">
            <div class="flex items-center gap-3">
              <button class="kt-btn kt-btn-icon kt-btn-ghost lg:hidden" data-kt-drawer-toggle="#taxi_portal_sidebar" type="button" aria-label="Menu">
                <i class="ki-filled ki-menu"></i>
              </button>
              <h2 class="text-base font-semibold text-foreground">{{ title }}</h2>
            </div>
            <button
              type="button"
              class="kt-btn kt-btn-primary border border-blue-600 bg-blue-600 text-white hover:border-blue-700 hover:bg-blue-700 dark:border-blue-500 dark:bg-blue-500 dark:hover:border-blue-400 dark:hover:bg-blue-400"
            >
              <i class="ki-filled ki-plus"></i> Nieuwe rit
            </button>
          </div>

          <!-- Rides -->
          <section v-if="tab === 'rides'" class="kt-card bg-white dark:!bg-[#111827] border !border-gray-200 dark:!border-gray-600">
            <div class="kt-card-header">
              <h3 class="kt-card-title">Ritten</h3>
            </div>
            <div class="kt-card-content">
              <div class="kt-scrollable-x-auto">
                <table class="kt-table table-auto kt-table-border">
                  <thead>
                    <tr>
                      <th class="min-w-[80px]">#</th>
                      <th class="min-w-[220px]">Van → Naar</th>
                      <th class="min-w-[180px]">Datum</th>
                      <th class="min-w-[140px]">Status</th>
                      <th class="min-w-[120px] text-end">Bedrag</th>
                      <th class="min-w-[160px]"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="r in rides" :key="r.id">
                      <td>{{ r.id }}</td>
                      <td>{{ r.from }} → {{ r.to }}</td>
                      <td>{{ r.at }}</td>
                      <td>
                        <span class="kt-badge" :class="r.status === 'Voltooid' ? 'kt-badge-success' : 'kt-badge-info'">
                          {{ r.status }}
                        </span>
                      </td>
                      <td class="text-end">{{ r.amount }}</td>
                      <td class="text-end">
                        <button class="kt-btn kt-btn-sm kt-btn-outline kt-btn-primary" type="button">Details</button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </section>

          <!-- Invoices -->
          <section v-else-if="tab === 'invoices'" class="kt-card bg-white dark:!bg-[#111827] border !border-gray-200 dark:!border-gray-600">
            <div class="kt-card-header">
              <h3 class="kt-card-title">Facturen</h3>
            </div>
            <div class="kt-card-content">
              <div class="kt-alert kt-alert-info" role="alert">
                <div class="kt-alert-title">Volgende stap</div>
                <div class="kt-alert-description">
                  Hier koppelen we straks de API: per rit de factuur openen en downloaden (PDF).
                </div>
              </div>
            </div>
          </section>

          <!-- Profile -->
          <section v-else class="kt-card bg-white dark:!bg-[#111827] border !border-gray-200 dark:!border-gray-600">
            <div class="kt-card-header">
              <h3 class="kt-card-title">Mijn gegevens</h3>
            </div>
            <div class="kt-card-content">
              <div class="grid gap-4 md:grid-cols-2">
                <div>
                  <label class="kt-form-label">Voornaam</label>
                  <input v-model="profile.first_name" class="kt-input" type="text" />
                </div>
                <div>
                  <label class="kt-form-label">Achternaam</label>
                  <input v-model="profile.last_name" class="kt-input" type="text" />
                </div>
                <div>
                  <label class="kt-form-label">E-mail</label>
                  <input v-model="profile.email" class="kt-input" type="email" />
                </div>
                <div>
                  <label class="kt-form-label">Telefoon</label>
                  <input v-model="profile.phone" class="kt-input" type="tel" />
                </div>
              </div>

              <div class="mt-6 flex justify-end">
                <button class="kt-btn kt-btn-primary" type="button">Opslaan</button>
              </div>
            </div>
          </section>
        </div>
      </main>
    </div>
  </div>
</template>

