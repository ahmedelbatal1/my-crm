# Phase 1 Data Model: Real Estate Unit Sales CRM

All entities are Eloquent models backed by MySQL migrations (SQLite in tests). Enum-like columns
are plain `string` columns validated by Form Requests and backed by PHP backed enums in code
(per research.md #2 — no new packages).

## User (extends existing `users` table)

| Field | Type | Rules |
|---|---|---|
| `name` | string | required (existing) |
| `email` | string | required, unique (existing) |
| `password` | string, hashed | required (existing) |
| `role` | string enum: `admin`, `sales_rep` | required, default `sales_rep` (new column) |

**Relationships**: `hasMany(Contact::class)` (contacts this user owns, only meaningful for
`sales_rep`; an `admin` typically owns none).

**Notes**: No self-registration; accounts are created directly (seeder/tinker for this feature's
scope, per research.md #1). Role is fixed per user — no multi-role support (spec Assumptions).

## Company

| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max 255 |

**Relationships**: `hasMany(Contact::class)`.

**Deletion rule (FR-015 conceptually, enforced as its own rule for Company)**: blocked while
`contacts()->exists()` — a Company with linked Contacts cannot be deleted until they are
reassigned or removed (mirrors FR-021/FR-022/FR-023's pattern for the other entities; Company
itself isn't enumerated by number in the FR list but the same protective rule applies for
referential integrity).

## Contact

| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max 255 |
| `phone` | string | required, max 30 (FR-004; research.md #6) |
| `email` | string, nullable | optional, valid email format, max 255 |
| `company_id` | nullable FK → `companies.id` | optional (FR-005) |
| `user_id` | FK → `users.id` | required — the owning Sales Rep (FR-019) |

**Relationships**: `belongsTo(Company::class)` (nullable), `belongsTo(User::class)` (owner),
`hasMany(Deal::class)`.

**Validation/business rules**:
- On create by a Sales Rep: `user_id` is forced to the acting user (FR-019); the field is not
  exposed in the form for that role.
- On create by an Admin: `user_id` is a required select of an existing Sales Rep (FR-019).
- Reassignment (`user_id` change) is Admin-only (FR-018); when it happens, all of this Contact's
  Deals move ownership with it automatically since Deal ownership is derived, not stored
  (research.md #3) — no extra write needed on `deals`.

**Deletion rule**: blocked while `deals()->exists()` (FR-021).

## Project

| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max 255 |
| `location` | string, nullable | optional, max 255 |
| `description` | text, nullable | optional |

**Relationships**: `hasMany(Unit::class)`.

**Deletion rule**: blocked while `units()->exists()` (FR-023).

## Unit

| Field | Type | Rules |
|---|---|---|
| `project_id` | FK → `projects.id` | required (FR-002) |
| `type` | string enum: `apartment`, `villa`, `shop` | required (FR-002) |
| `area` | decimal(10,2) | required, > 0 |
| `price` | decimal(12,2) | required, > 0 — the asking/list price, independent of any Deal's negotiated `full_price` |
| `status` | string enum: `available`, `reserved`, `sold` | **system-derived only** (research.md #4); defaults to `available` (FR-003); never accepted as input on create/update |

**Relationships**: `belongsTo(Project::class)`, `hasMany(Deal::class)`.

**Status derivation rule (FR-011, FR-013)** — recomputed by `DealObserver` on every `saved`/
`deleted` event of any of this Unit's Deals:

```text
if any linked Deal.stage == contracted_won: status = sold
else if any linked Deal.stage == reserved:  status = reserved
else:                                        status = available
```

This is a pure function of the Unit's current Deals and is idempotent — safe to recompute on
every Deal write without tracking prior state.

**Deletion rule**: blocked while `deals()->exists()` (FR-022).

## Deal

| Field | Type | Rules |
|---|---|---|
| `contact_id` | FK → `contacts.id` | required (FR-006) |
| `unit_id` | FK → `units.id` | required (FR-006) |
| `full_price` | decimal(12,2) | required, > 0 (FR-024) |
| `deposit_amount` | decimal(12,2), nullable | optional; if present, ≥ 0 and ≤ `full_price` (FR-025) |
| `deposit_paid_at` | date, nullable | optional; only meaningful alongside `deposit_amount` |
| `stage` | string enum: `lead`, `reserved`, `contracted_won`, `lost` | required, default `lead` (FR-007) |

**Relationships**: `belongsTo(Contact::class)`, `belongsTo(Unit::class)`.

**Ownership**: derived, not stored — `deal.contact.user_id` (research.md #3).

**Validation/business rules**:
- **Create**: the acting Sales Rep must own `contact_id` (FR-020; Admins exempt); `unit_id` must
  not currently have `status == sold` (FR-010; research.md #5).
- **Stage transitions**: any stage → any other stage is allowed, including reversal away from
  `contracted_won` (FR-008; edge case in spec.md). No state-machine guard is implemented —
  `stage` is a plain validated enum field.
- **Manual close-out**: any authenticated user who owns the Deal (via its Contact) or is an Admin
  may set `stage = lost` on any of that Unit's other open Deals once one Deal reaches
  `contracted_won` (FR-012). This is the existing update authorization/validation path — no
  special-case code beyond normal Policy + Form Request checks.
- Multiple Deals may exist concurrently for the same `unit_id` with different `contact_id`s
  (FR-009) — no uniqueness constraint on `unit_id`.

**No deletion rule of its own** — Deals are the "leaf" entity; deleting one is unrestricted
(other entities' deletion rules exist specifically to protect against orphaning a Deal, not the
reverse).

## Entity relationship summary

```text
User (role) 1---* Contact *---1 Company (optional)
Contact 1---* Deal *---1 Unit *---1 Project
```

- A `User` with role `sales_rep` owns zero or more `Contact`s (and, transitively, their `Deal`s).
- A `Contact` optionally belongs to one `Company`; a `Company` may have many `Contact`s.
- A `Deal` always links exactly one `Contact` to exactly one `Unit`.
- A `Unit` belongs to exactly one `Project`; a `Project` has many `Unit`s.
- A `Unit` may have zero, one, or many concurrent `Deal`s (competing buyers, FR-009).
