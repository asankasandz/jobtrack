<?php

// The public landing page (guests and applicants).
class HomeController
{
    public function index()
    {
        // HR and the Super Admin have nothing to do on the public page - send them to their dashboard.
        if (Auth::isStaff()) {
            header('Location: index.php?route=' . (Auth::isAdmin() ? 'admin.dashboard' : 'hr.dashboard'));
            exit;
        }

        $vacancies = Vacancy::allOpen();
        require __DIR__ . '/../Views/home.php';
    }
}
