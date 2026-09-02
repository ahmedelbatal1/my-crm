# Quickstart Validation: Design System & Application Shell

**Feature**: `003-design-system-shell` | **Date**: 2026-09-02

How to prove this feature works. Part A is automated and must be green. Part B is the visual pass
— unavoidably manual for a design system, and stated as manual rather than pretended otherwise
(research.md #5).

---

## Prerequisites

- WAMP running with the MySQL dev database migrated (`php artisan migrate`).
- Three users in the dev database: one Admin and two Sales Reps. If absent:
  `php artisan tinker` → `User::factory()->admin()->create(['email' => 'admin@example.test', 'password' => bcrypt('password')]);`
  and the same with `->salesRep()` twice.
- Some inventory to look at: at least one Project with several Units at **very different price
  magnitudes** (e.g. 850000.00, 4500000.00, 12750000.50) — the differing magnitudes are the point,
  since they are what makes FR-006/SC-006 checkable.
- At least one Deal in each of the four stages, one Unit in each of the three availability values,
  one Deal with **no** deposit recorded and one with a deposit of exactly `0.00` (that pair is what
  proves FR-025).
- `npm run dev` for the visual pass, or `npm run build` to check the production bundle.

---

## Part A — Automated (must all pass)

```sh
php artisan test
vendor/bin/pint --test
npm run build
```

Expected:

| Check | Expectation |
|---|---|
| `php artisan test` | **All of feature 002's 68 tests still pass, unchanged** (FR-045, SC-013), plus the new tests below |
| `SharedPropsTest` | `auth.user` carries name + role for each role, is `null` for a guest, and exposes no other attribute; `flash` keys always present |
| `ErrorPageTest` | 403, 404 and 419 each render `Errors/Error` **and** keep their status code — the status assertion is the one that must not be dropped |
| `DesignTokenContrastTest` | Every pairing in [data-model.md §4](./data-model.md) meets its threshold, computed from the tokens in `app.css` |
| `DesignSystemFitnessTest` | Every page imports `AppLayout` (except Login, Home, Errors); no literal colour or stock Tailwind palette class anywhere under `resources/js/`; no raw enum string in any template; `StatusBadge` names all seven values; no stale `StageBadge` import remains; every list page uses `DataTable` and no page declares its own `<table>`; every detail page uses `DescriptionList`; every form uses `FormField`; every delete control uses `ConfirmAction`; **`PipelineBoard`/`DealCard` contain no stage-write call** (FR-029) |
| `vendor/bin/pint --test` | clean |
| `npm run build` | succeeds; CSS stays well under 100 kB uncompressed; per-page chunks still emitted |

A red existing test means a behavioural regression to **revert**, never a test to edit (FR-045).

---

## Part B — Visual pass (manual, ~15 minutes)

### B1. Shell consistency — US1

1. Sign in as a Sales Rep. Visit `/deals`, `/contacts`, `/projects`, `/companies`, then a detail
   and a form screen in each section.
2. Confirm on **every** screen: same brand, same nav, same identity block, same sign-out position,
   same page-header treatment. Nothing shifts position between screens.
3. Confirm the nav marks the right section — then open `/projects/{id}/units/create` and confirm
   **Projects** is still marked (FR-008; this is the case that a naive exact-URL match gets wrong).
4. Confirm your name and "Sales rep" are visible without scrolling. Sign out, sign in as the
   Admin, confirm it reads "Admin".
5. Visit `/login` while signed out and `/` — confirm both use the same palette, type, and controls
   even though they sit outside the shell.
6. **Keyboard only**: Tab through a form screen end to end. Every control must be reachable with a
   clearly visible focus ring (FR-013, SC-012).

### B2. Status vocabulary — US2

1. On `/deals`, confirm all four stage badges read "Lead", "Reserved", "Contracted / Won", "Lost".
   No underscored value anywhere (SC-002).
2. On a Project's Show screen, confirm availability badges read "Available", "Reserved", "Sold".
3. Find a screen showing a **Reserved Deal and a Reserved Unit together** (a deal detail for a
   reserved unit). Confirm they are tellable apart by shape, not just wording (FR-017).
4. Take a greyscale screenshot (or set the OS display to greyscale) and confirm every badge is
   still readable and distinguishable (FR-018, SC-002).

### B3. Records — US3

1. On a Project's Show screen with the mixed-magnitude Units: confirm prices are right-aligned,
   digits are equal-width, and the values **align on the decimal point** so the larger number is
   visibly longer (SC-006).
2. Confirm the price column header reads `Full price (EGP)` and **no cell repeats "EGP"** (FR-023),
   and the area header names `m²`.
3. Confirm the Deal with no deposit shows `—` while the `0.00` deposit shows `0.00` (FR-025).
4. Confirm dates read as `02 Sep 2026` everywhere they appear.
5. Confirm clicking anywhere on a row opens the record, the same way on every list.
6. On `/deals`, confirm each column shows its label, its count, and its **stage total**; confirm a
   column with many deals scrolls inside itself without changing the other three columns' widths
   (FR-030).
7. Confirm the board offers **no** way to change a stage — no drag, no advance control (FR-029).
8. Rename a contact to something very long and confirm it truncates in the table and on its
   pipeline card without breaking either layout (FR-027).

### B4. States — US4

1. **Empty**: with a fresh Sales Rep who owns nothing, visit `/contacts` and `/deals`. Confirm a
   purposeful empty state with a create action, and that the copy says the list is empty *for you*
   rather than implying the system is empty (FR-033).
2. **Validation**: submit the Contact form with no name. Confirm the message sits with the field,
   your other input survives, and focus lands on the offending field (FR-034).
3. **In flight**: submit a valid form and confirm the button shows progress and cannot be
   double-clicked (FR-035).
4. **Blocked deletion, prevented**: open a Project that has Units. Confirm the delete control is
   presented as unavailable **with the reason stated** rather than being clickable-and-failing
   (FR-038). Repeat for a Unit with Deals, a Contact with Deals, and a Company with Contacts —
   all four cases (SC-009).
5. **Confirmation**: on a Project with no Units, click delete. Confirm the inline two-step appears,
   Escape cancels, and confirming proceeds (FR-040).
6. **403**: as Sales Rep A, open Sales Rep B's contact URL directly. Confirm a styled in-app page
   explaining the permission problem with a route back — not Laravel's default error page — and
   confirm via devtools that the response status is still **403** (FR-041).
7. **404**: visit `/contacts/999999`. Same styled treatment, status 404.
8. **419**: open a form, clear the session cookie in devtools, submit. Confirm the page explains
   the session expired and directs you to sign in again (US4 scenario 8).

### B5. Token propagation — SC-005

1. In `resources/css/app.css`, temporarily repoint `--color-primary` to a different ramp value.
2. Reload. Every primary button, active nav item, and focus ring across all screens must change,
   with **no other file edited** (FR-003, SC-005).
3. Revert the change. Re-run `DesignTokenContrastTest` — if the temporary value had failed
   contrast, the test should have caught it.

### B6. Responsive — FR-012

1. Narrow the browser to 768px — the floor stated in FR-012. Confirm the shell collapses
   predictably, the page never scrolls horizontally, tables scroll inside their own container, and
   the pipeline row scrolls sideways rather than crushing its columns.
2. Confirm on a real tablet or device-emulation that the viewport meta tag is doing its job — if
   the page renders zoomed-out at a ~980px virtual width, the meta tag is missing (research.md #11).

---

## Expected end state

- 68 existing tests pass unchanged; the four new test files pass.
- All 14 screens render from the shared shell/tokens; zero literal colours remain under
  `resources/js/`.
- All seven status values render through one badge, distinguishable in greyscale and by kind.
- Money reads as grouped two-decimal figures aligned on the decimal, with EGP named once per
  column.
- Every list has a purposeful empty state; all four blocked deletions explain themselves before
  being attempted; 403/404/419 are styled and keep their status codes.
- The palette can be changed from the token block alone.
