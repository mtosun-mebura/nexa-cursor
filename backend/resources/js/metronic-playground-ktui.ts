/** Herinitialiseer Metronic KTUI/KTComponents na Vue-render (modal, tabs, accordion, …). */
export function initPlaygroundStepComponents(): void {
  const w = window as Window & {
    KTComponents?: { init?: () => void }
    KTMenu?: { init?: () => void }
  }

  w.KTComponents?.init?.()
  w.KTMenu?.init?.()
}
