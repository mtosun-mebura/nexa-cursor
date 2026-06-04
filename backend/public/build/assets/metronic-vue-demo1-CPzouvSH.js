import{c as a,d as e,r as s}from"./runtime-dom.esm-bundler-DLe3Rwn-.js";const l=e({name:"MetronicVueDemo1Playground",setup(){return{count:s(0)}},template:`
    <div class="flex flex-col gap-6">
      <div class="kt-alert kt-alert-info" role="alert">
        <div class="kt-alert-title">Component catalog</div>
        <div class="kt-alert-description">
          Dit is een afgeschermde Vue-pagina met Metronic demo1 layout + styling.
          We gebruiken dit als “showroom” om componenten te kiezen voor Mijn Taxi.
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="kt-card">
          <div class="kt-card-header">
            <h3 class="kt-card-title">Buttons & badges</h3>
          </div>
          <div class="kt-card-content flex flex-col gap-4">
            <div class="flex flex-wrap items-center gap-2">
              <button type="button" class="kt-btn kt-btn-primary" @click="count++">Primary</button>
              <button type="button" class="kt-btn kt-btn-outline kt-btn-primary">Outline</button>
              <button type="button" class="kt-btn kt-btn-ghost">Ghost</button>
              <button type="button" class="kt-btn kt-btn-danger">Danger</button>
              <button type="button" class="kt-btn kt-btn-success">Success</button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <span class="kt-badge kt-badge-info">Info</span>
              <span class="kt-badge kt-badge-success">Success</span>
              <span class="kt-badge kt-badge-warning">Warning</span>
              <span class="kt-badge kt-badge-danger">Danger</span>
              <span class="text-sm text-muted-foreground">Count: <span class="font-semibold text-foreground">{{ count }}</span></span>
            </div>
          </div>
        </div>

        <div class="kt-card">
          <div class="kt-card-header">
            <h3 class="kt-card-title">Form controls</h3>
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
              <label class="flex flex-col gap-1 md:col-span-2">
                <span class="kt-label">Opmerking</span>
                <textarea class="kt-input" rows="3" placeholder="Bijv. bel bij aankomst"></textarea>
              </label>
              <label class="flex items-center gap-2 md:col-span-2">
                <input type="checkbox" class="kt-checkbox" />
                <span class="text-sm text-foreground">Voorbeeld checkbox</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <div class="kt-card">
        <div class="kt-card-header flex items-center justify-between">
          <h3 class="kt-card-title">Table</h3>
          <button type="button" class="kt-btn kt-btn-outline kt-btn-primary" data-kt-modal-toggle="#playground_modal">
            Open modal
          </button>
        </div>
        <div class="kt-card-content">
          <div class="kt-scrollable-x-auto">
            <table class="kt-table table-auto kt-table-border">
              <thead>
                <tr>
                  <th class="min-w-[180px]">Rit</th>
                  <th class="min-w-[160px]">Datum</th>
                  <th class="min-w-[140px]">Status</th>
                  <th class="min-w-[120px] text-end">Bedrag</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Schiphol → Amsterdam</td>
                  <td>2026-06-02 10:30</td>
                  <td><span class="kt-badge kt-badge-info">Gepland</span></td>
                  <td class="text-end">€ 52,00</td>
                </tr>
                <tr>
                  <td>Amsterdam → Utrecht</td>
                  <td>2026-05-30 18:10</td>
                  <td><span class="kt-badge kt-badge-success">Voltooid</span></td>
                  <td class="text-end">€ 84,50</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="kt-modal" data-kt-modal="true" id="playground_modal">
        <div class="kt-modal-content max-w-[600px] top-[15%]">
          <div class="kt-modal-header py-4 px-5">
            <h3 class="kt-modal-title">Voorbeeld modal</h3>
            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-modal-dismiss="true" type="button">
              <i class="ki-filled ki-cross"></i>
            </button>
          </div>
          <div class="kt-modal-body p-5">
            <p class="text-sm text-muted-foreground">
              Dit is een Metronic modal. Als deze werkt, kunnen we dezelfde pattern gebruiken voor
              “factuur bekijken”, “rit annuleren”, etc.
            </p>
          </div>
          <div class="kt-modal-footer px-5 py-4 flex justify-end gap-2">
            <button class="kt-btn kt-btn-ghost" data-kt-modal-dismiss="true" type="button">Sluiten</button>
            <button class="kt-btn kt-btn-primary" data-kt-modal-dismiss="true" type="button">Oké</button>
          </div>
        </div>
      </div>
    </div>
  `}),t=document.getElementById("metronic-vue-demo1-app");t&&a(l).mount(t);
