# Feature Specification: Real Estate Unit Sales CRM

**Feature Branch**: `002-real-estate-sales`

**Created**: 2026-08-25

**Status**: Draft

**Input**: User description: "A CRM system for a real estate developer to manage sales of units within compounds/projects. The developer owns Projects (compounds), each containing multiple Units (apartments, villas, shops) with type, area, price, and status (available, reserved, sold). Contacts are potential buyers only. A Deal links a Contact to a specific Unit, tracking the unit's full price, an optional deposit amount and payment date, and a pipeline stage (Lead, Reserved, Contracted/Won, Lost). Multiple Deals can exist for the same Unit from different interested Contacts simultaneously; a Sales Rep or Admin manually closes out the other Deals once one is finalized. A Contact's Company is optional (some buyers are individuals, not companies). Users have roles: Admin (sees everything) and Sales Rep (sees only their own contacts and deals)."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Sales Rep runs a Unit Deal from Lead to Close (Priority: P1)

A Sales Rep works with a potential buyer (Contact): they pick an available Unit within a Project, open a Deal linking the Contact to that Unit with its full price, and progress the Deal through pipeline stages (Lead → Reserved → Contracted/Won) as the buyer's commitment increases, optionally recording a deposit amount and payment date along the way.

**Why this priority**: This is the core revenue-generating workflow the whole system exists for — turning buyer interest into a closed sale on a specific unit. Without it there is no sales CRM.

**Independent Test**: Can be fully tested by logging in as a Sales Rep, creating a Contact, creating a Deal linking that Contact to an available Unit with a full price, recording an optional deposit, and advancing the Deal to Contracted/Won — delivers a complete, working sale record even with no other reps or competing deals in the system.

**Acceptance Scenarios**:

1. **Given** a logged-in Sales Rep and an available Unit in a Project, **When** they create a Contact and open a new Deal linking that Contact to the Unit with the unit's full price, **Then** the Deal is saved at the "Lead" stage, owned by that Sales Rep, and appears in their pipeline.
2. **Given** a Sales Rep viewing one of their own Deals at "Lead" stage, **When** they record a deposit amount and payment date and advance the Deal to "Reserved", **Then** the Deal's stage, deposit amount, and payment date are saved and reflected immediately in their pipeline view.
3. **Given** a Sales Rep viewing one of their own Deals at "Reserved" stage, **When** they advance it to "Contracted/Won", **Then** the Deal's stage updates and the linked Unit's status becomes "sold".
4. **Given** a Sales Rep has multiple Deals across different stages, **When** they view their pipeline, **Then** Deals are grouped or filterable by stage (Lead, Reserved, Contracted/Won, Lost).
5. **Given** a Contact who is an individual buyer with no Company, **When** a Sales Rep creates that Contact without selecting a Company, **Then** the Contact is saved successfully with no Company association.

---

### User Story 2 - Competing Deals on the same Unit are resolved manually (Priority: P2)

Two or more Contacts independently express interest in the same Unit, so two or more Deals exist for that Unit at the same time, potentially owned by different Sales Reps. Once one of those Deals is finalized (Contracted/Won), a Sales Rep or Admin manually closes out the remaining open Deals on that Unit by marking them Lost.

**Why this priority**: This reflects a real, common scenario in unit sales (multiple buyers chasing the same unit) and protects against overselling a unit, but it only matters once the core single-deal workflow (Story 1) already works.

**Independent Test**: Can be fully tested by creating two Deals from two different Contacts on the same Unit, advancing one to "Contracted/Won," and confirming the Unit shows "sold" while the other Deal remains open until a Sales Rep or Admin manually marks it "Lost."

**Acceptance Scenarios**:

1. **Given** a Unit with no existing Deals, **When** two different Contacts each get a separate Deal opened against that same Unit, **Then** both Deals are saved independently and both remain visible in their respective owners' pipelines.
2. **Given** two open Deals on the same Unit, **When** one Deal is advanced to "Contracted/Won", **Then** the Unit's status becomes "sold" and the other Deal on that Unit is NOT automatically changed.
3. **Given** a Unit whose status is "sold" because one of its Deals reached "Contracted/Won", **When** a Sales Rep or Admin views the other still-open Deal(s) on that Unit, **Then** they can manually change that Deal's stage to "Lost" to close it out.
4. **Given** a Unit that already has a Deal at "Contracted/Won", **When** any user attempts to create a brand-new Deal on that same Unit, **Then** the system prevents it and indicates the Unit is already sold.

