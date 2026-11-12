<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Application;
use App\Models\Vacancy;

class ApplicationController
{
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx'];
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    private const MAX_SIZE_BYTES = 2 * 1024 * 1024; // 2MB

    private function storagePath(): string
    {
        $path = __DIR__ . '/../../storage/resumes';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    // Applicant: show the application form for a vacancy (UC-01).
    public function showApply(): void
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

    // Applicant: submit the application (UC-01, FR3, FR4, NFR2).
    public function store(): void
    {
        Auth::requireRole('applicant');
        $vacancyId = (int) ($_POST['vacancy_id'] ?? 0);
        $coverLetter = trim($_POST['cover_letter'] ?? '');
        $vacancy = Vacancy::find($vacancyId);

        if ($vacancy === null || $vacancy['status'] !== 'open') {
            http_response_code(404);
            echo '<h1>404</h1><p>Vacancy not available.</p>';
            return;
        }

        // FR4 / UNIQUE constraint: one active application per applicant per vacancy.
        if (Application::existsActiveForVacancyAndApplicant($vacancyId, Auth::id())) {
            $_SESSION['errors'] = ['You have already applied to this vacancy.'];
            header('Location: index.php?route=applications.apply&vacancy_id=' . $vacancyId);
            exit;
        }

        $resumePath = $this->handleResumeUpload($vacancyId);
        if ($resumePath === null) {
            // Error already stashed in session by handleResumeUpload().
            header('Location: index.php?route=applications.apply&vacancy_id=' . $vacancyId);
            exit;
        }

        Application::create($vacancyId, Auth::id(), $resumePath, $coverLetter);
        header('Location: index.php?route=applications.mine');
        exit;
    }

    // NFR2: validated by extension, MIME type, and size; stored under a generated
    // filename outside the public web directory.
    private function handleResumeUpload(int $vacancyId): ?string
    {
        if (!isset($_FILES['resume']) || $_FILES['resume']['error'] === UPLOAD_ERR_NO_FILE) {
            $_SESSION['errors'] = ['Please attach your CV.'];
            return null;
        }

        $file = $_FILES['resume'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['errors'] = ['The file failed to upload. Please try again.'];
            return null;
        }

        if ($file['size'] > self::MAX_SIZE_BYTES) {
            $_SESSION['errors'] = ['The CV file must be 2MB or smaller.'];
            return null;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $_SESSION['errors'] = ['Only PDF, DOC, or DOCX files are accepted.'];
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            $_SESSION['errors'] = ['The uploaded file does not look like a valid PDF/DOC/DOCX document.'];
            return null;
        }

        // Generated filename: never trust the original name supplied by the browser.
        $generatedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $this->storagePath() . '/' . $generatedName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $_SESSION['errors'] = ['Could not save the uploaded file. Please try again.'];
            return null;
        }

        return $generatedName;
    }

    // Applicant: landing spot after login — the Applicant-side counterpart to
    // ApplicationController::hrDashboard(), same app shell, different content.
    public function applicantDashboard(): void
    {
        Auth::requireRole('applicant');
        $applications = Application::forApplicant(Auth::id());

        $active = array_filter($applications, fn($a) => !in_array($a['status'], Application::FINAL_STATUSES, true));
        $offers = array_filter($applications, fn($a) => $a['status'] === 'Offered');
        $awaitingDecision = array_filter($applications, fn($a) => $a['status'] === 'DecisionPending');

        $stats = [
            'open_vacancies' => count(Vacancy::allOpen()),
            'my_applications' => count($applications),
            'active' => count($active),
            'awaiting_decision' => count($awaitingDecision),
            'offers' => count($offers),
        ];

        // Most recent 5, for a quick preview (full list + history lives on "My Applications").
        $recent = array_slice($applications, 0, 5);

        require __DIR__ . '/../Views/applications/dashboard.php';
    }

    // Applicant: view own applications + status history (UC-03).
    public function mine(): void
    {
        Auth::requireRole('applicant');
        $applications = Application::forApplicant(Auth::id());
        foreach ($applications as &$application) {
            $application['history'] = Application::historyFor($application['id']);
        }
        unset($application);
        require __DIR__ . '/../Views/applications/mine.php';
    }

    // Applicant: withdraw before a decision (UC-05).
    public function withdraw(): void
    {
        Auth::requireRole('applicant');
        $id = (int) ($_POST['id'] ?? 0);
        Application::withdraw($id, Auth::id());
        header('Location: index.php?route=applications.mine');
        exit;
    }

    // HR: advance an application through the controlled state machine (UC-02).
    public function advance(): void
    {
        Auth::requireRole('hr');
        $id = (int) ($_POST['id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';
        $note = trim($_POST['note'] ?? '') ?: null;

        $ok = Application::changeStatus($id, $newStatus, Auth::id(), $note);

        if (!$ok) {
            $_SESSION['errors'] = ['That status change is not a valid transition from the current stage.'];
        }

        header('Location: index.php?route=hr.dashboard');
        exit;
    }

    // Authorised download: HR can view any CV; an applicant can view only their own.
    // Never served as a direct static link — always through this controller action.
    public function downloadResume(): void
    {
        Auth::requireLogin();
        $id = (int) ($_GET['id'] ?? 0);
        $application = Application::findById($id);

        if ($application === null) {
            http_response_code(404);
            echo '<h1>404</h1><p>Application not found.</p>';
            return;
        }

        $isOwner = Auth::isApplicant() && (int) $application['applicant_id'] === Auth::id();
        if (!Auth::isHr() && !$isOwner) {
            http_response_code(403);
            echo '<h1>403 Forbidden</h1>';
            return;
        }

        $path = $this->storagePath() . '/' . $application['resume_path'];
        if (!is_file($path)) {
            http_response_code(404);
            echo '<h1>404</h1><p>File not found.</p>';
            return;
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="resume-application-' . $id . '.' . pathinfo($path, PATHINFO_EXTENSION) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    // HR dashboard: pipeline + summary stats (FR12-adjacent live view).
    public function hrDashboard(): void
    {
        Auth::requireRole('hr');
        $applications = Application::forHr();
        $stats = Application::summaryStats();
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);
        require __DIR__ . '/../Views/hr/dashboard.php';
    }
}
