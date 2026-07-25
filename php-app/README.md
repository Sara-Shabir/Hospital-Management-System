# HMS — PHP / MySQL Frontend

A standalone PHP frontend for the Hospital Management System that talks
**directly to a MySQL database** (it does not call the Node/Express API in
`../src`; that backend and this one are independent implementations of the
same lab-manual spec).

## Requirements

- PHP 8+ with the `pdo_mysql` extension
- MySQL 8+ (or MariaDB)
- Any local server stack: XAMPP, WAMP, Laragon, MAMP, or PHP's built-in server

## Setup

### 1. Create the database

```bash
mysql -u root -p -e "CREATE DATABASE hospital_management_system"
mysql -u root -p hospital_management_system < schema.sql
mysql -u root -p hospital_management_system < seed.sql   # optional demo data
```

### 2. Configure the DB connection

Edit `config/db.php` and set your MySQL host/username/password:

```php
$DB_HOST = 'localhost';
$DB_NAME = 'hospital_management_system';
$DB_USER = 'root';
$DB_PASS = '';
```

### 3. Run it

**Option A — PHP's built-in server (quickest for testing):**
```bash
cd php-app
php -S localhost:8000
```
Then open `http://localhost:8000`.

**Option B — XAMPP/WAMP/Laragon:**
Copy the `php-app` folder into your server's web root (e.g. `htdocs/hms-php`)
and open `http://localhost/hms-php`.

> The app uses root-relative links (`/login.php`, `/assets/style.css`, etc.),
> so it expects to be served from the **web root**, not a subfolder — with
> PHP's built-in server this is automatic; with XAMPP/WAMP, set the site's
> document root to the `php-app` folder itself (e.g. a virtual host) rather
> than nesting it under another folder.

### Demo accounts (from `seed.sql`)

All demo accounts use the password `password123`.

| Role | Email |
|---|---|
| Admin | admin@hms.test |
| Receptionist | receptionist@hms.test |
| Nurse | nurse@hms.test |
| Doctor | doctor@hms.test |
| Lab Technician | labtech@hms.test |
| Pharmacist | pharmacist@hms.test |
| Billing Accountant | billing@hms.test |
| Patient | patient@hms.test |

## Structure

```
php-app/
├── config/db.php          PDO connection (edit your credentials here)
├── schema.sql              Database schema
├── seed.sql                 Demo accounts + sample inventory
├── includes/
│   ├── auth.php             Session login guard, RBAC, idle auto-logout, audit log
│   ├── header.php / footer.php / sidebar.php
├── assets/style.css
├── login.php / logout.php / index.php   Feature 1: Authentication
├── receptionist/            Feature 2
├── nurse/                   Feature 3
├── doctor/                  Feature 4
├── lab/                     Feature 5
├── pharmacist/              Feature 6
├── billing/                 Feature 7
├── patient/                 Feature 8: Patient Portal (read-only)
└── admin/                   Feature 9: System Administrator
```

## Notes on security practices used

- Passwords hashed with `password_hash()` / verified with `password_verify()` (bcrypt)
- All queries use PDO **prepared statements** — no raw string concatenation of user input into SQL
- Every authenticated page re-validates the session and refreshes `last_activity_at`; idle sessions past 10 minutes are force-logged-out (`includes/auth.php`)
- Role checks (`require_role`) gate every page, mirroring the RBAC rules from the Node API
