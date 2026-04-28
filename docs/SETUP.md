# Setup & Installation Guide

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 7.2 or higher |
| MySQL / MariaDB | 5.7+ / 10.4+ |
| Apache | 2.4+ (with `mod_rewrite`) |
| phpMyAdmin | Optional, for DB management |

---

## Step 1 — Clone / Upload the Project

Upload all project files to your server's web root (e.g., `public_html/` on shared hosting or `/var/www/html/` on VPS).

---

## Step 2 — Create the Database

1. Open **phpMyAdmin** (or your MySQL client)
2. Create a new database — e.g., `u664913565_tp`
3. Select the database and click **Import**
4. Upload the file: `u664913565_tp.sql`
5. Click **Go** — all tables and seed data will be created

---

## Step 3 — Configure Environment

Copy the example env file and fill in your values:

```bash
cp .env.example .env
```

Edit `.env`:

```env
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password
BASE_URL=https://yourdomain.com
```

> `.env` is read by `includes/config.php` using a custom `loadEnv()` parser. Never commit real credentials to version control.

---

## Step 4 — Folder Permissions

The uploads folder must be writable by the web server:

```bash
chmod -R 775 uploads/
```

If it doesn't exist yet, create it:

```bash
mkdir -p uploads/notices
chmod -R 775 uploads/
```

---

## Step 5 — Apache Configuration

Ensure `mod_rewrite` is enabled:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

The `.htaccess` file in the project root handles URL routing and security headers.

---

## Step 6 — Access the Portal

| URL | Page |
|-----|------|
| `http://yourdomain.com/` | Public landing page |
| `http://yourdomain.com/login.php` | Admin & TP login |
| `http://yourdomain.com/tp/tp_signup.php` | New TP registration |

---

## Default Admin Credentials

The SQL dump includes a pre-seeded admin account:

| Field | Value |
|-------|-------|
| Email | `admin@nielitbhubaneswar.in` |
| Password | `Admin@2025` |
| Role | `admin` |

> **Change this password immediately after first login.**

---

## Shared Hosting (Hostinger / cPanel) Notes

- Set the document root to the project folder
- Use the **File Manager** or FTP to upload files
- Use **phpMyAdmin** in the hosting panel to import the SQL file
- The `.env` file must be in the project root (same level as `index.php`)
- Ensure PHP version is set to **7.2+** in the hosting panel

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Blank page / 500 error | Check PHP error logs; verify `.env` values |
| "DB Connection Failed" | Confirm DB credentials in `.env` |
| File upload not working | Check `uploads/notices/` folder exists and is writable |
| Login redirects to wrong page | Clear browser cookies/session |
| `.env` not found error | Ensure `.env` is in the project root, not inside a subfolder |
