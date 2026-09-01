# Implementation Log: Real Estate Unit Sales CRM

This is a running record of the Spec Kit commands run for this feature and every file created or
modified along the way, in order. Since this project is not a git repository, this file is the
audit trail that `git log` would normally provide.

---

## 1. `/speckit-specify`

**Input**: "A CRM system for a real estate developer to manage sales of units within
compounds/projects. The developer owns Projects (compounds), each containing multiple Units
(apartments, villas, shops) with type, area, price, and status (available, reserved, sold).
Contacts are potential buyers only. A Deal links a Contact to a specific Unit, tracking the
unit's full price, an optional deposit amount and payment date, and a pipeline stage (Lead,
Reserved, Contracted/Won, Lost). Multiple Deals can exist for the same Unit from different
interested Contacts simultaneously; a Sales Rep or Admin manually closes out the other Deals
once one is finalized. A Contact's Company is optional (some buyers are individuals, not
companies). Users have roles: Admin (sees everything) and Sales Rep (sees only their own
contacts and deals)."

**Files created**:
- `specs/002-real-estate-sales/spec.md` — 4 prioritized user stories, 26 functional requirements,
  5 key entities, 7 success criteria
- `specs/002-real-estate-sales/checklists/requirements.md` — spec quality checklist, all 16
  items passed

**Files modified**:
- `.specify/feature.json` — set `feature_directory` to `specs/002-real-estate-sales`

**Notable fix during this step**: the spec initially listed a "reserved" Unit status with no rule
that ever set it. Patched FR-011/FR-013 (and the related edge case + assumption) to define a
consistent derivation: `sold` > `reserved` > `available`.

---

## 2. `/speckit-plan`

Ran `.specify/scripts/powershell/setup-plan.ps1 -Json` to scaffold the plan.

**Files created**:
- `specs/002-real-estate-sales/plan.md` — tech stack (Laravel 12 / Inertia+Vue3 / MySQL),
  Constitution Check (all 5 principles pass), project structure
- `specs/002-real-estate-sales/research.md` — 7 design decisions (auth approach, role storage,
  derived Deal ownership, derived Unit status, sold-unit block, contact minimum field,
  inventory-management authorization)
- `specs/002-real-estate-sales/data-model.md` — full schema for User, Company, Contact, Project,
  Unit, Deal
- `specs/002-real-estate-sales/contracts/web-routes.md` — every route/request field/policy/response
- `specs/002-real-estate-sales/quickstart.md` — manual + automated validation walkthrough

---

## 3. `/speckit-tasks`

Ran `.specify/scripts/powershell/setup-tasks.ps1 -Json`.

**Files created**:
- `specs/002-real-estate-sales/tasks.md` — 82 tasks (T001–T082) across Setup, Foundational, and
  4 user-story phases, plus Polish

---

## 4. `/speckit-implement` (scoped to Setup + Foundational + User Story 1, per user instruction)

Ran `.specify/scripts/powershell/check-prerequisites.ps1 -Json -RequireTasks -IncludeTasks`.
Checklist gate: 16/16 passed, proceeded without prompting.

### Phase 1 — Setup (T001–T004)

**Files created**:
- `app/Enums/UserRole.php`
- `app/Enums/UnitType.php`
- `app/Enums/UnitStatus.php`
- `app/Enums/DealStage.php`

### Phase 2 — Foundational (T005–T031)

**Migrations created** (generated via `php artisan make:migration`, then renamed to enforce FK
order: users → companies → projects → units → contacts → deals):
- `database/migrations/2026_08_25_125315_add_role_to_users_table.php`
- `database/migrations/2026_08_25_125316_create_companies_table.php`
- `database/migrations/2026_08_25_125317_create_projects_table.php`
- `database/migrations/2026_08_25_125318_create_units_table.php`
- `database/migrations/2026_08_25_125319_add_fields_to_contacts_table.php`
- `database/migrations/2026_08_25_125320_create_deals_table.php`

**Models**:
- `app/Models/Company.php` (new)
- `app/Models/Project.php` (new)
- `app/Models/Unit.php` (new)
- `app/Models/Deal.php` (new)
- `app/Models/Contact.php` (modified — `$fillable`, relationships)
- `app/Models/User.php` (modified — `role` fillable/cast, `contacts()`, `isAdmin()`/`isSalesRep()`)

