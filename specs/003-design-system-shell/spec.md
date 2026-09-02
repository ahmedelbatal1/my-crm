# Feature Specification: Design System & Application Shell

**Feature Branch**: `003-design-system-shell`

**Created**: 2026-09-02

**Status**: Draft

**Input**: User description: "Design system and application shell for the CRM. LTR English, warm earthy palette, Tailwind v4 with tokens in resources/css/app.css under @theme. Shared AppLayout, a StatusBadge covering all seven DealStage and UnitStatus values, consistent data tables with tabular figures, a pipeline board for Deals/Index, and empty/error/form states. No new routes, controllers, or migrations. Applies over the existing 13 Inertia pages from feature 002."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Every screen reads as one product (Priority: P1) 🎯 MVP

A Sales Rep signs in and moves between the pipeline, their contacts, the project inventory, and
the company list. Every screen wears the same frame: the same brand mark, the same navigation
with the current section visibly marked, the same indication of who they are signed in as and in
what role, the same page-title treatment, and the same place where the system talks back to them
about what just happened. Nothing about changing screens makes them re-orient.

**Why this priority**: Today each of the 13 screens carries its own hand-written styling and the
frame around them shows no active section and no signed-in identity. This is the single change
that turns a set of pages into an application, and every later story depends on the shell and
the shared palette it establishes.

**Independent Test**: Sign in and visit all 14 screens in sequence. Confirm each renders inside
the same shell, that the navigation marks the correct section (including on nested screens like
adding a unit to a project), that the signed-in user's name and role are visible without
scrolling, and that colors/typography come from one shared palette rather than per-page choices.

**Acceptance Scenarios**:

1. **Given** a signed-in Sales Rep, **When** they visit any of the application's screens,
   **Then** the same header, navigation, identity display, and content frame appear in the same
   position on every one.
2. **Given** a signed-in user viewing the contacts list, **When** they look at the navigation,
   **Then** the "Contacts" item is visibly marked as the current section and the others are not.
3. **Given** a signed-in user on a nested screen (adding a unit inside a project), **When** they
   look at the navigation, **Then** the "Projects" section is still marked as current.
4. **Given** a signed-in Admin, **When** any screen loads, **Then** their name and their Admin
   role are both shown, distinguishing their view from a Sales Rep's.
5. **Given** a signed-in user on any screen, **When** they choose to sign out, **Then** the sign
   out control is in the same place it occupies on every other screen.
6. **Given** the sign-in screen (which sits outside the authenticated shell), **When** it
   renders, **Then** it still uses the same palette, typography, form controls, and button
   treatment as the rest of the application.

---

### User Story 2 - One status vocabulary everywhere (Priority: P2)

A user scanning any screen can tell a Deal's stage or a Unit's availability at a glance, because
each of the seven possible values always looks the same and always reads in plain English —
whether it appears on a pipeline card, in a deals list, on a deal's detail screen, or in a
project's unit table. No screen ever shows a raw internal value.

**Why this priority**: Stage and availability are the primary facts this CRM communicates, and
they currently render through two separate look-alike components with overlapping colors — a
`reserved` Deal and a `reserved` Unit are indistinguishable in kind, while `contracted_won`
leaks its underscored internal form into some views. Fixing the vocabulary is the highest-value
change after the shell itself, and it is independently valuable even if tables and boards keep
their current layout.

**Independent Test**: Create data covering all seven values, then visit every screen that
displays a stage or availability. Confirm each value renders through the same shared badge with
a distinct, human-readable label, and that no screen displays an underscored internal value.

**Acceptance Scenarios**:

1. **Given** Deals in all four stages, **When** any screen shows their stage, **Then** each
   renders as a labeled badge reading "Lead", "Reserved", "Contracted / Won", or "Lost".
2. **Given** Units in all three availability values, **When** any screen shows availability,
   **Then** each renders as a labeled badge reading "Available", "Reserved", or "Sold".
3. **Given** a Deal at the "Reserved" stage and a Unit with "Reserved" availability shown on the
   same screen, **When** a user compares them, **Then** the badges are visually distinguishable
   as different kinds of fact, not just different words.
