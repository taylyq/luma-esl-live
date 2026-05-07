# Hostinger Live Deployment

This repo is prepared to deploy directly into Hostinger's `public_html` folder.

## What Is Included

- Root `index.php` for Hostinger shared hosting
- Root `.htaccess` that routes clean URLs to `public/index.php`
- `public/.htaccess` for setups where the domain root is pointed at `/public`
- Hostinger-safe live demo SQL: `database/hostinger_live_demo.sql`
- One-time upgrade SQL files for existing live databases in `database/migrate_*_hostinger.sql`
- Production environment template: `.env.hostinger.example`

## 1. Prepare Hostinger

1. Open Hostinger hPanel.
2. Go to Websites.
3. Click Manage for your domain.
4. Set PHP version to PHP 8.2 or PHP 8.3.
5. Open File Manager.
6. Make sure `public_html` is empty before Git deployment.

Hostinger's Git deployment expects the install path directory to be empty. Leaving the install path blank deploys into `/public_html`.

## 2. Create The MySQL Database

1. In hPanel, open Databases.
2. Create a MySQL database.
3. Create a database user and password.
4. Assign the user to the database.
5. Save these values:
   - database host, usually `localhost`
   - database name
   - database username
   - database password

## 3. Import Live Demo Data

1. Open phpMyAdmin from Hostinger for the new database.
2. Confirm the database is empty.
3. Click Import.
4. Choose:

```text
database/hostinger_live_demo.sql
```

5. Keep default import settings.
6. Click Go.

Demo login password for every seeded account is:

```text
password
```

Seeded accounts:

- `admin@luma.test`
- `student@luma.test`
- `amelia@luma.test`
- `linh@luma.test`
- `marcus@luma.test`

Change or remove these before a real public launch.

## 4. Deploy From GitHub

1. In hPanel, open Websites.
2. Click Manage for your domain.
3. Search for Git.
4. Create a new repository deployment.
5. Repository URL:

```text
https://github.com/taylyq/luma-esl-live.git
```

6. Branch:

```text
main
```

7. Leave Install Path empty to deploy into `/public_html`.
8. Deploy.

## 5. Add The Live Environment File

After deployment, create this file in Hostinger File Manager:

```text
public_html/.env
```

Use `.env.hostinger.example` as the template:

```env
APP_NAME="Luma ESL"
APP_URL=https://your-domain.com
APP_ENV=production
APP_MAIL_FROM=hello@your-domain.com
APP_USE_PHP_MAIL=true

DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_hostinger_database_name
DB_USERNAME=your_hostinger_database_user
DB_PASSWORD=your_hostinger_database_password
DB_CHARSET=utf8mb4
```

Do not commit `.env`.

## 6. Folder Permissions

Make sure these folders are writable by PHP:

```text
public_html/storage
public_html/public/uploads
public_html/public/uploads/teachers
```

If `public/uploads/teachers` does not exist yet, create it in File Manager.

Recommended permissions:

```text
755 for folders
644 for files
```

If teacher photo uploads fail, set `public/uploads` and `public/uploads/teachers` to `775`.

## 7. Live Test Checklist

Open your domain and test:

1. Homepage loads.
2. `/teachers` loads teacher cards.
3. Login with `student@luma.test` / `password`.
4. Student dashboard shows an approved Zoom class.
5. Open Messages and confirm unread badge clears.
6. Login with `amelia@luma.test` / `password`.
7. Edit teacher profile and upload a JPG/PNG/WebP profile photo.
8. Login with `admin@luma.test` / `password`.
9. Admin dashboard loads educator review queue.
10. Use Forgot Password and confirm email delivery.
11. Register a new test account and confirm verification email delivery.

## 8. Common Fixes

If you see "Database setup needed":

- Check `.env` exists at `public_html/.env`.
- Check `DB_DRIVER=mysql`.
- Check database name, username, password, and host.
- Confirm `database/hostinger_live_demo.sql` was imported.

If CSS or JS do not load:

- Confirm root `.htaccess` exists in `public_html`.
- Confirm the `public/assets` folder deployed.

If routes return 404:

- Confirm Hostinger deployed into `public_html`.
- Confirm `.htaccess` files are present.
- Re-save the Git deployment or redeploy.

If uploads fail:

- Create `public/uploads/teachers`.
- Check folder permissions.
- Keep uploaded photos under 2MB.

## 9. Updating An Existing Live Database

If the site was already live before direct admin/teacher/student messaging was added, deploy the latest Git code and then run this one-time phpMyAdmin import:

```text
database/migrate_direct_messaging_hostinger.sql
```

This migration keeps existing student-teacher chats and upgrades the `chats` table so:

- admins can message students
- admins can message teachers
- teachers and students can message each other
- students cannot message other students

If the site was already live before message blocking/reporting was added, also run this one-time phpMyAdmin import after the direct messaging migration:

```text
database/migrate_message_safety_hostinger.sql
```

This creates the tables used by student/teacher message reports, student/teacher blocks, and the admin report panel. Admin accounts cannot be blocked or reported.
