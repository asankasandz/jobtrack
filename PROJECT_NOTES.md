# Internal Job Application Tracking System — Project Notes

Handoff/reference notes summarizing what's been built, for continuity across sessions.
Last updated: 2026-11-12.

## What this is

MIT4301 Independent Study project. Plain PHP (no framework), hand-written MVC, MySQL via PDO,
running on XAMPP. **Internal system — there is NO public registration.** Three roles, all created by the Super Admin (or seeded):
**Employee** (role value `applicant`; logs in and applies for internal vacancies), **HR Staff** and **Super Admin**.

Built initially for an **interim progress demo** requiring: (1) working login (accounts are created by the Super Admin, no public sign-up), (2) all
finalized UIs navigable, (3) two full CRUD features beyond auth. Chosen CRUD features: **Vacancy**
(full CRUD) and **Application** (Create/Read/Update + controlled Withdraw).

## Running it

- `http://localhost/interview-project/public/index.php` (landing page)
- XAMPP: Apache + MySQL must be running
- Database: `interview_project` (already created in phpMyAdmin, schema imported from `database/schema.sql`)
- **HR login:** credentials are NOT stored in the repo. Check phpMyAdmin's `users` table (role=`hr`)
  for the email; if the password is lost, reset its `password_hash` there.
  `seed.php` now takes credentials as CLI arguments and does nothing if any HR account already exists.
- Employee accounts: created by the Super Admin on the Users page (`admin.users.create`). There is no sign-up page.

## Folder structure

```
interview-project/
  public/
    index.php              <- front controller: require_once all classes + two switch statements (GET / POST) pick the controller method
    assets/style.css        <- internal app stylesheet (navy theme) — used by every view except home.php
    assets/landing.css      <- public landing page ONLY (sky-blue theme: --accent #0369a1 / --accent-bright #38bdf8 / --accent-soft #e0f2fe; responsive); loaded by home.php
    assets/uploads/vacancies/  <- PUBLIC vacancy images (created at runtime)
  app/
    Core/
      Database.php           PDO singleton (prepared statements only, EMULATE_PREPARES off)
      Auth.php                 session helpers; requireRole() is the server-side authorization gate
    Models/
      User.php, Vacancy.php, Application.php, ApplicationStageHistory.php, Report.php (read-only dashboard/report queries)
    Controllers/
      HomeController.php      public landing page
      AuthController.php      login, logout (no sign-up: internal system)
      VacancyController.php   HR full CRUD + image upload handling
      ApplicationController.php  apply, CV upload, status history, withdraw, HR advance/reject, resume download
    Views/
      home.php (landing), auth/, vacancies/, applications/, hr/dashboard.php
  config/database.php       DB credentials (git-ignored; copy from database.example.php)
  database/
    schema.sql                 source-of-truth DDL (keep in sync with real migrations below)
    migrate_add_vacancy_image.php   one-off migration already applied to the live DB
    seed.php                    creates the first HR or Super Admin account: php seed.php <email> <password> ["Name"] [hr|admin]
    migrate_add_admin_role.php  adds role 'admin' + users.is_active (idempotent; the deploy workflow runs every migrate_*.php)
    smoke_test_admin.php        28-check test of user management + reports (model level)
    smoke_test.php               15-check end-to-end test of auth/vacancy/application logic (self-cleaning)
    smoke_test_image.php          4-check test of vacancy image_path create/update/keep-old-image behaviour
  storage/
    resumes/                   PRIVATE CV storage, outside public/, .htaccess "Require all denied",
                                only ever served through ApplicationController::downloadResume()
```

## Routes (see `public/index.php`)

| Route | Method | Who | Does |
|---|---|---|---|
| `home` | GET | anyone | landing page; HR auto-redirected to `hr.dashboard`. Applicants are NOT redirected away (they legitimately browse/apply here even while logged in) |
| `login` / `logout` | GET/POST | anyone | auth; login supports `?next=` to return to an intended page after login. Default post-login landing: `hr.dashboard` for HR, `applicant.dashboard` for Applicant |
| `applicant.dashboard` | GET | Applicant | Applicant's own dashboard (counterpart to `hr.dashboard`) — stat cards (open vacancies, my applications, in progress, awaiting decision, offers) + recent applications preview + quick links to browse/My Applications |
| `vacancies.manage` | GET | HR | list all vacancies |
| `vacancies.create` / `.store` | GET/POST | HR | create (+ image upload) |
| `vacancies.edit` / `.update` | GET/POST | HR | edit (image optional — kept if not replaced) |
| `vacancies.delete` | POST | HR | delete (blocked if the vacancy has applications) |
| `applications.apply` / `.store` | GET/POST | Applicant | apply + CV upload |
| `applications.mine` | GET | Applicant | own applications + status history |
| `applications.withdraw` | POST | Applicant | withdraw (only from a non-final stage) |
| `applications.advance` | POST | HR | move to next valid stage (rejects invalid transitions) |
| `applications.resume` | GET | HR, or the owning Applicant | authorised CV download (never a direct file link) |
| `hr.dashboard` | GET | HR | stats + full pipeline table |

## Database (4 tables, matches Chapter 1's ERD/Class Diagram/State Diagram)