4. **Given** any of the seven values, **When** its badge renders, **Then** its text and
   background meet the accessibility contrast threshold, and its meaning survives being read in
   greyscale (colour is never the only carrier of meaning).
5. **Given** a value the interface does not recognise, **When** a badge is asked to render it,
   **Then** it degrades to a neutral badge showing readable text rather than breaking the layout
   or rendering blank.

---

### User Story 3 - Records are scannable and comparable (Priority: P3)

A Sales Rep with a real book of business opens their contact list, a project's unit inventory,
or a contact's deal history, and can scan down a column to compare records — names left-aligned
and readable, money right-aligned with digits that line up so magnitudes are visually obvious,
dates in one consistent format, and a clear row-level path into each record. On the pipeline, the
same records arrange as a board of stage columns showing each column's count and total value.

**Why this priority**: Comparison is the core reading task in a CRM, and it is what the current
mixed-alignment, proportional-figure tables make hardest. It comes after the shell and the
status vocabulary because it refines how records read rather than whether the application hangs
together, but before edge-state polish because it affects everyday use.

**Independent Test**: Load a project with several units at prices of very different magnitudes
and a rep with a dozen contacts and deals. Confirm money columns align on the digit, that three-
and seven-figure amounts are distinguishable at a glance, that dates share one format, and that
the pipeline board shows per-stage counts and totals.

**Acceptance Scenarios**:

1. **Given** a list of records, **When** it renders, **Then** every list in the application uses
   the same table treatment: identical header styling, row separation, row height, and hover
   affordance.
2. **Given** money values of differing digit counts in one column, **When** they render, **Then**
   digits occupy equal width and the values align on their decimal separator, so a larger number
   is visibly longer.
3. **Given** any money value anywhere in the application, **When** it renders, **Then** it is
   thousands-separated and shows exactly two decimal places, so two amounts differing only below
   the whole unit never look identical.
4. **Given** a money column, **When** it renders, **Then** the currency is named once at the
   column header (or beside the field label on a detail or form screen) and is not repeated in
   every cell.
5. **Given** any date value, **When** it renders, **Then** it uses one unambiguous format
   consistent across every screen.
6. **Given** a record row, **When** a user wants to open it, **Then** the whole row offers a
   consistent, discoverable way in, in the same manner on every list.
7. **Given** Deals spread across stages, **When** the pipeline board renders, **Then** each stage
   column shows its heading, its count of deals, and the total value of the deals in it.
8. **Given** a stage column holding many more deals than fit the viewport, **When** the board
   renders, **Then** that column remains usable without distorting the other columns' widths.
9. **Given** a record with an unusually long name, **When** it renders in a table or on a
   pipeline card, **Then** it is truncated or wrapped predictably without breaking the layout,
   and the full value remains obtainable.

---

### User Story 4 - The interface behaves when things are empty, wrong, or in flight (Priority: P4)

A new user with no data, a user who mistypes a form, a user whose session has expired, and a user
who tries to delete something another record depends on all get a clear, styled, plain-English
response that tells them what happened and what they can do next — instead of a blank area, an
unstyled framework error page, or a silent no-op.

**Why this priority**: These states are less frequent than everyday reading, but they are where
an interface loses a user's trust fastest. Their value depends on the shell (which owns the
message region) and the table patterns (which own the empty state), so they come last.

**Independent Test**: With a freshly seeded empty account, visit every list screen and confirm a
purposeful empty state. Then submit each form invalid, attempt each of the four blocked
deletions, and let a session expire — confirming each produces a styled, explanatory response.

**Acceptance Scenarios**:

1. **Given** a list with no records, **When** it renders, **Then** it shows a styled empty state
   explaining what belongs there and offering the action that creates the first record.
2. **Given** a list that is empty only because of the acting user's ownership scope, **When** it
   renders, **Then** the empty state says so in terms the user understands, rather than implying
   the whole system is empty.
