# Phase 0 Research: Design System & Application Shell

**Feature**: `003-design-system-shell` | **Date**: 2026-09-02

All Technical Context unknowns are resolved below. Each entry records the decision, why it was
chosen, and what was rejected. Every colour value quoted here was measured, not estimated — see
decision #2.

---

## 1. Token architecture under Tailwind v4 `@theme`

**Decision**: Define one flat token layer in `resources/css/app.css` under the existing `@theme`
block, using Tailwind v4's `--color-*`, `--font-*`, `--text-*`, `--radius-*` and `--spacing-*`
namespaces so every token automatically becomes a utility class. Two tiers:

- **Palette tier** — the neutral ramp `--color-sand-*` plus `--color-ink-*`, the primary
  `--color-clay-600/700`, and the four status families `--color-quiet-*`, `--color-ochre-*`,
  `--color-palm-*`, `--color-brick-*`. Only shades that are actually referenced exist; unused
  ramp entries are deleted, since Principle V forbids unused configuration options.
- **Semantic tier** — role aliases pointing at palette entries: `--color-surface`,
  `--color-surface-raised`, `--color-surface-sunken`, `--color-ink`, `--color-ink-strong`,
  `--color-ink-muted`, `--color-ink-disabled`, `--color-line`, `--color-line-strong`,
  `--color-primary`, `--color-primary-hover`, `--color-primary-text`, `--color-focus`. The four
  status families are referenced by family name and get no further aliases.

Components reference the **semantic tier only**; the palette tier exists so the semantic tier has
something to point at.

**Rationale**: Tailwind v4 generates `bg-surface`, `text-ink-muted`, `border-line` etc. straight
from `@theme` variables with zero config file, so a token *is* a utility — no `tailwind.config.js`
is needed and none is added. The two-tier split is what makes FR-003/SC-005 true: repointing
`--color-primary` at a different ramp restyles every button in the app without touching a
component.

**Alternatives rejected**:
- *Single flat tier of raw ramps only* — components would hardcode `bg-clay-600`, so a palette
  change means editing every component. Fails SC-005.
- *A `tailwind.config.js` with a JS theme object* — Tailwind v4's CSS-first config makes this
  legacy; adding it re-introduces a build config the project deliberately does not have.
- *CSS custom properties outside `@theme`* — would not generate utilities, forcing
  `style="color: var(--x)"` and losing every Tailwind state/variant modifier.

---

## 2. The warm earthy palette, with measured contrast

**Decision**: A warm palette on a sand ground — the sand/ink neutrals, a clay primary, and four
status families (quiet, ochre, palm, brick) serving as the single semantic set. Every pairing the
design system actually uses was measured with the WCAG 2.1 relative-luminance formula before
being adopted:

| Pairing | Foreground | Background | Measured | Needs | Result |
|---|---|---|---|---|---|
| Body text | `ink-800` `#362E24` | `sand-50` `#FAF7F2` | **12.49:1** | 4.5 | PASS |
| Headings | `ink-900` `#221C15` | `sand-50` | **15.78:1** | 4.5 | PASS |
| Muted text | `sand-600` `#6B5C48` | `sand-50` | **6.05:1** | 4.5 | PASS |
| Muted text on raised | `sand-600` | `sand-100` `#F4EEE5` | **5.60:1** | 4.5 | PASS |
| Primary button | `white` | `clay-600` `#96431F` | **6.73:1** | 4.5 | PASS |
| Primary button hover | `white` | `clay-700` `#7A3719` | **8.79:1** | 4.5 | PASS |
| Link / text action | `clay-700` | `sand-50` | **8.23:1** | 4.5 | PASS |
| Focus ring | `clay-600` | `sand-50` | **6.30:1** | 3.0 | PASS |
| Control border | `sand-500` `#8C7A62` | `sand-50` | **3.87:1** | 3.0 | PASS |
| Control border on white | `sand-500` | `white` | **4.14:1** | 3.0 | PASS |
| Badge — Lead | `quiet-800` `#4F4335` | `quiet-100` `#E7DDCE` | **7.15:1** | 4.5 | PASS |
| Badge — Reserved | `ochre-800` `#7A4E08` | `ochre-100` `#FBF0DB` | **6.36:1** | 4.5 | PASS |
| Badge — Contracted/Won | `palm-800` `#3D5426` | `palm-100` `#EDF1E3` | **7.33:1** | 4.5 | PASS |
| Badge — Lost | `brick-800` `#8C2A25` | `brick-100` `#FBE9E7` | **7.24:1** | 4.5 | PASS |
| Badge — Sold | `white` | `ink-800` | **13.35:1** | 4.5 | PASS |
| Flash — palm (success) | `palm-900` `#2E3F1C` | `palm-100` | **9.92:1** | 4.5 | PASS |
| Flash — ochre (warning) | `ochre-900` `#5C3A06` | `ochre-100` | **9.01:1** | 4.5 | PASS |
| Flash — brick (failure) | `brick-900` `#6E211D` | `brick-100` | **9.44:1** | 4.5 | PASS |

