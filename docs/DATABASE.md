# Database Reference

**Database name:** `u664913565_tp`  
**Engine:** InnoDB  
**Charset:** utf8mb4 / utf8mb4_unicode_ci  
**SQL file:** `u664913565_tp.sql`

---

## Entity Relationship Overview

```
users (admin / tp)
  │
  ├──< activities       (tp_id → users.id)
  ├──< placements       (tp_id → users.id)
  ├──< students         (tp_id → users.id)
  │       └── course_id → courses.id
  └──< tp_batches       (tp_id → users.id)
          └── course_id → courses.id

notices               (published_by → users.id, no FK enforced)
helpdesk_videos       (standalone)
contact_messages      (standalone)
courses               (standalone)
```

---

## Tables

### `users`
Stores both Admin and Training Partner accounts.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int PK AI | Unique user ID |
| `center_id` | varchar(50) UNIQUE | TP center code (e.g., `OD001`) |
| `name` | varchar(100) | Center / admin name |
| `email` | varchar(100) UNIQUE | Login email |
| `phone` | varchar(15) | Contact number |
| `address` | text | Center address (optional) |
| `gps_link` | varchar(255) | Google Maps link (optional) |
| `password` | varchar(255) | Bcrypt hashed password |
| `role` | enum(`admin`, `tp`) | User role |
| `status` | enum(`active`, `inactive`, `pending`) | Account status |
| `created_at` | timestamp | Registration time |

> New TP registrations default to `status = 'pending'` until approved by Admin.

---

### `courses`
NSQF-certified courses managed by Admin.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int PK AI | Course ID |
| `course_name` | varchar(255) | Full course name |
| `duration` | varchar(50) | e.g., `540 Hours` |
| `eligibility` | varchar(255) | NSQF level & credits |
| `carpet_area` | varchar(255) | Min required floor area |
| `system_requirements` | text | PC/hardware specs |
| `faculty_requirements` | text | Trainer qualifications |
| `status` | enum(`active`, `inactive`) | Visibility to TPs |

> 42 courses are pre-seeded in the SQL dump.

---

### `students`
Student records uploaded by Training Partners.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int PK AI | Student ID |
| `tp_id` | int FK | References `users.id` |
| `course_id` | int FK | References `courses.id` |
| `batch_id` | int | References `tp_batches.id` (optional) |
| `student_name` | varchar(100) | Full name |
| `email` | varchar(100) | Student email |
| `phone` | varchar(15) | Student phone |
| `enrollment_date` | date | Date of enrollment |

---

### `tp_batches`
Batches created by TPs for specific courses.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int PK AI | Batch ID |
| `tp_id` | int FK | References `users.id` |
| `course_id` | int FK | References `courses.id` |
| `batch_number` | varchar(100) | Batch identifier/name |
| `batch_timing` | varchar(100) | Timing/schedule info |
| `batch_capacity` | int | Max students (default 50) |
| `deadline_date` | date | Batch end/deadline date |
| `status` | enum(`active`, `completed`) | Batch status |
| `created_at` | timestamp | Creation time |

---

### `activities`
Campus events and photos uploaded by TPs.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int PK AI | Activity ID |
| `tp_id` | int FK | References `users.id` |
| `title` | varchar(255) | Event title |
| `description` | text | Event description |
| `image_path` | varchar(255) | Server path to uploaded image |
| `created_at` | timestamp | Upload time |

---

### `placements`
Job placement records logged by TPs.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int PK AI | Placement ID |
| `tp_id` | int FK | References `users.id` |
| `student_name` | varchar(150) | Placed student name |
| `company_name` | varchar(150) | Hiring company |
| `designation` | varchar(100) | Job role/title |
| `package` | varchar(50) | CTC (optional) |
| `placement_date` | date | Date of placement |
| `created_at` | timestamp | Record creation time |

---

### `notices`
PDF circulars published by Admin.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int PK AI | Notice ID |
| `title` | varchar(255) | Notice heading |
| `description` | text | Optional summary |
| `file_path` | varchar(255) | Relative path to PDF (`uploads/notices/...`) |
| `published_by` | int | Admin user ID |
| `created_at` | timestamp | Publish time |

---

### `helpdesk_videos`
Tutorial video links published by Admin for TPs.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int PK AI | Video ID |
| `title` | varchar(255) | Tutorial title |
| `video_url` | varchar(255) | YouTube or Drive URL |
| `description` | text | Brief description |
| `created_at` | timestamp | Publish time |

---

### `contact_messages`
Messages submitted via the public contact form.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int PK AI | Message ID |
| `name` | varchar(100) | Sender name |
| `email` | varchar(100) | Sender email |
| `subject` | varchar(200) | Message subject |
| `message` | text | Full message body |
| `created_at` | timestamp | Submission time |

---

## Foreign Key Constraints

| Table | Column | References | On Delete |
|-------|--------|------------|-----------|
| `activities` | `tp_id` | `users.id` | CASCADE |
| `placements` | `tp_id` | `users.id` | CASCADE |
| `students` | `tp_id` | `users.id` | CASCADE |
| `students` | `course_id` | `courses.id` | CASCADE |
| `tp_batches` | `tp_id` | `users.id` | CASCADE |
| `tp_batches` | `course_id` | `courses.id` | CASCADE |
