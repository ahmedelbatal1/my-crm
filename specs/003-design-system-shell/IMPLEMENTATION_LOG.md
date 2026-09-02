# Implementation Log: Design System & Application Shell

**Feature**: `003-design-system-shell` | **Implemented**: 2026-09-02

Records what was built, what was found along the way, and what is deliberately left open.

---

## Phase 1 — Setup (T001–T004)

- `tests/Unit/DesignTokenContrastTest.php` (new, written first) — parses the `@theme` block,
  resolves `var()` chains, and computes WCAG 2.1 ratios for the 18 pairings in data-model.md §4.
  Also asserts the semantic tier is complete, that no parallel success/warning/danger/info
  vocabulary exists (FR-002), and that **no colour token is defined without being used**
  (Principle V).
- `resources/css/app.css` (rewritten) — palette tier, the four status families
  (quiet/ochre/palm/brick), semantic tier, type scale, radii, a `tabular` utility for
  equal-width digits, and a `@layer base` focus-ring rule.
- `resources/views/app.blade.php` — added `lang="en" dir="ltr"` and the viewport meta tag.
  **Without the viewport tag FR-012's 768px floor is unreachable**, so this was a prerequisite,
  not polish.
- `resources/js/lib/format.js` (new) — `money`/`area`/`number`/`date`/`humanise`.

**Found**: `date()` rendered "02 Sept 2026" via `Intl`, because ICU abbreviates September as
"Sept" in newer versions. FR-024 asks for *one* format, so the month names are now pinned in an
array rather than delegated to the runtime's locale data.

---

## Phase 2 — Foundational (T005–T011)

- `tests/Feature/SharedPropsTest.php` (new, written first) — 6 tests.
- `app/Http/Middleware/HandleInertiaRequests.php` — shares `auth.user` (`name` + `role` only)
  and `flash` (all three severities always present).
- `tests/Feature/ErrorPageTest.php` (new, written first) — 7 tests asserting page component
  **and** status code together.
- `resources/js/Components/AppButton.vue`, `resources/js/Pages/Errors/Error.vue` (new).
- `bootstrap/app.php` — `withExceptions` responder mapping 403/404/405/419 to the Inertia error
  page with the status set explicitly; 500s defer to Laravel while `app.debug` is true.

**Found (worth knowing)**: an **unrouted** URL is rejected before the `web` middleware group
runs, so `HandleInertiaRequests` never shares props for it. The error page reads them with
optional chaining and renders fine, but on a mistyped URL it always offers "Go to sign in" even
to a signed-in user. That link still behaves correctly, since `/login` is guest-only and
redirects them onward. Covered by
`test_the_error_page_survives_having_no_shared_props_at_all`.

**Found**: `assertInertia` has no `toArray()`. The "no other user attribute is exposed" check
instead uses a scoped closure without `etc()`, which fails if `auth.user` carries any third key —
a stronger guarantee than an array comparison.

---

## Phase 3 — US1, the shell (T012–T026, T024 excluded)

- `tests/Unit/DesignSystemFitnessTest.php` (new, written first) — every authenticated page
  imports `AppLayout`; no file under `resources/js/` contains a literal hex/`rgb()`/`hsl()` or
  any of 23 stock Tailwind palette families.
- `PageHeader.vue`, `FlashMessages.vue` (new); `AppLayout.vue` (rewritten) with nav
  current-section marking (`path === href || path.startsWith(href + '/')`, which is what keeps
  **Projects** marked on `/projects/5/units/create`), identity + role, and the message region.
- All 13 authenticated screens plus `Home.vue` and `Auth/Login.vue` migrated onto the tokens.
- Focus visibility declared once in `@layer base` rather than per element — relying on utility
  classes guarantees something gets missed (FR-013, SC-012).

**Held**: `Home.vue` was restyled onto the tokens but **its Arabic copy is untouched**. T024 (the
translation) is blocked at the requester's instruction. While held, FR-005 and FR-044 are
knowingly unmet on that one screen. An interim `dir="rtl"` on the copy block makes it render
correctly under the document's `dir="ltr"`; remove it when T024 runs.

---

## Phase 4 — US2, the status vocabulary (T027–T034)

- Fitness assertions added first: `StatusBadge` names all seven values and labels; no raw enum
  string reaches any `<template>` block (with a guard so the check cannot pass vacuously on a
  parse miss); nothing imports the retired `StageBadge`.
- `StatusBadge.vue` (rewritten) — one component, `value` + `kind`. `kind` drives **shape**:
  stage is a pill with a leading dot, availability is a bordered square-cornered tag. Meaning
  therefore survives greyscale (FR-018), and a Reserved deal is tellable from a Reserved unit
  (FR-017). Unknown values fall back to a readable quiet badge (FR-019).
- `StageBadge.vue` deleted after its three importers were migrated.

