# Test Strategy

## Required layers

1. **Static checks:** PHP syntax, coding standards, configuration and secret scanning.
2. **Unit tests:** Slugs, URLs, validation, sanitization, permissions, and reusable helpers.
3. **Integration tests:** Database queries, migrations, authentication, publishing, comments, menus, and media metadata.
4. **HTTP tests:** Installer, login/logout, CSRF, authorization, CRUD, uploads, clean URLs, errors, and security headers.
5. **Browser tests:** Editor, media library, menus, responsive administration, keyboard workflows, and public pages.
6. **Upgrade tests:** Clean install and upgrades from every supported release using representative content.
7. **Non-functional tests:** Security, accessibility, performance, backup restoration, and failure recovery.

## Release gates

- All automated checks pass from a clean environment.
- No open critical or high-severity defects.
- Upgrade and rollback/recovery procedures are verified.
- No secrets or runtime content are present in the repository or release archive.
- Installation, administration, publishing, backup, and restoration documentation matches observed behavior.

## Evidence

Every release records the environment, commands, test results, known limitations, migration result, and final commit/tag.