**Factories**:
- `database/factories/CompanyFactory.php`
- `database/factories/ProjectFactory.php`
- `database/factories/UnitFactory.php`
- `database/factories/ContactFactory.php`
- `database/factories/DealFactory.php`
- `database/factories/UserFactory.php` (modified — `role`, `admin()`/`salesRep()` states)

**Test harness**:
- `tests/TestCase.php` (modified — `RefreshDatabase`, `actingAsAdmin()`/`actingAsSalesRep()`)

**Authentication**:
- `tests/Feature/Auth/LoginTest.php` (written first)
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `resources/js/Pages/Auth/Login.vue`
- `routes/web.php` (modified — `/login`, `/logout` routes)

**Unit-status derivation**:
- `app/Observers/DealObserver.php`
- `app/Providers/AppServiceProvider.php` (modified — registered `DealObserver`)
- `tests/Unit/UnitStatusDerivationTest.php` (written first)

**Bug fixed during this phase**: `app/Http/Controllers/Controller.php` was an empty base class
missing the `AuthorizesRequests` trait, which broke every `$this->authorize()` call. Added the
trait.

### Phase 3 — User Story 1 (T032–T050)

**Tests written first**:
- `tests/Feature/ContactManagementTest.php`
- `tests/Feature/DealLifecycleTest.php`

**Policies**:
- `app/Policies/CompanyPolicy.php`
- `app/Policies/ContactPolicy.php`
- `app/Policies/DealPolicy.php`
- `app/Providers/AppServiceProvider.php` (modified — registered the 3 policies via `Gate::policy`)

**Form Requests**:
- `app/Http/Requests/CompanyRequest.php`
- `app/Http/Requests/ContactRequest.php`
- `app/Http/Requests/DealRequest.php`

**Controllers**:
- `app/Http/Controllers/CompanyController.php`
- `app/Http/Controllers/ContactController.php`
- `app/Http/Controllers/DealController.php`
- `routes/web.php` (modified — resource routes for companies/contacts/deals)

**Vue pages**:
- `resources/js/Pages/Companies/Index.vue`, `Companies/Form.vue`
- `resources/js/Pages/Contacts/Index.vue`, `Contacts/Show.vue`, `Contacts/Form.vue`
- `resources/js/Pages/Deals/Index.vue`, `Deals/Form.vue`, `Deals/Show.vue`

**Verification**: 24/24 tests passing, Pint clean, `npm run build` succeeded, migrations applied
to the local dev MySQL database.

Stopped here per user instruction — User Story 2 (T051–T056) not started.

---

## 5. Ad hoc: fixed blank white page after login

**Root cause**: `resources/js/app.js` used `import(\`./Pages/${name}.vue\`)`, which only resolves
Vue files directly inside `Pages/` (single-level glob). Every page built for this feature lives
in a subfolder (`Pages/Auth/Login.vue`, `Pages/Contacts/Index.vue`, etc.), so those pages were
silently dropped from the production build and failed to load at runtime.

**Fix**:
- `resources/js/app.js` (modified) — switched to `resolvePageComponent` from
  `laravel-vite-plugin/inertia-helpers` with `import.meta.glob('./Pages/**/*.vue')`, the standard
  recursive resolver for nested Inertia page folders.

Rebuilt (`npm run build`) and confirmed every page produced its own chunk.

---

## 6. Ad hoc: selectable dropdowns, Project/Unit CRUD, redesigned UI

