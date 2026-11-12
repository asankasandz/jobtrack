<?php

// Hand-written autoloader: maps the App\ namespace to the app/ directory,
// so there is no framework or Composer dependency doing this invisibly.
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/../app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

use App\Core\Auth;
use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\VacancyController;
use App\Controllers\ApplicationController;

Auth::start();

$home = new HomeController();
$auth = new AuthController();
$vacancies = new VacancyController();
$applications = new ApplicationController();

$router = new Router();

// --- Public landing page (guest or applicant; HR is redirected to their dashboard) ---
$router->get('home', [$home, 'index']);

// --- Authentication ---
$router->get('login', [$auth, 'showLogin']);
$router->post('login', [$auth, 'login']);
$router->get('signup', [$auth, 'showSignup']);
$router->post('signup', [$auth, 'signup']);
$router->get('logout', [$auth, 'logout']);

// --- Vacancies (HR CRUD; applicant browsing now lives on the public home page) ---
$router->get('vacancies.manage', [$vacancies, 'manage']);
$router->get('vacancies.create', [$vacancies, 'showCreate']);
$router->post('vacancies.store', [$vacancies, 'store']);
$router->get('vacancies.edit', [$vacancies, 'showEdit']);
$router->post('vacancies.update', [$vacancies, 'update']);
$router->post('vacancies.delete', [$vacancies, 'destroy']);

// --- Applications ---
$router->get('applications.apply', [$applications, 'showApply']);
$router->post('applications.store', [$applications, 'store']);
$router->get('applications.mine', [$applications, 'mine']);
$router->post('applications.withdraw', [$applications, 'withdraw']);
$router->post('applications.advance', [$applications, 'advance']);
$router->get('applications.resume', [$applications, 'downloadResume']);

// --- Dashboards (post-login landing spots, one per role) ---
$router->get('hr.dashboard', [$applications, 'hrDashboard']);
$router->get('applicant.dashboard', [$applications, 'applicantDashboard']);

$method = $_SERVER['REQUEST_METHOD'];
$route = $_GET['route'] ?? 'home';

$router->dispatch($method, $route);
