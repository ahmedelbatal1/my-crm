---

description: "Task list for Design System & Application Shell"
---

# Tasks: Design System & Application Shell

**Input**: Design documents from `/specs/003-design-system-shell/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/component-api.md,
contracts/shared-props.md, quickstart.md

**Tests**: Included and REQUIRED — the project constitution (Principle III, Test-First
Development, NON-NEGOTIABLE) mandates an automated test for every feature. Because this feature's
output is mostly CSS and markup, plan.md and research.md #5 define a three-layer PHPUnit strategy
that adds **no new test runner**: Feature tests for shared props and error responses, a token
contrast test, and architecture-fitness tests that read `resources/js/**` as text. Write each
story's tests before its implementation tasks.

**One declared test gap**: `resources/js/lib/format.js` has no automated test. Vitest and a
`node -e` subprocess were both considered and rejected (research.md #5); quickstart.md step B3 is
its only guard, executed by a human. This is an accepted risk, not an oversight.

**Organization**: Tasks are grouped by user story (US1–US4 from spec.md). Note that each story
here is a *cross-cutting concern*, not a set of screens — so several pages are touched by more
than one story (US1 puts them in the shell, US2 fixes their badges, US3 their tables, US4 their
states). That is inherent to a design-system migration and is why each story's fitness assertions
are introduced in the story that makes them true.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no unmet dependency)
- **[Story]**: Maps the task to US1–US4 from spec.md
- All paths are relative to the repository root (`C:\wamp64\www\my-crm`)

## Path Conventions

Single Laravel application — see plan.md's Project Structure. Tokens in `resources/css/app.css`;
shared components in `resources/js/Components/`; the shell in `resources/js/Layouts/`; pages in
`resources/js/Pages/`; the format helper in `resources/js/lib/`; the three permitted server files
are `app/Http/Middleware/HandleInertiaRequests.php`, `bootstrap/app.php`, and
`resources/views/app.blade.php`; tests in `tests/Feature/` and `tests/Unit/`.

**Hard constraints on every task below**:

- No route, controller action, validation rule, policy, or migration may be added, changed, or
  removed (FR-043). The three server files above are the exhaustive list of server-side changes.
- Feature 002's 68 tests must pass unchanged (FR-045) — a red existing test is a regression to
  revert, never a test to edit.
- **T024 is BLOCKED** pending the requester's decision on translating the home screen's Arabic
  copy. Do not run it. Every other task may proceed.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: The token layer, the root-template prerequisites, and the format helper — every
component in every story reads from these.

- [ ] T001 [P] Write `tests/Unit/DesignTokenContrastTest.php`: parse the `@theme` block out of `resources/css/app.css`, resolve the semantic tier and the four status families to hex, and assert every pairing in data-model.md §4 meets its threshold (4.5:1 text, 3:1 non-text); exclude `--color-line` as decorative per research.md #3 (write first, expect it to fail — the tokens do not exist yet)
- [ ] T002 Rewrite the `@theme` block in `resources/css/app.css` with the palette tier, the four status families (quiet/ochre/palm/brick) and the semantic tier from data-model.md §1, keeping the existing `@import 'tailwindcss'` and `@source` directives; set `--font-sans` to the system stack and drop the unloaded `'Instrument Sans'` (research.md #11). Define **only** referenced tokens — no `info` family, no unused ramp shades (FR-002, Principle V) — makes T001 pass
- [ ] T003 [P] Add `lang="en" dir="ltr"` to the `<html>` tag and `<meta name="viewport" content="width=device-width, initial-scale=1">` to the `<head>` in `resources/views/app.blade.php` (research.md #11 — FR-012's 768px floor is unreachable without the viewport tag)
- [ ] T004 [P] Create `resources/js/lib/format.js` exporting `money`, `number`, `area`, `date` per data-model.md §3: coerce `decimal` strings with `Number()`, always two decimals for money/area, `—` for null/undefined/empty, `DD MMM YYYY` dates, and no currency symbol in the returned string (research.md #9). **No automated test exists for this file by design — verify it by hand against quickstart.md B3 before considering the task done**

**Checkpoint**: Tokens exist and are contrast-verified; the root template can support responsive
layout; formatting rules are centralised.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: The two permitted server-side changes plus the lowest-level component. Every user
story depends on these — US1's shell cannot show an identity or a message without T006, and US4's
error states cannot exist without T010.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [ ] T005 [P] Write `tests/Feature/SharedPropsTest.php` per contracts/shared-props.md §1: `auth.user` carries exactly `name` and `role` for an Admin and for a Sales Rep, is `null` for a guest, and exposes no other User attribute (assert `email`/`id` absent); `flash` always has all three keys (write first, expect it to fail)
- [ ] T006 Extend `App\Http\Middleware\HandleInertiaRequests::share()` in `app/Http/Middleware/HandleInertiaRequests.php` with `auth.user` (`name`, `role` only) and `flash` (`success`, `warning`, `error` from the session) — makes T005 pass (after T005)
- [ ] T007 [P] Write `tests/Feature/ErrorPageTest.php` per contracts/shared-props.md §2: a 403 (Sales Rep opening another rep's Contact), a 404 (`/contacts/999999`) and a 419 each render the `Errors/Error` Inertia page **and** keep their original status code — assert page component and status together (write first, expect it to fail)
- [ ] T008 [P] Create `resources/js/Components/AppButton.vue` per contracts/component-api.md: `variant` (primary/secondary/danger/ghost), `href` switching to an Inertia `Link`, `loading` implying `disabled`, visible `--color-focus` ring, disabled text in `--color-ink-disabled`
- [ ] T009 Create `resources/js/Pages/Errors/Error.vue` accepting a `status` prop, with per-status plain-English copy and a route back that uses shared `auth.user` to choose the pipeline (signed in) or sign-in (guest) — standalone, not inside the shell (after T006, T008)
- [ ] T010 Configure the currently-empty `withExceptions` closure in `bootstrap/app.php` to render 403/404/405/419 as the `Errors/Error` Inertia page with the status code set **explicitly**, deferring 500s to Laravel's handler while `config('app.debug')` is true; do **not** exclude the `testing` environment (research.md #7) — makes T007 pass (after T009)
- [ ] T011 Run `php artisan test` and confirm all 68 existing tests still pass after T006 and T010 — the regression gate before any page is touched; if `assertForbidden`/`assertNotFound` fail, T010 is dropping status codes

**Checkpoint**: Identity and flash reach every page, error responses are styled and status-correct,
and the existing suite is proven unbroken. User story work can begin.

---

## Phase 3: User Story 1 - Every screen reads as one product (Priority: P1) 🎯 MVP

**Goal**: One shell around all 13 authenticated screens — brand, navigation with current-section
marking (including nested screens), signed-in name and role, sign-out, page-header pattern, and
message region — with every screen's styling moved onto the tokens so nothing carries its own
palette any more.

**Independent Test**: Sign in and visit all 14 screens in sequence; confirm each renders inside
the same shell with the correct nav section marked (including `/projects/{id}/units/create`
marking Projects), identity visible without scrolling, and no stock-Tailwind colour left anywhere.

### Tests for User Story 1 ⚠️ (write first, confirm they fail, then implement)

- [ ] T012 [P] [US1] Create `tests/Unit/DesignSystemFitnessTest.php` with its first assertion: every file under `resources/js/Pages/**` imports `AppLayout`, excluding `Auth/Login.vue`, `Home.vue` and `Errors/Error.vue` (SC-001) (write first, expect it to fail)
- [ ] T013 [P] [US1] Add to `tests/Unit/DesignSystemFitnessTest.php`: no file under `resources/js/` contains a literal `#rrggbb`, `rgb(`, or `hsl(` value, nor any stock Tailwind palette class (`slate-`, `gray-`, `blue-`, `emerald-`, `rose-`, `amber-`, `indigo-`) (SC-004, FR-001) (write first, expect it to fail)

### Implementation for User Story 1

- [ ] T014 [P] [US1] Create `resources/js/Components/PageHeader.vue` (title, optional description, `action` slot) per contracts/component-api.md
- [ ] T015 [P] [US1] Create `resources/js/Components/FlashMessages.vue` rendering each non-null severity from shared `flash` using the palm/ochre/brick families and `role="status"`/`role="alert"`; all three severities supported even though this feature emits none of the success kind (FR-010, research.md #8) (after T006)
- [ ] T016 [US1] Rewrite `resources/js/Layouts/AppLayout.vue`: token-based shell with brand, nav, signed-in name + role label derived client-side from `auth.user.role`, sign-out, `FlashMessages` region above the content, and current-section matching by `path === href || path.startsWith(href + '/')` per data-model.md §5 (after T014, T015)
- [ ] T017 [P] [US1] Migrate `resources/js/Pages/Deals/{Index,Show,Form}.vue` onto the shell, `PageHeader`, `AppButton` and semantic tokens — replacing all `slate-*`/`blue-*` classes (after T016)
- [ ] T018 [P] [US1] Migrate `resources/js/Pages/Contacts/{Index,Show,Form}.vue` the same way (after T016)
- [ ] T019 [P] [US1] Migrate `resources/js/Pages/Projects/{Index,Show,Form}.vue` the same way (after T016)
- [ ] T020 [P] [US1] Migrate `resources/js/Pages/Companies/{Index,Form}.vue` the same way (after T016)
- [ ] T021 [P] [US1] Migrate `resources/js/Pages/Units/Form.vue` the same way (after T016)
- [ ] T022 [P] [US1] Restyle `resources/js/Pages/Auth/Login.vue` onto the tokens and shared form/button treatment — stays outside the shell for guests (FR-014) (after T008)
- [ ] T023 [US1] Restyle `resources/js/Pages/Home.vue` onto the tokens — layout, palette, and the single action that leads to the pipeline (signed in) or sign-in (guest) via `auth.user`. Keep it public and outside the shell so `tests/Feature/ExampleTest.php`'s 200 for guests still passes. **Leave the existing Arabic copy exactly as it is** (after T008)
- [ ] T024 🚫 **BLOCKED — do not run** [US1] Replace `resources/js/Pages/Home.vue`'s Arabic copy with English per FR-005 (research.md #12). Held at the requester's instruction pending their decision; while held, FR-005 and FR-044 are knowingly unmet on this one screen. Unblock only on explicit confirmation, or drop the task if FR-005/FR-044 are amended to exempt this screen (after T023)
- [ ] T025 [US1] Keyboard and focus pass across all migrated screens: every interactive control on every screen focusable in a sensible order with a visible `--color-focus` ring (FR-013, SC-012)
- [ ] T026 [US1] Run `php artisan test` — T012 and T013 must now pass and all 68 existing tests must still pass

**Checkpoint**: User Story 1 is independently demoable — the app looks and behaves like one
product, with zero ad-hoc styling left (home-screen copy excepted while T024 is held).

---

## Phase 4: User Story 2 - One status vocabulary everywhere (Priority: P2)

**Goal**: All seven Deal-stage and Unit-availability values render through one `StatusBadge`, with
stage and availability distinguishable by shape (not just colour) and no raw internal value ever
shown.

**Independent Test**: With data covering all seven values, visit every screen showing a stage or
availability; confirm human labels everywhere, no underscored value, and that a Reserved Deal and
a Reserved Unit on the same screen are tellable apart — including in greyscale.

### Tests for User Story 2 ⚠️ (write first, confirm they fail, then implement)

- [ ] T027 [P] [US2] Add to `tests/Unit/DesignSystemFitnessTest.php`: `resources/js/Components/StatusBadge.vue` names all seven raw values and all seven human labels (FR-015) (write first, expect it to fail)
- [ ] T028 [P] [US2] Add to `tests/Unit/DesignSystemFitnessTest.php`: no `.vue` file contains a raw enum string (`contracted_won`, `sales_rep`) in template text, and no file imports the deleted `StageBadge` (FR-016, SC-002) (write first, expect it to fail)

### Implementation for User Story 2

- [ ] T029 [US2] Rewrite `resources/js/Components/StatusBadge.vue` per data-model.md §2 and contracts/component-api.md: required `value` and `kind` (`stage`|`availability`), pill-with-dot for stage vs bordered rectangular tag for availability, `title="Deal stage: …"`/`"Unit availability: …"`, family colours from quiet/ochre/palm/brick, and the quiet capitalised fallback for unrecognised values (FR-017–FR-019) — makes T027 pass
- [ ] T030 [P] [US2] Point `resources/js/Pages/Deals/{Index,Show}.vue` and `resources/js/Pages/Contacts/Show.vue` at `<StatusBadge :value="deal.stage" kind="stage" />`, removing their `StageBadge` imports (after T029)
- [ ] T031 [P] [US2] Point `resources/js/Pages/Projects/Show.vue`, `resources/js/Pages/Units/Form.vue` and `resources/js/Pages/Deals/Form.vue` at `<StatusBadge :value="unit.status" kind="availability" />` — note the prop is `status`, the user-facing word is "availability" (data-model.md §2) (after T029)
- [ ] T032 [US2] Delete `resources/js/Components/StageBadge.vue` (after T030, T031 — all three importers must be migrated first) — makes T028 pass
- [ ] T033 [US2] Verify per quickstart.md B2: all seven labels correct, no underscored value on any screen, and stage vs availability distinguishable in a greyscale screenshot (FR-018)
- [ ] T034 [US2] Run `php artisan test` — T027, T028 and all prior tests pass

**Checkpoint**: User Stories 1 AND 2 both work independently.

---

## Phase 5: User Story 3 - Records are scannable and comparable (Priority: P3)

**Goal**: One table treatment across all eight list surfaces with right-aligned equal-width
two-decimal EGP figures aligned on the decimal separator, one date format, row-level entry points,
one detail-screen pattern, and the read-only pipeline board with per-stage counts and totals.

**Independent Test**: Load a project whose Units span 3-, 6- and 9-digit prices plus a rep with a
dozen contacts and deals; confirm decimal alignment at all three magnitudes, `EGP` named once per
column header, `—` for an absent deposit vs `0.00` for a recorded zero, and per-stage counts and
totals on the board.

### Tests for User Story 3 ⚠️ (write first, confirm they fail, then implement)

- [ ] T035 [P] [US3] Add to `tests/Unit/DesignSystemFitnessTest.php`: every list page (`Contacts/Index`, `Companies/Index`, `Projects/Index`, `Projects/Show`, `Contacts/Show`) imports `DataTable`, and no file under `resources/js/Pages/**` declares raw `<table>` markup of its own (FR-020) (write first, expect it to fail)
- [ ] T036 [P] [US3] Add to `tests/Unit/DesignSystemFitnessTest.php`: every detail page (`Contacts/Show`, `Deals/Show`, `Projects/Show`) imports `DescriptionList` (FR-031) (write first, expect it to fail)
- [ ] T037 [P] [US3] Add to `tests/Unit/DesignSystemFitnessTest.php`: **neither `resources/js/Components/PipelineBoard.vue` nor `resources/js/Components/DealCard.vue` contains `useForm`, `router.put`, `router.post`, `router.patch`, `router.delete`, `draggable`, or `@drop`** — the automated guard for FR-029's no-write-path contract, which would otherwise depend on a human remembering to look (write first, expect it to fail)

### Implementation for User Story 3

- [ ] T038 [P] [US3] Create `resources/js/Components/DataTable.vue` per contracts/component-api.md: `columns` definitions carrying `key`/`label`/`align`/`format`/`truncate`, `rows`, optional `rowHref` making the whole row navigable (FR-026), `cell:{key}`/`actions`/`empty` slots, right-alignment plus `tabular-nums` on numeric columns, `—` for null (FR-025), predictable truncation, and its own horizontal-scroll container (FR-020–FR-027) (after T004)
- [ ] T039 [P] [US3] Create `resources/js/Components/DescriptionList.vue` taking `items` of `{label, value, format?, hint?}` and reusing `lib/format.js` so detail screens read identically to tables (FR-031) (after T004)
- [ ] T040 [P] [US3] Create `resources/js/Components/DealCard.vue`: contact name, project · unit type, `money(full_price)`, whole card one navigable link, long names truncating, **no stage-write call of any kind** (FR-027, FR-029) (after T004)
- [ ] T041 [US3] Create `resources/js/Components/PipelineBoard.vue` taking the existing `dealsByStage` prop unchanged: four columns in stage order each showing label, count and client-computed stage total, `grid-template-columns: repeat(4, minmax(0, 1fr))`, per-column vertical scroll, horizontal scroll below 768px, and **no control that writes a stage** (FR-027–FR-030) — makes T037 pass (after T040)
- [ ] T042 [P] [US3] Convert `resources/js/Pages/Contacts/Index.vue` and `resources/js/Pages/Companies/Index.vue` to `DataTable` with `rowHref` and correct per-column alignment (after T038)
- [ ] T043 [P] [US3] Convert `resources/js/Pages/Projects/Index.vue` and the Units table inside `resources/js/Pages/Projects/Show.vue` to `DataTable`, with headers reading `Price (EGP)` and `Area (m²)` so the unit is named once, never per cell (FR-023) (after T038)
- [ ] T044 [P] [US3] Convert the Deals list inside `resources/js/Pages/Contacts/Show.vue` to `DataTable`, and its Contact field/value block to `DescriptionList` (after T038, T039)
- [ ] T045 [P] [US3] Convert `resources/js/Pages/Deals/Show.vue`'s field/value block to `DescriptionList`, with `Full price (EGP)` / `Deposit (EGP)` labels carrying the unit and an absent deposit rendering `—` distinct from a recorded `0.00` (FR-025, FR-031) (after T039)
- [ ] T046 [US3] Replace the hand-rolled column markup in `resources/js/Pages/Deals/Index.vue` with `PipelineBoard`, and confirm no stage-write control exists anywhere on it (FR-029) (after T041)
- [ ] T047 [US3] Verify per quickstart.md B3: decimal alignment at 3/6/9 digits, two decimals everywhere, `EGP`/`m²` named once per column, `—` vs `0.00`, one date format, row-level entry on every list, and per-column scroll not resizing sibling columns. **This step is also the only guard on `lib/format.js` — do not skip it** (research.md #5)
- [ ] T048 [US3] Run `php artisan test` — T035, T036, T037 must now pass alongside every prior test

**Checkpoint**: User Stories 1, 2 and 3 all work independently.

---

## Phase 6: User Story 4 - The interface behaves when things are empty, wrong, or in flight (Priority: P4)

**Goal**: Purposeful empty states (ownership-aware), field-level validation with preserved input
and focus, in-flight submit protection, up-front prevention of the four blocked deletions with the
reason stated, inline destructive confirmation, and styled permission/not-found/expired-session
responses.

**Independent Test**: With an empty account visit every list; submit each form invalid; attempt all
four blocked deletions; expire a session — each produces a styled, explanatory response rather
than a blank area, a silent failure, or a default error page.

### Tests for User Story 4 ⚠️ (write first, confirm they fail, then implement)

- [ ] T049 [P] [US4] Add to `tests/Unit/DesignSystemFitnessTest.php`: every list page imports `EmptyState` or renders `DataTable` (which owns the empty case), and every `Pages/**/Form.vue` imports `FormField` and references `form.processing` (FR-032, FR-034, FR-035) (write first, expect it to fail)
- [ ] T050 [P] [US4] Add to `tests/Unit/DesignSystemFitnessTest.php`: every page carrying a delete control imports `ConfirmAction`, and `resources/js/Pages/Deals/Form.vue` retains no hand-styled "already sold" banner markup from feature 002 (FR-040, FR-042) (write first, expect it to fail)

### Implementation for User Story 4

- [ ] T051 [P] [US4] Create `resources/js/Components/EmptyState.vue` (`title`, optional `description`, `action` slot) per contracts/component-api.md
- [ ] T052 [P] [US4] Create `resources/js/Components/FormField.vue`: `<label for>` binding, required marker, `hint` for unit markers, error text beneath the control in brick, `--color-line-strong` border switching to the brick tone on error, and `aria-invalid`/`aria-describedby` wiring (FR-034, FR-036)
- [ ] T053 [P] [US4] Create `resources/js/Components/ConfirmAction.vue`: inline two-step confirm, Escape/blur cancels, emits `confirmed`, and a `disabled` + `disabledReason` mode that renders the control unavailable with its reason shown (FR-038, FR-040; research.md #10 — no modal, no focus trap)
- [ ] T054 [P] [US4] Wire `EmptyState` into every list via `DataTable`'s empty handling — `Contacts/Index`, `Deals/Index` (all four board columns), `Companies/Index`, `Projects/Index`, `Projects/Show`'s Units, `Contacts/Show`'s Deals — with ownership-aware copy on the two scoped lists ("None of your contacts yet", not "No contacts") (FR-032, FR-033) (after T051, T038, T041)
- [ ] T055 [P] [US4] Convert every form to `FormField` and bind `AppButton`'s `loading` to Inertia's `form.processing` in `resources/js/Pages/{Contacts,Deals,Projects,Companies,Units}/Form.vue` and `Auth/Login.vue`; move focus to the first field with an error on a failed submit (FR-034, FR-035) — makes T049 pass (after T052)
- [ ] T056 [US4] Wire `ConfirmAction` into all four delete paths — `Projects/{Index,Show}`, `Projects/Show`'s Units, `Contacts/{Index,Show}`, `Companies/Index` — using the dependent-record data each page already receives to disable the control with a stated reason when a Project has Units, a Unit has Deals, a Contact has Deals, or a Company has Contacts (FR-037, FR-038, FR-040, SC-009) (after T053)
- [ ] T057 [US4] Replace `resources/js/Pages/Deals/Form.vue`'s bespoke "already sold" banner and disabled-submission markup (built in feature 002) with the shared `StatusBadge` availability indicator plus `ConfirmAction`/`AppButton`'s disabled-with-reason treatment, leaving no hand-styled remnant (FR-042) — makes T050 pass (after T029, T053)
- [ ] T058 [US4] Finalise `resources/js/Pages/Errors/Error.vue` copy per status: 403 explains the permission problem, 404 the missing record, 419 the expired session with a prompt to sign in again, each with a route back (FR-041, US4 scenarios 7–8) (after T009)
- [ ] T059 [US4] Verify per quickstart.md B4: empty states on a fresh rep's lists, field-level validation with preserved input and focus, in-flight double-submit prevention, all four blocked deletions prevented up front with reasons, inline confirm behaviour, and styled 403/404/419 that keep their status codes
- [ ] T060 [US4] Run `php artisan test` — `ErrorPageTest`, `SharedPropsTest`, every fitness assertion, the contrast test, and all 68 existing tests pass

**Checkpoint**: All four user stories are independently functional.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Final quality gates across all stories.

- [ ] T061 [P] Run `vendor/bin/pint` across the changed PHP files (`HandleInertiaRequests.php`, `bootstrap/app.php`, and the four new test files) and confirm clean
- [ ] T062 [P] Run `npm run build` and confirm it succeeds, per-page chunks are still emitted, and CSS stays well under 100 kB uncompressed (plan.md Performance Goals)
- [ ] T063 Audit `resources/css/app.css` for tokens no component references and delete them — Principle V forbids unused configuration options, and the original token draft failed this check (plan.md Constitution Check, Principle V)
- [ ] T064 Run the full `php artisan test` suite and confirm 68 existing + all new tests pass with zero regressions (FR-045, SC-013)
- [ ] T065 Verify SC-014 by diffing `php artisan route:list --except-vendor` against its pre-feature output (36 routes) and confirming no migration was added — no route, controller action, validation rule, policy or table changed (FR-043)
- [ ] T066 Execute the token-propagation check in quickstart.md B5: repoint `--color-primary`, confirm every primary button/active nav/focus ring changes with no other file edited, then revert (FR-003, SC-005)
- [ ] T067 Execute the responsive check in quickstart.md B6 at 768px and on device emulation: shell collapses, no horizontal page scroll, tables scroll in their own container, pipeline row scrolls sideways (FR-012)
- [ ] T068 Execute the remaining manual visual passes in quickstart.md B1–B4 end to end and record the result in an implementation log, noting T024's held status and the `format.js` test gap as known open items

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately. T002 depends on T001 being written
  first (TDD); T003 and T004 are independent of both.
- **Foundational (Phase 2)**: Depends on Setup (T008's button needs the tokens) — BLOCKS all user
  stories.
- **User Stories (Phase 3–6)**: All depend on Foundational completion.
  - US1 depends on T006 (identity/flash), T008 (button) and T002 (tokens).
  - US2 depends on US1 having migrated the six pages that show a badge, since T030/T031 edit those
    same files.
  - US3 depends on US1 (same page files) and on T004's format helper.
  - US4 depends on US1 (same page files), on T038/T041 for empty-state wiring, on T029 for T057's
    badge, and on T009 for the error page.
  - The stories are ordered by priority and each is independently demoable at its checkpoint, but
    they are **not** independently *codable in parallel* the way feature 002's were: US2, US3 and
    US4 all edit the same page files US1 migrates. Sequence them.
- **Polish (Phase 7)**: Depends on all four user stories.

### Within Each User Story

- Tests are written first and MUST fail before their implementation tasks.
- Components before the pages that consume them.
- `StageBadge.vue` is deleted only after all three of its importers are migrated (T032 after
  T030/T031).
- Each story ends with a full-suite run so a regression is caught at its own checkpoint rather
  than at the end.

### Parallel Opportunities

- Phase 1: T003 and T004 in parallel; T001 in parallel with both.
- Phase 2: T005, T007 and T008 in parallel; then T006 and (T009 → T010) in parallel.
- US1: T012 and T013 in parallel; T014 and T015 in parallel; then all seven page-migration tasks
  (T017–T023) in parallel since each touches a distinct set of files.
- US2: T027 and T028 in parallel; then T030 and T031 in parallel.
- US3: T035, T036 and T037 in parallel; T038, T039 and T040 in parallel; then T042–T045 in
  parallel.
- US4: T049 and T050 in parallel; T051, T052 and T053 in parallel; then T054 and T055 in parallel.
- Phase 7: T061 and T062 in parallel.

---

## Parallel Example: User Story 3

```bash
# Tests first (after US1/US2, before implementation):
Task: "Fitness assertion — every list page imports DataTable, no page declares its own <table>"
Task: "Fitness assertion — every detail page imports DescriptionList"
Task: "Fitness assertion — PipelineBoard/DealCard contain no stage-write call (FR-029)"

