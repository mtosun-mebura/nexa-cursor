import { createApp } from 'vue'
import WebsitePageBuilderV2 from './WebsitePageBuilderV2.vue'
import type { BuilderBootstrap } from './website-page-builder-v2/types'

declare global {
  interface Window {
    __WEBSITE_BUILDER_V2__?: BuilderBootstrap
  }
}

const bootstrap = window.__WEBSITE_BUILDER_V2__
const mountEl = document.getElementById('website-page-builder-v2')

if (bootstrap && mountEl) {
  createApp(WebsitePageBuilderV2, { bootstrap }).mount(mountEl)
}
