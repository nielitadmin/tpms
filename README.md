# NIELIT TPS — Training Partner Management System

A web-based portal for **NIELIT Bhubaneswar** to manage Training Partner (TP) centers, student records, NSQF courses, placements, notices, and activities.

---

## Project Structure

```
/
├── index.php               ← Public landing page
├── login.php               ← Portal login (Admin & TP)
├── logout.php              ← Session destroy & redirect
├── .env                    ← Environment variables (DB credentials)
├── .htaccess               ← Apache URL/security config
│
├── public/                 ← Public-facing pages (no login required)
│   ├── notices.php         ← Official notice board
│   ├── courses.php         ← Active NSQF course listing
│   └── contact.php         ← Contact form
│
├── includes/               ← Shared backend utilities
│   ├── config.php          ← DB connection (reads .env)
│   └── auth.php            ← Session management & role checks
│
├── admin/                  ← Admin portal (role: admin)
│   ├── admin_dashboard.php
│   ├── admin_manage_tp.php
│   ├── admin_courses.php
│   ├── admin_activities.php
│   ├── admin_placements.php
│   ├── admin_student_reports.php
│   ├── admin_upload_notice.php
│   └── admin_helpdesk_upload.php
│
├── tp/                     ← Training Partner portal (role: tp)
│   ├── tp_dashboard.php
│   ├── tp_signup.php
│   ├── tp_profile.php
│   ├── tp_courses.php
│   ├── tp_students_data.php
│   ├── tp_upload_students.php
│   ├── tp_activities.php
│   ├── tp_placements.php
│   ├── tp_notices.php
│   └── tp_helpdesk.php
│
└── uploads/
    └── notices/            ← Uploaded PDF notices (auto-created)
```

---

## Quick Setup

See [docs/SETUP.md](docs/SETUP.md) for full installation steps.

**TL;DR:**
1. Import `u664913565_tp.sql` into your MySQL database
2. Copy `.env.example` to `.env` and fill in your DB credentials
3. Point your web server root to this project folder
4. Visit `http://localhost/` — default admin login is in [docs/SETUP.md](docs/SETUP.md)

---

## User Roles

| Role | Access | Entry Point |
|------|--------|-------------|
| **Admin** | Full system control | `login.php` → `admin/admin_dashboard.php` |
| **TP (Training Partner)** | Center-specific data | `login.php` → `tp/tp_dashboard.php` |
| **Public** | Notices, courses, contact | `public/` pages |

---

## Tech Stack

- **Backend:** PHP 7.2+ (no framework)
- **Database:** MySQL / MariaDB
- **Frontend:** Bootstrap 5.3, Font Awesome 6.4
- **Server:** Apache (with `.htaccess`)

---

## Docs

| Document | Description |
|----------|-------------|
| [docs/SETUP.md](docs/SETUP.md) | Installation & configuration guide |
| [docs/DATABASE.md](docs/DATABASE.md) | Database schema & table reference |
| [docs/FEATURES.md](docs/FEATURES.md) | Full feature list by role |

---

## License

Internal use — NIELIT Bhubaneswar. All rights reserved.
