# BatState-U RGO Ordering System

A PHP + SQLite ordering system for BatState-U RGO with a public landing page, product catalog, and admin/staff login options.

## Features
- Product catalog and cart
- Landing page with featured items
- Checkout and order tracking
- Admin dashboard (full order status control)
- Staff dashboard (limited order processing statuses)
- Local email/password login for XAMPP demos

## Run in XAMPP
1. Place project in `c:/xampp/htdocs/CAPSTONE`.
2. Start Apache from XAMPP Control Panel.
3. Open `http://localhost/CAPSTONE/index.php`.

## Login Setup
Open `app/config.php` and configure:
- `ADMIN_EMAILS`
- `STAFF_EMAILS`
- `LOCAL_LOGIN_USERS`

Login URL:
- `http://localhost/CAPSTONE/admin/login.php`

## Local Demo Login
The login page also includes a local email/password form for quick testing in XAMPP.

Default demo credentials:
- `admin@g.batstate-u.edu.ph` / `Admin123!`
- `staff@g.batstate-u.edu.ph` / `Staff123!`

Update `LOCAL_LOGIN_USERS` in `app/config.php` before deploying beyond local development.

## Notes
- Database schema and seed products are auto-created on first run.
