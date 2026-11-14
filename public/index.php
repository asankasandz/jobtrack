<?php

// ======================================================================
//  index.php - the ONE entry point for the whole application.
//  Every page is index.php?route=something, e.g. index.php?route=login
//
//  1. Load all the class files
//  2. Start the session (remembers who is logged in)
//  3. Look at "route" in the URL and call the matching controller method
// ======================================================================

// ---- 1. Load the classes ----
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Auth.php';

require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Vacancy.php';
require_once __DIR__ . '/../app/Models/Application.php';
require_once __DIR__ . '/../app/Models/ApplicationStageHistory.php';
require_once __DIR__ . '/../app/Models/Report.php';

require_once __DIR__ . '/../app/Controllers/HomeController.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/VacancyController.php';
require_once __DIR__ . '/../app/Controllers/ApplicationController.php';
require_once __DIR__ . '/../app/Controllers/AdminController.php';

// ---- 2. Start the session ----
Auth::start();

// ---- 3. Routing ----
$home = new HomeController();
$auth = new AuthController();
$vacancies = new VacancyController();
$applications = new ApplicationController();
$admin = new AdminController();

$route = $_GET['route'] ?? 'home';

// POST = a form was submitted (something will be saved or changed).
// GET  = just show a page.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Every form must carry the secret CSRF token (see Auth::csrfField()),
    // otherwise a form posted from another website could act on the user's behalf.
    Auth::checkCsrf();

    switch ($route) {
        // Authentication
        case 'login':                  $auth->login(); break;
        case 'signup':                 $auth->signup(); break;

        // Vacancies (HR)
        case 'vacancies.store':        $vacancies->store(); break;
        case 'vacancies.update':       $vacancies->update(); break;
        case 'vacancies.delete':       $vacancies->destroy(); break;

        // Applications
        case 'applications.store':     $applications->store(); break;
        case 'applications.withdraw':  $applications->withdraw(); break;
        case 'applications.advance':   $applications->advance(); break;

        // Super Admin: user management
        case 'admin.users.store':      $admin->store(); break;
        case 'admin.users.update':     $admin->update(); break;
        case 'admin.users.toggle':     $admin->toggle(); break;
        case 'admin.users.delete':     $admin->destroy(); break;
        case 'admin.users.approve':    $admin->approve(); break;
        case 'admin.users.reject':     $admin->reject(); break;

        default:                       pageNotFound($route);
    }

} else {

    switch ($route) {
        // Public landing page
        case 'home':                   $home->index(); break;

        // Authentication
        case 'login':                  $auth->showLogin(); break;
        case 'signup':                 $auth->showSignup(); break;
        case 'logout':                 $auth->logout(); break;

        // Vacancies (HR)
        case 'vacancies.manage':       $vacancies->manage(); break;
        case 'vacancies.create':       $vacancies->showCreate(); break;
        case 'vacancies.edit':         $vacancies->showEdit(); break;

        // Applications
        case 'applications.apply':     $applications->showApply(); break;
        case 'applications.mine':      $applications->mine(); break;
        case 'applications.resume':    $applications->downloadResume(); break;

        // Dashboards (one for each role)
        case 'hr.dashboard':           $applications->hrDashboard(); break;
        case 'applicant.dashboard':    $applications->applicantDashboard(); break;

        // Super Admin
        case 'admin.dashboard':        $admin->dashboard(); break;
        case 'admin.users':            $admin->users(); break;
        case 'admin.users.create':     $admin->showCreate(); break;
        case 'admin.users.edit':       $admin->showEdit(); break;
        case 'admin.reports':          $admin->reports(); break;
        case 'admin.reports.csv':      $admin->reportsCsv(); break;

        default:                       pageNotFound($route);
    }

}

// Shown when the route in the URL does not exist.
function pageNotFound($route)
{
    http_response_code(404);
    echo '<h1>404 Not Found</h1>';
    echo '<p>The page "' . htmlspecialchars($route) . '" does not exist.</p>';
    echo '<p><a href="index.php">Return home</a></p>';
}
