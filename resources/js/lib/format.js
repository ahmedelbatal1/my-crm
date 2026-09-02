/*
 * Display formatting — specified in specs/003-design-system-shell/data-model.md §3.
 *
 * Every money and area value arrives from a MySQL `decimal(_,2)` column as a STRING
 * ("4500000.00"), so each helper coerces with Number() before formatting. Skipping that
 * is the specific bug this module exists to prevent.
 *
 * There is no automated test for this file by design (research.md #5): a JS test runner
 * would be a new dependency, and shelling out to node from PHPUnit is worse. The guard is
 * quickstart.md step B3, run by a human. Keep these functions trivial enough that this
 * stays a reasonable trade — if they grow, add Vitest.
 */

/** Shown wherever a value is absent, so it never reads as a recorded zero (FR-024). */
export const ABSENT = '—';

function isAbsent(value) {
  return value === null || value === undefined || value === '';
}

function toNumber(value) {
  const number = Number(value);

  return Number.isFinite(number) ? number : null;
}

/**
 * Money: grouped thousands, always exactly two decimals (FR-022). No currency symbol —
 * "EGP" is named once in the column header or field label, never per cell (FR-023).
 */
export function money(value) {
  if (isAbsent(value)) {
    return ABSENT;
  }

  const number = toNumber(value);

  if (number === null) {
    return ABSENT;
  }

  return new Intl.NumberFormat('en-EG', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(number);
}

/** Area: same treatment as money; the m² marker lives in the header (spec assumption). */
export function area(value) {
  return money(value);
}

/** Counts: grouped, no decimals. */
export function number(value) {
  if (isAbsent(value)) {
    return ABSENT;
  }

  const parsed = toNumber(value);

  if (parsed === null) {
    return ABSENT;
  }

  return new Intl.NumberFormat('en-EG').format(parsed);
}

/*
 * One unambiguous date format app-wide: 02 Sep 2026 (FR-024). Month names are pinned
 * rather than taken from Intl on purpose — ICU renders September as "Sept" in some
 * versions, and FR-024 asks for ONE format, not one that shifts with the runtime.
 */
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

export function date(value) {
  if (isAbsent(value)) {
    return ABSENT;
  }

  const parsed = new Date(value);

  if (Number.isNaN(parsed.getTime())) {
    return ABSENT;
  }

  const day = String(parsed.getDate()).padStart(2, '0');

  return `${day} ${MONTHS[parsed.getMonth()]} ${parsed.getFullYear()}`;
}

/** Turns a raw enum value into readable words, for the StatusBadge fallback (FR-019). */
export function humanise(value) {
  if (isAbsent(value)) {
    return ABSENT;
  }

  return String(value)
    .split('_')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}

export const formatters = { money, area, number, date };
