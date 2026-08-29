# VRAPress Issue Register

This register tracks confirmed and suspected product risks until GitHub issues become the operational source of truth.

| ID | Severity | Area | Finding | State |
|---|---|---|---|---|
| VRP-001 | Critical | Authentication | Login page advertises a default credential | Confirmed |
| VRP-002 | High | Authentication | No login throttling or temporary lockout | Confirmed |
| VRP-003 | High | Content security | Stored page/post HTML lacks a server-side sanitization policy | Confirmed |
| VRP-004 | High | Installer | Schema installation is not reliably atomic or resumable | Confirmed |
| VRP-005 | High | Configuration | Secure session-cookie and security-header defaults are missing | Confirmed |
| VRP-006 | Medium | Publishing | Future-dated published posts may be publicly visible immediately | Confirmed |
| VRP-007 | Medium | Navigation | Custom menu URLs do not restrict unsafe URI schemes | Confirmed |
| VRP-008 | Medium | Validation | Content status and several submitted fields lack strict allow-list validation | Confirmed |
| VRP-009 | Medium | Settings | Settings-cache invalidation function is ineffective | Confirmed |
| VRP-010 | Medium | Database | Several relationships lack referential-integrity constraints | Confirmed |
| VRP-011 | Low | Session | Logout is a GET action without CSRF protection | Confirmed |
| VRP-012 | High | Quality | No automated functional, security, migration, or browser tests | Confirmed |
| VRP-013 | Critical | Upgrade security | Legacy upgrade scripts forcibly replace the `admin` password hash | Confirmed |
| VRP-014 | High | Import integrity | CSV imports lack strict structure/size validation and transactions, allowing errors or partial imports | Confirmed |
| VRP-015 | Medium | Media lifecycle | Media records and uploaded files have no safe deletion/orphan-management workflow | Confirmed |
| VRP-016 | High | Error handling | Several caught database/runtime errors are displayed directly to administrators | Confirmed |
| VRP-017 | Medium | Release integrity | Upgrade filenames/order do not provide a dependable migration sequence | Confirmed |
| VRP-018 | High | Runtime verification | PHP syntax is verified, but `pdo_mysql`, MariaDB, and database-backed baseline tests are not yet available | Open |

## Workflow

1. Reproduce and document the problem.
2. Define acceptance criteria and regression coverage.
3. Implement on a focused branch.
4. Review security and data-migration impact.
5. Merge only after required checks pass.