---

### User Story 3 - Admin manages Project & Unit inventory and oversees all activity (Priority: P3)

An Admin sets up the sales inventory — creating Projects (compounds) and adding Units to them with type, area, price, and status — and can view and manage all Contacts and Deals across every Sales Rep, including reassigning ownership.

**Why this priority**: Inventory setup and cross-rep oversight are essential for running the business but build on top of the core deal workflow (Story 1) and competing-deal handling (Story 2) already being in place.

**Independent Test**: Can be fully tested by logging in as an Admin, creating a Project, adding several Units to it with distinct types/areas/prices, then confirming the Admin's view of Contacts and Deals includes every Sales Rep's records and that ownership can be reassigned.

**Acceptance Scenarios**:

1. **Given** a logged-in Admin, **When** they create a new Project with a name and add Units to it specifying type (apartment, villa, shop), area, and price, **Then** each Unit is saved with an initial status of "available" under that Project.
2. **Given** multiple Sales Reps each with their own Contacts and Deals, **When** an Admin views the contact list or deal pipeline, **Then** all Contacts and Deals from every rep are visible.
3. **Given** an Admin viewing a Contact owned by one Sales Rep, **When** the Admin reassigns that Contact to a different Sales Rep, **Then** the Contact and all Deals linked to it are now owned by the new Sales Rep and appear in that rep's lists instead of the previous owner's.
4. **Given** an Admin viewing a Project's Unit list, **When** they filter or scan by status, **Then** they can distinguish available, reserved, and sold Units at a glance.

---

### User Story 4 - Data isolation between Sales Reps (Priority: P4)

A Sales Rep sees and can act only on the Contacts and Deals they own; they cannot see, edit, or act on another rep's Contacts or Deals, even when those Deals compete for the same Unit.

**Why this priority**: This is the privacy/access boundary the system depends on once more than one rep is active, but it is only meaningfully testable after Stories 1–2 establish that Deals and competing Deals exist.

**Independent Test**: Can be fully tested by creating two Sales Reps, each with their own Contacts and Deals (including two competing Deals on one Unit owned by different reps), and verifying neither rep can see, list, or close the other's records.

**Acceptance Scenarios**:

1. **Given** two Sales Reps (Rep A and Rep B) each with their own Contacts, **When** Rep A views their contact list, **Then** only Rep A's Contacts are shown, never Rep B's.
2. **Given** two Sales Reps each with their own Deals, **When** Rep A views their pipeline, **Then** only Rep A's Deals are shown, never Rep B's.
3. **Given** Rep A and Rep B have competing Deals on the same Unit, **When** Rep A's Deal is finalized, **Then** Rep A can see that the Unit is sold but MUST NOT be able to directly edit or close Rep B's competing Deal (only Rep B or an Admin can).
4. **Given** Rep A attempts to directly open a Deal or Contact owned by Rep B (e.g., via a direct link), **When** the request is made, **Then** the system denies access.

---

### Edge Cases

