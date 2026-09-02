# Phase 1 Data Model: Design System & Application Shell

**Feature**: `003-design-system-shell` | **Date**: 2026-09-02

**This feature persists nothing.** No table, column, index, model, factory, or migration is added,
removed, or altered (FR-043, SC-014). Feature 002's schema is the schema.

What follows is therefore not a persistence model but the **presentation model**: the token
vocabulary, the value→appearance mappings, and the display-formatting rules that every component
reads from. These are the "entities" this feature actually defines, and each is written to be
directly checkable by the tests in research.md #5.

---

## 1. Design Token Set

Location: `resources/css/app.css`, inside `@theme`. Two tiers (research.md #1).

### Palette tier — raw ramps

| Token | Value | Notes |
|---|---|---|
| `--color-sand-50` | `#FAF7F2` | app ground |
| `--color-sand-100` | `#F4EEE5` | raised/inset surfaces, table header |
| `--color-sand-200` | `#E7DDCE` | decorative dividers |
| `--color-sand-400` | `#B39F86` | disabled control text |
| `--color-sand-500` | `#8C7A62` | **control borders** (≥3:1, see §4) |
| `--color-sand-600` | `#6B5C48` | muted/supporting text |
| `--color-ink-800` | `#362E24` | body text, Sold badge ground |
| `--color-ink-900` | `#221C15` | headings |
| `--color-clay-600` | `#96431F` | **primary action**, focus ring |
| `--color-clay-700` | `#7A3719` | primary hover, text links |

**Status families** — the four families below are the **single** semantic set (FR-002). Each
carries a `100` ground, an `800` text tone for badges, and a `900` text tone for the denser flash
context. There is deliberately no parallel success/warning/danger/info palette: a flash message
reuses the same family a badge does.

| Token | Value | Family role |
|---|---|---|
| `--color-quiet-100` | `#E7DDCE` | quiet ground — Lead badge, neutral fallback |
| `--color-quiet-800` | `#4F4335` | quiet text |
| `--color-ochre-100` | `#FBF0DB` | ochre ground — Reserved, warning outcomes |
| `--color-ochre-800` | `#7A4E08` | ochre text (badges) |
| `--color-ochre-900` | `#5C3A06` | ochre text (flash) |
| `--color-palm-100` | `#EDF1E3` | palm ground — Available, Contracted/Won, success outcomes |
| `--color-palm-800` | `#3D5426` | palm text (badges) |
| `--color-palm-900` | `#2E3F1C` | palm text (flash) |
| `--color-brick-100` | `#FBE9E7` | brick ground — Lost, failure outcomes |
| `--color-brick-800` | `#8C2A25` | brick text (badges) |
| `--color-brick-900` | `#6E211D` | brick text (flash) |

`--color-quiet-100` carries the same value as `sand-200`; the two coexist because one is a
decorative divider and the other a status ground, and a future palette change may well move only
one of them. `--color-quiet-800` replaces the former `ink-700`, which is now deleted as
unreferenced.

### Semantic tier — the only tier components may reference

| Token | Points at | Used for |
|---|---|---|
| `--color-surface` | `sand-50` | page ground |
| `--color-surface-raised` | `#FFFFFF` | cards, table body, form panels |
| `--color-surface-sunken` | `sand-100` | table headers, board columns |
| `--color-ink` | `ink-800` | body text |
| `--color-ink-strong` | `ink-900` | headings |
| `--color-ink-muted` | `sand-600` | supporting text, table headers |
| `--color-ink-disabled` | `sand-400` | disabled control text and labels |
| `--color-line` | `sand-200` | decorative dividers only |
| `--color-line-strong` | `sand-500` | interactive control borders only |
| `--color-primary` | `clay-600` | primary buttons, active nav |
| `--color-primary-hover` | `clay-700` | hover/active states |
| `--color-primary-text` | `clay-700` | text links |
| `--color-focus` | `clay-600` | focus rings |

The status families are referenced by their family names (`quiet`/`ochre`/`palm`/`brick`) rather
than through further semantic aliases — a fifth alias tier for four families would be the kind of
indirection Principle V forbids.

### Non-colour tokens

| Token | Value |
|---|---|
| `--font-sans` | system stack (`ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif`) — no webfont (research.md #11) |
| `--text-page-title` | `1.5rem` / `700` |
| `--text-section` | `1.125rem` / `600` |
| `--text-body` | `0.875rem` / `400` |
| `--text-support` | `0.75rem` / `400` |
| `--radius-control` | `0.5rem` |
| `--radius-panel` | `0.75rem` |
| `--radius-pill` | `9999px` |

**Invariant (enforced by `DesignSystemFitnessTest`)**: no file under `resources/js/` may contain a
literal `#rrggbb`, `rgb(`, `hsl(`, or a stock Tailwind palette class (`slate-*`, `gray-*`,
`blue-*`, `emerald-*`, `rose-*`, `amber-*`). Components use semantic-tier utilities only.

---

## 2. Status vocabulary

One component, seven values, two kinds. `kind` drives shape so meaning survives greyscale
(FR-017, FR-018; research.md #4).

### `kind="stage"` — pill, leading dot, `title="Deal stage: {label}"`

| Value (raw) | Label | Text | Ground |
|---|---|---|---|
| `lead` | Lead | `quiet-800` | `quiet-100` |
| `reserved` | Reserved | `ochre-800` | `ochre-100` |
| `contracted_won` | Contracted / Won | `palm-800` | `palm-100` |
| `lost` | Lost | `brick-800` | `brick-100` |

### `kind="availability"` — rectangular tag, bordered, no dot, `title="Unit availability: {label}"`

| Value (raw) | Label | Text | Ground |
|---|---|---|---|
| `available` | Available | `palm-800` | `palm-100` |
| `reserved` | Reserved | `ochre-800` | `ochre-100` |
| `sold` | Sold | `#FFFFFF` | `ink-800` |

**Fallback (FR-019)**: an unrecognised value renders in the quiet treatment (`quiet-800` on
`quiet-100`) showing the raw value with underscores replaced by spaces and the words
capitalised — never blank, never a broken layout.

**Source of truth**: these seven values are exactly `App\Enums\DealStage` and
`App\Enums\UnitStatus` from feature 002. This feature only maps them to appearance; it never
changes the sets, their order, or how a Unit's availability is derived.

---

## 3. Display formatting rules

Implemented in `resources/js/lib/format.js` (research.md #9). All input arrives as strings from
`decimal` columns and must be coerced.

| Helper | Input | Output | Rule |
|---|---|---|---|
| `money(v)` | `"4500000.00"`, `0`, `null` | `4,500,000.00` / `0.00` / `—` | grouped thousands, **always exactly 2 decimals**, no currency symbol in the cell |
| `number(v)` | integer-ish | `1,234` | grouped, no decimals — counts only |
| `area(v)` | `"350.00"` | `350.00` | same as money; unit named in the header (research.md, spec assumption) |
| `date(v)` | `"2026-09-02"`, `null` | `02 Sep 2026` / `—` | one unambiguous format app-wide |

**Currency and unit placement (FR-023)**: the marker appears **once** — in a table column header
(`Full price (EGP)`, `Area (m²)`) or beside a field label on a detail/form screen — and never
inside a cell.

**Absent vs zero (FR-025)**: `null`/`undefined`/`""` → `—`; a real `0` → `0.00`. This is the
distinction that makes an unrecorded deposit visibly different from a recorded zero deposit.

**Alignment (FR-006, FR-021)**: numeric cells carry `text-right` plus the `tabular-nums`
font-feature so digits are equal-width and columns align on the decimal separator. Text cells
are `text-left`.

---

## 4. Contrast pairings (test fixture)

`DesignTokenContrastTest` parses `@theme` and asserts each pairing below meets its threshold.
Measured values are in research.md #2; this is the machine-checked list.

| Foreground token | Background token | Threshold |
|---|---|---|
| `--color-ink` | `--color-surface` | 4.5 |
| `--color-ink-strong` | `--color-surface` | 4.5 |
| `--color-ink-muted` | `--color-surface` | 4.5 |
| `--color-ink-muted` | `--color-surface-sunken` | 4.5 |
| `#FFFFFF` | `--color-primary` | 4.5 |
| `#FFFFFF` | `--color-primary-hover` | 4.5 |
| `--color-primary-text` | `--color-surface` | 4.5 |
| `--color-focus` | `--color-surface` | 3.0 |
| `--color-line-strong` | `--color-surface` | 3.0 |
| `--color-line-strong` | `--color-surface-raised` | 3.0 |
| `--color-quiet-800` | `--color-quiet-100` | 4.5 |
| `--color-ochre-800` | `--color-ochre-100` | 4.5 |
| `--color-palm-800` | `--color-palm-100` | 4.5 |
| `--color-brick-800` | `--color-brick-100` | 4.5 |
| `--color-ochre-900` | `--color-ochre-100` | 4.5 |
| `--color-palm-900` | `--color-palm-100` | 4.5 |
| `--color-brick-900` | `--color-brick-100` | 4.5 |
| `#FFFFFF` | `--color-ink-800` (Sold badge) | 4.5 |

`--color-line` (decorative) is deliberately **excluded** — WCAG 1.4.11 exempts purely decorative
boundaries, and holding a row divider to 3:1 would cage every table (research.md #3).

---

## 5. Shell regions

`AppLayout.vue`'s structure, fixed across all 13 authenticated screens (FR-007).

| Region | Content | Requirement |
|---|---|---|
| Brand | app name, links to `/deals` | FR-007 |
| Primary nav | Pipeline, Contacts, Projects, Companies — current section marked | FR-007, FR-008 |
| Identity | signed-in `name` + `role` label ("Admin" / "Sales rep") | FR-009 |
| Sign out | same position on every screen | FR-007 |
| Message region | flash outcomes in palm/ochre/brick, directly above the page header | FR-010, FR-039 |
| Page header | title, optional description, primary action slot | FR-011 |
| Content | the page's own slot | FR-007 |

**Current-section matching (FR-008)**: a nav item is active when the current URL path equals its
href or begins with `href + '/'`. This is what makes `/projects/5/units/create` mark **Projects**
active. `/` and `/login` match nothing and render with no active item.

**Outside the shell**: `Auth/Login.vue` (guests only, FR-014), `Home.vue` (public landing,
research.md #12), and `Errors/Error.vue` (may render for guests).