`users` (role: applicant/hr) · `vacancies` (+ `image_path`, added via migration) ·
`applications` (status enum = the controlled state machine) · `application_stage_history` (audit trail)

Status values: `Applied → Screening → InterviewScheduled → InterviewCompleted → DecisionPending →
{Offered | Rejected}`, with `Withdrawn` reachable from any non-final state. Valid transitions are
enforced in code (`Application::nextStatuses()`), not just assumed — `smoke_test.php` verifies
an invalid jump (Applied → Offered) is rejected.

## Security decisions worth remembering (will come up in Q&A)

- **No account can be self-registered (internal system).** Only the Super Admin creates accounts (employees, HR, admins) —
  outsiders cannot sign up, and nobody can grant themselves HR or admin access. Deliberate, not a missing feature.
- **Vacancies are only visible after login** — guests see the welcome page and a lock panel (the vacancy cards are not in the page HTML).
- **CVs vs vacancy images are stored differently on purpose**: CVs are private (outside `public/`,
  `.htaccess`-denied, served only via an authorised controller method); vacancy images are public
  marketing content (stored inside `public/assets/uploads/...`, linked directly) — different
  sensitivity, different handling.
- Both upload paths validate **extension + real MIME type (via `finfo`) + size**, and always
  generate a random filename — the original browser-supplied filename is never trusted or used.
- Every state-changing action is gated server-side via `Auth::requireRole()` — not just hidden in
  the UI. `Application::changeStatus()` re-validates the transition is legal even if a request
  reaches the controller directly.

## Known gaps / not yet done

- No rate-limiting on login attempts
- Chapter 2 (UI Design rationale) documents a single consistent colour system "across every
  screen" — the landing page was added afterward with an intentionally different scheme (now
  sky-blue via `assets/landing.css`, originally teal — it's been restyled more than once), so
  that line is now only true of the authenticated app, not the whole system. Worth a short
  clarifying note if Chapter 2 is revisited.
- `public/assets/style.css` still contains an old `.landing-page`-scoped teal CSS block from an
  earlier iteration of the landing page. It's dead code now that `home.php` loads `landing.css`
  instead — harmless, but safe to delete next time that file is touched.

## How to re-verify everything still works

```
C:\xampp\php\php.exe database\smoke_test.php
C:\xampp\php\php.exe database\smoke_test_image.php
```
Both are self-cleaning (roll back/delete their own test rows) and safe to re-run anytime.

## Coding style (deliberately basic PHP — decided 2026-11-13)

Lecturers asked for a basic project, so the code avoids advanced language features while keeping
MVC + security: no namespaces/`use`/autoloader (plain `require_once`), no Router class (switch in
`index.php`), no type declarations, no class constants, no closures/arrow functions. Upload checks are
plain `checkX()` (returns error message or '') + `saveX()` helpers; `mime_content_type()` checks the
real file type; `uniqid()` names files. Keep new code in this same style.

## Super Admin (added 2026-11-14)

- Role `admin` = everything HR can do (HR pages use `Auth::requireStaff()`) PLUS user management and reports.
- Create the first one: `php database/seed.php boss@example.com "StrongPass1" "Super Admin" admin` (the Super Admin then creates everyone else).
- Pages (`AdminController`, views in `app/Views/admin/`, shared sidebar in `app/Views/partials/`):
  `admin.dashboard` (stat cards + Chart.js line chart of the last 30 days + stage bar chart + latest activity),
  `admin.users` / `.create` / `.edit` (add, edit, change role, reset password), `admin.users.toggle` (deactivate/activate),
  `admin.users.delete`, `admin.reports` (stage-by-stage: applicant name+email, vacancy, stage, Passed/Failed/Withdrawn, HR note, decided by; filters; CSV download via `admin.reports.csv`).
- **Deactivate vs delete:** a user with vacancies/applications/history cannot be deleted (foreign keys) — they are deactivated
  (`users.is_active = 0`): cannot log in, and an already-open session is logged out on the next request (`Auth::requireLogin()` re-checks the DB).
  The admin cannot delete/deactivate themselves or change their own role.
- **CSRF:** every POST is checked centrally in `public/index.php` (`Auth::checkCsrf()`); every form includes `Auth::csrfField()`. New forms MUST include it.
- **HR notes:** the note typed when advancing a stage (interview date/time/venue/link) is shown to the applicant (latest note on the card + full history) and to HR/admin (history section, reports).
- CSV export prefixes cells starting with = + - @ with an apostrophe (spreadsheet formula injection).
- Production: the deploy runs `database/migrate_*.php` automatically; the admin account itself is created once by hand on the server with seed.php.

## Internal system decision (2026-11-14)

The lecturer pointed out that a system named *internal* must not let outsiders register. Public sign-up was removed:
`signup` routes, `AuthController::signup()/showSignup()`, `views/auth/signup.php` and `User::createApplicant()` are gone;
the landing page and login page no longer offer registration. Accounts are created on `admin.users.create` by the Super Admin.
UI wording: the `applicant` role is called **Employee** (database value unchanged). Existing self-registered accounts can be
reviewed/deactivated on the Users page. **The report (Chapter 1 use cases / diagrams) must also say "Super Admin creates accounts"
instead of "Applicant registers".**
