<?php

// The public landing page (guests and applicants).
class HomeController
{
    public function index()
    {
        // HR has nothing to do on the public page - send them to their dashboard.
        if (Auth::isHr()) {
            header('Location: index.php?route=hr.dashboard');
            exit;
        }

        $vacancies = Vacancy::allOpen();
        require __DIR__ . '/../Views/home.php';
    }
}
