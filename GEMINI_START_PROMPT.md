# Gemini Kickoff Prompt

Copy the text below into Gemini when starting a new maintenance session.

---

You are taking over maintenance of Luma ESL, an adult ESL teacher marketplace built with PHP 8+, PDO, MySQL, SQLite for local development, vanilla JavaScript, and custom CSS. The repository is `https://github.com/taylyq/luma-esl-live`, the production branch is `main`, and Hostinger deploys that branch to `https://lumaesl.alpacatravels.com`.

Before changing anything, read these files in order:

1. `GEMINI_HANDOFF.md`
2. `README.md`
3. `HOSTINGER_DEPLOY.md`
4. `app/bootstrap.php`
5. `app/helpers.php`
6. `app/routes.php`
7. `database/schema.sql`

Inspect `git status` and preserve any existing user changes. Follow the existing controller/view/helper architecture and keep changes tightly scoped.

Production safety requirements:

- Never commit or print `.env`, `luma-esl.env`, database passwords, mail credentials, tokens, or production data.
- The production environment file and runtime uploads live outside `public_html` and outside Git. Do not move them into the repository.
- Never run destructive SQL, reseed production, replace the production upload directory, or push to `main` without explicit owner approval.
- Add schema changes as new migration files and back up the live database first.
- Preserve CSRF checks, output escaping, server-side validation, role authorization, secure password handling, upload validation, and adult/email-verification gates.
- Students cannot message students. Admins can message users and cannot be blocked by ordinary users.

Set up and verify locally with:

```bash
cp .env.example .env
php database/init_sqlite.php
php -S 127.0.0.1:8080 -t public dev-router.php
```

Before presenting a change, run:

```bash
for file in $(rg --files -g '*.php'); do php -l "$file" || exit 1; done
node --check public/assets/js/app.js
git diff --check
```

Test affected public, student, educator, and admin flows at desktop and mobile sizes. Report the files changed, migration/deployment steps, tests run, residual risks, and whether any production action is still required. Do not claim production verification unless you actually performed it.

Current task:

`[Describe the requested change here.]`

---
