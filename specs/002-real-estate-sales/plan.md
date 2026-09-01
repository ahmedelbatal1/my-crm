# Implementation Plan: Real Estate Unit Sales CRM

**Branch**: `002-real-estate-sales` | **Date**: 2026-08-25 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/002-real-estate-sales/spec.md`

## Summary

Extend the existing bare Laravel/Inertia skeleton into a real-estate sales CRM: developers own
Projects (compounds) containing Units (apartments/villas/shops); Sales Reps and Admins record
potential-buyer Contacts and open Deals linking a Contact to a Unit, tracking full price and an
optional deposit as the Deal progresses through Lead → Reserved → Contracted/Won (or Lost). Unit
status (available/reserved/sold) is fully system-derived from its Deals' stages, multiple
competing Deals may exist per Unit, and Sales Reps see only their own Contacts/Deals while Admins
see everything and can reassign ownership. Built entirely with standard Laravel conventions
(Eloquent, Form Requests, Policies, migrations) and Inertia/Vue pages, per the project
constitution — no new third-party packages.

## Technical Context

**Language/Version**: PHP 8.2+, Laravel Framework 12.67

**Primary Dependencies**: `inertiajs/inertia-laravel` ^3.3 (server) + `@inertiajs/vue3` ^3.7 and
Vue 3.5 (client), Tailwind CSS 4, Vite 7 — all already present in the repo; no new dependencies
are needed for this feature (per Simplicity & YAGNI, see research.md for the auth/roles
decisions that keep it that way)

**Storage**: MySQL (`DB_CONNECTION=mysql`, local WAMP instance) for dev/prod; SQLite in-memory
for the automated test suite (already configured in `phpunit.xml`)

**Testing**: PHPUnit 11 Feature tests (real HTTP requests through routes/controllers/policies)
and Unit tests for model logic (e.g. Unit-status derivation), run via `php artisan test`; Laravel
Pint for style

**Target Platform**: Server-rendered Inertia SPA in desktop browsers — an internal back-office
tool for the developer's own sales staff, not public-facing

**Project Type**: Web application — single Laravel monolith serving Inertia/Vue pages (not a
separate frontend/backend split)

**Performance Goals**: Internal back-office scale only — list/detail pages MUST feel instant
(sub-second) for the data volumes below; no high-throughput or public-traffic requirement

**Constraints**: Must extend the existing `users` and `contacts` tables additively (new
migrations, not edits to the migrations already present) since they are already part of the
checked-in schema; must not introduce new packages where a built-in Laravel mechanism suffices
(constitution Principle V)

**Scale/Scope**: A single developer's internal sales team — expect tens of Projects, hundreds to
low thousands of Units, and low thousands of Contacts/Deals; a handful to a few dozen concurrent
Sales Reps/Admins

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | How the plan satisfies it |
|---|---|---|
| I. Convention-Driven Laravel Architecture | PASS | Eloquent models (Project, Unit, Contact, Company, Deal) with relationships; Form Requests for all validation; Policies for all authorization; a `DealObserver` (framework event mechanism) for Unit-status derivation instead of ad hoc controller logic; thin controllers delegating to models/observers. |
| II. Inertia/Vue Frontend Consistency | PASS | All new pages are Inertia page components under `resources/js/Pages`, reusing the existing `HandleInertiaRequests` shared-props middleware; no Blade views introduced; no JSON-only API routes needed since all consumers are the app's own Inertia pages. |
| III. Test-First Development (NON-NEGOTIABLE) | PASS (planned) | `tasks.md` (next phase) will pair every model/policy/controller with Feature tests exercising real HTTP requests, plus Unit tests for the Unit-status derivation rule and price/deposit validation edge cases, before/alongside implementation. |
| IV. Code Style & Static Quality | PASS | New PHP follows existing Laravel naming norms (singular StudlyCase models, plural snake_case tables); Pint run before considering any task done; Vue components follow the existing `resources/js` conventions. |
| V. Simplicity & YAGNI | PASS | No new Composer/npm packages (see research.md: rejected Breeze/Fortify/spatie-permission in favor of built-in Laravel auth/authorization); no owner column duplicated on `deals` (derived via `contact_id` instead of syncing two sources of truth). |
| Data & Security Requirements | PASS (planned) | `Contact`/`Deal`/etc. models use `$fillable`; every resource route sits behind `auth` middleware; every mutation is Policy-gated (ownership checked, not assumed); schema changes are additive migrations only. |

No violations — Complexity Tracking table is not needed.

**Post-Phase 1 re-check**: research.md and data-model.md confirm no new packages were
introduced (role via a plain column + PHP enum, no permissions package; auth via built-in
`Auth::attempt`, no Breeze/Fortify) and every business rule (FR-010, FR-011/013) lands in a
Form Request or a single Observer rather than scattered controller logic. Constitution Check
still PASSes with no changes.

## Project Structure

### Documentation (this feature)

```text
specs/002-real-estate-sales/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md        # Phase 1 output (/speckit-plan command)
├── quickstart.md        # Phase 1 output (/speckit-plan command)
├── contracts/           # Phase 1 output (/speckit-plan command)
│   └── web-routes.md
└── tasks.md             # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

