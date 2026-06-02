import { createApp, defineComponent, ref } from 'vue'

const App = defineComponent({
  name: 'MetronicVueDemo1Playground',
  setup() {
    const count = ref(0)

    return { count }
  },
  template: `
    <div class="flex flex-col gap-4">
      <div class="kt-alert kt-alert-info" role="alert">
        <div class="kt-alert-title">Playground</div>
        <div class="kt-alert-description">
          Dit is een afgeschermde Vue-pagina met Metronic demo1 styling.
          Hier gaan we straks componenten (tables, forms, cards, modals) uitwerken voor Mijn Taxi.
        </div>
      </div>

      <div class="flex items-center gap-3">
        <button type="button" class="kt-btn kt-btn-primary" @click="count++">Klik</button>
        <span class="text-sm text-muted-foreground">Count: <span class="font-semibold text-foreground">{{ count }}</span></span>
      </div>

      <div class="kt-card">
        <div class="kt-card-header">
          <h4 class="kt-card-title">Voorbeeld card</h4>
        </div>
        <div class="kt-card-content">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="flex flex-col gap-1">
              <span class="kt-label">Naam</span>
              <input class="kt-input" placeholder="Jan Jansen" />
            </label>
            <label class="flex flex-col gap-1">
              <span class="kt-label">E-mail</span>
              <input class="kt-input" placeholder="jan@voorbeeld.nl" />
            </label>
          </div>
        </div>
      </div>
    </div>
  `,
})

const el = document.getElementById('metronic-vue-demo1-app')
if (el) {
  createApp(App).mount(el)
}

