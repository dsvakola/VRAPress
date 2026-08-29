# VRAPress

> **Own your publishing platform. Build without unnecessary complexity.**

VRAPress is an independent, lightweight content management system built with PHP and MySQL. Its goal is to provide a secure, dependable, and approachable foundation for creating and maintaining professional websites directly online.

![Version](https://img.shields.io/badge/version-1.1.1-2563eb)
![PHP](https://img.shields.io/badge/PHP-8.x-777bb4)
![Database](https://img.shields.io/badge/database-MySQL%20%7C%20MariaDB-f29111)
![License](https://img.shields.io/badge/license-MIT-16a34a)

## Why VRAPress?

VRAPress is being developed as a focused CMS product—not as a WordPress clone. It favors clear architecture, controlled extensibility, strong security, predictable upgrades, and an uncluttered administration experience.

## Current capabilities

- Pages and blog posts with drafts and publishing controls
- Categories, comments, menus, and media management
- Browser-based content editor
- Clean public URLs and metadata fields
- Web installer for PHP/MySQL hosting
- CSRF protection, prepared database queries, and password hashing
- Import tools and responsive administration interface

## Product direction

The `1.1.1` release continues a structured product-hardening programme covering:

1. Architecture, database, installer, routing, and configuration audits
2. Authentication, authorization, validation, sanitization, and upload security
3. Reliable revisions, previews, scheduling, recovery, and publishing workflows
4. Themes, extensibility hooks, roles, migrations, backups, import/export, and SEO
5. Automated security, migration, browser, performance, and release testing

See [ROADMAP.md](ROADMAP.md) for the complete development path.

## Requirements

- PHP 8.x with PDO MySQL and Fileinfo
- MySQL or MariaDB
- Apache with `mod_rewrite` recommended

## Installation

1. Copy the project to the web root or a subdirectory.
2. Create an empty MySQL/MariaDB database and user.
3. Copy `config/config.sample.php` to `config/config.local.php`, or open `/install/`.
4. Complete the installation wizard.
5. Remove or disable `/install/` after installation.
6. Sign in at `/admin/login.php`.

Full instructions are available in [docs/INSTALLATION.md](docs/INSTALLATION.md).

## Project status

Version `1.1.1` introduces the original Aurora design system across the public website and content editor. A meticulous security, reliability, and product-readiness programme remains underway. Production deployment is not recommended until the documented quality gates are complete.

## Contributing and security

- Read [CONTRIBUTING.md](CONTRIBUTING.md) before proposing changes.
- Report security concerns according to [SECURITY.md](SECURITY.md).
- Never commit `config/config.local.php`, installation locks, credentials, or runtime uploads.

## License

VRAPress is released under the [MIT License](LICENSE).

---

**VRAPress — a focused CMS foundation built for ownership, clarity, and dependable growth.**
