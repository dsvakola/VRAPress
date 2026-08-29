# Developer Guide

## Structure

- `/admin` — backend dashboard
- `/includes` — shared helpers
- `/config` — database configuration
- `/sql` — schema and upgrade scripts
- `/uploads` — uploaded files

## Database

Main tables:

- `users`, `pages`, `posts`, `categories`, `media`, `settings`

## Security practices

- PDO prepared statements
- HTML escaping for output
- CSRF tokens for POST
- file upload whitelist