# Components (independent files):
Task: "Create resources/js/Components/DataTable.vue"
Task: "Create resources/js/Components/DescriptionList.vue"
Task: "Create resources/js/Components/DealCard.vue"

# Page conversions (independent file sets, after the components exist):
Task: "Convert Contacts/Index.vue and Companies/Index.vue to DataTable"
Task: "Convert Projects/Index.vue and Projects/Show.vue's Units table to DataTable"
Task: "Convert Contacts/Show.vue's Deals list to DataTable and its fields to DescriptionList"
Task: "Convert Deals/Show.vue's field/value block to DescriptionList"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup — tokens exist and are contrast-verified.
2. Complete Phase 2: Foundational — identity, flash, styled errors, and a proven-unbroken suite.
3. Complete Phase 3: User Story 1 (skipping the blocked T024).
4. **STOP and VALIDATE**: run `php artisan test` plus quickstart.md B1.
5. Demo: the whole application as one coherent product, every screen in the same shell.

### Incremental Delivery

1. Setup + Foundational → tokens, identity, styled error pages.
2. + User Story 1 → one shell, one palette, zero ad-hoc styling (MVP).
3. + User Story 2 → one status vocabulary, greyscale-safe.
4. + User Story 3 → scannable tables and the read-only pipeline board.
5. + User Story 4 → empty, validation, in-flight, confirm and error states.
6. Polish → Pint, build, unused-token audit, full suite, route/schema diff, token propagation,
   responsive, visual pass.

