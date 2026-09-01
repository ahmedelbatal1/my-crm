---

description: "Task list for Real Estate Unit Sales CRM"
---

# Tasks: Real Estate Unit Sales CRM

**Input**: Design documents from `/specs/002-real-estate-sales/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/web-routes.md, quickstart.md

**Tests**: Included and REQUIRED — the project constitution (Principle III, Test-First
Development, NON-NEGOTIABLE) mandates an automated test for every feature, with Feature tests
covering controllers/policies via real HTTP requests. Write each story's tests before its
implementation tasks.

**Organization**: Tasks are grouped by user story (from spec.md) to enable independent
implementation and testing of each story, after a shared Setup + Foundational base.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no unmet dependency)
- **[Story]**: Maps the task to US1–US4 from spec.md
- All paths are relative to the repository root (`C:\wamp64\www\my-crm`)

## Path Conventions

Single Laravel application (no separate frontend/backend split) — see plan.md's Project
Structure. Backend under `app/`, `database/`, `routes/`; frontend Inertia pages under
`resources/js/Pages/`; tests under `tests/Feature/` and `tests/Unit/`.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Shared PHP enums used by migrations, models, and validation across every story.

- [X] T001 [P] Create backed enum `UserRole` (`admin`, `sales_rep`) in `app/Enums/UserRole.php`
- [X] T002 [P] Create backed enum `UnitType` (`apartment`, `villa`, `shop`) in `app/Enums/UnitType.php`
- [X] T003 [P] Create backed enum `UnitStatus` (`available`, `reserved`, `sold`) in `app/Enums/UnitStatus.php`
- [X] T004 [P] Create backed enum `DealStage` (`lead`, `reserved`, `contracted_won`, `lost`) in `app/Enums/DealStage.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Database schema, models, factories, authentication, and the Unit-status derivation
observer that every user story depends on.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

### Migrations

- [X] T005 [P] Migration: add `role` string column (default `sales_rep`) to `users` table in `database/migrations/2026_08_25_125315_add_role_to_users_table.php`
- [X] T006 [P] Migration: add `name`, `phone`, `email`, `company_id` (nullable FK), `user_id` (FK) columns to `contacts` table in `database/migrations/2026_08_25_125319_add_fields_to_contacts_table.php`
- [X] T007 [P] Migration: create `companies` table (`name`) in `database/migrations/2026_08_25_125316_create_companies_table.php`
- [X] T008 [P] Migration: create `projects` table (`name`, `location` nullable, `description` nullable) in `database/migrations/2026_08_25_125317_create_projects_table.php`
- [X] T009 Migration: create `units` table (`project_id` FK, `type`, `area` decimal(10,2), `price` decimal(12,2), `status` default `available`) in `database/migrations/2026_08_25_125318_create_units_table.php` (run after T008)
- [X] T010 Migration: create `deals` table (`contact_id` FK, `unit_id` FK, `full_price` decimal(12,2), `deposit_amount` nullable decimal(12,2), `deposit_paid_at` nullable date, `stage` default `lead`) in `database/migrations/2026_08_25_125320_create_deals_table.php` (run after T006, T009)

### Models

- [X] T011 [P] Create `Company` model with `contacts()` hasMany relationship in `app/Models/Company.php` (after T007)
- [X] T012 [P] Create `Project` model with `units()` hasMany relationship in `app/Models/Project.php` (after T008)
- [X] T013 Create `Unit` model with `project()` belongsTo, `deals()` hasMany, `$fillable`, and `type`/`status` enum casts (`UnitType`/`UnitStatus`) in `app/Models/Unit.php` (after T009, T012, T002, T003)
- [X] T014 Update `Contact` model: `$fillable` (`name`,`phone`,`email`,`company_id`,`user_id`), `company()` belongsTo, `user()` belongsTo, `deals()` hasMany in `app/Models/Contact.php` (after T006, T011)
- [X] T015 Create `Deal` model with `contact()` belongsTo, `unit()` belongsTo, `$fillable`, `stage` enum cast (`DealStage`) in `app/Models/Deal.php` (after T010, T013, T014, T004)
- [X] T016 Update `User` model: add `role` to `$fillable`, cast to `UserRole`, add `contacts()` hasMany, add `isAdmin()`/`isSalesRep()` helper methods in `app/Models/User.php` (after T005, T001)

