<!--
Sync Impact Report
==================
Version change: [TEMPLATE] → 1.0.0
Rationale: Initial ratification. Project had only the unfilled placeholder scaffold; this is
the first concrete constitution, not an amendment, so it is versioned as a new MAJOR release.

Modified principles: N/A (initial adoption, no prior named principles)

Added sections:
- Core Principles: I. Convention-Driven Laravel Architecture
- Core Principles: II. Inertia/Vue Frontend Consistency
- Core Principles: III. Test-First Development (NON-NEGOTIABLE)
- Core Principles: IV. Code Style & Static Quality
- Core Principles: V. Simplicity & YAGNI
- Data & Security Requirements
- Development Workflow
- Governance

Removed sections: none (all template placeholders replaced)

Deferred / TODO placeholders: none — RATIFICATION_DATE set to today (2026-08-24) since this is
the initial adoption and no earlier ratification date exists.

Templates requiring follow-up review (checked, not modified per scope guard):
- .specify/templates/plan-template.md — verify Constitution Check section references these
  five principles by name once next feature plan is authored. ⚠ pending (not blocking)
- .specify/templates/spec-template.md — no direct constitution references found. ✅
- .specify/templates/tasks-template.md — no direct constitution references found. ✅
- .claude/commands/*.md (speckit-*) — read constitution at runtime; no edits needed. ✅
-->

# my-crm Constitution

## Core Principles

### I. Convention-Driven Laravel Architecture
All application code MUST follow standard Laravel conventions rather than inventing bespoke
patterns: Eloquent models for persistence, Form Request classes for input validation, Policy
classes for authorization, and thin controllers that delegate business logic to models,
actions, or service classes instead of accumulating logic inline. New features MUST reuse
framework-provided mechanisms (migrations, factories, seeders, queued jobs, events/listeners)
before introducing custom infrastructure.
**Rationale**: This is a small Laravel/Inertia skeleton today; consistent use of framework
conventions keeps the codebase approachable as it grows into a full CRM and avoids one-off
patterns that only the original author understands.

### II. Inertia/Vue Frontend Consistency
All user-facing pages MUST be built as Inertia page components following the existing
`resources/js` structure, sharing layouts and shared props (e.g. via
`HandleInertiaRequests`) rather than mixing Blade views and Inertia pages for the same
feature. Server responses to page visits MUST return Inertia responses; JSON-only endpoints
(if needed for polling or webhooks) MUST be clearly separated under an API-style route group.
**Rationale**: Mixing rendering strategies for the same features fragments the frontend and
makes shared layout, auth state, and navigation harder to reason about.

### III. Test-First Development (NON-NEGOTIABLE)
Every new feature or bug fix MUST have an automated test (PHPUnit Feature or Unit test)
written before or alongside the implementation, and the test suite (`composer test` /
`php artisan test`) MUST pass before a change is considered done. Bug fixes MUST include a
regression test that fails before the fix and passes after. Controllers and policies MUST be
covered by Feature tests that exercise real HTTP requests, not only unit tests that bypass
the framework.
**Rationale**: A CRM handles customer-facing data and workflows; regressions here have direct
business impact, and automated tests are the cheapest way to catch them before deployment.

### IV. Code Style & Static Quality
PHP code MUST be formatted with Laravel Pint using the project's default ruleset before commit;
no code with unresolved Pint violations may be merged. Naming MUST follow Laravel norms
(singular StudlyCase models, plural snake_case tables, camelCase methods). JavaScript/Vue code
MUST follow the formatting already configured in the project's tooling (e.g. Vite/ESLint config
if present) rather than ad hoc styles.
**Rationale**: Consistent style removes noise from code review and diffs, letting reviewers
focus on behavior rather than formatting.

### V. Simplicity & YAGNI
Implementations MUST solve the current, stated requirement — no speculative abstractions,
unused configuration options, or framework features "for later." Every new package dependency,
service layer, or design pattern MUST be justified by a concrete need in the feature being
built. Prefer Laravel's built-in solution to a problem over a third-party package unless the
built-in solution is clearly insufficient.
**Rationale**: As a young project, premature abstraction costs more than it saves; it is
easier to add structure later when a real second use case appears than to unwind speculative
design now.

## Data & Security Requirements

All models handling customer/contact data (e.g. `Contact`) MUST use mass-assignment protection
(`$fillable` or `$guarded`) and MUST NOT expose sensitive attributes in API/Inertia responses
without explicit resource/serialization control. Every route that reads or mutates customer
data MUST be behind authentication middleware and MUST be authorized via a Policy or gate check
— controllers MUST NOT assume request ownership without checking it. Secrets and credentials
MUST live in `.env` (never committed) and MUST be read via Laravel's `config()` accessors, not
hardcoded. Database schema changes MUST go through migrations, never manual schema edits, so
history stays reproducible across environments.

## Development Workflow

Every change that alters schema MUST include a migration (and, where relevant, a factory/seeder
update). Before a change is considered ready for review, the author MUST run the PHP test suite
and Pint locally. Pull requests MUST describe what changed and why, and MUST NOT be merged with
failing tests or unresolved style violations. Feature work follows the Spec Kit flow
(`/speckit-specify` → `/speckit-plan` → `/speckit-tasks` → `/speckit-implement`) for any
non-trivial feature so specs, plans, and tasks stay traceable to the code that implements them.

## Governance

This constitution supersedes ad hoc conventions and prior undocumented practices for this
project. Amendments are made by editing `.specify/memory/constitution.md` directly, updating
the version per semantic versioning (MAJOR for incompatible principle removal/redefinition,
MINOR for new or materially expanded principles/sections, PATCH for clarifications), and
recording the change in a Sync Impact Report comment at the top of the file. All pull requests
and code reviews MUST verify compliance with the principles above; any deviation MUST be called
out explicitly in the PR description with a justification. If a `CLAUDE.md` or equivalent
agent-guidance file is added later, it should be kept consistent with this constitution for
day-to-day runtime development guidance.

**Version**: 1.0.0 | **Ratified**: 2026-08-24 | **Last Amended**: 2026-08-24