### Source Code (repository root)

```text
app/
├── Models/
│   ├── User.php                 # extended: role column + relationships
│   ├── Contact.php              # extended: fillable, relationships
│   ├── Company.php              # new
│   ├── Project.php              # new
│   ├── Unit.php                 # new
│   └── Deal.php                 # new
├── Observers/
│   └── DealObserver.php         # new — recomputes linked Unit.status per FR-011/FR-013
├── Policies/
│   ├── ContactPolicy.php        # new
│   ├── DealPolicy.php           # new
│   ├── ProjectPolicy.php        # new
│   ├── UnitPolicy.php           # new
│   └── CompanyPolicy.php        # new
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── AuthenticatedSessionController.php  # new — login/logout only
│   │   ├── ProjectController.php    # new
│   │   ├── UnitController.php       # new
│   │   ├── CompanyController.php    # new
│   │   ├── ContactController.php    # new
│   │   └── DealController.php       # new
│   └── Requests/
│       ├── ProjectRequest.php       # new
│       ├── UnitRequest.php          # new
│       ├── CompanyRequest.php       # new
│       ├── ContactRequest.php       # new
│       └── DealRequest.php          # new (StoreDealRequest/UpdateDealRequest if rules diverge)

database/
├── migrations/
│   ├── ..._add_role_to_users_table.php          # new
│   ├── ..._add_fields_to_contacts_table.php      # new — name/phone/email/company_id/user_id
│   ├── ..._create_companies_table.php            # new
│   ├── ..._create_projects_table.php             # new
│   ├── ..._create_units_table.php                # new
│   └── ..._create_deals_table.php                # new
└── factories/
    ├── UserFactory.php           # extended with role states
    ├── ContactFactory.php        # new
    ├── CompanyFactory.php        # new
    ├── ProjectFactory.php        # new
    ├── UnitFactory.php           # new
    └── DealFactory.php           # new

resources/js/Pages/
├── Auth/Login.vue                # new
├── Projects/{Index,Show,Form}.vue    # new (Show lists a Project's Units)
├── Contacts/{Index,Show,Form}.vue    # new
├── Companies/{Index,Form}.vue        # new
└── Deals/{Index,Form}.vue            # new (Index is the pipeline/board view)

routes/
└── web.php                       # extended with auth + resource routes

tests/
├── Feature/
│   ├── Auth/LoginTest.php
│   ├── ProjectManagementTest.php
│   ├── UnitManagementTest.php
│   ├── CompanyManagementTest.php
│   ├── ContactManagementTest.php
│   ├── ContactOwnershipTest.php       # data isolation (User Story 4)
│   ├── DealLifecycleTest.php          # User Story 1
│   ├── CompetingDealsTest.php         # User Story 2
│   └── AdminOversightTest.php         # User Story 3
└── Unit/
    └── UnitStatusDerivationTest.php   # FR-011/FR-013 rule in isolation
```

**Structure Decision**: Single Laravel application (no separate frontend/backend projects) —
matches the existing repo layout exactly. Backend logic lives under `app/` following standard
Laravel folders (`Models`, `Http/Controllers`, `Http/Requests`, `Policies`, `Observers`);
frontend pages live under the existing `resources/js/Pages` Inertia convention. No `Option
2/3`-style split applies since this is a monolith, not a decoupled API + separate client.

## Complexity Tracking

*No Constitution Check violations — table not needed.*
