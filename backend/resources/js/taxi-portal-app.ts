import { createApp } from 'vue'
import TaxiPortalApp from './taxi-portal/TaxiPortalApp.vue'

const el = document.getElementById('taxi-portal-app')
if (el) createApp(TaxiPortalApp).mount(el)
