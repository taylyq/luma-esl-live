# Luma ESL Maintainer Handoff

This document is the source of truth for a new AI or human maintainer. Read it before changing code or deploying.

## Project Snapshot

- Product: adult ESL teacher marketplace with teacher discovery, lessons, class scheduling, direct messaging, join requests, and admin moderation
- Live site: `https://lumaesl.alpacatravels.com`
- Repository: `https://github.com/taylyq/luma-esl-live`
- Production branch: `main`
- Hosting: Hostinger Git deployment
- Stack: PHP 8+, PDO, MySQL 8, SQLite for local development, vanilla JavaScript, and custom CSS
- Framework: small custom MVC-style application; no Composer or Node runtime is required

## Non-Negotiable Production Rules

1. Never commit `.env`, `luma-esl.env`, database passwords, SMTP credentials, tokens, or production user data.
2. The production environment file must remain outside `public_html`. The preferred path is `/home/u223591156/luma-esl.env`.
3. Runtime uploads must remain outside the Git deployment directory. Production uses `LUMA_UPLOAD_PATH` for persistent teacher and lesson images.
4. Never place production uploads in `public/uploads`; Git deployment can replace that directory. It contains only tracked placeholders.
5. Never run `database/hostinger_live_demo.sql`, `database/schema.sql`, or destructive SQL against the live database without a verified backup and explicit owner approval.
6. Add production schema changes as new, idempotent migration files. Do not silently rewrite the live schema.
7. Hostinger deploys `main`. Lint and test before pushing. A push can immediately affect the live site.
8. Do not expose raw exceptions, filesystem paths, environment values, password state, or SQL errors in production responses.

## Request Lifecycle

1. Hostinger routes requests through the root `index.php`.
2. Root `index.php` loads `public/index.php`.
3. `public/index.php` loads `app/bootstrap.php`, applies security headers, resolves routes, and invokes a controller action.
4. `app/bootstrap.php` loads environment variables, configuration, autoloading, sessions, and the PDO connection.
5. `app/routes.php` maps GET and POST paths to controllers.
6. Controllers query through the global `db()` helper and render PHP views through `view()`.
7. `app/views/layout.php` supplies the shared navigation, account menu, language selector, theme control, and page shell.

## Important Paths

- `app/Controllers/`: page, authentication, teacher, class, chat, upload, dashboard, and admin behavior
- `app/views/`: server-rendered templates organized by feature
- `app/helpers.php`: environment parsing, auth/role checks, CSRF, escaping, localization, mail, settings, and upload path helpers
- `app/Database.php`: PDO connection creation and driver settings
- `app/routes.php`: route inventory
- `public/assets/css/app.css`: design system and responsive layouts
- `public/assets/js/app.js`: menu, theme, translation, CAPTCHA, lesson navigation, upload progress, and UI interactions
- `database/schema.sql`: canonical fresh MySQL schema
- `database/sqlite_schema.sql`: local SQLite schema
- `database/migrate_*_hostinger.sql`: incremental Hostinger migrations
- `HOSTINGER_DEPLOY.md`: production deployment and persistence procedure

## Environment Loading

`app/bootstrap.php` checks environment files in this order:

1. Path provided by the server variable `LUMA_ENV_PATH`
2. Account-level `luma-esl.env`
3. Domain-level `luma-esl.env`
4. Domain-level `.env`
5. Account-level `.env`
6. Repository `.env` for local development only

Use `.env.example` locally and `.env.hostinger.example` only as a production template. Never insert a real password into either tracked example.

The live environment must set at least:

```dotenv
APP_ENV=production
APP_URL=https://lumaesl.alpacatravels.com
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
LUMA_UPLOAD_PATH=/home/u223591156/domains/lumaesl.alpacatravels.com/luma-esl-uploads
```

## Persistent Media

Teacher and lesson uploads are stored beneath `LUMA_UPLOAD_PATH`, outside `public_html`. The app serves those files through guarded `/uploads/teachers/{filename}` and `/uploads/lessons/{filename}` routes in `UploadController`.

