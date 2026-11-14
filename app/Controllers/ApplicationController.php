<?php

// Handles everything about job applications:
// applying (with CV upload), viewing, withdrawing, HR status changes,
// CV download, and the two dashboards.
class ApplicationController
{
    // Applicant: show the application form for a vacancy (UC-01).
    public function showApply()
    {
        Auth::requireRole('applicant');
        $vacancyId = (int) ($_GET['vacancy_id'] ?? 0);
        $vacancy = Vacancy::find($vacancyId);

        if ($vacancy === null || $vacancy['status'] !== 'open') {
            http_response_code(404);
            echo '<h1>404</h1><p>Vacancy not available.</p>';
            return;
        }

        if (Application::existsActiveForVacancyAndApplicant($vacancyId, Auth::id())) {
            $errors = ['You have already applied to this vacancy.'];
        } else {
            $errors = $_SESSION['errors'] ?? [];
            unset($_SESSION['errors']);
        }

        require __DIR__ . '/../Views/applications/form.php';
    }

    // Applicant: submit the application (UC-01).
    public function store()
    {
        Auth::requireRole('applicant');
        $vacancyId = (int) ($_POST['vacancy_id'] ?? 0);
        $coverLetter = trim($_POST['cover_letter'] ?? '');
        $vacancy = Vacancy::find($vacancyId);
        $formPage = 'Location: index.php?route=applications.apply&vacancy_id=' . $vacancyId;

        if ($vacancy === null || $vacancy['status'] !== 'open') {
            http_response_code(404);
            echo '<h1>404</h1><p>Vacancy not available.</p>';
            return;
        }

        // Only one application per applicant per vacancy.
        if (Application::existsActiveForVacancyAndApplicant($vacancyId, Auth::id())) {
            $_SESSION['errors'] = ['You have already applied to this vacancy.'];
            header($formPage);
            exit;
        }

        // A CV is required.
        if (empty($_FILES['resume']) || $_FILES['resume']['error'] === UPLOAD_ERR_NO_FILE) {
            $_SESSION['errors'] = ['Please attach your CV.'];
            header($formPage);
            exit;
        }

        $error = $this->checkResume($_FILES['resume']);
        if ($error !== '') {
            $_SESSION['errors'] = [$error];
            header($formPage);
            exit;
        }

        $fileName = $this->saveResume($_FILES['resume']);
        if ($fileName === '') {
            $_SESSION['errors'] = ['Could not save the uploaded file. Please try again.'];
            header($formPage);
            exit;
        }

        Application::create($vacancyId, Auth::id(), $fileName, $coverLetter);
        header('Location: index.php?route=applications.mine');
        exit;
    }

    // Checks the uploaded CV. Returns an error message, or '' if the file is OK.
    private function checkResume($file)
    {
        $maxSize = 2 * 1024 * 1024; // 2MB
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        $allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'The file failed to upload. Please try again.';
        }

        if ($file['size'] > $maxSize) {
            return 'The CV file must be 2MB or smaller.';
        }

        // 1) Check the file extension (.pdf, .doc, .docx).
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            return 'Only PDF, DOC, or DOCX files are accepted.';
        }

        // 2) Check the REAL file type by reading the file's content.
        //    (A renamed virus.exe -> cv.pdf is caught here.)
        $realType = mime_content_type($file['tmp_name']);
        if (!in_array($realType, $allowedTypes)) {
            return 'The uploaded file does not look like a valid PDF/DOC/DOCX document.';
        }

        return '';
    }

    // Saves the CV in storage/resumes (outside the public folder) with a new
    // unique name. Returns the new file name, or '' if saving failed.
    private function saveResume($file)
    {
        $folder = __DIR__ . '/../../storage/resumes';
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        // Never use the name the user's browser sent - make our own.
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newName = uniqid('cv_') . '.' . $extension;

        if (!move_uploaded_file($file['tmp_name'], $folder . '/' . $newName)) {
            return '';
        }
        return $newName;
    }

    // Applicant: view own applications + status history (UC-03).
    public function mine()
    {
        Auth::requireRole('applicant');
        $applications = Application::forApplicant(Auth::id());

        // Add the status history to each application.
        foreach ($applications as $i => $app) {
            $applications[$i]['history'] = Application::historyFor($app['id']);
        }

        require __DIR__ . '/../Views/applications/mine.php';
    }

    // Applicant: withdraw before a decision (UC-05).
    public function withdraw()
    {
        Auth::requireRole('applicant');
        $id = (int) ($_POST['id'] ?? 0);
        Application::withdraw($id, Auth::id());
        header('Location: index.php?route=applications.mine');
        exit;
    }

    // HR: move an application to its next stage (UC-02).
    public function advance()
    {
        Auth::requireStaff();
        $id = (int) ($_POST['id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';
        $note = trim($_POST['note'] ?? '');
        if ($note === '') {
            $note = null;
        }

        $ok = Application::changeStatus($id, $newStatus, Auth::id(), $note);

        if (!$ok) {
            $_SESSION['errors'] = ['That status change is not a valid transition from the current stage.'];
        }

        header('Location: index.php?route=hr.dashboard');
        exit;
    }

    // CV download: HR can download any CV; an applicant only their own.
    // CVs are never linked directly - they can only be downloaded through here.
    public function downloadResume()
    {
        Auth::requireLogin();
        $id = (int) ($_GET['id'] ?? 0);
        $application = Application::findById($id);

        if ($application === null) {
            http_response_code(404);
            echo '<h1>404</h1><p>Application not found.</p>';
            return;
        }

        $isOwner = Auth::isApplicant() && $application['applicant_id'] == Auth::id();
        if (!Auth::isStaff() && !$isOwner) {
            http_response_code(403);
            echo '<h1>403 Forbidden</h1>';
            return;
        }

        $path = __DIR__ . '/../../storage/resumes/' . $application['resume_path'];
        if (!is_file($path)) {
            http_response_code(404);
            echo '<h1>404</h1><p>File not found.</p>';
            return;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="resume-application-' . $id . '.' . $extension . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    // HR dashboard: summary numbers + every application.
    public function hrDashboard()
    {
        Auth::requireStaff();
        $applications = Application::forHr();

        // Add the status history (with notes) to each application.
        foreach ($applications as $i => $app) {
            $applications[$i]['history'] = Application::historyFor($app['id']);
        }

        $stats = Application::summaryStats();
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);
        require __DIR__ . '/../Views/hr/dashboard.php';
    }

    // Applicant dashboard: their own numbers + latest applications.
    public function applicantDashboard()
    {
        Auth::requireRole('applicant');
        $applications = Application::forApplicant(Auth::id());

        // Count the applications in each group.
        $active = 0;
        $awaitingDecision = 0;
        $offers = 0;
        foreach ($applications as $app) {
            if (!in_array($app['status'], Application::finalStatuses())) {
                $active++;
            }
            if ($app['status'] === 'DecisionPending') {
                $awaitingDecision++;
            }
            if ($app['status'] === 'Offered') {
                $offers++;
            }
        }

        $stats = [
            'open_vacancies' => count(Vacancy::allOpen()),
            'my_applications' => count($applications),
            'active' => $active,
            'awaiting_decision' => $awaitingDecision,
            'offers' => $offers,
        ];

        // The latest 5 applications (the full list is on "My Applications").
        $recent = array_slice($applications, 0, 5);

        require __DIR__ . '/../Views/applications/dashboard.php';
    }
}
