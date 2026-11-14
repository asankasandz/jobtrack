<?php

// The index page (hero + open vacancies). Internal system: only for logged-in users.
class HomeController
{
    public function index()
    {
        // Guests go to the login page first.
        if (!Auth::check()) {
            header('Location: index.php?route=login');
            exit;
        }

        // HR and the Super Admin have nothing to do on the public page - send them to their dashboard.
        if (Auth::isStaff()) {
            header('Location: index.php?route=' . (Auth::isAdmin() ? 'admin.dashboard' : 'hr.dashboard'));
            exit;
        }

        $vacancies = Vacancy::allOpen();
        require __DIR__ . '/../Views/home.php';
    }
}
