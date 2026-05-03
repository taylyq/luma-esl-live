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

1. For immediate local demo mode, create the SQLite demo database:

```bash
php database/init_sqlite.php
```

2. Start the PHP server:

```bash
php -S localhost:8080 -t public
```

3. Open `http://localhost:8080`.

Demo password for seeded accounts: `password`

Seeded accounts:

- `student@luma.test`
- `amelia@luma.test`
- `linh@luma.test`
- `marcus@luma.test`
- `admin@luma.test`

## MySQL Setup

To run against MySQL instead of SQLite, change `config.php` `db.driver` to `mysql`, update the credentials, then create the database and tables:

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql
```

The MySQL schema and seed files are kept in `database/schema.sql` and `database/seed.sql`.
