# Feature Specification: CRM Pipeline Management

**Feature Branch**: `001-crm-pipeline-management`

**Created**: 2026-08-24

**Status**: Draft

**Input**: User description: "A CRM system to manage Contacts, Companies, and Deals. Each Contact has a name, email, phone, and belongs to a Company. Each Deal is linked to a Contact, has a value, and moves through pipeline stages (Lead, Qualified, Proposal, Won, Lost). Users have roles: Admin (sees everything) and Sales Rep (sees only their own contacts and deals)."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Sales Rep manages own contacts and deals (Priority: P1)

A Sales Rep logs in and works their own book of business: adding new contacts, linking them to companies, creating deals for those contacts, and moving deals through pipeline stages as conversations progress.

**Why this priority**: This is the core daily-use workflow the whole system exists for. Without it there is no CRM — it's the minimum slice that delivers value to an individual salesperson.

**Independent Test**: Can be fully tested by logging in as a Sales Rep, creating a Contact under a Company, creating a Deal for that Contact, and advancing the Deal through pipeline stages — delivers a working personal pipeline even with no other roles or reps in the system.

**Acceptance Scenarios**:

1. **Given** a logged-in Sales Rep with no existing contacts, **When** they create a new Contact with name, email, phone, and an existing Company, **Then** the Contact is saved and appears in their contact list.
2. **Given** a Sales Rep viewing one of their own Contacts, **When** they create a new Deal linked to that Contact with a value and an initial stage of "Lead", **Then** the Deal is saved, owned by that Sales Rep, and appears in their pipeline at the "Lead" stage.
3. **Given** a Sales Rep viewing one of their own Deals, **When** they move it from one pipeline stage to the next (e.g., "Qualified" to "Proposal"), **Then** the Deal's stage updates and the change is reflected immediately in their pipeline view.
4. **Given** a Sales Rep has multiple Deals across different stages, **When** they view their pipeline, **Then** Deals are grouped or filterable by stage (Lead, Qualified, Proposal, Won, Lost).

---

### User Story 2 - Data isolation between Sales Reps (Priority: P2)

A Sales Rep only sees the Contacts and Deals they own; they cannot see, edit, or act on another rep's Contacts or Deals.

**Why this priority**: This is the privacy/security boundary the feature depends on. It's independently testable and demonstrable but only matters once there is more than one rep in the system, so it follows the core single-rep workflow.

**Independent Test**: Can be fully tested by creating two Sales Reps, having each create their own Contacts and Deals, and verifying neither can see, list, or open the other's records — delivers verifiable data isolation on its own.

**Acceptance Scenarios**:

1. **Given** two Sales Reps (Rep A and Rep B) each with their own Contacts, **When** Rep A views their contact list, **Then** only Rep A's Contacts are shown, never Rep B's.
2. **Given** two Sales Reps each with their own Deals, **When** Rep A views their pipeline, **Then** only Rep A's Deals are shown, never Rep B's.
3. **Given** a Company shared across multiple reps' Contacts, **When** Rep A views that Company, **Then** Rep A sees all Contacts under that Company regardless of which rep owns each Contact (Company records themselves are shared, not owned).
4. **Given** Rep A attempts to directly open a Deal or Contact owned by Rep B (e.g., via a direct link), **When** the request is made, **Then** the system denies access.

---

### User Story 3 - Admin oversight across all reps (Priority: P3)

An Admin views and manages all Contacts, Companies, and Deals across every Sales Rep, and can reassign ownership of a Contact or Deal to a different Sales Rep.

**Why this priority**: Oversight and reassignment are valuable for managing a team but are not required for an individual rep or the isolation guarantee to already deliver value, so this builds on top of the first two stories.

**Independent Test**: Can be fully tested by logging in as an Admin alongside multiple reps with existing data, confirming the Admin's view includes every rep's Contacts and Deals, and reassigning one Contact's owner to a different rep.

**Acceptance Scenarios**:

1. **Given** multiple Sales Reps each with their own Contacts and Deals, **When** an Admin views the contact list or pipeline, **Then** all Contacts and Deals from every rep are visible.
2. **Given** an Admin viewing a Contact owned by Rep A, **When** the Admin reassigns it to Rep B, **Then** the Contact is now owned by Rep B and appears in Rep B's list instead of Rep A's.
3. **Given** an Admin viewing a Deal owned by Rep A, **When** the Admin reassigns it to Rep B, **Then** the Deal is now owned by Rep B and appears in Rep B's pipeline instead of Rep A's.
4. **Given** an Admin, **When** they create, edit, or delete a Company, **Then** the change applies system-wide for all reps who reference that Company.

---

### Edge Cases