Before changing upload behavior:

- Confirm `LUMA_UPLOAD_PATH` points outside the repository.
- Keep generated filenames and database paths compatible with `UploadController`.
- Preserve MIME validation, file size limits, basename/path traversal protection, and admin-only write access.
- Back up the external upload directory independently of Git.

## Authentication and Authorization

- Roles are `student`, `educator`, and `admin`.
- Registration is adults-only and requires a drag puzzle plus email verification.
- Passwords use PHP password hashing APIs.
- Password reset tokens expire and must remain one-time use.
- Every state-changing route must be POST and pass `verify_csrf()`.
- Use `require_auth()` and role checks in controller actions; hiding a link is not authorization.
- Students must not message other students.
- Admins may message users and cannot be blocked by ordinary users.
- Students and educators may block or report permitted conversation partners.

## Local Setup

```bash
cp .env.example .env
php database/init_sqlite.php
php -S 127.0.0.1:8080 -t public dev-router.php
```

Open `http://127.0.0.1:8080`. Local verification and password-reset messages are written to `storage/mail.log` unless PHP mail is enabled.

Seeded accounts are for local testing only. Do not publish their credentials or keep known seeded passwords on production accounts.

## Required Verification Before Commit

```bash
for file in $(rg --files -g '*.php'); do php -l "$file" || exit 1; done
node --check public/assets/js/app.js
git diff --check
```

Then test these workflows on desktop and mobile widths:

- Public calendar, teacher directory, lessons, terms, privacy, login, and registration
- Student registration, age confirmation, CAPTCHA, email verification, password reset, and password change
- Teacher profile/photo update, class create/edit, messages, join request approval, and history
- Admin educator/student management, bulk teacher photos, lesson CRUD/bulk uploads, reports, and site settings
- Blocking/reporting rules and unread message counts
- Light/dark theme, navigation/account menus, and English/Vietnamese/Spanish selection
- Persistent upload URLs after a simulated redeploy

## Database Change Procedure

1. Back up the live MySQL database.
2. Add a new migration file instead of editing an already-applied migration.
3. Make the migration safe to apply once and document prerequisites.
4. Test against a copy or fresh local MySQL database.
5. Deploy compatible application code and schema in the correct order.
6. Verify critical pages and inspect server logs without exposing them publicly.
7. Record the migration in this document or the deployment guide.

## Known Beta Risks and Recommended Next Work

1. Replace PHP `mail()` with authenticated SMTP or a transactional email provider, including bounce handling and delivery logs.
2. Replace or augment the custom drag puzzle with a managed anti-bot service plus server-side rate limiting. A custom puzzle alone is not strong bot protection.
3. Add automated PHPUnit/integration tests and browser smoke tests. The current verification process is mostly linting and manual flows.
4. Move file-based rate-limit state to a database or Redis before horizontal scaling.
5. Add centralized error monitoring, uptime checks, database backups, upload backups, and a tested restore procedure.
6. Remove or rotate all seeded production accounts and known passwords before an official launch.
7. Review client-side translation for privacy and consistency; a first-party translation catalog is preferable for a mature product.
8. Add a carefully tested Content Security Policy after inventorying all external scripts and image sources.
9. Complete counsel-reviewed marketplace terms, privacy disclosures, moderation policy, teacher classification language, payment/refund terms, and incident response procedures before monetization.

## Design Direction

The current UI uses a calm white/navy/gold identity with restrained cards, compact navigation, responsive page bands, a dark theme, and prominent real task flows. Keep typography readable, controls stable, and mobile layouts free of horizontal overflow. Avoid marketing-only filler, nested cards, oversized headings inside operational pages, and decorative effects that compete with teacher/class content.

## Definition of Done

A change is complete only when it preserves role boundaries, uses CSRF protection, escapes untrusted output, validates uploads and input server-side, includes any needed migration, works in SQLite and MySQL where applicable, passes PHP/JavaScript linting, is tested at mobile and desktop widths, and does not put secrets or runtime media into Git.