- What happens when a Sales Rep tries to create a Deal on a Unit that already has a Deal at "Contracted/Won"? The system MUST prevent it and indicate the Unit is already sold.
- What happens when a Sales Rep tries to create a Deal linked to a Contact they do not own? The system MUST prevent this — a rep may only attach Deals to Contacts they own.
- What happens when a Contact is deleted but has existing Deals linked to it? The system MUST block deletion of a Contact that has one or more linked Deals, until those Deals are reassigned or deleted first.
- What happens when a Unit is deleted but has existing Deals linked to it? The system MUST block deletion of a Unit that has one or more linked Deals, until those Deals are reassigned or deleted first.
- What happens when a Project is deleted but still contains Units? The system MUST block deletion of a Project that has one or more Units, until those Units are removed or reassigned first.
- What happens when a Deal's deposit amount is entered as greater than the Deal's full price? The system MUST reject a deposit amount that exceeds the full price.
- What happens when a Deal's full price or deposit amount is entered as negative? The system MUST reject negative values; a full price of zero is not allowed (a Unit sale must have a positive price), while a deposit of zero or empty (no deposit yet) is allowed.
- What happens when a Deal is advanced directly from "Lead" to "Contracted/Won" or to "Lost", skipping "Reserved"? The system MUST allow any stage-to-stage transition — pipeline stage order is informational, not enforced as a strict sequence.
- What happens when a Deal that already reached "Contracted/Won" is later changed to "Lost" (e.g., a buyer backs out after contracting)? The system MUST allow this reversal and MUST recompute the Unit's status per FR-011 (falling back to "reserved" if another Deal on the Unit is at "Reserved", otherwise "available").
- What happens when a Sales Rep's account is deactivated or removed while they still own Contacts and Deals? Ownership reassignment (via Admin) MUST happen before or as part of deactivation so no records are left ownerless.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST allow authenticated users to create, view, edit, and delete Projects, each with at least a name.
- **FR-002**: System MUST allow authenticated users to create, view, edit, and delete Units within a Project, capturing at minimum a type (apartment, villa, or shop), area, price, and status (available, reserved, sold).
- **FR-003**: System MUST default a newly created Unit's status to "available."
- **FR-004**: System MUST allow authenticated users to create, view, edit, and delete Contacts, capturing at minimum a name and contact details, with an optional association to a Company.
- **FR-005**: System MUST allow authenticated users to create, view, edit, and delete Company records, independent of any specific Contact, and MUST allow a Contact to be saved with no Company at all.
- **FR-006**: System MUST allow authenticated users to create a Deal linking exactly one Contact to exactly one Unit, capturing the unit's full price, an optional deposit amount, an optional deposit payment date, and a pipeline stage.
- **FR-007**: System MUST support the following Deal pipeline stages: Lead, Reserved, Contracted/Won, Lost.
- **FR-008**: System MUST allow a Deal to move between any of the pipeline stages without restriction on order, including reversal from Contracted/Won to Lost.
- **FR-009**: System MUST allow more than one open Deal to exist simultaneously for the same Unit, from different Contacts, without automatically resolving them.
- **FR-010**: System MUST prevent creation of a new Deal for a Unit that already has an existing Deal at the "Contracted/Won" stage.
- **FR-011**: System MUST derive a Unit's status automatically from the current stages of its Deals, and MUST NOT automatically change any of that Unit's Deals when doing so: "sold" if any Deal on the Unit is at "Contracted/Won"; otherwise "reserved" if any Deal on the Unit is at "Reserved"; otherwise "available".
- **FR-012**: System MUST allow a Sales Rep who owns a Deal, or an Admin, to manually change that Deal's stage to "Lost" to close it out (e.g., after a competing Deal on the same Unit has been finalized).
- **FR-013**: System MUST recompute a Unit's status (per the rule in FR-011) whenever any of its Deals' stages changes, including reversals (e.g., a Deal moved back from "Contracted/Won" to "Lost" or "Lead").
- **FR-014**: System MUST support two user roles: Admin and Sales Rep.
- **FR-015**: System MUST restrict a Sales Rep's view of Contacts and Deals to only those they own; a Sales Rep MUST NOT be able to view, edit, or delete another rep's Contacts or Deals.
- **FR-016**: System MUST allow every authenticated user (Admin or Sales Rep) to view all Projects, Units, and Company records regardless of which Sales Rep created them — Projects, Units, and Companies are shared, not owned by a rep.
- **FR-017**: System MUST allow Admin users to view, edit, and delete all Contacts and Deals across all Sales Reps.
- **FR-018**: System MUST allow Admin users to reassign the owner of any Contact to a different Sales Rep, and this reassignment MUST also move ownership of all Deals linked to that Contact to the new Sales Rep.
- **FR-019**: System MUST assign a newly created Contact's owner to the Sales Rep who created it (or, if created by an Admin, the Admin MUST choose the owning Sales Rep at creation time); any Deal linked to that Contact is owned by the same Sales Rep as the Contact and cannot be reassigned independently of it.
- **FR-020**: System MUST prevent a Sales Rep from linking a new Deal to a Contact they do not own.
- **FR-021**: System MUST prevent deletion of a Contact that has one or more linked Deals until those Deals are reassigned or deleted first.
- **FR-022**: System MUST prevent deletion of a Unit that has one or more linked Deals until those Deals are reassigned or deleted first.
- **FR-023**: System MUST prevent deletion of a Project that has one or more Units until those Units are removed or reassigned first.
- **FR-024**: System MUST reject a Deal full price that is zero or negative; a positive value is required.
- **FR-025**: System MUST reject a Deal deposit amount that is negative or that exceeds the Deal's full price; a deposit is optional and may be left unset.
- **FR-026**: System MUST require users to authenticate before accessing any Project, Unit, Contact, Company, or Deal data.

