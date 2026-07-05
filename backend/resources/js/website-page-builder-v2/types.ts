export type SectionCatalogItem = {
  type: string
  label: string
}

export type ComponentCatalogItem = {
  id: string
  name: string
  description: string
  moduleName: string
  sectionKey: string
}

export type PaletteDragPayload =
  | { kind: 'section'; type: string; label: string }
  | { kind: 'component'; sectionKey: string; label: string }

export type EmailTemplateOption = {
  id: number
  name: string
  type: string
}

export type PageMetaForm = {
  title: string
  slug: string
  pageType: string
  moduleName: string | null
  frontendThemeId: number | null
  isActive: boolean
  showInMenu: boolean
  sortOrder: number
  metaDescription: string
  companyId: number | null
}

export type ModuleOption = {
  name: string
  displayName: string
  themeId: number | null
}

export type ThemeOption = {
  id: number
  name: string
  slug: string
}

export type PageMetaTenantContext = {
  visible: boolean
  showCompanyDropdown: boolean
  storedCompanyName: string | null
  effectiveCompanyName: string | null
  companies: { id: number; name: string }[]
}

export type PageMetaOptions = {
  modules: ModuleOption[]
  themes: ThemeOption[]
  isCentralWelcome: boolean
  slugReadonly: boolean
  tenant: PageMetaTenantContext
}

export type BuilderBootstrap = {
  page: {
    id: number
    title: string
    slug: string
    pageType: string
    moduleName: string | null
    companyId: number | null
  }
  pageMeta: PageMetaForm
  pageMetaOptions: PageMetaOptions
  themeSlug: string
  themeName: string
  homeSections: Record<string, unknown>
  defaults: Record<string, unknown>
  catalog: {
    sections: SectionCatalogItem[]
    components: ComponentCatalogItem[]
  }
  componentDefaults: Record<string, Record<string, unknown>>
  routes: {
    save: string
    updateMeta: string
    generateSeo: string
    preview: string
    classicEdit: string
    index: string
    self: string
    uploadHeroImage: string
    uploadFooterLogo: string
    uploadWebsiteMedia: string
    websiteMediaServeBase: string
    postcodeLookup: string
  }
  googleMapsApiKey: string
  googleMapsMapId: string
  siteBrandingLogoUrl: string
  wizardBackUrl: string | null
  emailTemplates: EmailTemplateOption[]
}

export type CanvasBlock = {
  key: string
  baseType: string
  label: string
  isComponent: boolean
}
