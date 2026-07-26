# T Attend — Final Year Project

A full PHP + MySQL implementation of T Attend, built from the original project
report: lecturers create time-limited attendance sessions shared as a link or
QR code; students check in with their index number; submissions are validated
against the registered class list and blocked from duplicating.

This version is **multi-tenant at the Admin level**: anyone can register their
own Admin account, and each Admin gets a completely isolated workspace. An
Admin only ever sees the lecturers they personally added (or who joined using
their workspace code) — never another Admin's lecturers, courses, students,
sessions, or attendance records.

## Requirements
- XAMPP (Apache + MySQL + PHP 8.x) — https://www.apachefriends.org/

## Setup

1. **Copy the project folder**
   Copy the whole `tattend_v2` folder into your XAMPP `htdocs` directory, e.g.
   `C:\xampp\htdocs\tattend_v2` (Windows) or `/Applications/XAMPP/htdocs/tattend_v2` (Mac).

2. **Start Apache and MySQL** from the XAMPP Control Panel.

3. **Create the database**
   - Open http://localhost/phpmyadmin
   - Click **Import**, choose `database/tattend.sql`, and click **Go**.
     (This creates the `tattend` database and all tables, including the
     `lecturers.admin_id` column that enforces tenant isolation.)

4. **Seed demo accounts**
   - Visit **http://localhost/tattend_v2/database/seed_users.php** once in your browser.
   - This creates one demo admin and one demo lecturer under that admin's
     workspace, plus a demo course and 5 sample students. It deletes itself
     after running.

5. **Open the app**
   - Go to **http://localhost/tattend_v2/**

## Demo Logins
| Role      | Username / Email          | Password      |
|-----------|----------------------------|---------------|
| Admin     | `admin`                    | `admin123`    |
| Lecturer  | `lecturer@tattend.com`     | `lecturer123` |

Students don't log in — they just enter their index number on a check-in page.

## Multi-Tenancy: How Isolation Works
- Every **Admin** is its own tenant. Admins can self-register at
  `admin/register.php` (linked from the admin login page) — no approval
  needed, since each new admin simply gets a fresh, empty workspace.
- Every **Lecturer** belongs to exactly one Admin, via a required
  `admin_id` column on the `lecturers` table.
- A lecturer can end up under an Admin in one of two ways:
  1. The **Admin adds them directly** from Manage Lecturer Accounts.
  2. The **Lecturer self-registers** and enters their Admin's **Workspace
     Code** — this is simply the Admin's username/login. If the code
     doesn't match a real admin account, registration is rejected.
- All of the Admin dashboard's counters and the "Manage Lecturers" list are
  filtered with `WHERE admin_id = <current admin>` (or a join down to it for
  students/sessions/attendance), and the enable/disable/delete actions
  re-check `admin_id` before touching a row — so even guessing another
  lecturer's ID in the URL can't affect an admin's data outside their own
  tenant.
- Courses, students, sessions, and attendance records are still scoped by
  `lecturer_id` exactly as before; because each lecturer now has a single
  owning admin, that chain of ownership flows through automatically.

## Database Config
If your MySQL uses a different host, user, or password, edit `config/db.php`.
Defaults match a stock XAMPP install (host `localhost`, user `root`, no password).

## How It Works
- **Admin**: registers their own workspace, logs in separately from
  lecturers, and can add, disable/enable, or delete lecturer accounts —
  scoped entirely to their own tenant.
- **Lecturer**: registers (with a workspace code) or logs in, adds courses,
  adds students individually or via CSV bulk import (`index_number,full_name`
  per line), creates a time-limited session (auto-generates a short code,
  shareable link, and QR code), watches check-ins arrive live, closes the
  session early if needed, and exports attendance to CSV. A simple bar chart
  on the Reports page shows check-ins per session, alongside a per-student
  attendance summary exportable as CSV or print-to-PDF.
- **Student**: opens the shared link or scans the QR code (or types the
  session code manually), enters their index number, and gets immediate
  confirmation of their name and course. The system rejects index numbers not
  on the class list, and rejects a second submission for the same session.

## Project Structure
```
tattend_v2/
├── index.php                 Landing page (role selection)
├── config/db.php              Database connection (PDO)
├── includes/auth.php          Session helpers, tenant helpers, session-code & QR-URL generation
├── includes/header.php, footer.php   (with mobile hamburger nav)
├── assets/css/style.css       Responsive design system (green theme)
├── database/tattend.sql       Schema (lecturers.admin_id enforces tenancy)
├── database/seed_users.php    One-time demo data seeder (self-deletes)
├── lecturer/                  Register (workspace code), login, dashboard, students, sessions, reports
├── student/                   Check-in flow
└── admin/                     Register (new tenant), login, dashboard, lecturer account management
```

## Security Notes
This is a student/demo project. Passwords are hashed with PHP's
`password_hash()`/`password_verify()`, and all queries use PDO prepared
statements, with tenant ownership (`admin_id`) re-checked on every
read/update/delete an Admin performs. For real deployment you would
additionally want HTTPS, rate limiting on the check-in and registration
endpoints, CSRF tokens on forms, and email verification for new admin and
lecturer accounts (see Chapter 5 recommendations in the original report).

