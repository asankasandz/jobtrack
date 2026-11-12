# Internal Job Application Tracking System — Project Notes

Handoff/reference notes summarizing what's been built, for continuity across sessions.
Last updated: 2026-11-12.

## What this is

MIT4301 Independent Study project. Plain PHP (no framework), hand-written MVC, MySQL via PDO,
running on XAMPP. Two roles: **Applicant** (self-registers) and **HR Staff** (seeded, not public
sign-up — see Security Decisions below).

Built initially for an **interim progress demo** requiring: (1) working login + sign-up, (2) all
finalized UIs navigable, (3) two full CRUD features beyond auth. Chosen CRUD features: **Vacancy**
(full CRUD) and **Application** (Create/Read/Update + controlled Withdraw).

## Running it

- `http://localhost/interview-project/public/index.php` (landing page)
- XAMPP: Apache + MySQL must be running
- Database: `interview_project` (already created in phpMyAdmin, schema imported from `database/schema.sql`)
- **HR login:** credentials are NOT stored in the repo. Check phpMyAdmin's `users` table (role=`hr`)
  for the email; if the password is lost, reset its `password_hash` there.
  `seed.php` now takes credentials as CLI arguments and does nothing if any HR account already exists.
- Applicant accounts: create via the public Sign Up page (self-service, always role=`applicant`).

## Folder structure

```
interview-project/
  public/
    index.php              <- front controller + router dispatch (all requests go through this)
    assets/style.css        <- internal app stylesheet (navy theme) — used by every view except home.php
    assets/landing.css      <- public landing page ONLY (sky-blue theme: --accent #0369a1 / --accent-bright #38bdf8 / --accent-soft #e0f2fe; responsive); loaded by home.php
    assets/uploads/vacancies/  <- PUBLIC vacancy images (created at runtime)
  app/
    Core/
      Database.php           PDO singleton (prepared statements only, EMULATE_PREPARES off)
      Router.php              tiny GET/POST route table + dispatch
      Auth.php                 session helpers; requireRole() is the server-side authorization gate
    Models/
      User.php, Vacancy.php, Application.php, ApplicationStageHistory.php
    Controllers/
      HomeController.php      public landing page
      AuthController.php      login, signup (applicant-only), logout
      VacancyController.php   HR full CRUD + image upload handling
      ApplicationController.php  apply, CV upload, status history, withdraw, HR advance/reject, resume download
    Views/
      home.php (landing), auth/, vacancies/, applications/, hr/dashboard.php
  config/database.php       DB credentials (git-ignored; copy from database.example.php)
  database/
    schema.sql                 source-of-truth DDL (keep in sync with real migrations below)
    migrate_add_vacancy_image.php   one-off migration already applied to the live DB
    seed.php                    creates the HR account: php seed.php <email> <password> ["Name"]
    smoke_test.php               15-check end-to-end test of auth/vacancy/application logic (self-cleaning)
    smoke_test_image.php          4-check test of vacancy image_path create/update/COALESCE behaviour
  storage/
    resumes/                   PRIVATE CV storage, outside public/, .htaccess "Require all denied",
                                only ever served through ApplicationController::downloadResume()
```

## Routes (see `public/index.php`)

| Route | Method | Who | Does |
|---|---|---|---|
| `home` | GET | anyone | landing page; HR auto-redirected to `hr.dashboard`. Applicants are NOT redirected away (they legitimately browse/apply here even while logged in) |
| `login` / `signup` / `logout` | GET/POST | anyone | auth; both support `?next=` to return to an intended page after login. Default post-login landing: `hr.dashboard` for HR, `applicant.dashboard` for Applicant |
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
enforced in code (`Application::VALID_TRANSITIONS`), not just assumed — `smoke_test.php` verifies
an invalid jump (Applied → Offered) is rejected.

## Security decisions worth remembering (will come up in Q&A)

- **HR accounts are seeded, not self-registered** — stops anyone from self-granting HR access
  through the public form. This is a deliberate choice, not a missing feature.
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

- No CSRF tokens on forms (acceptable for an interim demo; would be a real gap for a production app)
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