**Adjusted**: the "nothing imports StageBadge" assertion first failed on a comment in
`StatusBadge.vue` explaining why the component was retired. Tightened to look for imports and
tags rather than any mention of the name — the test's actual intent.

---

## Phase 5 — US3, records and the board (T035–T048)

- Fitness assertions added first, including **the board's no-write-path guard**: neither
  `PipelineBoard.vue`, `DealCard.vue` nor `Deals/Index.vue` may contain `useForm`, `router.put`,
  `router.post`, `router.patch`, `router.delete`, `draggable`, `@drop`, `@dragstart` or
  `v-model`. FR-029 is the hardest contract in the feature and this is what stops it resting on
  a human remembering to look.
- `DataTable.vue`, `DescriptionList.vue`, `EmptyState.vue`, `DealCard.vue`, `PipelineBoard.vue`
  (new). Column definitions carry `align` and `format`, so alignment and equal-width digits are
  structural rather than re-decided per page.
- All five list surfaces and three detail surfaces converted. `Deals/Index.vue` reduced to a
  `PipelineBoard` with per-column count and total.
- `grid-cols-[repeat(4,minmax(0,1fr))]`: the zero minimum is what stops one long word blowing
  out a column and squeezing the other three (FR-030).

---

## Phase 6 — US4, the states (T049–T060)

- Fitness assertions added first: every form uses `FormField` and references `form.processing`;
  every page that deletes goes through `ConfirmAction`; `Deals/Form.vue` retains no hand-styled
  "already sold" banner.
- `FormField.vue`, `ConfirmAction.vue` (new). `FormField` styles the control from a scoped
  `:deep()` rule so a new form cannot ship an unstyled or low-contrast input; its border uses
  `--color-line-strong` (≥3:1) rather than the decorative `--color-line`.
- `ConfirmAction` is an inline two-step, not a modal — no focus trap, no scroll lock, no new
  dependency (research.md #10). Its `disabled` + `reason` mode is the FR-038 mechanism.
- All six forms converted; `Deals/Form.vue`'s sold-unit state now uses the shared status
  indicator plus a stated reason and a disabled submit (FR-042).

**Scope observation**: the four blocked deletions had **no delete UI at all** before this
feature — the `destroy` routes existed but nothing in the interface reached them. FR-037/038/040
and quickstart B4 presuppose reachable delete controls, so they were added, using only the
existing routes. No route or controller changed.

**Partial by constraint (FR-043)**: FR-038 pre-empts a blocked deletion only "where a screen
already carries the data". Two of the four cases do; two do not:

| Case | Pre-empted? | Why |
|---|---|---|
| Project with Units | yes | `units_count` on the row / `units` array on the page |
| Contact with Deals | yes | `contact.deals` is loaded |
| Unit with Deals | no | no per-unit deal count is sent |
| Company with Contacts | no | no `contacts_count` is sent |

Adding those two counts means editing `ProjectController` and `CompanyController`, which FR-043
forbids. A blocked attempt in those two cases therefore lands on the styled 403 page, whose copy
names dependent records as the usual cause. **SC-009 ("all four explain which dependent records
block them") is met specifically for two of four, and generically for the other two.** Closing
it properly needs a one-line `withCount` in each of two controllers — a follow-up, not a
workaround.

---

## Phase 7 — Polish (T061–T066 done; T067–T068 open)

| Gate | Result |
|---|---|
| `vendor/bin/pint --test` | passed (it also reformatted two pre-existing seeders) |
| `npm run build` | clean; CSS **60.62 kB** uncompressed (budget was under 100 kB); per-page chunks intact |
| Unused-token audit (T063) | made a permanent test, then **verified it bites** by injecting a probe token and watching it fail |
| `php artisan test` | **96 passed** (68 from feature 002, unchanged, plus 28 new) |
| Route/schema diff (T065) | 36 routes, 10 migrations, and a **zero-line diff** across `routes/`, `app/Http/Controllers/`, `app/Http/Requests/`, `app/Policies/`, `app/Models/`, `database/migrations/` — FR-043 and SC-014 verified mechanically |
| Token propagation (T066) | repointed `--color-primary` at the ochre family, rebuilt, confirmed the new value in the built CSS, reverted |

---

## Not done

1. **T024 — the home screen translation.** Blocked pending the requester's decision.
2. **T067 — the responsive pass at 768px** and **T068 — the visual passes in quickstart B1–B4**.
   These need a browser, device emulation and a seeded dev database; they are declared manual in
   the plan and cannot be executed here. Everything they check *behaviourally* is covered by the
   96 automated tests; what remains is judgement about how it looks — layout at the 768px floor,
   the greyscale badge check, decimal alignment across magnitudes, and the inline confirm's feel.
3. **`lib/format.js` has no automated test**, by the requester's decision. It was exercised by
   hand during T004 (`"4500000.00"` to `4,500,000.00`; `0` to `0.00`; `null` to an em dash;
   `"2026-09-02"` to `02 Sep 2026`), and quickstart B3 remains its standing guard.