### Key Entities

- **Project**: Represents a compound or development the business is selling units within. Key attributes: name, location/description. Contains one or more Units. Shared across all users, not owned by an individual Sales Rep.
- **Unit**: Represents a single sellable property (apartment, villa, or shop) within a Project. Key attributes: type, area, price, status (available, reserved, sold). Belongs to exactly one Project. Can have zero or more Deals linked to it.
- **Contact**: Represents a potential buyer — an individual or a company's representative. Key attributes: name, contact details. Optionally belongs to one Company. Owned by exactly one Sales Rep. Can have zero or more Deals linked to it.
- **Company**: Represents an organization a Contact may optionally belong to (not all buyers are companies). Shared across all users — not owned by an individual Sales Rep.
- **Deal**: Represents a specific sale opportunity for one Unit with one Contact. Key attributes: full price, optional deposit amount, optional deposit payment date, pipeline stage (Lead, Reserved, Contracted/Won, Lost). Linked to exactly one Contact and exactly one Unit. Owned by the same Sales Rep as its linked Contact.
- **User**: Represents a person who logs into the system. Key attributes: role (Admin or Sales Rep). A Sales Rep owns zero or more Contacts and their Deals; an Admin owns none directly but can view and manage all of them.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A Sales Rep can create a Contact, open a Deal against an available Unit, and advance it to "Contracted/Won" in under 3 minutes total.
- **SC-002**: 100% of Contacts and Deals viewed by a Sales Rep belong to that Sales Rep — zero cross-rep data leakage in any list or detail view.
- **SC-003**: An Admin can view the complete set of Contacts and Deals across all Sales Reps, and the full Unit inventory across all Projects, without needing to switch between per-rep or per-project views.
- **SC-004**: When a Deal on a Unit reaches "Contracted/Won," the Unit's status reflects "sold" immediately, and staff can identify and manually close out any remaining competing Deals on that Unit within 30 seconds.
- **SC-005**: An Admin can reassign ownership of a Contact or Deal to a different Sales Rep in under 30 seconds, with the change immediately reflected in both reps' views.
- **SC-006**: 95% of new Sales Reps can successfully create their first Contact and Deal without assistance, on their first attempt.
- **SC-007**: Staff can determine, for any Project, how many of its Units are available, reserved, or sold at a glance, without cross-referencing separate reports.

## Assumptions

- Sales Reps and Admins are internal employees of the real estate developer; there is no external/buyer-facing login.
- A user has exactly one role (Admin or Sales Rep) at a time; there is no multi-role or role-switching requirement in this scope.
- Deal full price and deposit amounts are recorded in a single currency; multi-currency support is out of scope for this feature.
- A Unit's status is entirely a system-maintained reflection of its Deals' stages (per FR-011), not a field staff set independently of Deal activity: "sold" if any Deal is Contracted/Won, else "reserved" if any Deal is at Reserved, else "available." An "available" or "reserved" Unit may still have zero, one, or several open (non-Lost, non-Won) Deals against it.
- Deal ownership always follows its linked Contact's owner rather than being tracked independently; reassigning a Contact's owner (by an Admin) also moves ownership of that Contact's Deals to the new owner.
- Only a single deposit amount and payment date are tracked per Deal; multi-installment payment plans or full payment schedules are out of scope for this feature.
- Historical tracking of pipeline stage changes (e.g., an audit trail of when a Deal moved from one stage to another) is out of scope for this feature; only the current stage is required.
- Reporting/analytics dashboards beyond basic inventory and pipeline views (e.g., revenue forecasting, sales-velocity charts) are out of scope for this feature.