---

## Notes

- [P] tasks touch different files with no unmet dependency.
- Tests are required by the constitution (not optional) — write each one first and confirm it fails
  before writing the implementation task(s) below it.
- Commit after each task or logical group (the project is a git repository on `master`).
- **T024 is blocked and must not be run** until the requester confirms the home-screen
  translation. Everything else in US1 proceeds without it.
- **`lib/format.js` has no automated test by design** (research.md #5). T047's manual pass is its
  only guard. If the helper ever grows beyond its four one-line functions, that is the trigger to
  revisit the rejected Vitest decision.
- No success flash messages are produced by this feature; `FlashMessages` supports the severity but
  no controller flashes it, and controllers are out of bounds (research.md #8). FR-010 is worded
  "MUST support" for exactly this reason.
- The four status families (quiet/ochre/palm/brick) are the single semantic set — a badge and a
  flash message of the same meaning draw from the same family. Do not introduce a parallel
  success/warning/danger/info palette; FR-002 forbids it.
- Unit `status` is never accepted as input and is never made editable — the availability badge is
  read-only wherever it appears. The prop is `status`; the user-facing word is "availability".
- The three server files named in the Path Conventions section are the exhaustive list of
  server-side changes. Any task appearing to need a fourth is a scope violation to raise, not to
  implement.
