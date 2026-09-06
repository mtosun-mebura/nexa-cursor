import { createApp } from 'vue'
import IncidentApp from './incidents/IncidentApp.vue'

declare global {
  interface Window {
    __INCIDENT_APP__?: Record<string, unknown>
  }
}

const bootstrap = window.__INCIDENT_APP__
const mountEl = document.getElementById('incident-app')

if (bootstrap && mountEl) {
  createApp(IncidentApp, { bootstrap }).mount(mountEl)
}