3. **Given** a form submitted with invalid values, **When** it comes back, **Then** every
   offending field is marked, its message sits with the field, the user's other input is
   preserved, and the first offending field receives focus.
4. **Given** a form being submitted, **When** the request is in flight, **Then** the submit
   control shows progress and cannot be triggered twice.
5. **Given** an attempt to delete a Project with Units, a Unit with Deals, a Contact with Deals,
   or a Company with Contacts, **When** the user initiates it, **Then** the interface explains
   which dependent records block the deletion instead of failing silently or showing a raw error
   page.
6. **Given** a destructive action, **When** the user initiates it, **Then** they must confirm
   before it proceeds.
7. **Given** a user reaching a screen they are not permitted to see, or one that does not exist,
   **When** the response renders, **Then** it appears as a styled in-application message with a
   route back, not an unstyled default error page.
8. **Given** an expired session, **When** the user submits anything, **Then** they are told their
   session expired and directed to sign in again.

---

### Edge Cases

- What happens when a badge is handed a stage or availability value the interface does not know?
  (Covered: US2 scenario 5 — neutral fallback, never a blank or broken badge.)
- What happens to a very long contact, company, project, or unit name in a fixed table column or
  a narrow pipeline card? (Covered: US3 scenario 8.)
- What happens when a pipeline stage column holds far more deals than the viewport?
  (Covered: US3 scenario 7.)
- What happens when every pipeline stage is empty — does the board disappear, or show four empty
  columns with an invitation to create the first deal?
- How does a money column read when one value is zero, and when a Deal has no deposit recorded at
  all? (An absent value must be visibly different from a recorded zero.)
- What does the navigation mark as current on a screen that belongs to no navigation section
  (e.g. the sign-in screen, or a not-found response)?
- How does the shell render for an Admin whose view is unscoped, versus a Sales Rep's — the
  identity display must make the distinction visible without implying extra navigation the role
  does not have.
- What happens on a viewport narrower than a desktop window: which shell regions collapse, and
  does the four-column pipeline board reflow or scroll?
- How does a screen behave if a user's role changes mid-session (their existing tab still shows
  the old role until the next page load)?
