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

export type BuilderBootstrap = {
  page: {
    id: number
    title: string
    slug: string
    pageType: string
    moduleName: string | null
    companyId: number | null
  }
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
    preview: string
    classicEdit: string
    index: string
    self: string
    uploadHeroImage: string
    uploadWebsiteMedia: string
    websiteMediaServeBase: string
  }
  wizardBackUrl: string | null
  emailTemplates: EmailTemplateOption[]
}

export type CanvasBlock = {
  key: string
  baseType: string
  label: string
  isComponent: boolean
}
