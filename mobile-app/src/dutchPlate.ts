/** Nederlandse sidecodes. L = letter, D = cijfer, streepjes op de groepsgrenzen. */
const FORMATS = [
  'LL-DD-DD',
  'DD-DD-LL',
  'DD-LL-DD',
  'LL-DD-LL',
  'LL-LL-DD',
  'DD-LL-LL',
  'DD-LLL-D',
  'D-LLL-DD',
  'LL-DDD-L',
  'L-DDD-LL',
  'LLL-DD-L',
  'L-DD-LLL',
  'D-LL-DDD',
  'DDD-LL-D',
];

function matchesPrefix(format: string, chars: string): boolean {
  const slots = format.replace(/-/g, '');
  if (chars.length > slots.length) return false;
  for (let i = 0; i < chars.length; i++) {
    const digit = chars[i] >= '0' && chars[i] <= '9';
    if (slots[i] === 'D' && !digit) return false;
    if (slots[i] === 'L' && digit) return false;
  }
  return true;
}

function dashBefore(format: string): Set<number> {
  const dashes = new Set<number>();
  let index = 0;
  for (const token of format) {
    if (token === '-') dashes.add(index);
    else index += 1;
  }
  return dashes;
}

/** Zet streepjes in een Nederlands kenteken op basis van de ingevoerde tekens. */
export function formatDutchPlate(input: string): string {
  const chars = input.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
  if (!chars) return '';
  const matches = FORMATS.filter((format) => matchesPrefix(format, chars));
  if (matches.length === 0) return chars;
  let out = '';
  for (let i = 0; i < chars.length; i++) {
    if (i > 0 && matches.every((format) => dashBefore(format).has(i))) out += '-';
    out += chars[i];
  }
  return out;
}