- What happens when the same underlying record appears twice on one screen with different status
  kinds (a Unit's availability alongside the stage of a Deal on that Unit)?

## Requirements *(mandatory)*

### Functional Requirements

**Palette & typographic foundation**

- **FR-001**: The application MUST draw all colour, typography, spacing, and shape decisions from
  a single named design-token set, so that no screen introduces its own one-off values.
- **FR-002**: The token set MUST express a warm, earthy palette with a neutral ground, a warm
  primary action colour, and four distinct status families — quiet, ochre, palm and brick — which
  serve as the single semantic set for both status indication and action-outcome messages. No
  parallel set of colours for success/warning/danger meaning may exist alongside them.
- **FR-003**: Changing a token's value MUST propagate that change to every screen that uses it,
  with no per-screen edits required.
- **FR-004**: Every text-on-background and interactive-control colour pairing the interface uses
  MUST meet WCAG 2.1 AA contrast (4.5:1 for body text, 3:1 for large text and interface
  controls).
- **FR-005**: The interface MUST present all content left-to-right in English, with one
  consistent type scale for page titles, section headings, body text, and supporting text.
- **FR-006**: Numeric values MUST render with equal-width digits so that figures in a column
  align on their decimal separator regardless of their value.

**Application shell**

- **FR-007**: Every authenticated screen MUST render inside one shared shell providing the brand
  mark, primary navigation, signed-in identity, sign-out control, page-header region, content
  region, and system-message region.
- **FR-008**: The shell MUST mark the navigation entry matching the current section, including
  when the user is on a nested or child screen of that section.
- **FR-009**: The shell MUST display the signed-in user's name and role on every authenticated
  screen.
- **FR-010**: The shell MUST reserve one consistent region in which the system reports the
  outcome of an action, positioned identically on every screen, and it MUST support rendering
  success, warning, and failure outcomes distinguishably within that region. Note that this
  feature produces no success outcome — emitting one requires controller changes FR-043 forbids —
  so the success treatment ships supported but unexercised, ready for a later feature.
- **FR-011**: The shell MUST offer a consistent page-header pattern carrying the screen's title,
  optional supporting description, and that screen's primary action.
- **FR-012**: The shell MUST remain usable down to a 768px-wide viewport, collapsing its regions
  predictably rather than overflowing horizontally. 768px is the stated floor: below it, layout is
  not guaranteed; at or above it, no screen may scroll horizontally.
- **FR-013**: All interactive controls, on every screen, MUST be reachable and operable by
  keyboard, with a visible focus indicator.
- **FR-014**: The sign-in screen, which sits outside the authenticated shell, MUST still use the
  same tokens, typography, form controls, and button treatment as the rest of the application.

**Status vocabulary**

- **FR-015**: The interface MUST render every Deal stage and Unit availability value through one
  shared status indicator covering all seven values (Lead, Reserved, Contracted / Won, Lost;
  Available, Reserved, Sold).
- **FR-016**: The status indicator MUST display a human-readable label for each value and MUST
  never expose a raw internal value.
- **FR-017**: The status indicator MUST visually distinguish a Deal stage from a Unit
  availability value even when both read "Reserved".
- **FR-018**: The status indicator MUST convey each value's meaning without relying on colour
  alone.
- **FR-019**: The status indicator MUST degrade to a neutral, readable presentation when handed
  an unrecognised value.

**Record presentation**

- **FR-020**: All record lists MUST share one table treatment: consistent header styling, row
  separation, row height, alignment rules, and hover affordance.
- **FR-021**: Tables MUST right-align numeric and money columns and left-align text columns.
- **FR-022**: Money values MUST render thousands-separated and MUST always show exactly two
  decimal places, on every screen, so that two amounts differing only below the whole unit never
  render identically.
- **FR-023**: The currency (Egyptian Pound, EGP) MUST be named once per money column — in the
  column header on tables, or beside the field label on detail and form screens — and MUST NOT
  be repeated in every cell.
- **FR-024**: Date values MUST render in one unambiguous format across every screen.
- **FR-025**: An absent value MUST be visibly distinguishable from a recorded zero or empty
  string.
- **FR-026**: Every table row MUST offer one consistent, discoverable way to open the record it
  represents.
- **FR-027**: Long text values MUST truncate or wrap predictably within their column or card
  without breaking the layout, and the full value MUST remain obtainable.
- **FR-028**: The Deals pipeline MUST present as a board of one column per stage, each column
  showing its label, its deal count, and the total value of its deals.
- **FR-029**: The pipeline board MUST be a reading surface only — opening a Deal MUST be the sole
  path to changing its stage. The board MUST NOT offer any control that writes a stage change.
- **FR-030**: A pipeline column holding more deals than fit the viewport MUST remain usable
  without changing the widths of the other columns.
- **FR-031**: Detail screens MUST share one pattern for presenting a record's field/value pairs
  and its related-record lists.

**States**

- **FR-032**: Every list MUST show a purposeful empty state naming what belongs there and
  offering the action that creates the first record.
- **FR-033**: An empty state caused by the acting user's ownership scope MUST say so, rather than
  implying the whole system is empty.
- **FR-034**: Forms MUST show validation messages beside the offending field, preserve the user's
  other input, and move focus to the first offending field.
- **FR-035**: Forms MUST indicate in-flight submission and MUST prevent a second submission while
  one is in flight.
- **FR-036**: Required and optional fields MUST be distinguished consistently on every form.
- **FR-037**: The interface MUST explain, in plain English, which dependent records block each of
  the four blocked deletions (Project with Units, Unit with Deals, Contact with Deals, Company
  with Contacts) rather than failing silently or surfacing a raw error page.
- **FR-038**: Where a screen already carries the data showing an action is blocked, the interface
  MUST prevent that action up front — presenting the control as unavailable with its reason
  stated — rather than letting the user attempt it and fail.
- **FR-039**: Where a blocked or failed action can only be detected once attempted, its outcome
  MUST be reported in the shell's system-message region in plain English.
- **FR-040**: Destructive actions MUST require explicit confirmation before proceeding.
- **FR-041**: Permission-denied, not-found, and expired-session responses MUST render as styled
  in-application messages offering a route back, not unstyled default error pages.
- **FR-042**: The Deal form MUST present the already-sold Unit condition (established in feature
  002) using the shared status vocabulary and message patterns defined here — both the Unit's
  availability badge **and** the bespoke "already sold" banner and disabled submission that
  feature 002 built must be replaced with the shared status indicator and the shared
  blocked-action treatment, leaving no hand-styled remnant on that form.

**Scope boundaries**

- **FR-043**: This feature MUST NOT add, remove, or change any route, controller action,
  validation rule, authorization policy, or database table — every behaviour it presents MUST be
  driven by data the existing screens already receive, or by presentation-layer data sharing that
  adds no new endpoint.
- **FR-044**: All 13 screens delivered by feature 002, plus the existing home screen, MUST be
  migrated onto this design system, leaving no screen on its previous ad-hoc styling.
- **FR-045**: Every behaviour verified by feature 002's existing automated tests MUST continue to
  pass unchanged after the migration.

### Key Entities

This feature introduces no new persisted data. It establishes shared presentation elements:

- **Design Token Set**: The single named source for colour, typography, spacing, and shape values
  that every screen reads from. Attributes: neutral ground scale, warm primary action colour,
  semantic success/warning/danger/info colours, type scale, spacing scale, radius scale.
- **Application Shell**: The persistent frame around every authenticated screen. Regions: brand
  mark, primary navigation with current-section marking, signed-in identity and role, sign-out
  control, system-message region, page header, content region.
- **Status Indicator**: The single element rendering all seven Deal stage and Unit availability
  values, carrying each value's human label, its semantic treatment, and its kind (stage vs
  availability).
