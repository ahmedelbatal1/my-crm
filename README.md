# my-crm

An internal CRM for a residential real-estate developer in Egypt. It tracks the inventory the
developer sells and the sales conversations around it, for the developer's own sales staff — it is
not customer-facing.

## The domain

Four records carry the whole product:

- **Project** — a compound or development. Has a name, an optional location and description.
- **Unit** — one sellable property inside a Project: an `apartment`, `villa` or `shop`, with an
  area and a price. A Project owns many Units.
- **Contact** — a prospective buyer, with a name, phone, optional email, and an optional
  **Company** for buyers purchasing through an organisation. Every Contact is owned by one
  Sales Rep.
- **Deal** — links one Contact to one Unit, recording the agreed full price and an optional
  deposit amount and date. A Deal's ownership is not stored on the Deal; it is derived through
  `contact_id` to `contacts.user_id`.

A Deal carries a stage, defined in `app/Enums/DealStage.php`:

```
lead  ->  reserved  ->  contracted_won        lost
```

`lost` is the losing outcome rather than a later step. There is no state machine in the code:
`DealRequest` validates only that the submitted stage is one of the four, so any stage can be set
from any other. That is deliberate — staff correct mistakes and re-open conversations.

### Unit status is derived, never entered

A Unit's `status` (`available`, `reserved`, `sold`) is **not** a field anyone can edit. It is
recomputed server-side by `app/Observers/DealObserver.php`, which fires on every Deal `saved` and
`deleted` event and applies one rule to all the Deals on that Unit:

- any Deal at `contracted_won` — the Unit is **sold**
- otherwise, any Deal at `reserved` — the Unit is **reserved**
- otherwise — the Unit is **available**

Because the rule reads current state rather than reacting to transitions, it reverses correctly:
move the winning Deal back to `lead` and the Unit returns to `available`. `UnitRequest` accepts no
`status` key at all, and `resources/js/Pages/Units/Form.vue` shows availability read-only.

Two consequences worth knowing:

- **Several Deals may compete for one Unit.** Nothing prevents two Contacts having open Deals on
  the same Unit.
- **Winning one Deal does not close the others.** When a Deal reaches `contracted_won` the Unit
  becomes `sold` and *new* Deals on it are rejected by `DealRequest`, but the competing Deals are
  left open for staff to mark `lost` themselves. Editing an existing Deal on a since-sold Unit
  stays allowed, which is what lets you close a losing Deal out.

## The two roles

`app/Enums/UserRole.php` defines `admin` and `sales_rep`. Read from the policies, the role changes
**only how Contacts and Deals are reached** — read the code rather than trusting this summary, but
as of now:

| Area | Sales Rep | Admin |
| --- | --- | --- |
| Contact view / update / delete | own Contacts only | any Contact |
| Deal view / update / delete | Deals whose Contact they own | any Deal |
| `/contacts` and `/deals` lists | filtered to `auth()->id()` | unfiltered |
| Contact picker on the Deal form | only their own Contacts | all Contacts |
| Contact owner (`user_id`) | forced to self, input ignored | may set it, and reassign an existing Contact to another rep |
| Sales-rep picker on the Contact form | not rendered | rendered |

Everything else is identical for both roles. `ProjectPolicy`, `UnitPolicy` and `CompanyPolicy`
return `true` for `viewAny`, `view`, `create` and `update` — **a Sales Rep can create and edit
Projects, Units and Companies exactly as an Admin can.** Inventory is shared, not
administrator-owned.

Deletion is guarded by dependency rather than by role, in all four cases:

- a Project with Units cannot be deleted
- a Unit with Deals cannot be deleted
- a Contact with Deals cannot be deleted
- a Company with Contacts cannot be deleted

Reassigning a Contact moves its Deals with it, because Deal ownership is derived from the Contact
rather than stored separately.

## Tech stack

Server:

| | |
| --- | --- |
| PHP | 8.2+ (`^8.2`; 8.2.26 in this checkout) |
| Laravel | 12.67.0 |
| Inertia (server) | `inertiajs/inertia-laravel` 3.3.1 |
| PHPUnit | 11.5.56 |
| Laravel Pint | 1.30.4 |

Client:

| | |
| --- | --- |
| Vue | 3.5.41 |
| Inertia (client) | `@inertiajs/vue3` 3.7.0 |
| Tailwind CSS | 4.3.3, via `@tailwindcss/vite` |
| Vite | 7.3.6 |
| Node | 22.14.0 / npm 10.9.2 in this checkout |

One Laravel application serving Inertia pages — no separate API, no JSON endpoints. There is no
authentication package: login is a single controller over `Auth::attempt`, and roles are a string
column cast to an enum. Tailwind v4 needs no config file; the design tokens live in
`resources/css/app.css` under `@theme`.

