# Luma ESL

A polished PHP, JavaScript, and MySQL MVP for an ESL teacher marketplace. Students browse verified educators, message them, request class access, and receive Zoom links after teacher approval. Educators manage profiles, classes, conversations, and join requests. Admins approve teachers and monitor the marketplace.

## Stack

- PHP 8+
- MySQL 8+ for production schema
- SQLite demo mode for local preview when MySQL is not running
- Vanilla JavaScript
- CSS design system
- PDO, sessions, CSRF protection, role guards

## Quick Start

1. Create a local environment file:

```bash
cp .env.example .env
```

The default `.env` values use SQLite for fast local preview.

2. For immediate local demo mode, create the SQLite demo database:

```bash
php database/init_sqlite.php
```

3. Start the PHP server:

```bash
php -S localhost:8080 -t public
```

4. Open `http://localhost:8080`.

Demo password for seeded accounts: `password`

Seeded accounts:

- `student@luma.test`
- `amelia@luma.test`
- `linh@luma.test`
- `marcus@luma.test`
- `admin@luma.test`

Local email verification and password reset messages are written to `storage/mail.log` unless `APP_USE_PHP_MAIL=true` is configured.

## Beta Features

- Environment-driven config with `.env`
- Email verification with resend support
- Password reset links with expiring tokens
- Teacher profile photo uploads to `public/uploads/teachers`
- Lesson image uploads to `public/uploads/lessons` so redeploys do not overwrite admin-uploaded images
- Unread message badges in navigation, dashboards, and chat lists
- SQLite demo mode plus MySQL production schema

## MySQL Setup

To run against MySQL instead of SQLite, change `config.php` `db.driver` to `mysql`, update the credentials, then create the database and tables:

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql
```

The MySQL schema and seed files are kept in `database/schema.sql` and `database/seed.sql`.

## Hostinger Live Deployment

This live repo includes Hostinger-ready root routing files and a complete live-demo SQL import.

Use:

- `HOSTINGER_DEPLOY.md` for the step-by-step deployment guide
- `.env.hostinger.example` for live environment settings
- `database/hostinger_live_demo.sql` for phpMyAdmin import