User asked for: selectable dropdowns for Deal's Contact/Unit and Contact's Company (previously
raw number-ID inputs), a way to actually create/manage Units (didn't exist yet), and a nicer
overall design.

### New shared UI

- `resources/js/Layouts/AppLayout.vue` (new) — top nav (Pipeline / Contacts / Projects /
  Companies + logout) wrapping every authenticated page
- `resources/js/Components/StageBadge.vue` (new) — colored pill for Deal stage
- `resources/js/Components/StatusBadge.vue` (new) — colored pill for Unit status
- `vite.config.js` (modified) — added `@` → `resources/js` import alias

### Project/Unit CRUD (pulled forward from User Story 3's tasks, minus the Admin-oversight pieces)

**Backend**:
- `app/Policies/ProjectPolicy.php` (new)
- `app/Policies/UnitPolicy.php` (new)
- `app/Http/Requests/ProjectRequest.php` (new)
- `app/Http/Requests/UnitRequest.php` (new)
- `app/Http/Controllers/ProjectController.php` (new)
- `app/Http/Controllers/UnitController.php` (new)
- `app/Providers/AppServiceProvider.php` (modified — registered `ProjectPolicy`/`UnitPolicy`)
- `routes/web.php` (modified — `projects` resource + nested `units` routes)

**Vue pages**:
- `resources/js/Pages/Projects/Index.vue` (new)
- `resources/js/Pages/Projects/Show.vue` (new — lists a Project's Units with status badges)
- `resources/js/Pages/Projects/Form.vue` (new)
- `resources/js/Pages/Units/Form.vue` (new)

**Test written first**:
- `tests/Feature/ProjectManagementTest.php` (new) — unit-status-defaults-to-available,
  Project/Unit deletion blocking, cross-role visibility

### Selectable relations + redesign of existing pages

- `app/Http/Controllers/ContactController.php` (modified — `create`/`edit` now pass `companies`
  for the select)
- `app/Http/Controllers/DealController.php` (modified — `create`/`edit` now pass `contacts`
  (ownership-scoped) and `units` for the two selects, via new `selectableContacts()` /
  `selectableUnits()` helpers)
- `resources/js/Pages/Auth/Login.vue` (redesigned)
- `resources/js/Pages/Companies/Index.vue`, `Companies/Form.vue` (redesigned)
- `resources/js/Pages/Contacts/Index.vue`, `Contacts/Show.vue` (redesigned)
- `resources/js/Pages/Contacts/Form.vue` (redesigned — `company_id` is now a `<select>`)
- `resources/js/Pages/Deals/Index.vue`, `Deals/Show.vue` (redesigned)
- `resources/js/Pages/Deals/Form.vue` (redesigned — `contact_id` and `unit_id` are now
  `<select>` dropdowns)

**Verification**: 29/29 tests passing, Pint clean, `npm run build` succeeded (every page —
including the new Projects/Units ones — produced its own chunk).

**`tasks.md` updated** to mark the User Story 3 tasks actually completed by this detour
(T057, T062–T069, T071, T072 fully done; T070/T073 confirmed already satisfied by earlier
User Story 1 work) — while leaving the Admin-oversight-specific tests (T059/T060) and the
Contact/Company deletion-blocking tests (remainder of T061) explicitly open for a future pass.

---

## 7. `/speckit-implement` (completing User Story 2, User Story 3 remainder, User Story 4, Polish)

Ran `.specify/scripts/powershell/check-prerequisites.ps1 -Json -RequireTasks -IncludeTasks`.
Checklist gate: 16/16 passed, proceeded without prompting.

### Phase 4 - User Story 2, competing Deals (T051-T056)

**Test written first**:
- `tests/Feature/CompetingDealsTest.php` (new) - coexisting Deals on one Unit, both-reserved
  case, winner marks the Unit sold while the loser is left untouched, new-Deal-on-sold-Unit
  rejection, edit-still-allowed on a sold Unit, manual close-out by the owning rep and by an
  Admin, and reversal of a winning Deal falling back to `reserved`.

7 of the 8 tests passed immediately (US1's Observer already covered them); the one real gap was
FR-010, exactly as `tasks.md` predicted.

**Implementation**:
- `app/Http/Requests/DealRequest.php` (modified) - added the FR-010 rule on `unit_id`: a Unit
  whose derived status is `sold` rejects *new* Deals. Scoped to creation via
  `$this->route('deal')`, so editing an existing Deal on a since-sold Unit (closing a loser as
  Lost) stays allowed, per research.md #5.
- `resources/js/Pages/Deals/Form.vue` (modified) - sold Units are disabled in the Unit
  `<select>` when creating, an "Unit already sold" banner appears, and submit is blocked; the
  existing-Deal edit path is deliberately exempt.

### Phase 5 - User Story 3 remainder (T058-T061)

**Tests written** (all passed on first run - no implementation gaps):
- `tests/Feature/UnitManagementTest.php` (new) - Admin and Sales Rep both see Units created by
  someone else, Project index visibility for both roles, Admin can add a Unit, `status` is never
  accepted from request input, invalid `type` rejected, auth required.
- `tests/Feature/AdminOversightTest.php` (new) - Admin's Contact/Deal lists span every rep,
  Admin can open another rep's records directly, Admin reassigns a Contact's owner and the
  Contact's Deals follow (verified from both the new and the previous owner's side), and a
  Sales Rep's attempt to reassign is silently forced back to self.
- `tests/Feature/ProjectManagementTest.php` (modified) - added the two missing T061 cases
  (Contact-with-Deals and Company-with-Contacts deletion blocking) plus a positive case proving
  all five deletions succeed once their blockers are removed.

### Phase 6 - User Story 4, data isolation (T074-T078)

**Tests written first**:
- `tests/Feature/ContactOwnershipTest.php` (new) - list scoping for Contacts and Deals, 403 on
  another rep's Contact/Deal via direct URL (show and edit), 403 on update/delete, the Deal form
  only offering the acting rep's own Contacts, a rep unable to open a Deal against someone
  else's Contact, and the competing-Deals case where Rep A wins their own Deal but cannot close
  out Rep B's Deal on the same Unit.

**Real gap found (T078)**: a Form Request authorizes *before* it validates, but `DealRequest`
returned `true` from `authorize()` and left the Policy check to the controller. So Rep A
updating Rep B's Deal hit the `contact_id` ownership *validation* rule first and got a 302 with
"You may only create deals for contacts you own" instead of a 403 - the record was never
modified, but the response was wrong and the message misleading.

**Fix**:
- `app/Http/Requests/DealRequest.php` (modified) - `authorize()` now runs the real Policy check
  (`update` when a `deal` route parameter is present, otherwise `create`), so authorization
  resolves before validation. The `contact_id` ownership rule stays (it still guards re-pointing
  your own Deal at someone else's Contact); its message was generalized to "You may only link
  deals to contacts you own."
- `app/Http/Requests/ContactRequest.php` (modified) - same hardening for the identical
  ordering issue on the Contact path.
- `tests/Feature/ContactOwnershipTest.php` - added a test asserting that invalid input on
  another rep's Contact/Deal still answers 403, not a validation error.

Controller `index` scoping and `authorize()` calls were audited and needed no change.

### Phase 7 - Polish (T079-T082)

- `database/factories/DealFactory.php` (modified) - added `reserved()` (with a derived 10%
  deposit and paid date), `contractedWon()`, and `lost()` states.
- `database/factories/UnitFactory.php` (modified) - added `sold()` and `reserved()` fixture
  states, documented as fixture-only since the app derives status via `DealObserver`.
- `tests/Feature/CompetingDealsTest.php` (modified) - four tests exercising the new states.
- `tests/Feature/QuickstartWalkthroughTest.php` (new) - executes quickstart.md's manual
  walkthrough (steps 1-5 plus its "Expected end state") end-to-end over real HTTP requests:
  three real logins/logouts, inventory setup, a Deal run to Contracted/Won, a competing Deal,
  the sold-Unit block, isolation 403s from both reps' sides, and Admin reassignment. Passed on
  its first run (67 assertions).
- `vendor/bin/pint` - clean (it also reordered imports in the pre-existing `bootstrap/app.php`).
- `npm run build` - succeeded, every page still emits its own chunk.
- `php artisan test` - **68 passed (342 assertions)**.

All 82 tasks in `tasks.md` are now marked `[X]`.

---

## Current state / what's NOT done yet

All 82 tasks in `specs/002-real-estate-sales/tasks.md` (T001-T082) are complete and marked
`[X]`. Full suite: 68 passing, Pint clean, frontend build clean.

Not covered by automation, left for a human:

- The browser/visual pass over quickstart.md's manual walkthrough. Its *behavior* is executed
  by `tests/Feature/QuickstartWalkthroughTest.php`, but nobody has clicked through the UI to
  confirm layout, wording, and the new sold-Unit banner render correctly.
- Seeding the local dev MySQL database with an Admin and two Sales Reps (quickstart.md's
  Prerequisites) - the test suite runs on in-memory SQLite and does not touch the dev database.
