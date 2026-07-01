import { deepMerge } from './nested-data'

export function mergeFooterData(
  defaults: unknown,
  stored: Record<string, unknown>,
): Record<string, unknown> {
  const base =
    defaults && typeof defaults === 'object' && !Array.isArray(defaults)
      ? deepMerge({}, defaults as Record<string, unknown>)
      : {}
  return deepMerge(base, stored)
}
