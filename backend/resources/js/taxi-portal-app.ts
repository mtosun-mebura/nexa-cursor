import { createApp, defineComponent, computed, ref } from 'vue'

type TabKey = 'rides' | 'profile' | 'invoices'

const App = defineComponent({
  name: 'TaxiPortalApp',
  setup() {
    const tab = ref<TabKey>('rides')

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

    return { tab, title, rides, profile }
  },
  template: `
    <div class="flex grow">
      <!-- Sidebar -->
      <aside
        id="taxi_portal_sidebar"
        class="kt-sidebar bg-background border-e border-e-border fixed top-0 bottom-0 z-20 hidden lg:flex flex-col items-stretch shrink-0 [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]"
        data-kt-drawer="true"
        data-kt-drawer-class="kt-drawer kt-drawer-start top-0 bottom-0"
      >
        <div class="kt-sidebar-header hidden lg:flex items-center relative justify-between px-3 lg:px-6 shrink-0">
          <div class="kt-sidebar-logo min-w-0">
            <a class="dark:hidden" href="#">
              <img class="default-logo min-h-[22px] max-w-none" src="/metronic-v9.4.13/demo1/assets/media/app/default-logo.svg" />
              <img class="small-logo min-h-[22px] max-w-none" src="/metronic-v9.4.13/demo1/assets/media/app/mini-logo.svg" />
            </a>
            <a class="hidden dark:block" href="#">
              <img class="default-logo min-h-[22px] max-w-none" src="/metronic-v9.4.13/demo1/assets/media/app/default-logo-dark.svg" />
              <img class="small-logo min-h-[22px] max-w-none" src="/metronic-v9.4.13/demo1/assets/media/app/mini-logo.svg" />
            </a>
          </div>
        </div>

        <div class="kt-sidebar-content flex grow shrink-0 py-5 pe-2">
          <div class="kt-scrollable-y-hover grow shrink-0 flex ps-2 lg:ps-5 pe-1 lg:pe-3" data-kt-scrollable="true">
            <nav class="kt-menu flex flex-col grow gap-1" data-kt-menu="true">
              <div class="kt-menu-item">
                <button type="button"
                  class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                  :class="tab === 'rides' ? 'kt-menu-item-active:bg-accent/60 rounded-lg' : ''"
                  @click="tab = 'rides'"
                >
                  <span class="kt-menu-icon items-start text-muted-foreground w-[20px]"><i class="ki-filled ki-calendar text-lg"></i></span>
                  <span class="kt-menu-title text-sm font-medium text-foreground">Ritten</span>
                </button>
              </div>

              <div class="kt-menu-item">
                <button type="button"
                  class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                  :class="tab === 'invoices' ? 'kt-menu-item-active:bg-accent/60 rounded-lg' : ''"
                  @click="tab = 'invoices'"
                >
                  <span class="kt-menu-icon items-start text-muted-foreground w-[20px]"><i class="ki-filled ki-document text-lg"></i></span>
                  <span class="kt-menu-title text-sm font-medium text-foreground">Facturen</span>
                </button>
              </div>

              <div class="kt-menu-item">
                <button type="button"
                  class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]"
                  :class="tab === 'profile' ? 'kt-menu-item-active:bg-accent/60 rounded-lg' : ''"
                  @click="tab = 'profile'"
                >
                  <span class="kt-menu-icon items-start text-muted-foreground w-[20px]"><i class="ki-filled ki-profile-circle text-lg"></i></span>
                  <span class="kt-menu-title text-sm font-medium text-foreground">Mijn gegevens</span>
                </button>
              </div>
            </nav>
          </div>
        </div>
      </aside>
      <!-- /Sidebar -->

      <div class="kt-wrapper flex grow flex-col">
        <header class="kt-header fixed top-0 z-10 start-0 end-0 flex items-stretch shrink-0 bg-background border-b border-border" id="header">
          <div class="kt-container-fixed flex justify-between items-stretch lg:gap-4">
            <div class="flex gap-2.5 lg:hidden items-center -ms-1">
              <a class="shrink-0" href="#">
                <img class="max-h-[25px] w-full" src="/metronic-v9.4.13/demo1/assets/media/app/mini-logo.svg" />
              </a>
              <div class="flex items-center">
                <button class="kt-btn kt-btn-icon kt-btn-ghost" data-kt-drawer-toggle="#taxi_portal_sidebar" type="button">
                  <i class="ki-filled ki-menu"></i>
                </button>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <span class="text-sm text-muted-foreground">{{ title }}</span>
            </div>

            <div class="flex items-center gap-2">
              <button type="button" class="kt-btn kt-btn-outline kt-btn-primary">
                <i class="ki-filled ki-plus"></i> Nieuwe rit
              </button>
            </div>
          </div>
        </header>

        <main class="grow pt-[--kt-header-height]">
          <div class="kt-container-fixed py-6">
            <!-- Rides -->
            <section v-if="tab === 'rides'" class="kt-card">
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
            <section v-else-if="tab === 'invoices'" class="kt-card">
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
            <section v-else class="kt-card">
              <div class="kt-card-header">
                <h3 class="kt-card-title">Mijn gegevens</h3>
              </div>
              <div class="kt-card-content">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <label class="flex flex-col gap-1">
                    <span class="kt-label">Voornaam</span>
                    <input class="kt-input" v-model="profile.first_name" />
                  </label>
                  <label class="flex flex-col gap-1">
                    <span class="kt-label">Achternaam</span>
                    <input class="kt-input" v-model="profile.last_name" />
                  </label>
                  <label class="flex flex-col gap-1">
                    <span class="kt-label">E-mail</span>
                    <input class="kt-input" v-model="profile.email" />
                  </label>
                  <label class="flex flex-col gap-1">
                    <span class="kt-label">Telefoon</span>
                    <input class="kt-input" v-model="profile.phone" />
                  </label>
                </div>

                <div class="flex justify-end mt-5">
                  <button class="kt-btn kt-btn-primary" type="button">Opslaan</button>
                </div>
              </div>
            </section>
          </div>
        </main>
      </div>
    </div>
  `,
})

const el = document.getElementById('taxi-portal-app')
if (el) {
  createApp(App).mount(el)
}

