# JobTrack — Internal Job Application Tracking System

MIT4301 Independent Study project. Plain PHP (hand-written MVC, no framework), MySQL via PDO.

- **Applicants** sign up, browse vacancies, apply with a CV, track every stage and withdraw.
- **HR staff** manage vacancies and move applications through a controlled pipeline:
  `Applied → Screening → InterviewScheduled → InterviewCompleted → DecisionPending → Offered | Rejected`
  (`Withdrawn` is reachable from any non-final stage).

## Run locally (XAMPP)

1. Put the project in `C:\xampp\htdocs\interview-project` and start **Apache** and **MySQL**.
2. Create a database called `interview_project` in phpMyAdmin and import `database/schema.sql`.
3. Copy `config/database.example.php` to `config/database.php` and adjust credentials if needed.
4. Create the HR account and the Super Admin account (the last word is the role):
   `C:\xampp\php\php.exe database\seed.php hr@example.com "YourStrongPassword" "HR Name" hr`
   `C:\xampp\php\php.exe database\seed.php admin@example.com "YourStrongPassword" "Super Admin" admin`
5. Open `http://localhost/interview-project/public/index.php`

## Tests

```
C:\xampp\php\php.exe database\smoke_test.php
C:\xampp\php\php.exe database\smoke_test_image.php
```

Both are self-cleaning and safe to re-run.

## Structure

```
public/      front controller (index.php) + CSS + public vacancy images
app/Core     Database (PDO), Router, Auth
app/Models   User, Vacancy, Application, ApplicationStageHistory
app/Controllers, app/Views
database/    schema.sql, seed, migrations, smoke tests
storage/     private CV uploads (denied by .htaccess, served only via an authorised controller)
```
