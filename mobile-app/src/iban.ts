import { t } from './i18n';
/** Zelfde landlengtes als iban_country_length() in de backend. */
const IBAN_LENGTHS: Record<string, number> = {
  AD: 24, AE: 23, AL: 28, AT: 20, AZ: 28, BA: 20, BE: 16, BG: 22, BH: 22, BR: 29, BY: 28,
  CH: 21, CR: 22, CY: 28, CZ: 24, DE: 22, DK: 18, DO: 28, EE: 20, EG: 29, ES: 24, FI: 18,
  FO: 18, FR: 27, GB: 22, GE: 22, GI: 23, GL: 18, GR: 27, GT: 28, HR: 21, HU: 28, IE: 22,
  IL: 23, IQ: 23, IS: 26, IT: 27, JO: 30, KW: 30, KZ: 20, LB: 28, LC: 32, LI: 21, LT: 20,
  LU: 20, LV: 21, MC: 27, MD: 24, ME: 22, MK: 19, MR: 27, MT: 31, MU: 30, NL: 18, NO: 15,
  PK: 24, PL: 28, PS: 29, PT: 25, QA: 29, RO: 24, RS: 22, SA: 24, SC: 31, SE: 24, SI: 19,
  SK: 24, SM: 27, ST: 25, TL: 23, TN: 24, TR: 26, UA: 29, VA: 22, VG: 24, XK: 20,
};

function ibanMod97(iban: string): boolean {
  const rearranged = iban.slice(4) + iban.slice(0, 4);
  let checksum = 0;
  for (const char of rearranged) {
    const digits = char >= 'A' && char <= 'Z' ? String(char.charCodeAt(0) - 55) : char;
    for (const digit of digits) {
      checksum = (checksum * 10 + Number(digit)) % 97;
    }
  }
  return checksum === 1;
}

/**
 * null = geldig.
 * duringTyping toont nog geen fout zolang het land-aantal tekens niet vol is.
 * Een vol nummer wordt altijd gecontroleerd op landlengte en ISO 13616 mod-97.
 */
export function ibanFieldError(value: string, duringTyping = false): string | null {
  const iban = value.replace(/[\s-]+/g, '').toUpperCase();
  if (!iban) return duringTyping ? null : 'Vul een IBAN in.';
  if (!/^[A-Z0-9]+$/.test(iban)) return t("Dit IBAN klopt niet.");
  if (iban.length >= 2 && !/^[A-Z]{2}/.test(iban.slice(0, 2))) return t("Dit IBAN klopt niet.");
  if (iban.length >= 4 && !/^[A-Z]{2}[0-9]{2}/.test(iban.slice(0, 4))) return t("Dit IBAN klopt niet.");
  if (iban.length < 2) return duringTyping ? null : t("Dit IBAN klopt niet.");

  const expected = IBAN_LENGTHS[iban.slice(0, 2)];
  if (!expected) {
    if (duringTyping && iban.length < 4) return null;
    return t("Dit IBAN klopt niet.");
  }
  if (iban.length !== expected) {
    if (duringTyping && iban.length < expected) return null;
    return t("Dit IBAN klopt niet.");
  }
  if (!ibanMod97(iban)) return t("Dit IBAN klopt niet.");
  return null;
}