### Factories

- [X] T017 [P] Create `CompanyFactory` in `database/factories/CompanyFactory.php` (after T011)
- [X] T018 [P] Create `ProjectFactory` in `database/factories/ProjectFactory.php` (after T012)
- [X] T019 [P] Create `UnitFactory` (default `status: available`) in `database/factories/UnitFactory.php` (after T013, T018)
- [X] T020 [P] Create `ContactFactory` in `database/factories/ContactFactory.php` (after T014, T017)
- [X] T021 [P] Create `DealFactory` (default `stage: lead`) in `database/factories/DealFactory.php` (after T015, T019, T020)
- [X] T022 [P] Update `UserFactory` with `admin()` and `salesRep()` state methods in `database/factories/UserFactory.php` (after T016)

### Test harness

- [X] T023 Configure `tests/TestCase.php` with `RefreshDatabase` and `actingAsAdmin()` / `actingAsSalesRep()` helper methods (after T022)

### Authentication (blocks every route in every story)

- [X] T024 [P] Write Feature test for login success, login failure, and logout in `tests/Feature/Auth/LoginTest.php` (after T023; write first, expect it to fail)
- [X] T025 Create `LoginRequest` (`email` required/email, `password` required) in `app/Http/Requests/Auth/LoginRequest.php`
- [X] T026 Create `AuthenticatedSessionController` (`create`/`store`/`destroy`) using `Auth::attempt` in `app/Http/Controllers/Auth/AuthenticatedSessionController.php` (after T025)
- [X] T027 [P] Create `resources/js/Pages/Auth/Login.vue`
- [X] T028 Add `GET/POST /login` and `POST /logout` routes in `routes/web.php` (after T026, T027; makes T024 pass)

### Unit-status derivation (used by every Deal write, starting in US1)

- [X] T029 Create `DealObserver` implementing the FR-011/FR-013 rule (`sold` if any Deal `contracted_won`, else `reserved` if any Deal `reserved`, else `available`) recomputing the related Unit on `saved`/`deleted` in `app/Observers/DealObserver.php` (after T013, T015)
- [X] T030 Register `DealObserver` for the `Deal` model in `app/Providers/AppServiceProvider.php` (after T029)
- [X] T031 [P] Write Unit test for the derivation rule in isolation (all stage combinations, reversal from `contracted_won`) in `tests/Unit/UnitStatusDerivationTest.php` (after T029; write first, expect it to fail)

**Checkpoint**: Foundation ready — schema, models, factories, auth, and Unit-status derivation
all work. User story implementation can now begin.

---

## Phase 3: User Story 1 - Sales Rep runs a Unit Deal from Lead to Close (Priority: P1) 🎯 MVP

**Goal**: A Sales Rep can create a Contact, open a Deal linking that Contact to an available
Unit with its full price, optionally record a deposit, and advance the Deal through Lead →
Reserved → Contracted/Won, with the Unit's status reflecting that progress automatically.

**Independent Test**: Log in as a Sales Rep, create a Contact, create a Deal on an available
Unit, record a deposit, and advance the Deal to Contracted/Won — confirm the Deal, its stage
history of values, and the Unit's final `sold` status, all without any other rep or story
present.

### Tests for User Story 1 ⚠️ (write first, confirm they fail, then implement)

- [X] T032 [P] [US1] Feature test: Sales Rep creates a Contact with and without a Company in `tests/Feature/ContactManagementTest.php`
- [X] T033 [P] [US1] Feature test: Sales Rep opens a Deal on an available Unit at `lead` stage, owned by that rep, appearing in their pipeline in `tests/Feature/DealLifecycleTest.php`
- [X] T034 [P] [US1] Feature test: Sales Rep advances a Deal `lead`→`reserved` with a deposit amount/date; Unit status becomes `reserved` in `tests/Feature/DealLifecycleTest.php`
- [X] T035 [P] [US1] Feature test: Sales Rep advances a Deal `reserved`→`contracted_won`; Unit status becomes `sold` in `tests/Feature/DealLifecycleTest.php`
- [X] T036 [P] [US1] Feature test: Sales Rep's deal list is grouped/filterable by stage in `tests/Feature/DealLifecycleTest.php`

