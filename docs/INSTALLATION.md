# Installation

## Recommended workflow

1. Install and test on **XAMPP**.
2. Move to **cPanel staging**.
3. Import real content.
4. Go live.

## Installer

Open:

- `/install/`

The installer will:

- write a local config file
- create tables from `sql/schema.sql`
- create your admin user/password
- set initial site title/tagline

### Security

After installation:

- delete `/install` OR rename it
- keep `config/installed.lock`
