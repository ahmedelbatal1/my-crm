# Specification Quality Checklist: Design System & Application Shell

**Purpose**: Validate specification completeness and quality before proceeding to planning
**Created**: 2026-09-02
**Feature**: [spec.md](../spec.md)

## Content Quality

- [x] No implementation details (languages, frameworks, APIs)
- [x] Focused on user value and business needs
- [x] Written for non-technical stakeholders
- [x] All mandatory sections completed

## Requirement Completeness

- [x] No [NEEDS CLARIFICATION] markers remain
- [x] Requirements are testable and unambiguous
- [x] Success criteria are measurable
- [x] Success criteria are technology-agnostic (no implementation details)
- [x] All acceptance scenarios are defined
- [x] Edge cases are identified
- [x] Scope is clearly bounded
- [x] Dependencies and assumptions identified

## Feature Readiness

- [x] All functional requirements have clear acceptance criteria
- [x] User scenarios cover primary flows
- [x] Feature meets measurable outcomes defined in Success Criteria
- [x] No implementation details leak into specification

## Notes

**Scale**: 4 user stories (P1–P4) with 28 acceptance scenarios (6/5/9/7), 45 functional
requirements, 14 success criteria, 10 edge cases. Covers all 13 screens delivered by feature 002 plus the pre-existing home screen.

**On "no implementation details"**: the requester dictated specific technical constraints
(Tailwind v4 `@theme` tokens in `resources/css/app.css`, the shared `AppLayout` component, a
single `StatusBadge` component). These are deliberately confined to the *User-provided technical
constraints* subsection of Assumptions and are named there as the requester's own inputs. All 45
functional requirements and all 14 success criteria remain behavioural and technology-agnostic —
no requirement or criterion names a framework, component, or file.

**All three open decisions were resolved by the requester before planning** and are recorded in
the *Resolved clarifications* subsection of Assumptions, so no `[NEEDS CLARIFICATION]` marker
remains:

1. **Pipeline board is read-only** — no drag-and-drop, no on-card write control (FR-029).
2. **Feedback works both ways** — pre-emptive prevention where the screen already has the data
   (FR-038), plus a shell message region for outcomes only detectable on attempt (FR-039). This
   makes sharing the signed-in identity and outcome messages a required part of the feature.
3. **Money is EGP**, currency named once per column/field, always two decimal places (FR-022,
   FR-023). The requester's rationale is preserved in the spec: the three money columns are all
   stored to two decimal places and Deal validation admits amounts from 0.01 up, so truncating
   decimals would render distinct amounts identically and defeat FR-006's decimal alignment.

**Anticipated during authoring and covered in the initial draft** (not caught by a later
iteration — validation passed on its first run): success criteria drifting into the unmeasurable
("looks consistent"), accessibility going unstated, and the requester's "no new
routes/controllers/migrations" boundary living only in prose. Addressed by SC-001/SC-004/SC-005/
SC-006/SC-007 (all countable), FR-004/FR-013/FR-018 with SC-003 and SC-012, and FR-043–FR-045
with SC-014 respectively.

**Verified against the codebase rather than assumed**:

- The 13 feature-002 screens plus the pre-existing home screen (14 total) were enumerated from
  `resources/js/Pages/`.
- The absence of any data source for signed-in identity and action-outcome messages was confirmed
  in the Inertia shared-props middleware — which is why decision 2 above expands scope to include
  shared props.
- The overlapping `StageBadge`/`StatusBadge` pair, their colliding "Reserved" treatments, and the
  underscored raw-value leak were confirmed in the component and page sources.
- The four blocked-deletion cases were confirmed to currently answer with a bare permission-denied
  response carrying no explanation.
- The `decimal(12,2)` storage of `price`/`full_price`/`deposit_amount` and Deal validation's
  `min:0.01` were confirmed, substantiating decision 3.

**One inferred extension, flagged in Assumptions**: unit *area* (the application's one other
decimal column) is given the same equal-width-digit, two-decimal, unit-named-once treatment as
money for consistency. The requester specified this for money only.

---

## Post-analysis revision (2026-09-02)

`/speckit-analyze` ran after `/speckit-tasks` and found defects in this spec that this checklist's
first pass had missed. Re-validated after remediation: **still 16/16 PASS**, and three items are
now genuinely rather than nominally satisfied.

| Item previously passed on thin evidence | What the analysis found | Fixed by |
|---|---|---|
| "Success criteria are measurable" | **SC-001 was unsatisfiable**: it demanded all 14 screens render inside a shell that FR-014 deliberately excludes three of. A criterion that cannot be met is not measurable. | SC-001 reworded to 13 authenticated screens in the shell + 2 unauthenticated + the error page on the same tokens |
| "Requirements are testable and unambiguous" | **FR-012's "tablet-width" named no number**; **FR-013 was scoped to the shell** while SC-012 demanded every screen; **FR-010 said "MUST render" a success outcome the feature knowingly never emits** | FR-012 fixed at 768px; FR-013 broadened to every screen; FR-010 changed to "MUST support" with the boundary stated inline |
| "No implementation details" | **FR-002 mandated a success/warning/danger/info palette in parallel with the status colours** — a duplicated vocabulary that produced unused tokens and a Principle V violation | FR-002 now names the four status families (quiet/ochre/palm/brick) as the single semantic set and forbids a parallel one |

**Accepted deviation, recorded deliberately**: FR-002 now names four colour families, which is
closer to implementation detail than the rest of the spec. That is at the requester's explicit
direction — they own the design vocabulary — and it buys the elimination of the duplicate
semantic set. Noted rather than silently absorbed.

**Two items remain open by decision, not by oversight**:

1. **FR-005/FR-044 are knowingly unmet on the home screen.** Translating its Arabic copy is on
   hold at the requester's instruction, so `tasks.md` T024 ships blocked. Resolve by confirming
   the translation or amending FR-005/FR-044 to exempt that screen.
2. **`lib/format.js` has no automated test.** The requester declined both remedies (Vitest as a
   new dependency; a `node -e` subprocess from PHPUnit) as worse than the gap. Recorded as an
   accepted risk in research.md #5, plan.md's risk table, and tasks.md's header, with
   quickstart.md B3 as the human guard.

**One correction to the analysis itself**: it reported the project as not being a git repository
and flagged tasks.md's commit instructions as inconsistent. That was wrong — the project is a git
repository on `master` with an `origin` remote. The finding was withdrawn and no edit made.
