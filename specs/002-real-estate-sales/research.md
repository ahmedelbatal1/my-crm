# Phase 0 Research: Real Estate Unit Sales CRM

No Technical Context items were left as `NEEDS CLARIFICATION` — the language, framework,
storage, and testing stack are all fixed by the existing repository (Laravel 12 + Inertia/Vue3 +
MySQL/SQLite) and the project constitution. Research below instead resolves the *design*
decisions needed to turn the spec's functional requirements into a concrete, convention-driven
implementation.

## 1. Authentication approach

- **Decision**: Hand-write a minimal session-based auth flow (login + logout only) using
  Laravel's built-in `Auth` facade and `web` guard — one controller
  (`AuthenticatedSessionController`), one Form Request, one Inertia `Login.vue` page. No
  self-registration, password-reset, or email-verification flow.
- **Rationale**: Spec Assumptions state Sales Reps/Admins are internal employees with no
  external/self-service login; accounts are provisioned by an Admin (or a seeder/tinker for now).
  FR-026 only requires *authentication*, not a specific flow. Building the two routes needed
  keeps to constitution Principle V (Simplicity & YAGNI) and Principle I (prefer Laravel's
  built-in `Auth::attempt` over a package).
- **Alternatives considered**: Laravel Breeze — rejected: scaffolds registration, password-reset,
  and profile-management views/routes not in scope, adding unused surface area. Laravel Fortify —
  rejected: a headless building-block package aimed at teams needing heavy customization of many
  auth flows; overkill for two routes.

## 2. Role storage

- **Decision**: A single `role` string column on `users` constrained to `admin` / `sales_rep`
  (enforced via a Form Request `in:` rule and a PHP backed enum `UserRole` for type-safety in
  code), not a separate roles/permissions system.
- **Rationale**: FR-014 fixes exactly two roles with no mention of custom/dynamic permission
  sets. A column + enum is the simplest structure that satisfies every FR (015–020) about
  role-based visibility.
- **Alternatives considered**: `spatie/laravel-permission` — rejected: designed for dynamic,
  many-permission systems; adds a new dependency for a closed set of 2 roles (violates
  Simplicity & YAGNI). Separate `roles` table + pivot — rejected: unnecessary normalization when
  the set of roles is fixed and unlikely to grow within this feature's scope.

## 3. Deal ownership representation

- **Decision**: `deals` has no `user_id`/owner column. A Deal's owner is always its linked
  Contact's owner, read via `deals.contact_id -> contacts.user_id`.
- **Rationale**: FR-019 requires Deal ownership to always equal, and never diverge from, its
  Contact's ownership, and FR-018 requires reassigning a Contact to move all its Deals with it.
  Storing ownership twice would require observer/sync code to keep both in sync with no benefit;
  deriving it removes an entire class of drift bugs.
- **Alternatives considered**: Denormalized `user_id` on `deals`, synced via an observer whenever
  the parent Contact is reassigned — rejected: extra code and a window for inconsistency, for no
  query-performance win at this feature's scale (low thousands of rows).

## 4. Unit status derivation (FR-011/FR-013)

- **Decision**: `units.status` stays a real, persisted, enum-backed column, but the only writer
  of that column is a `DealObserver` (`saved` and `deleted` model events) that recomputes it from
  scratch on every affected Unit: `sold` if any linked Deal is `contracted_won`, else `reserved`
  if any linked Deal is `reserved`, else `available`. Controllers/Form Requests never accept
  `status` as user input for Units.
- **Rationale**: A persisted column lets Unit list/filter views (SC-007) query and sort by status
  directly in SQL without joining/aggregating Deals on every request. Centralizing the write in
  one observer means every Deal-mutation path (create, stage change, delete) recomputes the same
  way — satisfying FR-011's "MUST derive automatically" without duplicating the rule at each
  controller action.
- **Alternatives considered**: Computed accessor (no persisted column) — rejected: can't
  efficiently filter/sort a Unit index by status in SQL. Controllers setting `status` manually
  after each Deal action — rejected: easy to miss a call site (e.g. Deal deletion), and violates
  "automatic" derivation.

## 5. Blocking new Deals on an already-sold Unit (FR-010)

- **Decision**: Enforced as a custom validation rule inside the Deal-creation Form Request,
  checking the target Unit's current (derived) `status !== 'sold'`. Not a database constraint,
  and not checked on Deal *updates* (editing an existing Deal on a since-sold Unit — e.g.
  reopening a Lost Deal — is not creation and is out of this rule's scope).
- **Rationale**: This is a user-facing business validation with a specific error message,
  matching constitution Principle I's use of Form Requests for input validation. A DB check
  constraint cannot distinguish "creating a new Deal" from "editing an existing one."
- **Alternatives considered**: Enforcing it in `DealPolicy::create` — rejected: Policies answer
  "may this actor perform this action," not "is this specific data valid"; a validation rule
  produces a clearer, field-attributable error for the UI.

## 6. Contact "contact details" minimum (FR-004)

- **Decision**: `phone` is required; `email` is optional.
- **Rationale**: Real-estate lead capture is phone-first in this market — a phone number is
  reliably available at first contact while an email often is not yet.
- **Alternatives considered**: Require both — rejected as too strict for early-stage leads.
  Require "at least one of phone/email" via conditional validation — rejected as unnecessary
  complexity when a single fixed required field satisfies the spec just as well.

## 7. Who may manage Projects/Units/Companies

- **Decision**: Any authenticated user (Admin or Sales Rep) may create/edit/delete Projects,
  Units, and Companies, matching the literal text of FR-001/FR-002/FR-005 ("authenticated
  users"), even though User Story 3 illustrates an Admin doing this setup work.
- **Rationale**: The Functional Requirements are the binding contract for `/speckit-tasks`; user
  stories illustrate priority and value, not additional restrictions. Nothing in the FRs
  restricts inventory management to Admins.
- **Alternatives considered**: Restrict to Admin only — rejected: not supported by any FR, and
  would narrow scope beyond what was specified without a documented reason to do so.
