export function getByPath(data: Record<string, unknown>, path: string): unknown {
  if (!path.includes('.')) {
    return data[path]
  }
  let cursor: unknown = data
  for (const key of path.split('.')) {
    if (!cursor || typeof cursor !== 'object' || Array.isArray(cursor)) {
      return undefined
    }
    cursor = (cursor as Record<string, unknown>)[key]
  }
  return cursor
}

export function buildPatchForPath(
  data: Record<string, unknown>,
  path: string,
  value: unknown
): Record<string, unknown> {
  if (!path.includes('.')) {
    return { [path]: value }
  }

  const keys = path.split('.')
  const rootKey = keys[0]
  const rootValue = data[rootKey]
  const root =
    rootValue && typeof rootValue === 'object' && !Array.isArray(rootValue)
      ? { ...(rootValue as Record<string, unknown>) }
      : {}

  let cursor: Record<string, unknown> = root
  for (let i = 1; i < keys.length - 1; i++) {
    const key = keys[i]
    const next = cursor[key]
    const cloned =
      next && typeof next === 'object' && !Array.isArray(next)
        ? { ...(next as Record<string, unknown>) }
        : {}
    cursor[key] = cloned
    cursor = cloned
  }

  cursor[keys[keys.length - 1]] = value
  return { [rootKey]: root }
}

export function deepMerge(
  base: Record<string, unknown>,
  overlay: Record<string, unknown>
): Record<string, unknown> {
  const result: Record<string, unknown> = { ...base }
  for (const [key, value] of Object.entries(overlay)) {
    if (
      value &&
      typeof value === 'object' &&
      !Array.isArray(value) &&
      result[key] &&
      typeof result[key] === 'object' &&
      !Array.isArray(result[key])
    ) {
      result[key] = deepMerge(
        result[key] as Record<string, unknown>,
        value as Record<string, unknown>
      )
    } else if (value !== undefined) {
      result[key] = value
    }
  }
  return result
}
