# Implementation Plan: Design System & Application Shell

**Branch**: `003-design-system-shell` | **Date**: 2026-09-02 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/003-design-system-shell/spec.md`

## Summary

Replace the per-page ad-hoc styling of the 14 existing Inertia screens with one design system: a
two-tier token layer in `resources/css/app.css` (a warm earthy palette whose every pairing is
contrast-measured), a rewritten `AppLayout` shell carrying navigation with current-section
marking plus signed-in identity and a message region, a single `StatusBadge` covering all seven
Deal-stage and Unit-availability values with stage and availability visually distinguishable, one
shared table treatment with two-decimal EGP figures on tabular numerals, a read-only pipeline
board, and complete empty/in-flight/validation/confirmation/error states.

No route, controller, validation rule, policy, or migration changes. Three server-side files are
touched, each because a requirement is otherwise unbuildable: `HandleInertiaRequests` (to share
signed-in identity and flash — FR-009/FR-039), `bootstrap/app.php` (to render styled 403/404/419
pages — FR-041), and `app.blade.php` (`lang`/`dir` and the viewport meta tag, without which
FR-012's responsive behaviour cannot work at all). Feature 002's 68 tests must pass unchanged.

## Technical Context

**Language/Version**: PHP 8.2+ / Laravel 12.67 (server side, three files); Vue 3.5 SFCs and
Tailwind CSS 4 (the bulk of the work)

**Primary Dependencies**: Tailwind CSS 4 (`@tailwindcss/vite`), `@inertiajs/vue3` ^3.7,
`inertiajs/inertia-laravel` v3.3.1, Vite 7 — all already installed. **No new dependency of any
kind** is added: no CSS framework, no component library, no icon package, no JS test runner (see
research.md #5, #10, #14 for the specific rejections)

**Storage**: N/A — this feature persists nothing and reads no new data. Every value it displays is
already on the props each page receives

**Testing**: PHPUnit 11 via `php artisan test`, in three layers (research.md #5): Feature tests
with `assertInertia` for shared props and error responses; architecture-fitness Unit tests that
read `resources/js/**` as text to enforce SC-001/SC-002/SC-004, that every list/detail/form page
uses the shared component for its pattern, and that `PipelineBoard`/`DealCard` contain no
stage-write call (FR-029); and a Unit test that parses the `@theme` token block and computes WCAG
contrast ratios to enforce FR-004/SC-003. Laravel Pint for PHP style. Genuinely visual checks are
the documented manual pass in `quickstart.md`. **One accepted gap**: `lib/format.js` has no
automated test — Vitest and a `node -e` subprocess were both rejected, so quickstart B3 is its
only guard (research.md #5)

**Target Platform**: Desktop browsers primarily, usable down to a 768px viewport (FR-012); modern
evergreen browsers only. Internal back-office tool, LTR English — except the home screen, whose
Arabic copy stays put until the requester confirms the translation (research.md #12)

**Project Type**: Web application — single Laravel monolith serving Inertia/Vue pages. This
feature is a frontend/presentation layer over feature 002's existing backend

**Performance Goals**: No regression to the current instant-feeling page loads. The build must stay
per-page code-split (it currently emits one chunk per page); shared components are expected to
consolidate into the common chunk, and total CSS should stay well under 100 kB uncompressed (it is
56 kB today)

**Constraints**: FR-043 forbids touching routes, controller actions, validation rules, policies,
and tables — the three server files named in the Summary are permitted by the spec's resolved
clarifications and are the exhaustive list. FR-045 requires all 68 existing tests to pass
unchanged; a failing existing test signals a behavioural regression to revert, never a test to
edit. Styled error pages MUST preserve their HTTP status codes or `assertForbidden()`/
`assertNotFound()` in the existing suite will break

**Scale/Scope**: 14 Inertia pages migrated; ~11 new shared components; 1 rewritten layout; 1
rewritten badge (replacing 2); 1 token block; 1 format helper; 3 server files touched. 45
functional requirements, 14 success criteria

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | How the plan satisfies it |
|---|---|---|
| I. Convention-Driven Laravel Architecture | PASS | Almost nothing lands server-side. The two server changes that do use framework-provided mechanisms exactly as intended: Inertia's `share()` for shared props and Laravel 12's `withExceptions` responder for error rendering. No new service layer, no bespoke infrastructure, no controller logic added. |
| II. Inertia/Vue Frontend Consistency | PASS | This principle is the feature's whole point. All 14 screens become Inertia pages under `resources/js/Pages` sharing one `AppLayout` and the `HandleInertiaRequests` shared props. Notably, error pages are delivered as an Inertia page rather than Blade views in `resources/views/errors/` specifically to avoid the mixed-rendering the principle forbids (research.md #7). No Blade view is added; `app.blade.php` only gains `lang`/`dir`/viewport attributes. |
| III. Test-First Development (NON-NEGOTIABLE) | PASS, with one declared gap | The hardest gate for a CSS-heavy feature, addressed in research.md #5 with three PHPUnit layers and no new runner: Feature tests (real HTTP) for shared props and error statuses; fitness tests, grouped per story so each checkpoint is green, asserting every page imports `AppLayout`, no literal colours or stock Tailwind palette classes survive under `resources/js/`, no raw enum string reaches a template, every list/detail/form page uses its shared component, and `PipelineBoard`/`DealCard` contain no stage-write call; and a contrast test that recomputes every palette pairing from the token block. Tests are written before their implementation tasks. **Declared gap**: `lib/format.js` has no automated test — Vitest (new dependency, Principle V) and a `node -e` subprocess (couples the PHP suite to a Node binary) were both rejected, leaving quickstart B3 as its guard. Recorded in research.md #5 rather than left implicit. What remains genuinely visual is declared manual in `quickstart.md`. |
| IV. Code Style & Static Quality | PASS | Pint on the three touched PHP files and the new tests. Vue SFCs follow the existing `resources/js` conventions (script setup, two-space indent, `@/` alias imports). No ESLint/Prettier config exists in the project, so no new tooling is introduced — consistency is with the code already there. |
| V. Simplicity & YAGNI | PASS | Zero new packages: Vitest, `@vue/test-utils`, Playwright/Dusk, Headless UI, and an icon library were each considered and rejected with reasons (research.md #5, #10, #14). The component inventory is capped at eleven, each traceable to a named requirement; a generic Card/Stack/Grid kit was explicitly rejected as speculative. The confirm affordance is an inline two-step rather than a modal precisely to avoid focus-trap machinery. **Corrected after analysis**: the first token draft carried five palette entries and a whole `info` family that nothing referenced — an "unused configuration options" violation. They are deleted, and the four status families (quiet/ochre/palm/brick) now serve as the single semantic set for both badges and flash messages, replacing the parallel success/warning/danger/info palette the original FR-002 implied. |
| Data & Security Requirements | PASS | The one new data exposure is deliberately minimal: `auth.user` shares **only** `name` and `role`, not the User model, honouring the requirement not to expose model attributes wholesale (research.md #6). No authentication or authorization behaviour changes — every policy and `auth` middleware grouping is untouched, and the styled 403 preserves the 403 status so denial remains denial. No schema change, so no migration. |

No violations — Complexity Tracking table is not needed.

**Post-Phase 1 re-check**: The Phase 1 artifacts introduced no new dependency, no new route, and
no new persisted data. Two findings from the design pass strengthened rather than weakened
compliance: splitting the border token into decorative vs control (research.md #3) turned a
would-be WCAG failure into a measured pass, and the decision to *not* exclude the `testing`
environment from the error responder (research.md #7) is what keeps the new error page under
automated test instead of invisible to it. One scope boundary is documented rather than silently
dropped: the message region supports three severities but this feature ships no success messages,
because producing them requires the controller edits FR-043 forbids (research.md #8).
Constitution Check still PASSes with no changes.

**Post-analysis re-check (2026-09-02)**: `/speckit-analyze` found one Principle V violation
(unused palette tokens) and it has been resolved by deleting them and collapsing the two parallel
colour vocabularies into the four status families — see the Principle V row above. The analysis
also found SC-001 unsatisfiable as originally written (it demanded all 14 screens sit inside a
shell that FR-014 deliberately excludes three of), a systematic FR-024/FR-025 miscitation across
five documents, FR-013 under-scoped against SC-012, FR-012's breakpoint unnumbered, and FR-029's
no-write-path contract guarded only by a human. All are now fixed in the artifacts. The one
finding declined by the requester is the `format.js` test gap, which is recorded as an accepted
risk rather than closed.

## Project Structure

### Documentation (this feature)

```text
specs/003-design-system-shell/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md        # Phase 1 output (/speckit-plan command)
├── quickstart.md        # Phase 1 output (/speckit-plan command)
├── contracts/           # Phase 1 output (/speckit-plan command)
│   ├── component-api.md     # props/slots/events of every shared component
│   └── shared-props.md      # the Inertia shared-props and error-page contract
├── checklists/
│   └── requirements.md  # spec quality checklist (16/16 PASS)
└── tasks.md             # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

### Source Code (repository root)

```text
resources/
├── css/
│   └── app.css                       # rewritten @theme: palette tier + semantic tier
├── views/
│   └── app.blade.php                 # + lang="en" dir="ltr", + viewport meta
└── js/
    ├── lib/
    │   └── format.js                 # new — money/number/area/date, EGP two-decimal rules
    ├── Layouts/
    │   └── AppLayout.vue             # rewritten — nav w/ active state, identity, flash region
    ├── Components/
    │   ├── PageHeader.vue            # new
    │   ├── FlashMessages.vue         # new
    │   ├── StatusBadge.vue           # rewritten — 7 values, kind: stage | availability
    │   ├── StageBadge.vue            # DELETED — folded into StatusBadge
    │   ├── DataTable.vue             # new — column defs carry align + format
    │   ├── EmptyState.vue            # new
    │   ├── FormField.vue             # new
    │   ├── AppButton.vue             # new
    │   ├── ConfirmAction.vue         # new — inline two-step destructive confirm
    │   ├── DescriptionList.vue       # new — detail-screen field/value pattern
    │   ├── PipelineBoard.vue         # new — read-only, per-column count + total
    │   └── DealCard.vue              # new
    └── Pages/
        ├── Errors/Error.vue          # new — styled 403/404/405/419
        ├── Home.vue                  # migrated; Arabic copy → English (research.md #12)
        ├── Auth/Login.vue            # migrated (outside shell, same tokens — FR-014)
        ├── Companies/{Index,Form}.vue        # migrated
        ├── Contacts/{Index,Show,Form}.vue    # migrated
        ├── Deals/{Index,Show,Form}.vue       # migrated (Index becomes PipelineBoard)
        ├── Projects/{Index,Show,Form}.vue    # migrated
        └── Units/Form.vue                    # migrated

app/
└── Http/Middleware/
    └── HandleInertiaRequests.php     # + auth.user {name, role}, + flash {success,warning,error}

bootstrap/
└── app.php                           # withExceptions: 403/404/405/419 → Errors/Error page

tests/
├── Feature/
│   ├── SharedPropsTest.php           # new — identity per role, null for guest, flash passthrough
│   └── ErrorPageTest.php             # new — 403/404/419 render Errors/Error AND keep status
└── Unit/
    ├── DesignTokenContrastTest.php   # new — parses @theme, computes WCAG ratios (FR-004/SC-003)
    └── DesignSystemFitnessTest.php   # new — AppLayout usage, no literal colours, no raw enums
```

**Structure Decision**: Single Laravel application, unchanged from feature 002 — this feature adds
no directory that is not already a Laravel or Inertia convention. The only new folders are
`resources/js/lib/` (for the format helper) and `resources/js/Pages/Errors/`, both following the
existing `resources/js` layout. Shared components stay flat in `resources/js/Components/` rather
than being grouped into subfolders, since eleven components do not justify a taxonomy (Principle
V). No `Option 2/3`-style frontend/backend split applies.

## Complexity Tracking

*No Constitution Check violations — table not needed.*

## Risks & Watch Items

Carried into `tasks.md` as explicit verification steps rather than left implicit:

| Risk | Why it matters | Mitigation |
|---|---|---|
| Styled error pages swallow HTTP status codes | `assertForbidden()`/`assertNotFound()` appear across feature 002's suite; a 200-with-error-page would break them and, worse, would misreport denial | Set the status explicitly on the Inertia response; `ErrorPageTest` asserts page **and** status together (research.md #7) |
| `decimal(12,2)` values arrive as strings | `Intl.NumberFormat` on `"4500000.00"` mis-renders or throws; would silently corrupt every money figure | `format.js` coerces with `Number()` and returns `—` for null; covered by the fitness/manual passes (research.md #9) |
| Missing viewport meta makes FR-012 unachievable | Every responsive breakpoint is inert without it — the feature would "pass" review on a desktop and fail on the tablet it claims to support | Added in the same task as `lang`/`dir` (research.md #11) |
| `lib/format.js` is untested | It holds the decimal-string coercion and two-decimal rule — the feature's only real logic — and a silent mis-render would corrupt every money figure on every screen | Accepted gap, recorded in research.md #5; quickstart B3 is the human guard. Revisit Vitest if the helper grows beyond four one-liners |
| A palette tweak later drops below WCAG AA | Contrast is invisible until audited; the current app already ships 1.6:1 input borders | `DesignTokenContrastTest` recomputes every pairing from the tokens on every test run (research.md #2, #3) |
| Migrating 14 pages drifts back into per-page styling | The exact failure mode this feature exists to fix | `DesignSystemFitnessTest` fails on any literal colour or stock Tailwind palette class under `resources/js/` (SC-004) |
| Deleting `StageBadge.vue` breaks an unmigrated page | Three pages import it today | Its deletion is sequenced after all its importers are migrated, and the fitness test asserts no stale import remains |
| Home screen's Arabic copy is replaced with English | Irreversible content change, and the requester is an Egyptian developer | **On hold at the requester's instruction (2026-09-02).** The restyle may proceed; the translation may not. While held, FR-005 and FR-044 are knowingly unmet on that one screen (research.md #12) |
