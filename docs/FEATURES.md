# Features Reference

## Public (No Login Required)

| Feature | File | Description |
|---------|------|-------------|
| Landing Page | `index.php` | Hero section, feature highlights, links to register/login |
| Course Listing | `public/courses.php` | Lists all active NSQF courses with duration & eligibility |
| Notice Board | `public/notices.php` | Displays published PDF notices from Admin |
| Contact Form | `public/contact.php` | Submits messages to `contact_messages` table |
| TP Registration | `tp/tp_signup.php` | New center self-registration (status defaults to `pending`) |

---

## Admin Portal

Login → `login.php` → redirects to `admin/admin_dashboard.php`

| Feature | File | Description |
|---------|------|-------------|
| Dashboard | `admin_dashboard.php` | Stats overview: TPs, students, courses, notices. Pending TP alert banner |
| Manage TPs | `admin_manage_tp.php` | Approve / deactivate Training Partner accounts |
| Manage Courses | `admin_courses.php` | Add, edit, toggle status, delete NSQF courses. View infrastructure requirements |
| Student Reports | `admin_student_reports.php` | View all students across all centers, filter by course, export CSV |
| Placement Records | `admin_placements.php` | View all placements logged by TPs, delete records, export CSV |
| Activity Feed | `admin_activities.php` | Monitor event photos uploaded by TPs, delete posts |
| Upload Notice | `admin_upload_notice.php` | Publish PDF circulars (max 5MB), manage published notices |
| Helpdesk Control | `admin_helpdesk_upload.php` | Publish YouTube/Drive tutorial links for TPs |

---

## Training Partner (TP) Portal

Login → `login.php` → redirects to `tp/tp_dashboard.php`

> Account must be `active` (approved by Admin) to log in.

| Feature | File | Description |
|---------|------|-------------|
| Dashboard | `tp_dashboard.php` | Welcome panel, quick action links, announcement ticker |
| Profile | `tp_profile.php` | View and update center profile details |
| Courses | `tp_courses.php` | Browse active NSQF courses, create and manage batches |
| Student Data | `tp_students_data.php` | View all enrolled students for the center |
| Upload Students | `tp_upload_students.php` | Bulk upload students via CSV, assign to course & batch |
| Activities | `tp_activities.php` | Upload event/campus photos with title and description |
| Placements | `tp_placements.php` | Log student job placements (company, role, package, date) |
| Notices | `tp_notices.php` | View PDF notices published by Admin |
| Helpdesk | `tp_helpdesk.php` | Watch tutorial videos published by Admin |

---

## Authentication & Security

| Mechanism | Detail |
|-----------|--------|
| Password hashing | `password_hash()` with `PASSWORD_DEFAULT` (bcrypt) |
| Session management | `session_start()` in `includes/auth.php` |
| Role enforcement | `checkRole('admin')` / `checkRole('tp')` on every protected page |
| Cross-role redirect | Accessing wrong role's page redirects to own dashboard |
| DB credentials | Stored in `.env`, never hardcoded |
| File uploads | PDF only, max 5MB, files renamed with timestamp + random suffix |

---

## File Upload Details

| Upload Type | Folder | Allowed Types | Max Size |
|-------------|--------|---------------|----------|
| Notice PDFs | `uploads/notices/` | `.pdf` only | 5 MB |
| Activity Images | `uploads/activities/` (set in TP code) | Image files | — |
