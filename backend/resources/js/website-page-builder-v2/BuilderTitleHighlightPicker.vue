<script setup lang="ts">
import { computed, ref } from 'vue'

const props = defineProps<{
  title: string
  modelValue: string
  highlightColor?: string
  label: string
  hint?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [string]
}>()

type WordToken = {
  kind: 'word'
  text: string
  start: number
  end: number
  line: number
  wordIndex: number
}

type Token =
  | WordToken
  | { kind: 'sep'; text: string }
  | { kind: 'br' }

const WORD_RE = /\p{L}+(?:['’\-]\p{L}+)*/gu

function parsePhrases(raw: string): string[] {
  return raw
    .split('|')
    .map((part) => part.trim())
    .filter((part) => part !== '')
}

function isLetter(char: string): boolean {
  return /\p{L}/u.test(char)
}

function findRanges(title: string, raw: string): Array<{ start: number; end: number; text: string }> {
  const phrases = parsePhrases(raw).sort((a, b) => b.length - a.length)
  const matches: Array<{ start: number; end: number; text: string }> = []

  for (const phrase of phrases) {
    let offset = 0
    while (offset <= title.length) {
      const pos = title.indexOf(phrase, offset)
      if (pos === -1) {
        break
      }
      const before = pos > 0 ? title[pos - 1] ?? '' : ''
      const after = title[pos + phrase.length] ?? ''
      const bounded = (before === '' || !isLetter(before)) && (after === '' || !isLetter(after))
      const overlaps = matches.some((item) => pos < item.end && pos + phrase.length > item.start)
      if (bounded && !overlaps) {
        matches.push({ start: pos, end: pos + phrase.length, text: title.slice(pos, pos + phrase.length) })
      }
      offset = pos + Math.max(1, phrase.length)
    }
  }

  return matches.sort((a, b) => a.start - b.start)
}

function tokenize(title: string): Token[] {
  const tokens: Token[] = []
  let last = 0
  let line = 0
  let wordIndex = 0
  const re = new RegExp(WORD_RE.source, WORD_RE.flags)
  let match: RegExpExecArray | null
  while ((match = re.exec(title)) !== null) {
    if (match.index > last) {
      pushSeparators(tokens, title.slice(last, match.index))
      line += (title.slice(last, match.index).match(/\n/g) ?? []).length
    }
    tokens.push({
      kind: 'word',
      text: match[0],
      start: match.index,
      end: match.index + match[0].length,
      line,
      wordIndex,
    })
    wordIndex += 1
    last = match.index + match[0].length
  }
  if (last < title.length) {
    pushSeparators(tokens, title.slice(last))
  }
  return tokens
}

function pushSeparators(tokens: Token[], chunk: string): void {
  const parts = chunk.split('\n')
  parts.forEach((part, index) => {
    if (part !== '') {
      tokens.push({ kind: 'sep', text: part })
    }
    if (index < parts.length - 1) {
      tokens.push({ kind: 'br' })
    }
  })
}

function joinPhrases(phrases: string[]): string {
  const seen = new Set<string>()
  const unique: string[] = []
  for (const phrase of phrases) {
    const key = phrase.trim()
    if (key === '' || seen.has(key)) {
      continue
    }
    seen.add(key)
    unique.push(key)
  }
  return unique.join(' | ')
}

const titleText = computed(() => props.title.replace(/\r\n/g, '\n').replace(/\r/g, '\n'))
const tokens = computed(() => tokenize(titleText.value))
const words = computed(() => tokens.value.filter((token): token is WordToken => token.kind === 'word'))
const ranges = computed(() => findRanges(titleText.value, props.modelValue))
const lastWordIndex = ref<number | null>(null)

const previewColor = computed(() => {
  const color = (props.highlightColor ?? '').trim()
  return /^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/.test(color) ? color : ''
})

function coveringPhrase(word: WordToken): string | null {
  const range = ranges.value.find((item) => word.start >= item.start && word.end <= item.end)
  return range?.text ?? null
}

function isOn(word: WordToken): boolean {
  return coveringPhrase(word) !== null
}

function commit(phrases: string[]): void {
  emit('update:modelValue', joinPhrases(phrases))
}

function currentPhrases(): string[] {
  return parsePhrases(props.modelValue)
}

function toggleWord(word: WordToken, shiftKey: boolean): void {
  if (shiftKey && lastWordIndex.value !== null) {
    const startWord = words.value.find((item) => item.wordIndex === lastWordIndex.value)
    if (startWord && startWord.line === word.line) {
      const from = Math.min(startWord.start, word.start)
      const to = Math.max(startWord.end, word.end)
      const phrase = titleText.value.slice(from, to)
      const next = currentPhrases().filter((item) => {
        const pos = titleText.value.indexOf(item)
        if (pos === -1) {
          return true
        }
        return !(pos < to && pos + item.length > from)
      })
      next.push(phrase)
      lastWordIndex.value = word.wordIndex
      commit(next)
      return
    }
  }

  const covering = coveringPhrase(word)
  lastWordIndex.value = word.wordIndex
  if (covering) {
    commit(currentPhrases().filter((item) => item !== covering))
    return
  }
  commit([...currentPhrases(), word.text])
}

function onWordClick(event: MouseEvent, word: WordToken): void {
  event.preventDefault()
  toggleWord(word, event.shiftKey)
}

function onInput(value: string): void {
  emit('update:modelValue', value)
}
</script>

<template>
  <div class="builder-field builder-highlight-field">
    <span>{{ label }}</span>
    <div v-if="words.length === 0" class="builder-highlight-picker builder-highlight-picker--empty">
      Typ eerst een titel. Daarna kun je hier woorden aanklikken.
    </div>
    <div v-else class="builder-highlight-picker" role="group" :aria-label="label">
      <template v-for="(token, index) in tokens" :key="`${token.kind}-${index}`">
        <button
          v-if="token.kind === 'word'"
          type="button"
          class="builder-highlight-word"
          :class="{ 'is-on': isOn(token) }"
          :style="isOn(token) && previewColor ? { color: previewColor } : undefined"
          :aria-pressed="isOn(token)"
          @click="onWordClick($event, token)"
        >{{ token.text }}</button>
        <br v-else-if="token.kind === 'br'">
        <span v-else class="builder-highlight-sep">{{ token.text }}</span>
      </template>
    </div>
    <input
      class="kt-input"
      :value="modelValue"
      placeholder="zelf boeken | boek"
      @input="onInput(($event.target as HTMLInputElement).value)"
    >
    <p class="builder-field-hint">{{ hint }}</p>
  </div>
</template>

<style scoped>
.builder-highlight-picker {
  min-height: 2.75rem;
  padding: 0.6rem 0.75rem;
  border: 1px solid var(--border);
  border-radius: 0.55rem;
  background: var(--background);
  line-height: 1.7;
  white-space: pre-wrap;
}

.builder-highlight-picker--empty {
  color: var(--muted-foreground);
  font-size: 0.8125rem;
}

.builder-highlight-word {
  display: inline;
  margin: 0;
  padding: 0.05rem 0.12rem;
  border: 0;
  border-radius: 0.28rem;
  background: transparent;
  color: inherit;
  font: inherit;
  font-weight: 650;
  line-height: inherit;
  cursor: pointer;
}

.builder-highlight-word:hover {
  background: color-mix(in srgb, var(--theme-primary, #2563eb) 14%, transparent);
}

.builder-highlight-word.is-on {
  background: color-mix(in srgb, var(--theme-primary, #2563eb) 22%, transparent);
}

.builder-highlight-sep {
  white-space: pre-wrap;
}
</style>
