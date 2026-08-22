const flushes = new Set<() => void>()

export function registerWysiwygFlush(flush: () => void): () => void {
  flushes.add(flush)
  return () => {
    flushes.delete(flush)
  }
}

export function flushAllWysiwygFields(): void {
  flushes.forEach((flush) => flush())
}