- What happens when a Sales Rep tries to create a Deal linked to a Contact they do not own? The system MUST prevent this — a rep may only attach Deals to Contacts they own.
- What happens when a Contact is deleted but has existing Deals linked to it? The system MUST block deletion of a Contact that has one or more linked Deals, until those Deals are reassigned or deleted first.
- What happens when a Company is deleted but has existing Contacts linked to it? The system MUST block deletion of a Company that has one or more linked Contacts, until those Contacts are reassigned or removed first.
- What happens when a Deal's value is entered as zero or negative? The system MUST reject negative values; zero is allowed (e.g., a placeholder deal with value not yet estimated).
- What happens when a Sales Rep tries to move a Deal directly from "Lead" to "Won" or "Lost", skipping intermediate stages? The system MUST allow any stage-to-stage transition — pipeline stage order is informational, not enforced as a strict sequence.
- What happens when a Sales Rep's account is deactivated or removed while they still own Contacts and Deals? Ownership reassignment (via Admin) MUST happen before or as part of deactivation so no records are left ownerless.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST allow authenticated users to create, view, edit, and delete Contacts, capturing at minimum a name, email, phone number, and an associated Company.
- **FR-002**: System MUST allow authenticated users to create, view, edit, and delete Company records, independent of any specific Contact.
- **FR-003**: System MUST allow authenticated users to create, view, edit, and delete Deals, each linked to exactly one Contact, with a monetary value and a pipeline stage.
- **FR-004**: System MUST support the following Deal pipeline stages: Lead, Qualified, Proposal, Won, Lost.
- **FR-005**: System MUST allow a Deal to move between any of the pipeline stages without restriction on order.
- **FR-006**: System MUST support two user roles: Admin and Sales Rep.
- **FR-007**: System MUST restrict a Sales Rep's view of Contacts and Deals to only those they own; a Sales Rep MUST NOT be able to view, edit, or delete another rep's Contacts or Deals.
- **FR-008**: System MUST allow every authenticated user (Admin or Sales Rep) to view all Company records and every Contact listed under a Company, regardless of which Sales Rep owns each Contact — Companies are shared, not owned by a rep.
- **FR-009**: System MUST allow Admin users to view, edit, and delete all Contacts and Deals across all Sales Reps.
- **FR-010**: System MUST allow Admin users to reassign the owner of any Contact or Deal to a different Sales Rep.
- **FR-011**: System MUST assign a newly created Contact's owner to the Sales Rep who created it (or, if created by an Admin, the Admin MUST choose the owning Sales Rep at creation time).
- **FR-012**: System MUST assign a newly created Deal's owner independently from its linked Contact's owner, defaulting to the Sales Rep who created the Deal, and MUST allow this ownership to differ from the linked Contact's owner.
- **FR-013**: System MUST prevent a Sales Rep from linking a new Deal to a Contact they do not own.
- **FR-014**: System MUST prevent deletion of a Contact that has one or more linked Deals until those Deals are reassigned to another Contact or deleted.
- **FR-015**: System MUST prevent deletion of a Company that has one or more linked Contacts until those Contacts are reassigned to another Company or deleted.
- **FR-016**: System MUST reject a Deal value that is negative; zero and positive values MUST be accepted.
- **FR-017**: System MUST require users to authenticate before accessing any Contact, Company, or Deal data.

### Key Entities

- **Contact**: Represents an individual person the business is engaging with. Key attributes: name, email, phone number. Belongs to exactly one Company. Owned by exactly one Sales Rep. Can have zero or more Deals linked to it.
- **Company**: Represents an organization that one or more Contacts belong to. Shared across all users — not owned by an individual Sales Rep. Can have zero or more Contacts linked to it.
- **Deal**: Represents a potential sale opportunity. Key attributes: monetary value, pipeline stage (Lead, Qualified, Proposal, Won, Lost). Linked to exactly one Contact. Owned by exactly one Sales Rep, which may differ from the linked Contact's owner.
- **User**: Represents a person who logs into the system. Key attributes: role (Admin or Sales Rep). A Sales Rep owns zero or more Contacts and Deals; an Admin owns none directly but can view and manage all of them.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A Sales Rep can create a new Contact and a linked Deal, and move that Deal through all five pipeline stages, in under 2 minutes total.
- **SC-002**: 100% of Contacts and Deals viewed by a Sales Rep belong to that Sales Rep — zero cross-rep data leakage in any list or detail view.
- **SC-003**: An Admin can view the complete set of Contacts and Deals across all Sales Reps on a single screen without needing to switch between per-rep views.
- **SC-004**: An Admin can reassign ownership of a Contact or Deal to a different Sales Rep in under 30 seconds, with the change immediately reflected in both reps' views.
- **SC-005**: 95% of new Sales Reps can successfully create their first Contact and Deal without assistance, on their first attempt.

## Assumptions

- Sales Reps and Admins are internal employees of the business using the CRM; there is no external/customer-facing login.
- A user has exactly one role (Admin or Sales Rep) at a time; there is no multi-role or role-switching requirement in this scope.
- Deal values are recorded in a single currency; multi-currency support is out of scope for this feature.
- Reassigning a Contact's owner does not automatically reassign the owner of that Contact's linked Deals — each is reassigned independently, consistent with Deals having independent ownership (FR-012).
- Historical tracking of pipeline stage changes (e.g., an audit trail of when a Deal moved from one stage to another) is out of scope for this feature; only the current stage is required.
- Reporting/analytics dashboards beyond the basic pipeline view (e.g., revenue forecasting, conversion-rate charts) are out of scope for this feature.