- **Table Pattern**: The shared treatment for every record list — header, row, alignment rules,
  numeric presentation, row-level entry point, and empty state.
- **Pipeline Board**: The stage-column presentation of Deals, carrying per-column label, count,
  and total value, plus the per-deal card summary.
- **State Patterns**: The shared empty, in-flight, validation-error, confirmation, and
  system-message presentations that any screen can adopt.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: All 13 authenticated screens render inside the shared shell, and the 2
  unauthenticated screens plus the error page render from the same tokens; zero screens define
  their own page chrome.
- **SC-002**: All seven stage and availability values render through the one shared status
  indicator, and zero raw internal values (such as an underscored stage name) appear anywhere in
  the interface.
- **SC-003**: 100% of colour pairings used for text and interactive controls pass WCAG 2.1 AA
  contrast when measured.
- **SC-004**: Zero one-off colour values exist outside the token set — verifiable by searching
  the styling layer for literal colour values.
- **SC-005**: The entire palette can be changed by editing only the token set, with the change
  visibly propagating to all 14 screens and no other edits.
- **SC-006**: In every money column, values of 3, 6, and 9 digits align on their decimal
  separator, so the larger value is visibly longer — verifiable by inspection at all three
  magnitudes.
- **SC-007**: Every money value in the application shows exactly two decimal places, and the
  currency is named exactly once per column or field rather than per cell — verifiable by
  checking that amounts of 1,250,000.00 and 1,250,000.50 render distinguishably on every screen
  that displays them.
- **SC-008**: 100% of record lists show a purposeful empty state; zero lists render as a bare
  blank area.
- **SC-009**: All four blocked-deletion cases explain which dependent records block them; zero
  produce a silent failure or an unstyled error page.
- **SC-010**: A user landing on any screen can identify who they are signed in as, in what role,
  and which section they are in — all without scrolling.
- **SC-011**: A person unfamiliar with the codebase can assemble a new screen using only the
  shared shell, table, status, and state patterns, without authoring new visual styling.
- **SC-012**: Every screen is fully operable by keyboard, with a visible focus indicator on 100%
  of interactive controls.
- **SC-013**: Feature 002's full automated test suite (68 tests at time of writing) continues to
  pass with zero regressions.
