# Phase 1 Contracts: Web Routes (Inertia)

This app has no external/JSON API consumers — its only "interface" is the set of server routes
its own Inertia/Vue pages call. Every route below returns an Inertia response (full page render
or a redirect-back-to-Inertia-page after a mutation), per constitution Principle II. All routes
require the `auth` middleware (FR-026) unless noted.

Legend: **Policy** = ability checked via the model's Policy. **Owned-scope** = an index query is
additionally filtered to `auth()->user()`-owned records for `sales_rep`, unfiltered for `admin`
(FR-015/FR-016/FR-017).

## Auth

| Method | Path | Request fields | Policy | Response |
|---|---|---|---|---|
| GET | `/login` | — | guest only | `Auth/Login` page |
| POST | `/login` | `email` (required, email), `password` (required) | guest only | redirect to `/` on success; redirect back with field error on failure |
| POST | `/logout` | — | `auth` | redirect to `/login` |

## Projects (`/projects`)

| Method | Path | Request fields | Policy | Response |
|---|---|---|---|---|
| GET | `/projects` | — | `viewAny` (any authenticated user) | `Projects/Index` — all Projects, unowned/shared (FR-016) |
| GET | `/projects/create` | — | `create` | `Projects/Form` |
| POST | `/projects` | `name` (required, string, max:255), `location` (nullable, string, max:255), `description` (nullable, string) | `create` | redirect to `/projects/{project}` |
| GET | `/projects/{project}` | — | `view` | `Projects/Show` — Project + its Units (with derived `status`) |
| GET | `/projects/{project}/edit` | — | `update` | `Projects/Form` |
| PUT | `/projects/{project}` | same as store | `update` | redirect to `/projects/{project}` |
| DELETE | `/projects/{project}` | — | `delete` (blocked if `units()->exists()`, FR-023) | redirect to `/projects` with error if blocked, else to `/projects` |

## Units (`/projects/{project}/units`, `/units/{unit}`)

| Method | Path | Request fields | Policy | Response |
|---|---|---|---|---|
| GET | `/projects/{project}/units/create` | — | `create` | `Units/Form` |
| POST | `/projects/{project}/units` | `type` (required, in:apartment,villa,shop), `area` (required, numeric, min:0.01), `price` (required, numeric, min:0.01) — **no `status` field**, see data-model.md | `create` | redirect to `/projects/{project}` |
| GET | `/units/{unit}/edit` | — | `update` | `Units/Form` |
| PUT | `/units/{unit}` | same as store (still no `status`) | `update` | redirect to `/projects/{project}` |
| DELETE | `/units/{unit}` | — | `delete` (blocked if `deals()->exists()`, FR-022) | redirect back with error if blocked, else to `/projects/{project}` |

## Companies (`/companies`)

| Method | Path | Request fields | Policy | Response |
|---|---|---|---|---|
| GET | `/companies` | — | `viewAny` | `Companies/Index` — all Companies (shared, FR-016) |
| GET | `/companies/create` | — | `create` | `Companies/Form` |
| POST | `/companies` | `name` (required, string, max:255) | `create` | redirect to `/companies` |
| GET | `/companies/{company}/edit` | — | `update` | `Companies/Form` |
| PUT | `/companies/{company}` | same as store | `update` | redirect to `/companies` |
| DELETE | `/companies/{company}` | — | `delete` (blocked if `contacts()->exists()`) | redirect back with error if blocked, else to `/companies` |

## Contacts (`/contacts`)

| Method | Path | Request fields | Policy | Response |
|---|---|---|---|---|
| GET | `/contacts` | — | `viewAny` | `Contacts/Index` — **Owned-scope** (FR-015/FR-017) |
| GET | `/contacts/create` | — | `create` | `Contacts/Form` (includes Sales Rep picker only when `auth()->user()->role === admin`) |
| POST | `/contacts` | `name` (required, max:255), `phone` (required, max:30), `email` (nullable, email, max:255), `company_id` (nullable, exists:companies,id), `user_id` (required only if Admin; forced to self otherwise, FR-019) | `create` | redirect to `/contacts/{contact}` |
| GET | `/contacts/{contact}` | — | `view` (owner or Admin, FR-015) | `Contacts/Show` — Contact + its Deals |
| GET | `/contacts/{contact}/edit` | — | `update` | `Contacts/Form` |
| PUT | `/contacts/{contact}` | same as store; `user_id` change only permitted for Admin (FR-018) | `update` | redirect to `/contacts/{contact}` |
| DELETE | `/contacts/{contact}` | — | `delete` (blocked if `deals()->exists()`, FR-021) | redirect back with error if blocked, else to `/contacts` |

## Deals (`/deals`)

| Method | Path | Request fields | Policy | Response |
|---|---|---|---|---|
| GET | `/deals` | — | `viewAny` | `Deals/Index` — **Owned-scope** pipeline view grouped by `stage` (FR-015/FR-017) |
| GET | `/deals/create` | `contact_id` or `unit_id` optionally pre-selected via query string | `create` | `Deals/Form` |
| POST | `/deals` | `contact_id` (required, exists:contacts,id, must be owned by actor unless Admin — FR-020), `unit_id` (required, exists:units,id, target Unit's derived `status` must not be `sold` — FR-010), `full_price` (required, numeric, min:0.01), `deposit_amount` (nullable, numeric, min:0, lte:full_price), `deposit_paid_at` (nullable, date), `stage` (required, in:lead,reserved,contracted_won,lost, default `lead`) | `create` | redirect to `/deals/{deal}` — triggers `DealObserver` to recompute the Unit's status |
| GET | `/deals/{deal}` | — | `view` (owner via Contact, or Admin) | `Deals/Show` |
| GET | `/deals/{deal}/edit` | — | `update` | `Deals/Form` |
| PUT | `/deals/{deal}` | same fields as store minus the sold-Unit block (FR-010 applies to creation only, research.md #5); any stage → any stage (FR-008) | `update` (owner via Contact, or Admin — this is also the path for the manual close-out in FR-012) | redirect to `/deals/{deal}` — triggers `DealObserver` |
| DELETE | `/deals/{deal}` | — | `delete` (owner or Admin) | redirect to `/deals` — triggers `DealObserver` to recompute the Unit's status |
