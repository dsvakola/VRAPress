# Architecture Decision Log

## ADR-001 — Independent product direction

- **Status:** Accepted
- **Decision:** VRAPress is an independent CMS product, not a WordPress clone.
- **Reason:** Product decisions must favor clarity, ownership, predictable upgrades, security, and controlled extensibility.

## ADR-002 — Preserve stable releases

- **Status:** Accepted
- **Decision:** Stable releases are tagged; ongoing work uses focused branches and reviewed pull requests.
- **Reason:** This provides recovery points and keeps incomplete work away from release history.

## ADR-003 — Keep secrets and runtime data local

- **Status:** Accepted
- **Decision:** Local configuration, installation locks, generated uploads, logs, caches, and backups are never committed.
- **Reason:** Public source must not expose credentials or site-specific/private data.

## ADR-004 — Database changes require migrations

- **Status:** Accepted
- **Decision:** Every schema change must have an ordered, repeatable migration and an upgrade test.
- **Reason:** Production sites must upgrade without losing content or requiring manual database edits.

## ADR-005 — Security is a release gate

- **Status:** Accepted
- **Decision:** Authentication, authorization, input validation, output encoding, uploads, sessions, and migrations require explicit security review.
- **Reason:** CMS software processes untrusted public and administrator input and protects valuable site data.