### Implementation for User Story 1

- [X] T037 [P] [US1] Create `CompanyPolicy` (`viewAny`/`view`/`create`/`update` open to any authenticated user; `delete` denied while `contacts()->exists()`) in `app/Policies/CompanyPolicy.php`
- [X] T038 [P] [US1] Create `ContactPolicy` (`create` open to any authenticated user; `view`/`update`/`delete` allowed only to the owning Sales Rep or an Admin; `delete` additionally denied while `deals()->exists()`) in `app/Policies/ContactPolicy.php`
- [X] T039 [P] [US1] Create `DealPolicy` (`create`/`view`/`update`/`delete` allowed only when the acting user is Admin or owns the Deal's Contact) in `app/Policies/DealPolicy.php`
- [X] T040 [US1] Register `CompanyPolicy`, `ContactPolicy`, `DealPolicy` in `app/Providers/AppServiceProvider.php` (after T037, T038, T039)
- [X] T041 [P] [US1] Create `CompanyRequest` (`name` required, max:255) in `app/Http/Requests/CompanyRequest.php`
- [X] T042 [P] [US1] Create `ContactRequest` (`name`, `phone` required; `email` optional; `company_id` optional exists check; `user_id` forced to the acting Sales Rep, ignored from input unless Admin) in `app/Http/Requests/ContactRequest.php`
- [X] T043 [P] [US1] Create `DealRequest` (`contact_id`/`unit_id` required exists checks plus "acting Sales Rep must own `contact_id`" per FR-020; `full_price` required numeric min:0.01; `deposit_amount` nullable numeric min:0 lte:full_price; `deposit_paid_at` nullable date; `stage` required in the four `DealStage` values) in `app/Http/Requests/DealRequest.php`
- [X] T044 [US1] Create `CompanyController` (`index`/`create`/`store`/`edit`/`update`/`destroy`) in `app/Http/Controllers/CompanyController.php` (after T037, T041)
- [X] T045 [US1] Create `ContactController` (`index` scoped to `auth()->user()` unless Admin; `create`/`store`/`show`/`edit`/`update`/`destroy`) in `app/Http/Controllers/ContactController.php` (after T038, T042)
- [X] T046 [US1] Create `DealController` (`index` scoped via `whereHas('contact', ...)` to the owner unless Admin, grouped by `stage`; `create`/`store`/`show`/`edit`/`update`/`destroy`) in `app/Http/Controllers/DealController.php` (after T039, T043, T029)
- [X] T047 [US1] Add Company/Contact/Deal resource routes behind the `auth` middleware group in `routes/web.php` (after T044, T045, T046, T028)
- [X] T048 [P] [US1] Create `resources/js/Pages/Companies/Index.vue` and `Companies/Form.vue`
- [X] T049 [P] [US1] Create `resources/js/Pages/Contacts/Index.vue`, `Contacts/Show.vue`, `Contacts/Form.vue`
- [X] T050 [P] [US1] Create `resources/js/Pages/Deals/Index.vue` (pipeline view grouped by stage) and `Deals/Form.vue`

**Checkpoint**: User Story 1 is fully functional and independently testable — a single Sales Rep
can run a Deal from Lead to Contracted/Won.

---

## Phase 4: User Story 2 - Competing Deals on the same Unit are resolved manually (Priority: P2)

**Goal**: Two or more Deals can exist simultaneously on one Unit from different Contacts; once
one reaches Contracted/Won the Unit is sold, the others are left open (not auto-changed), a new
Deal on that Unit is blocked, and staff can manually mark the losing Deals Lost.

**Independent Test**: Create two Deals from two different Contacts on the same Unit, advance one
to Contracted/Won, confirm the Unit is `sold` and the other Deal is untouched, confirm a
third new Deal on that Unit is rejected, then manually close the second Deal to `lost`.

### Tests for User Story 2 ⚠️ (write first, confirm they fail, then implement)

- [X] T051 [P] [US2] Feature test: two Deals from different Contacts coexist on the same Unit in `tests/Feature/CompetingDealsTest.php`
- [X] T052 [P] [US2] Feature test: one Deal reaches `contracted_won` → Unit becomes `sold`, the other open Deal on that Unit is unchanged in `tests/Feature/CompetingDealsTest.php`
- [X] T053 [P] [US2] Feature test: creating a new Deal on a Unit that already has a `contracted_won` Deal is rejected with a validation error in `tests/Feature/CompetingDealsTest.php`
- [X] T054 [P] [US2] Feature test: the owning Sales Rep, or an Admin, can manually set the losing Deal's stage to `lost` in `tests/Feature/CompetingDealsTest.php`

### Implementation for User Story 2

- [X] T055 [US2] Add a custom validation rule to `DealRequest` that rejects `unit_id` on **creation** when that Unit's current derived `status` is `sold` (FR-010; research.md #5) in `app/Http/Requests/DealRequest.php` (after T043)
- [X] T056 [P] [US2] Show an "already sold" state and disable submission for a sold Unit in `resources/js/Pages/Deals/Form.vue` (after T050)

**Checkpoint**: User Stories 1 AND 2 both work independently.

---

## Phase 5: User Story 3 - Admin manages Project & Unit inventory and oversees all activity (Priority: P3)

**Goal**: Create Projects and Units (type/area/price, defaulting to `available`), view every
Sales Rep's Contacts and Deals, and reassign a Contact's (and therefore its Deals') ownership to
a different Sales Rep.

**Independent Test**: As an Admin, create a Project, add several Units with distinct
type/area/price, confirm the Admin's Contact/Deal views include every rep's records, and
reassign one Contact's owner — confirm its Deals move with it.

### Tests for User Story 3 ⚠️ (write first, confirm they fail, then implement)

- [X] T057 [P] [US3] Feature test: creating a Project and adding Units with type/area/price defaults each Unit's status to `available` in `tests/Feature/ProjectManagementTest.php`
- [X] T058 [P] [US3] Feature test: any authenticated user (Admin or Sales Rep) can view all Projects/Units regardless of who created them in `tests/Feature/UnitManagementTest.php`
- [X] T059 [P] [US3] Feature test: Admin's Contact/Deal list includes every Sales Rep's records in `tests/Feature/AdminOversightTest.php`
- [X] T060 [P] [US3] Feature test: Admin reassigns a Contact's owner; the Contact's Deals now resolve to the new owner in `tests/Feature/AdminOversightTest.php`
- [X] T061 [P] [US3] Feature test: deletion is blocked for a Project with Units, a Unit with Deals, a Contact with Deals, and a Company with Contacts in `tests/Feature/ProjectManagementTest.php`

### Implementation for User Story 3

- [X] T062 [P] [US3] Create `ProjectPolicy` (`viewAny`/`view`/`create`/`update` open to any authenticated user; `delete` denied while `units()->exists()`) in `app/Policies/ProjectPolicy.php`
- [X] T063 [P] [US3] Create `UnitPolicy` (`viewAny`/`view`/`create`/`update` open to any authenticated user; `delete` denied while `deals()->exists()`) in `app/Policies/UnitPolicy.php`
- [X] T064 [US3] Register `ProjectPolicy` and `UnitPolicy` in `app/Providers/AppServiceProvider.php` (after T062, T063)
- [X] T065 [P] [US3] Create `ProjectRequest` (`name` required max:255; `location`/`description` optional) in `app/Http/Requests/ProjectRequest.php`
- [X] T066 [P] [US3] Create `UnitRequest` (`type` required in the three `UnitType` values; `area`/`price` required numeric min:0.01; **no `status` field accepted**; `project_id` comes from the nested route, not request input) in `app/Http/Requests/UnitRequest.php`
- [X] T067 [US3] Create `ProjectController` (`index`/`create`/`store`/`show`/`edit`/`update`/`destroy`) in `app/Http/Controllers/ProjectController.php` (after T062, T065)
- [X] T068 [US3] Create `UnitController` (`create`/`store` nested under a Project; `edit`/`update`/`destroy`) in `app/Http/Controllers/UnitController.php` (after T063, T066)
- [X] T069 [US3] Add Project/Unit resource routes behind the `auth` middleware group in `routes/web.php` (after T067, T068, T047)
- [X] T070 [US3] Add Admin-only `user_id` reassignment handling (field accepted only when `auth()->user()->isAdmin()`) to `ContactRequest` and `ContactController` in `app/Http/Requests/ContactRequest.php` and `app/Http/Controllers/ContactController.php` — was already built as part of T042/T045 in User Story 1 (`ContactRequest::prepareForValidation` forces `user_id` to self for non-Admins, leaves it open for Admins); no dedicated test yet (see T060)
- [X] T071 [P] [US3] Create `resources/js/Pages/Projects/Index.vue`, `Projects/Show.vue` (lists the Project's Units with status), `Projects/Form.vue`
- [X] T072 [P] [US3] Create `resources/js/Pages/Units/Form.vue`
- [X] T073 [US3] Add an Admin-only Sales Rep picker to `resources/js/Pages/Contacts/Form.vue` (after T070, T049) — was already built as part of T049 in User Story 1

**Checkpoint**: User Stories 1, 2, and 3 all work independently.

---

## Phase 6: User Story 4 - Data isolation between Sales Reps (Priority: P4)

**Goal**: Confirm and, where needed, harden that a Sales Rep can never see or act on another
rep's Contacts or Deals, even when they compete for the same Unit.

**Independent Test**: Two Sales Reps, each with their own Contacts/Deals (including two
competing Deals on one Unit owned by different reps); verify neither can see, list, or close the
other's records.

### Tests for User Story 4 ⚠️ (write first — these largely confirm behavior already implied by US1–US3's ownership-scoped Policies/queries; any failure is a real gap to fix)

- [X] T074 [P] [US4] Feature test: Sales Rep A's `/contacts` list never includes Sales Rep B's Contacts in `tests/Feature/ContactOwnershipTest.php`
- [X] T075 [P] [US4] Feature test: Sales Rep A's `/deals` list never includes Sales Rep B's Deals in `tests/Feature/ContactOwnershipTest.php`
- [X] T076 [P] [US4] Feature test: Sales Rep A gets denied (403) opening Sales Rep B's Contact or Deal via a direct URL in `tests/Feature/ContactOwnershipTest.php`
- [X] T077 [P] [US4] Feature test: with competing Deals on one Unit owned by different reps, Sales Rep A cannot close out Sales Rep B's Deal in `tests/Feature/ContactOwnershipTest.php`

### Implementation for User Story 4

- [X] T078 [US4] Audit `ContactController`/`DealController` index scoping and `authorize()` calls; fix any gap surfaced by T074–T077 in `app/Http/Controllers/ContactController.php` and `app/Http/Controllers/DealController.php` (after T074, T075, T076, T077, T045, T046)

**Checkpoint**: All four user stories are independently functional.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Final quality gates across all stories.

- [X] T079 [P] Run `vendor/bin/pint` across all new/changed PHP files
- [X] T080 [P] Add edge-case factory states (`Deal::factory()->lost()`, `->contractedWon()`; `Unit::factory()->sold()`) for test readability in `database/factories/DealFactory.php` and `database/factories/UnitFactory.php`
- [X] T081 Run the full `php artisan test` suite and fix any regressions
- [X] T082 Execute the manual walkthrough in `specs/002-real-estate-sales/quickstart.md` end-to-end — automated as `tests/Feature/QuickstartWalkthroughTest.php`, which drives steps 1-5 and the "Expected end state" over real HTTP requests (three real logins, competing Deals, sold-Unit block, isolation 403s, Admin reassignment); the browser/visual pass is still worth doing by hand

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately.
- **Foundational (Phase 2)**: Depends on Setup — BLOCKS all user stories.
- **User Stories (Phase 3-6)**: All depend on Foundational completion.
  - US1 has no dependency on US2/US3/US4.
  - US2 reuses US1's `DealRequest`/`DealController`/`Deals/Form.vue` (adds to them, doesn't
    replace) — build after US1.
  - US3 reuses US1's `ContactRequest`/`ContactController`/`Contacts/Form.vue` (adds Admin-only
    reassignment) and adds the previously-deferred Project/Unit management UI — build after US1.
  - US4 only adds tests plus a hardening pass over US1's controllers — build after US1 (and
    ideally after US2/US3 so the hardening pass covers all ownership-sensitive code paths).
