# Phase 1 Contract: Shared Component API

**Feature**: `003-design-system-shell` | **Date**: 2026-09-02

The "interface" this feature exposes is the set of shared Vue components the 14 pages consume.
Each component below is justified by a named requirement (research.md #14); anything not listed
here is not built.

Conventions: Vue 3 `<script setup>`, props declared with `defineProps`, `@/` alias imports,
two-space indent — matching the existing `resources/js` code.

---

## `AppLayout.vue` (rewritten)

The shell. Wraps all 13 authenticated pages. Regions and active-matching rules are specified in
[data-model.md §5](../data-model.md).

| Prop | Type | Required | Purpose |
|---|---|---|---|
| — | — | — | takes no props; reads `auth.user` and `flash` from shared props |

| Slot | Purpose |
|---|---|
| `default` | page content |
| `header` | optional override of the page-header region (pages normally use `PageHeader` inside `default`) |

Satisfies FR-007 – FR-013. Renders `FlashMessages` above the content. Every interactive element
(brand, nav links, sign-out) is a real focusable element with a visible `--color-focus` ring.

---

## `PageHeader.vue`

| Prop | Type | Required | Default |
|---|---|---|---|
| `title` | String | yes | — |
| `description` | String | no | `null` |

| Slot | Purpose |
|---|---|
| `action` | the screen's primary action (typically one `AppButton`) |

Satisfies FR-011. Title renders at `--text-page-title` in `--color-ink-strong`.

---

## `FlashMessages.vue`

| Prop | Type | Required | Purpose |
|---|---|---|---|
| — | — | — | reads `flash` from shared props |

Renders one message block per non-null severity: success → palm, warning → ochre, error → brick
(grounds and text per [data-model.md §1](../data-model.md)). Each block is `role="status"` for
success/warning and `role="alert"` for error. Satisfies FR-010, FR-039.

---

## `StatusBadge.vue` (rewritten — replaces `StageBadge.vue` + old `StatusBadge.vue`)

| Prop | Type | Required | Allowed |
|---|---|---|---|
| `value` | String | yes | any of the seven raw values; unknown values hit the neutral fallback |
| `kind` | String | yes | `stage` \| `availability` |

Appearance, labels, and the fallback rule are fully specified in
[data-model.md §2](../data-model.md). Satisfies FR-015 – FR-019.

**Migration note**: `StageBadge.vue` is deleted. Its three importers (`Deals/Index.vue`,
`Deals/Show.vue`, `Contacts/Show.vue`) must move to `<StatusBadge :value="deal.stage" kind="stage" />`
before deletion.

---

## `DataTable.vue`

One table treatment for all eight list surfaces (FR-020 – FR-026).

| Prop | Type | Required | Default | Purpose |
|---|---|---|---|---|
| `columns` | Array | yes | — | column definitions, see below |
| `rows` | Array | yes | — | the records |
| `rowHref` | Function | no | `null` | `row => url`; when given, each row becomes navigable |
| `emptyTitle` | String | no | `'Nothing here yet'` | passed to `EmptyState` |
| `emptyDescription` | String | no | `null` | passed to `EmptyState` |

Column definition:

```
{
  key: String,            // property path on the row, e.g. 'unit.project.name'
  label: String,          // header text, carries the unit: 'Full price (EGP)'
  align: 'left'|'right',  // default 'left'; 'right' also applies tabular-nums
  format: 'money'|'area'|'number'|'date'|null,  // helper from lib/format.js
  truncate: Boolean,      // default true for text columns — FR-027
}
```

| Slot | Purpose |
|---|---|
| `cell:{key}` | override rendering for one column (e.g. to place a `StatusBadge`) |
| `empty` | override the empty state entirely |
| `actions` | trailing per-row action cell (e.g. a `ConfirmAction`) |

Behaviour contract:

- Numeric/money columns are right-aligned with equal-width digits so values align on the decimal
  separator (FR-006, FR-021).
- `null` values render `—`, distinct from a formatted `0.00` (FR-025).
- With `rowHref`, the whole row is one navigable target — consistently across every list (FR-026).
- With zero rows, renders `EmptyState` instead of an empty table body (FR-032).
- The table scrolls horizontally inside its own container rather than making the page scroll.

---

## `EmptyState.vue`

| Prop | Type | Required | Default |
|---|---|---|---|
| `title` | String | yes | — |
| `description` | String | no | `null` |

| Slot | Purpose |
|---|---|
| `action` | the action that creates the first record |

Satisfies FR-032. For ownership-scoped lists the calling page passes copy naming the scope ("None
of your contacts yet" rather than "No contacts"), which is what satisfies FR-033.

---

## `FormField.vue`

| Prop | Type | Required | Default | Purpose |
|---|---|---|---|---|
| `label` | String | yes | — | field label, `<label for>`-bound |
| `id` | String | yes | — | control id |
| `error` | String | no | `null` | validation message from `form.errors.*` |
| `required` | Boolean | no | `false` | renders the required marker (FR-036) |
| `hint` | String | no | `null` | supporting text, e.g. the unit marker "EGP" |

| Slot | Purpose |
|---|---|
| `default` | the input/select/textarea itself |

Behaviour: error text renders directly beneath the control in brick (FR-034); the control border
uses `--color-line-strong` so it meets 3:1 non-text contrast, and switches to the danger tone
when `error` is set. `aria-invalid` and `aria-describedby` are wired to the error element.

---

## `AppButton.vue`

| Prop | Type | Required | Default | Allowed |
|---|---|---|---|---|
| `variant` | String | no | `primary` | `primary` \| `secondary` \| `danger` \| `ghost` |
| `type` | String | no | `button` | standard button types |
| `href` | String | no | `null` | when set, renders an Inertia `Link` instead of a `<button>` |
| `loading` | Boolean | no | `false` | shows progress and sets `disabled` (FR-035) |
| `disabled` | Boolean | no | `false` | — |

`loading` is what satisfies FR-035's double-submit prevention: pages bind it to Inertia's
`form.processing`.

---

## `ConfirmAction.vue`

Inline two-step destructive confirm (FR-040; research.md #10 — no modal, no focus trap).

| Prop | Type | Required | Default | Purpose |
|---|---|---|---|---|
| `label` | String | no | `'Delete'` | resting-state text |
| `confirmLabel` | String | no | `'Confirm'` | armed-state confirm text |
| `question` | String | no | `'Delete this?'` | armed-state prompt |
| `disabled` | Boolean | no | `false` | renders unavailable — used by FR-038 |
| `disabledReason` | String | no | `null` | why it is unavailable, shown to the user |

| Event | Payload | When |
|---|---|---|
| `confirmed` | — | user confirms the second step |

Behaviour: click arms it in place; Escape or blur cancels; confirming emits `confirmed`. When
`disabled` with a `disabledReason`, the control renders unavailable and states the reason — this
is the mechanism for FR-038's up-front prevention of the four blocked deletions, using the
dependent-record data each page already receives.

---

## `DescriptionList.vue`

| Prop | Type | Required | Purpose |
|---|---|---|---|
| `items` | Array | yes | `[{ label, value, format?, hint? }]` |

Renders the shared field/value pattern used by `Contacts/Show`, `Deals/Show` and `Projects/Show`
(FR-031). `format` reuses the `lib/format.js` helpers so money and dates read identically to their
table counterparts; `null` values render `—`.

---

## `PipelineBoard.vue` + `DealCard.vue`

The read-only board for `Deals/Index` (FR-027 – FR-030).

`PipelineBoard.vue`:

| Prop | Type | Required | Purpose |
|---|---|---|---|
| `dealsByStage` | Object | yes | the existing prop `DealController@index` already sends — unchanged |

Renders four fixed columns in stage order (`lead`, `reserved`, `contracted_won`, `lost`), each
showing its label, its deal count, and the **total `full_price` of its deals** computed client-side
from the rows already present (FR-028). Grid uses `repeat(4, minmax(0, 1fr))` and each column's
card list scrolls vertically, so one heavy column never resizes the others (FR-030). Below the
tablet breakpoint the row scrolls horizontally.

**No write path.** The board renders no control that changes a stage — opening a Deal is the only
route to that (FR-029). This is a hard contract, not a default, and it is enforced by a fitness
assertion: neither `PipelineBoard.vue` nor `DealCard.vue` may contain `useForm`, `router.put`,
`router.post`, `router.patch`, `router.delete`, `draggable`, or `@drop` (research.md #5).

`DealCard.vue`:

| Prop | Type | Required | Purpose |
|---|---|---|---|
| `deal` | Object | yes | one deal row |

Shows contact name, project · unit type, and `money(full_price)`; the whole card is one navigable
link to the deal. Long names truncate rather than reflowing the card (FR-027).

---

## `lib/format.js`

Not a component, but part of the contract. Exports `money`, `number`, `area`, `date` with the
rules in [data-model.md §3](../data-model.md) — including string coercion for `decimal` values and
the `—` for absent values that makes FR-025 true.
