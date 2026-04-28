# Database Migrations

Each file represents one incremental change to the database schema or seed data.  
Run them **in order** on a fresh database, or use the full dump in `database/u664913565_tp.sql` for a one-shot setup.

## Order

| File | Description |
|------|-------------|
| `001_create_users_table.sql` | Users (admin & TP accounts) |
| `002_create_courses_table.sql` | NSQF courses |
| `003_create_students_table.sql` | Student records |
| `004_create_tp_batches_table.sql` | TP batches |
| `005_create_activities_table.sql` | Campus activity posts |
| `006_create_placements_table.sql` | Job placement records |
| `007_create_notices_table.sql` | Admin notices |
| `008_create_helpdesk_videos_table.sql` | Helpdesk tutorial videos |
| `009_create_contact_messages_table.sql` | Public contact form messages |
| `010_seed_courses.sql` | Seed 42 NSQF courses |
| `011_seed_admin_user.sql` | Seed default admin account |

## Running via PHP Runner (recommended)

From the project root:

```bash
php database/migrate.php
```

Or visit in browser:

```
http://localhost/database/migrate.php
```

The runner will:
- Auto-create a `migrations` tracking table on first run
- Skip files that have already been executed
- Run only new/pending migrations in order
- Show a summary of what ran and what was skipped

## Running via MySQL CLI (manual)

```bash
mysql -u your_user -p your_database < 001_create_users_table.sql
mysql -u your_user -p your_database < 002_create_courses_table.sql
# ... continue in order
```

## Running via phpMyAdmin (manual)

1. Open your database in phpMyAdmin
2. Click **Import**
3. Upload each file one by one in numbered order