- **Polish (Phase 7)**: Depends on all four user stories being complete.

### Within Each User Story

- Tests are written first and MUST fail before their implementation tasks.
- Policies/Requests before Controllers.
- Controllers before routes.
- Routes before Vue pages that call them.

### Parallel Opportunities

- All Phase 1 tasks (T001–T004) in parallel.
- Within Phase 2: all migrations marked [P] in parallel; all models marked [P] in parallel (after
  their migrations); all factories marked [P] in parallel (after their models).
- Once Phase 2 completes, US1 can start; if staffed, US2/US3/US4 implementation tasks that touch
  different files than US1 (e.g. T062/T063 Policies, T065/T066 Requests) can be drafted in
  parallel with US1, but their Controllers (T067, T068, T070) depend on US1's `ContactController`/
  `ContactRequest` already existing (T045, T042) since they extend rather than duplicate them.
- All tests within a story marked [P] run in parallel (different assertions, same or different
  file — Feature test methods within one file are independent of each other).

---

## Parallel Example: User Story 1

```bash
# Tests (after Foundational, before implementation):
Task: "Feature test: Sales Rep creates a Contact with and without a Company in tests/Feature/ContactManagementTest.php"
Task: "Feature test: Sales Rep opens a Deal on an available Unit at lead stage in tests/Feature/DealLifecycleTest.php"

# Policies (independent files):
Task: "Create CompanyPolicy in app/Policies/CompanyPolicy.php"
Task: "Create ContactPolicy in app/Policies/ContactPolicy.php"
Task: "Create DealPolicy in app/Policies/DealPolicy.php"

# Vue pages (independent files, after routes exist):
Task: "Create resources/js/Pages/Companies/Index.vue and Form.vue"
Task: "Create resources/js/Pages/Contacts/Index.vue, Show.vue, Form.vue"
Task: "Create resources/js/Pages/Deals/Index.vue and Form.vue"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup.
2. Complete Phase 2: Foundational (schema, models, auth, Unit-status derivation) — this alone
   already delivers a working login and data layer.
3. Complete Phase 3: User Story 1.
4. **STOP and VALIDATE**: run the User Story 1 tests and the first two steps of
   `quickstart.md`'s manual walkthrough.
5. Demo: a Sales Rep taking one Deal from Lead to Contracted/Won.

### Incremental Delivery

1. Setup + Foundational → login works, schema exists.
2. + User Story 1 → single-rep Deal workflow (MVP).
3. + User Story 2 → competing-Deals safety net.
4. + User Story 3 → Admin inventory setup + oversight (note: in practice, Project/Unit inventory
   must exist before US1 can be demoed with real UI-created data rather than factories — see
   `quickstart.md`'s manual walkthrough ordering — but as an independently *codable and testable*
   slice, US3 is still built after US1 per its P3 priority).
5. + User Story 4 → confirmed data isolation.
6. Polish → Pint, full test suite, quickstart validation.

---

## Notes

- [P] tasks touch different files with no unmet dependency.
- [Story] labels map every Phase 3+ task to US1–US4 from spec.md for traceability.
- Tests are required by the constitution (not optional) — write each one first and confirm it
  fails before writing the implementation task(s) below it.
- Commit after each task or logical group.
- Stop at any checkpoint to validate a story independently before moving to the next.
- Deal ownership is never stored on `deals` — it's always derived via `contact_id →
  contacts.user_id` (research.md #3); do not add a `user_id` column to the deals migration (T010).
- Unit `status` is never accepted as form input (T066) — it is written only by `DealObserver`
  (T029).