Shape of the codebase: 6 models, 5 policies, 6 form requests, 6 controllers plus a base class,
10 migrations, 36 routes, 15 Inertia pages and 11 shared Vue components.

## Setup

```bash
git clone <this-repo> my-crm
cd my-crm

composer install
npm install

cp .env.example .env
php artisan key:generate
```

`.env.example` ships `DB_CONNECTION=sqlite`, which needs no database server. Create the file and
migrate:

```bash
touch database/database.sqlite
php artisan migrate
```

For MySQL instead (what this checkout uses), set `DB_CONNECTION=mysql` and uncomment the
`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` lines that `.env.example`
ships commented out, create the database, then run `php artisan migrate`. The test suite ignores
all of this — `phpunit.xml` pins it to in-memory SQLite, so tests never touch your development
data.

Run it with two terminals:

```bash
php artisan serve   # http://127.0.0.1:8000
npm run dev
```

Or `composer dev`, which runs the server, queue listener, log tailer and Vite together under
`concurrently`. For a production-style asset build, `npm run build`.

Checks:

```bash
php artisan test        # 96 tests
vendor/bin/pint         # formats; --test to check only
```

### Demo users

```bash
php artisan db:seed
```

`DatabaseSeeder` calls `DemoUsersSeeder`, which upserts three accounts. All three share the
password `password`:

| Email | Name | Role |
| --- | --- | --- |
| `admin@test.com` | System Admin | admin |
| `rep1@test.com` | Mostafa | sales_rep |
| `rep2@test.com` | Heba | sales_rep |

**These are local-development credentials only.** The password is a known constant committed to
the repository. Never run this seeder against a shared, staging or production database, and never
deploy with these accounts present. Two reps exist because the ownership-isolation behaviour needs
two of them to be visible at all.

The seeder creates no Projects, Units, Contacts or Deals — sign in and create them, or use the
factories in `database/factories/`.

## How this was built

Both features were built with [spec-kit](https://github.com/github/spec-kit) (version
`1.0.2.dev0`, see `.specify/init-options.json`), a spec-driven workflow where the specification,
plan and task list are committed artifacts that precede the code. Everything under `specs/` is
that trail, and it is the best explanation of *why* the code looks the way it does.

`.specify/memory/constitution.md` is the governing document: five principles (Laravel
conventions, Inertia/Vue consistency, test-first development, code style, and simplicity/YAGNI)
that every plan is checked against before implementation starts. Test-first is marked
non-negotiable there, which is why both features wrote tests before implementation.

Five stages ran per feature, each leaving an artifact:

1. `/speckit-specify` — `spec.md` plus `checklists/requirements.md`, the quality gate on the spec
   itself. Written as user stories, functional requirements (`FR-###`) and measurable success
   criteria (`SC-###`), with no implementation detail.
2. `/speckit-plan` — `plan.md`, `research.md`, `data-model.md` and `contracts/`. `research.md`
   records each technical decision with its rationale and the alternatives rejected.
3. `/speckit-tasks` — `tasks.md`, a dependency-ordered task list grouped by user story so each
   story is independently completable and testable.
4. `/speckit-analyze` — a read-only cross-artifact consistency pass. Its findings and the
   resulting corrections are recorded in the feature's `plan.md` and requirements checklist.
5. `/speckit-implement` — executes the tasks; `IMPLEMENTATION_LOG.md` records what was built,
   what was discovered, and what was left open.

### The two features

**[`specs/002-real-estate-sales/`](specs/002-real-estate-sales/)** — the domain: schema, models,
policies, controllers, authentication, the `DealObserver` derivation rule, and the first Inertia
pages. **82 tasks, all complete**, delivering **68 tests**. Its four user stories were the Deal
lifecycle, competing Deals on one Unit, Admin inventory and oversight, and data isolation between
reps.

**[`specs/003-design-system-shell/`](specs/003-design-system-shell/)** — the design system over
those pages: a contrast-measured token layer, the shared `AppLayout` shell, one `StatusBadge`
covering all seven Deal-stage and Unit-availability values, one table treatment, a read-only
pipeline board, and the empty/validation/confirmation/error states. **68 tasks, 65 complete**,
adding **28 tests** for a total of 96. It changed no route, controller, validation rule, policy or
migration; the three server-side files it touched are the Inertia shared-props middleware,
`bootstrap/app.php` and `resources/views/app.blade.php`.

Three tasks in 003 are open and documented as such in its `tasks.md` and `IMPLEMENTATION_LOG.md`:
the home screen's Arabic copy is awaiting a decision before being translated, and two manual
browser passes (responsive behaviour at the 768px floor, and the visual walkthrough in
`quickstart.md`) have not been performed.

Each feature's `quickstart.md` is a runnable validation script for that feature. For 002 it is
also automated, as `tests/Feature/QuickstartWalkthroughTest.php`, which drives the whole
walkthrough over real HTTP requests.