**Rationale**: Satisfies FR-002 (warm ground, warm primary, distinct semantics) and FR-004 with
evidence rather than assertion. Terracotta (`clay`) as the primary keeps the palette warm where a
blue would fight it. The four status families (quiet/ochre/palm/brick) are the single semantic
set — a badge and a flash message of the same meaning draw from the same family rather than from
a parallel success/warning/danger palette, which is what the original spec wrongly implied and
which has since been struck from FR-002. Every value clears its threshold with margin, so small
future tweaks will not silently drop below the line.

**Alternatives rejected**:
- *Tailwind's stock `amber`/`stone`/`orange` ramps* — stone is a cool-leaning grey and the amber
  ramp's mid-tones fail 4.5:1 on light backgrounds, which is exactly the trap FR-004 guards
  against.
- *Keeping the current slate + blue* — not warm, and it is the thing the feature exists to
  replace.

---

## 3. Control borders vs decorative dividers (the one real accessibility subtlety)

**Decision**: Two distinct border tokens. `--color-line` (`sand-200` `#E7DDCE`, 1.34:1 on white)
is for **decorative** hairlines only — table row dividers, card edges, section rules.
`--color-line-strong` (`sand-500` `#8C7A62`, ≥3.87:1) is mandatory for anything that
**delineates an interactive control**: input, select, and textarea borders, and unfocused
checkbox/radio outlines.

**Rationale**: WCAG 2.1 SC 1.4.11 (Non-text Contrast) requires 3:1 for visual boundaries of user
interface components but explicitly exempts purely decorative elements. The first pass at this
palette used one border colour for everything, and measurement showed it at 1.57:1 — fine for a
row divider, a real failure on a text input. Splitting the token is what makes FR-004 and SC-003
honestly satisfiable. The current pages use `border-slate-300` on inputs (≈1.6:1 on white), so
this is a genuine accessibility fix, not a restyle.

**Alternatives rejected**:
- *One border token at `sand-500` everywhere* — passes contrast but makes every table look caged;
  heavy dividers are a known scanability regression.
- *One token at `sand-200` everywhere* — what the app effectively does today; leaves form controls
  non-compliant.

---

## 4. Distinguishing a Deal stage from a Unit availability (FR-017)

**Decision**: One `StatusBadge` component with a required `kind` prop (`stage` | `availability`)
and a required `value`. The kinds differ in **shape and marker**, not just colour:

- `kind="stage"` → fully rounded pill with a leading filled dot.
- `kind="availability"` → small-radius rectangular tag with no dot and a `1px` border in the
  matching darker tone.

