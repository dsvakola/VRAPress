# Phase 2 Technical Audit

**Status:** In progress  
**Baseline:** `v1.0.0` (`2feef42`)  
**Audit branch:** `audit/phase-2`

## Scope

Architecture, configuration, installer, database schema and upgrades, routing, authentication, authorization, content handling, publishing, imports, media, menus, comments, errors, documentation, and testability.

## Current architecture

- Server-rendered PHP 8.x application with procedural controllers and shared functions
- PDO MySQL/MariaDB persistence
- Apache rewrite-based public routes
- Session-based single-administrator authentication
- Database-backed pages, posts, categories, menus, comments, settings, and media metadata
- Browser-based content editor with direct media upload

The codebase is small and understandable, which is a strong foundation. However, controllers currently combine request handling, validation, persistence, and rendering; this will limit safe growth unless core services and policies are separated.

## Confirmed critical findings

### A-001 — Upgrade scripts reset administrator credentials

`sql/upgrade_v2.sql` and `sql/upgrade_v3.sql` update the `admin` password hash to embedded known hashes. Running an upgrade can silently replace the site owner's password.

**Required outcome:** Remove credential mutations from migrations; add migration tests proving user credentials remain unchanged.

### A-002 — Default credentials are publicly advertised

The login view displays a default username and password. The base schema also inserts a default administrator credential.

**Required outcome:** Installation must require a user-selected strong password, and no distributable screen or schema may expose a working default credential.

## Confirmed high findings

- No login throttling, temporary lockout, or security-event recording.
- Stored page/post HTML has no defined server-side sanitization policy.
- Installer schema execution is not safely resumable and relies on DDL transaction behavior that MySQL does not guarantee.
- CSV imports lack strict header, row-width, file-size, encoding, date, and transactional validation.
- Secure session-cookie defaults and standard security headers are missing.
- Database/runtime exception messages are sometimes shown directly in the administrator interface.
- No automated syntax, unit, integration, HTTP, browser, migration, security, accessibility, or performance tests exist.

## Confirmed medium findings

- Future-dated posts are selected solely by `status = 'published'` and can appear before their scheduled time.
- Custom menu URLs do not restrict schemes to safe values such as HTTP(S), root-relative paths, mail, or telephone links.
- Submitted statuses and several other fields lack strict centralized allow-list validation.
- Settings cache invalidation is implemented as a no-op.
- Menu items, comments, media ownership, and comment parents lack sufficient foreign-key constraints.
- Migration filenames and contents do not form a clear, monotonic, tracked upgrade sequence.
- Media has no complete deletion, reference detection, or orphan-cleanup workflow.
- Logout is performed through an unprotected GET request.

## Structural product gaps

- Roles and granular capabilities
- Content revisions, autosave, preview tokens, trash, and restoration
- Theme contract and controlled extension hooks
- Migration runner and schema-version ledger
- Backup, restore, export, and disaster-recovery workflows
- Background/scheduled task runner
- Search, canonical URLs, sitemap, feeds, and fuller SEO controls
- Observability, audit log, health checks, and privacy/data-retention controls
- Dependency policy, automated build, release archive, and update verification

## Positive controls already present

- PDO prepared statements are widely used.
- Passwords use PHP password hashing and verification.
- Login regenerates the session identifier.
- Administrator forms generally include CSRF protection.
- Uploads use server-side MIME detection and generated filenames.
- Public plain-text values are generally HTML-escaped.
- Runtime uploads and local configuration are excluded from Git.

## Runtime evidence

- PHP `8.4.24` was installed from the Windows package catalogue.
- All 38 PHP files passed `php -l` syntax verification.
- The installed default module set includes PDO and `mysqlnd` but does not currently load `pdo_mysql`.
- No MySQL/MariaDB executable or service was found in the checked local environment.

Therefore, clean installation, database behavior, database-backed HTTP flows, and full browser workflows remain unverified. Static findings and PHP syntax results are evidence-backed; remaining runtime conclusions will be added only after an isolated database environment is configured.

## Next audit actions

1. Configure `pdo_mysql` and an isolated MariaDB development database.
2. Execute clean-install and repeat-install tests.
3. Seed representative content and exercise every administrator/public workflow.
4. Convert findings into prioritized GitHub issues with acceptance criteria.
5. Freeze the Phase 2 report before Phase 3 repairs begin.