- **SC-014**: No route, controller action, validation rule, policy, or database table is added,
  removed, or altered — verifiable by comparing the route list and schema before and after.

## Assumptions

**Resolved clarifications** (decided by the requester on 2026-09-02, now binding):

- **Pipeline board is read-only.** Neither drag-and-drop nor an on-card advance control is in
  scope; opening a Deal is the only path to changing its stage (FR-029). This keeps the feature
  purely presentational and puts no write path at risk.
- **Feedback works both ways.** Where a screen already holds the data proving an action is
  blocked, the control is presented as unavailable with its reason stated (FR-038); where a block
  or failure is only detectable on attempt, the outcome is reported in the shell's message region
  (FR-039). This makes sharing the signed-in identity and action-outcome messages through the
  existing Inertia shared-props mechanism a required part of the feature.
- **Money is Egyptian Pounds (EGP)**, with the currency named once per column header or field
  label rather than repeated per cell (FR-023), and always shown to exactly two decimal places
  (FR-022). Two decimals are mandatory, not cosmetic: `price`, `full_price`, and
  `deposit_amount` are all stored to two decimal places and Deal validation admits any amount
  from 0.01 upward, so truncating the decimals would render distinct amounts identically and
  break the decimal alignment FR-006 requires.

**User-provided technical constraints** (dictated in the feature request, recorded here rather
than as requirements so the requirements above stay behavioural):

- The token set lives in `resources/css/app.css` under Tailwind v4's `@theme` block; Tailwind v4
  is already the project's styling tool and no new styling dependency is introduced.
- The shell is delivered as the shared `AppLayout` Inertia layout component; the status indicator
  is delivered as a single `StatusBadge` component covering all seven values, replacing the
  present pair of overlapping `StageBadge` and `StatusBadge` components.
- No new routes, controllers, or migrations. Sharing presentation-level data (the signed-in
  user's name and role, and action-outcome messages) through the existing Inertia shared-props
  mechanism is treated as within scope, since it introduces no new endpoint — this is required
  because the signed-in identity and the message region have no data source today.

**Scope boundaries**

- Dark mode is out of scope; the palette targets a single light appearance.
- Right-to-left layout and Arabic localisation are out of scope — the request specifies LTR
  English. Nothing in the design should make a future RTL pass harder than necessary, but no RTL
  work is delivered here.
- Desktop is the primary target, with graceful behaviour down to tablet width. A dedicated
  phone-optimised layout is out of scope.
- No new screen, field, filter, sort, search, or report is added — this feature restyles and
  restructures what feature 002 already delivers.
- Drag-and-drop or any other write control on the pipeline board is out of scope — see the
  resolved clarification above.
- Modern evergreen browsers only; no legacy browser support work.
- Accessibility target is WCAG 2.1 AA, not AAA. A full assistive-technology audit is out of
  scope; the deliverable is contrast compliance, keyboard operability, visible focus, and
  meaning not carried by colour alone.

**Environment and data**

- The seven status values are exactly those established by feature 002: Deal stages `lead`,
  `reserved`, `contracted_won`, `lost` and Unit availability `available`, `reserved`, `sold`.
  This feature presents them; it never changes the set or how a value is derived.
- The existing screens already receive the record data they display; no new data needs to be
  loaded for tables and badges. The per-stage totals on the pipeline board are computed from the
  deals each column already receives.
- Unit availability remains derived server-side (never user-editable), so the status indicator is
  read-only wherever availability appears.
- EGP is a display convention only. No currency field, conversion, or multi-currency support is
  added; every stored amount is already understood to be in one currency.
- Unit **area** is treated the same way as money for alignment purposes — equal-width digits, two
  decimal places (it is stored to two), and its unit of measure named once in the column header
  rather than in every cell. This extends the requester's money decision to the one other decimal
  column in the application for consistency; it was inferred, not requested.
- Feature 002's automated tests assert on rendered page names and props rather than on markup,
  so restyling should not require test changes; any test that does break indicates a behavioural
  change that must be reverted rather than accommodated.
