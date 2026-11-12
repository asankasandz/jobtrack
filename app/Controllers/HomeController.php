<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Vacancy;

class HomeController
{
    // Public landing page. HR is sent straight to their dashboard since the
    // public vacancy listing has nothing for them to do; Applicants and guests
    // see the same page, just with a different nav (login/signup vs. account links).
    public function index(): void
    {
        if (Auth::isHr()) {
            header('Location: index.php?route=hr.dashboard');
            exit;
        }

        $vacancies = Vacancy::allOpen();
        require __DIR__ . '/../Views/home.php';
    }
}
