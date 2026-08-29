# Coding Standards

## PHP

- Target PHP 8.x and use strict, readable code with explicit types where practical.
- Use PDO prepared statements for every value originating outside fixed source code.
- Validate input with allow-lists; encode output for its HTML, attribute, URL, JSON, or header context.
- Centralize authentication, authorization, CSRF, validation, sanitization, errors, and redirects.
- Never expose credentials, stack traces, database errors, or filesystem paths to public users.
- Keep controllers small; move reusable domain behavior into tested services/helpers.

## Database

- Use `utf8mb4`, InnoDB, indexes for common queries, and appropriate foreign keys.
- Apply schema changes only through versioned migrations.
- Migrations must be repeatable where possible and safe on populated databases.

## Frontend

- Prefer semantic HTML, keyboard-accessible controls, visible focus states, and responsive layouts.
- Avoid inline styles and scripts in new work unless a documented exception is necessary.
- Do not depend on JavaScript for essential content access.

## Changes

- One focused concern per branch and pull request.
- Update tests, documentation, changelog, and migration notes with behavior changes.
- Never mix secrets, generated files, or unrelated formatting into a product change.
