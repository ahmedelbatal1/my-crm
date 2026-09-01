# Quickstart: Validating the Real Estate Unit Sales CRM

This is a validation guide, not implementation instructions — it proves the feature works
end-to-end once built. See [data-model.md](./data-model.md) for field/rule details and
[contracts/web-routes.md](./contracts/web-routes.md) for exact routes/fields.

## Prerequisites

```powershell
composer install
npm install
copy .env.example .env   # if not already present
php artisan key:generate
php artisan migrate
```

Seed at least one `admin` and two `sales_rep` users (via `php artisan tinker` or a seeder added
in the implementation phase) — this feature assumes accounts are provisioned directly, not via
self-registration (see research.md #1).

```powershell
npm run dev        # in one terminal — Vite dev server
php artisan serve  # in another — app at http://127.0.0.1:8000
```

## Automated validation (preferred)

Once `tasks.md` implementation is complete, the full acceptance-scenario suite runs via:

```powershell
php artisan test
```

Each Feature test below maps to one of the spec's user stories/edge cases and should be read
alongside spec.md's Acceptance Scenarios:

- `tests/Feature/DealLifecycleTest.php` → User Story 1 (Lead → Reserved → Contracted/Won,
  deposit capture, Unit becomes `sold`)
- `tests/Feature/CompetingDealsTest.php` → User Story 2 (two Deals on one Unit, one wins, the
  other stays open until manually closed, new-Deal-on-sold-Unit is blocked)
- `tests/Feature/AdminOversightTest.php` + `ProjectManagementTest.php` + `UnitManagementTest.php`
  → User Story 3 (Project/Unit inventory setup, cross-rep visibility, ownership reassignment)
- `tests/Feature/ContactOwnershipTest.php` → User Story 4 (data isolation between Sales Reps)
- `tests/Unit/UnitStatusDerivationTest.php` → FR-011/FR-013 status-derivation rule in isolation

## Manual walkthrough (matches User Story 1 → 4 in order)

1. **Set up inventory** (as Admin or any Sales Rep — see research.md #7): log in, create a
   Project ("Palm Hills Compound"), add a Unit to it (type `villa`, area `350`, price `4500000`).
   Confirm the Unit shows status **available**.
2. **Run a Deal to close** (as Sales Rep A): create a Contact ("Jane Buyer", phone required,
   no Company), open a Deal linking Jane to the villa with `full_price = 4500000`, stage `lead`.
   Advance it to `reserved`, adding a deposit amount and date — confirm the Unit's status flips
   to **reserved**. Advance it to `contracted_won` — confirm the Unit's status flips to **sold**.
3. **Competing Deal** (as Sales Rep B, before step 2's Deal reaches `contracted_won`): create a
   second Contact and a second Deal on the *same* villa. Confirm both Deals coexist. After Rep
   A's Deal reaches `contracted_won`, confirm: (a) creating a third new Deal on that villa is
   rejected because it's sold; (b) Rep B's still-open Deal is untouched automatically; (c) Rep B
   or an Admin can manually set Rep B's Deal to `lost`.
4. **Isolation check**: log in as Sales Rep A and confirm Rep B's Contact/Deal never appear in
   Rep A's `/contacts` or `/deals` lists, and that opening Rep B's Deal/Contact URL directly is
   denied.
5. **Admin oversight**: log in as Admin, confirm `/contacts` and `/deals` show every rep's
   records, and reassign Jane Buyer to a different Sales Rep — confirm her Deal moves with her.

## Expected end state

- The villa Unit is `sold`, owned (via its winning Deal's Contact) by whichever rep's Deal
  reached `contracted_won`.
- Exactly one Deal on that Unit is `contracted_won`; any others are `lost` (once manually closed)
  or still open if not yet closed out.
- Sales Rep A and B each see only their own Contacts/Deals; Admin sees all of them plus the full
  Project/Unit inventory.