Both carry a `title` attribute naming the kind ("Deal stage: Reserved" / "Unit availability:
Reserved").

**Rationale**: FR-017 requires a Reserved *Deal* and a Reserved *Unit* to be tellable apart, and
FR-018 forbids colour as the only carrier. Shape survives greyscale, colour-blindness, and the
`title` text serves screen readers. A single component with a `kind` prop (rather than two
components) is what makes SC-002's "one shared status indicator" checkable and prevents the
present drift where two look-alike components disagree about what amber means.

**Alternatives rejected**:
- *Keep two components (`StageBadge`, `StatusBadge`)* — the status quo, and the direct cause of
  the collision. Explicitly out per the spec.
- *Same shape, different hue for the two kinds* — fails FR-018.
- *Prefix the label text ("Stage: Reserved")* — doubles badge width in every table cell and
  pipeline card for information the column header already gives.

---

## 5. How a presentational feature satisfies Test-First Development (Principle III)

This was the hardest gate. Constitution Principle III is NON-NEGOTIABLE and demands an automated
test for every feature, but "the palette is warm" and "figures are tabular" are not assertions a
PHPUnit test can make. No JavaScript test runner exists in the project.

**Decision**: A three-layer test strategy, all in PHPUnit, adding **no new dependency**:

1. **Feature tests (real HTTP)** for everything that is genuinely behavioural — the newly shared
   props (signed-in name and role present and correct per role; absent for guests), and the styled
   error responses (403/404/419 render the error page component *and* keep their status code).
   Uses `assertInertia`, already available in `inertiajs/inertia-laravel` v3 and already used 17
   times in the existing suite.
2. **Architecture-fitness Unit tests** that read the source tree as text and assert the design
   system's invariants — the layer that makes the otherwise-untestable requirements testable.
   Grouped by the story that makes each assertion true, so every story's checkpoint is green:

   *US1 — the shell:*
   - every file in `resources/js/Pages/**` (except `Auth/Login.vue`, `Home.vue` and
     `Errors/Error.vue`) imports `AppLayout` → SC-001;
   - no file under `resources/js/` contains a literal `#rrggbb`, `rgb(`, or `hsl(` value, and no
     stock Tailwind palette class (`slate-`, `blue-`, `gray-`, `emerald-`, `rose-`, `amber-`,
     `indigo-`) → SC-004, FR-001;

   *US2 — the status vocabulary:*
   - no `.vue` file contains a raw enum string (`contracted_won`, `sales_rep`) inside template
     text → SC-002, FR-016;
   - `StatusBadge.vue` names all seven values and all seven labels → FR-015;
   - no file imports the deleted `StageBadge` → SC-002;

   *US3 — records and the board:*
   - every list page (`Contacts/Index`, `Companies/Index`, `Projects/Index`, `Projects/Show`,
     `Contacts/Show`) imports `DataTable`, and **no page declares raw `<table>` markup of its
     own** → FR-020;
   - every detail page (`Contacts/Show`, `Deals/Show`, `Projects/Show`) imports
     `DescriptionList` → FR-031;
   - **`PipelineBoard.vue` and `DealCard.vue` contain no `useForm`, `router.put`, `router.post`,
     `router.patch`, `router.delete`, `draggable`, or `@drop`** → FR-029. This is the one that
     matters most: FR-029's "no write path" is the hardest contract in the feature and would
     otherwise be guarded only by a human remembering to look;

   *US4 — the states:*
   - every list page imports `EmptyState` (or renders `DataTable`, which owns the empty case) →
     FR-032;
   - every `Form.vue` page imports `FormField` and binds `form.processing` → FR-034, FR-035;
   - every page carrying a delete control imports `ConfirmAction` → FR-040;
   - `Deals/Form.vue` retains no hand-styled "already sold" banner markup → FR-042.
3. **A token contrast Unit test** that parses the `@theme` block out of
   `resources/css/app.css`, resolves the semantic tier to hex values, and computes the WCAG 2.1
   contrast ratio for a declared table of pairings, failing if any drops below its threshold
   (4.5:1 text, 3:1 non-text) → FR-004, SC-003. This makes decision #2's table a permanent
   regression guard rather than a one-time measurement, and it is the one test that would catch a
   future palette tweak quietly breaking accessibility.

Anything genuinely visual — does the shell look right, does the board read well — is covered by
the manual pass in `quickstart.md`, which is honest about being manual.

**Known, accepted gap: `lib/format.js` has no automated test.** It is the feature's only unit of
real logic (decimal-string coercion, the always-two-decimals rule, `—` for absent values), and
research #9 names the decimal-string bug as the specific failure this design exists to prevent —
yet nothing automated guards it. Two remedies were considered and **both rejected**:

- *Add Vitest scoped to this one module* — a new dev dependency and a second test runner for four
  small functions. Rejected under Principle V, consistently with the component-testing rejection
  below.
- *Have a PHPUnit test shell out to `node -e` and assert the helpers' output* — technically works
  with no new package, but couples the PHP suite to a Node binary being present and turns a unit
  test into a subprocess integration test. Rejected as worse than the gap it closes.

The guard is therefore **quickstart.md step B3** (verify 3/6/9-digit alignment, two decimals, and
`—` vs `0.00` against real data), executed by a human. This is recorded here rather than left
implicit so the risk is visible: if `format.js` later grows beyond these four one-line helpers,
that is the trigger to revisit the Vitest decision.

**Rationale**: Delivers real, meaningful coverage for a feature whose output is mostly CSS, while
respecting Principle V (no new packages). The fitness tests are cheap, fast, and directly encode
the success criteria; the contrast test converts an accessibility promise into arithmetic.

**Alternatives rejected**:
- *Add Vitest + `@vue/test-utils` and unit-test components* — a new dev dependency and a second
  test runner, for a feature with no component *logic* worth testing beyond label mapping (which
  the fitness test already covers). Rejected under Principle V; revisit if Vue components ever
  grow real behaviour.
- *Add Playwright/Dusk for visual regression* — heavyweight (browser driver, baseline images) and
  brittle; disproportionate for a 14-screen internal tool.
- *Declare the feature untestable and rely on the manual pass* — would violate a NON-NEGOTIABLE
  principle.

---

## 6. Sharing signed-in identity and action outcomes (FR-009, FR-039)

**Decision**: Extend `HandleInertiaRequests::share()` with exactly two keys:

```
auth.user  → { name, role } for a signed-in user, null for a guest
flash      → { success, warning, error } read from the session
```

No route, controller, validation rule, policy, or table is touched, satisfying FR-043. The
middleware is presentation-layer plumbing and is named as in-scope by the spec's resolved
clarification #2.

**Rationale**: `auth.user` has no data source today, so FR-009 (identity on every screen) is
otherwise unbuildable — this is why the spec's clarification pulled shared props into scope. Only
`name` and `role` are exposed; the constitution's Data & Security section forbids leaking model
attributes wholesale, so the email/password hash/timestamps stay server-side.

**Alternatives rejected**:
- *Share the whole `$request->user()` model* — leaks attributes for no benefit; violates the
  constitution's serialization-control requirement.
- *Fetch identity per page in each controller* — 6 controller edits, forbidden by FR-043 and
  duplicative.

---

## 7. Styled 403 / 404 / 419 responses (FR-041)

**Decision**: Add an exception responder in `bootstrap/app.php`'s existing (currently empty)
`withExceptions` closure that converts 403, 404, 405 and 419 responses into an Inertia
`Errors/Error` page, **preserving the original HTTP status code**. 500s stay on Laravel's default
handler whenever `config('app.debug')` is true, so local debugging keeps its stack trace.

**Rationale**: FR-041 requires these to be styled in-app pages. `bootstrap/app.php` is
application bootstrap configuration — not a route, controller, validation rule, policy, or table —
so FR-043 is respected. Preserving status codes is what keeps FR-045 true: feature 002's
`assertForbidden()` / `assertNotFound()` assertions check the status, which does not change.

**Risk noted for implementation**: the common recipe for this excludes the `testing` environment,
which would make the new page invisible to the test suite. This plan deliberately does **not**
exclude `testing` — the page renders in all environments so it can be asserted on, and the status
code is set explicitly rather than being left to Inertia's default 200.

**Alternatives rejected**:
- *Blade error views in `resources/views/errors/`* — Laravel's native mechanism, but it renders
  outside Inertia and so cannot use the shell, the tokens, or the shared props. Directly violates
  constitution Principle II (no mixing Blade and Inertia for the same feature's surfaces).
- *Client-side handling of Inertia error responses* — does not help for a full page load, which is
  exactly when a 403 or 404 arrives.

---

## 8. The success-message boundary (a deliberate, documented gap)

**Decision**: The shell's message region supports all three severities (success, warning, error)
per FR-010 — which has since been reworded from "MUST render" to "MUST support" precisely so this
boundary does not read as an unmet requirement — and renders whatever the session flashes. This feature ships **no** success messages,
because no controller currently flashes one and FR-043 forbids editing controllers. In practice
the region carries error and expired-session outcomes on delivery.

**Rationale**: Honest scoping. Building the region to handle three severities costs nothing extra
and satisfies FR-010; *producing* specific success text ("Deal updated") requires one line in each
of ~20 controller actions, which is a behavioural change this feature explicitly excludes. Adding
it later is a one-line-per-action follow-up against a region that already exists.

**Alternatives rejected**:
- *Middleware that auto-flashes a generic "Saved" on any successful mutating redirect* — possible
  without touching controllers, but produces vague messages, fires on requests where it is wrong,
  and is exactly the kind of clever indirection Principle V exists to prevent.
- *Build only error severity* — leaves FR-010 unmet and forces rework the moment a controller
  flashes success.

---

## 9. Number, money, and date formatting (FR-006, FR-022, FR-023, FR-024, FR-025)

**Decision**: One client-side helper module, `resources/js/lib/format.js`, exporting
`money(v)`, `number(v)`, `area(v)` and `date(v)`. `money` uses
`Intl.NumberFormat('en-EG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })` — grouped
thousands, always two decimals, **no currency style**, since FR-023 puts the "EGP" marker in the
column header rather than the cell. `date` uses `en-GB`-style `DD MMM YYYY` (e.g. `02 Sep 2026`),
unambiguous for both a `DD/MM` and an `MM/DD` reader. Tabular alignment comes from the
`tabular-nums` font-feature utility on numeric cells, not from the formatter.

Every amount arrives from MySQL `decimal(12,2)` as a **string** (`"4500000.00"`), so each helper
coerces with `Number()` and returns an em-dash for `null`/`undefined` — which is what makes
FR-025's "absent is distinguishable from zero" true (`0` renders `0.00`, absent renders `—`).

**Rationale**: Formatting is presentation, so it belongs on the client next to the components that
use it; a shared module means one place to change and makes SC-007 checkable. `Intl` is built into
every target browser — no dependency. The decimal-string coercion is the specific bug this
decision exists to prevent: `Intl.NumberFormat` on a string throws in strict cases and silently
mis-renders otherwise.

**Alternatives rejected**:
- *Format server-side in controllers/resources* — would edit controllers (FR-043) and send display
  strings where the client may later want the number.
- *A currency-style formatter emitting "EGP 4,500,000.00" per cell* — contradicts FR-023's
  explicit "named once, not per cell".
- *Cast money to float in the models* — a model change with real precision risk, to solve a
  presentation problem.

---

## 10. Destructive-action confirmation (FR-040)

**Decision**: An inline two-step `ConfirmAction` component. First click swaps the control in place
for "Delete this? **Confirm** / **Cancel**"; Escape or blur cancels. No overlay, no modal, no
focus trap.

**Rationale**: Meets FR-040 and stays keyboard-operable (FR-013) with no dependency and no focus
management to get wrong. A real modal needs a focus trap, scroll lock, `aria-modal`, and a return
-focus contract — roughly 100 lines of accessibility-critical code, or a new package, for four
delete buttons.

**Alternatives rejected**:
- *Native `window.confirm()`* — zero code, but unstyleable (breaks the "one product" goal of US1)
  and it blocks the main thread.
- *Headless UI / Radix Vue dialog* — a new runtime dependency, rejected under Principle V.

---

## 11. Root template gaps: language, viewport, and the phantom font

**Decision**: Three corrections to `resources/views/app.blade.php`:

- `<html lang="en" dir="ltr">` — currently `<html>` with no attributes at all.
- Add `<meta name="viewport" content="width=device-width, initial-scale=1">` — currently absent.
- Drop `'Instrument Sans'` from the `--font-sans` token and keep the system stack.

**Rationale**: These are prerequisites, not polish. **FR-012 (usable down to tablet width) is
unachievable without the viewport meta tag** — without it a mobile browser renders at a ~980px
virtual width and scales down, so every responsive breakpoint in the design system is dead on
arrival. `lang`/`dir` are what FR-005's "LTR English" means in markup and are required for screen
readers to pick the right voice. And `--font-sans` currently names Instrument Sans while nothing
loads it, so the app silently falls back to `ui-sans-serif` — the token lies about what renders.

**Alternatives rejected**:
- *Load Instrument Sans from Google Fonts* — an external network dependency and a render-blocking
  request for an internal back-office tool; the system stack renders instantly and looks native.
  Easy to revisit as a one-line token change later.
- *Self-host the font file* — adds binary assets and `@font-face` plumbing for a cosmetic gain.

---

## 12. The home screen's Arabic content

**Decision — PARTIALLY ON HOLD (2026-09-02)**: `resources/js/Pages/Home.vue` (the public `/`
landing, which is also where login redirects) is restyled onto the tokens. **The Arabic-to-English
translation is on hold at the requester's instruction** and must not be performed until they
confirm; the restyle may proceed without it, leaving the existing Arabic copy in place for now.
Note the consequence: while the copy stays Arabic, FR-005's "LTR English" is not fully met on this
one screen, and FR-044's migration is incomplete for it. The screen stays outside the shell — it is publicly reachable by guests — and offers a single action that leads to the
pipeline for a signed-in user or to sign-in for a guest, using the newly shared `auth.user`.

**Rationale**: The screen currently carries Arabic RTL copy ("أهلاً بيك في الـ CRM") left over
from feature 001, styled with stock `gray`/`blue` classes and no layout. FR-005 mandates LTR
English and FR-044 requires every screen migrated, so it cannot stay as it is. Keeping it outside
the shell preserves the existing public 200 response that `tests/Feature/ExampleTest.php` asserts
(FR-045).

**Flagged for the requester, and now formally held**: translating would delete the only Arabic copy
in the application. The spec puts RTL/Arabic localisation out of scope, so translating follows the
spec — but the requester has withheld approval pending a decision, so the translation task ships
blocked rather than done. Resolve by either confirming the translation, or amending FR-005/FR-044
to exempt this screen.

**Alternatives rejected**:
- *Leave `Home.vue` untouched* — violates FR-044 and leaves one screen visibly from another
  product.
- *Put `/` behind auth and make it a dashboard* — a routing/behaviour change (FR-043) and new
  scope (FR "no new screen"); it would also break `ExampleTest`.

---

## 13. Pipeline column overflow (FR-028, FR-030)

**Decision**: Four equal columns in a CSS grid with `grid-template-columns: repeat(4, minmax(0, 1fr))`,
each column's card list getting `max-height` plus `overflow-y: auto`. Below the tablet breakpoint
the grid becomes a horizontally scrolling row of fixed-width columns.

**Rationale**: `minmax(0, 1fr)` is the specific fix for FR-030's "without changing the widths of
the other columns" — plain `1fr` lets a long unbroken word blow out one column and squeeze the
rest, which is the default-behaviour bug this decision exists to avoid. Per-column vertical
scrolling keeps all four stages visible at once, which is the point of a board.

**Alternatives rejected**:
- *Let the page grow tall with the longest column* — the other three become empty whitespace and
  the board stops being scannable.
- *Paginate each column* — new interaction and state for an internal tool with hundreds of deals.

---

## 14. Component inventory and granularity

**Decision**: Ten shared components under `resources/js/Components/`, plus the layout and the
format helper. Deliberately small — each one exists because a specific requirement or a repeated
pattern across ≥3 existing pages demands it:

| Component | Exists for |
|---|---|
| `AppLayout.vue` (rewritten) | FR-007…FR-013 — the shell |
| `PageHeader.vue` | FR-011 — title + description + primary action |
| `FlashMessages.vue` | FR-010, FR-039 — the message region |
| `StatusBadge.vue` (rewritten) | FR-015…FR-019 — all seven values, two kinds |
| `DataTable.vue` + `TableCell` conventions | FR-020…FR-026 — one table treatment |
| `EmptyState.vue` | FR-032, FR-033 — purposeful empties |
| `FormField.vue` | FR-034…FR-036 — label, required marker, error, focus |
| `AppButton.vue` | consistent primary/secondary/danger treatment, focus ring |
| `ConfirmAction.vue` | FR-040 — inline two-step confirm |
| `DescriptionList.vue` | FR-031 — the detail-screen field/value pattern |
| `PipelineBoard.vue` + `DealCard.vue` | FR-027…FR-030 — the read-only board |

**Rationale**: Principle V — every component is justified by a named requirement, and none is
speculative. `DataTable` takes a column definition array (with per-column `align` and `format`) so
alignment and tabular figures are structural rather than re-decided per page, which is what makes
FR-020/FR-021 hold across all eight list screens.

**Alternatives rejected**:
- *A component per screen type (`ContactsTable`, `UnitsTable`, …)* — eight near-identical
  components and eight places for the treatment to drift.
- *A full generic component kit (Card, Stack, Grid, Text, …)* — speculative abstraction with no
  second use case yet; squarely what Principle V forbids.
