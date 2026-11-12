<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Vacancy;

class VacancyController
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_SIZE_BYTES = 2 * 1024 * 1024; // 2MB

    // Unlike CVs (private, served only via a controller action), vacancy images
    // are meant to be publicly visible on the landing page, so they're stored
    // inside public/ and linked directly.
    private function uploadsPath(): string
    {
        $path = __DIR__ . '/../../public/assets/uploads/vacancies';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    // HR-facing: every vacancy, with manage actions.
    public function manage(): void
    {
        Auth::requireRole('hr');
        $vacancies = Vacancy::allForHr();
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);
        require __DIR__ . '/../Views/vacancies/manage.php';
    }

    public function showCreate(): void
    {
        Auth::requireRole('hr');
        $vacancy = null;
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);
        require __DIR__ . '/../Views/vacancies/form.php';
    }

    public function store(): void
    {
        Auth::requireRole('hr');
        $data = $this->validate($_POST);

        if ($data === null) {
            header('Location: index.php?route=vacancies.create');
            exit;
        }

        $imagePath = $this->handleImageUpload();
        if ($imagePath === false) {
            header('Location: index.php?route=vacancies.create');
            exit;
        }
        $data['image_path'] = $imagePath;

        Vacancy::create($data, Auth::id());
        header('Location: index.php?route=vacancies.manage');
        exit;
    }

    public function showEdit(): void
    {
        Auth::requireRole('hr');
        $id = (int) ($_GET['id'] ?? 0);
        $vacancy = Vacancy::find($id);

        if ($vacancy === null) {
            http_response_code(404);
            echo '<h1>404</h1><p>Vacancy not found.</p>';
            return;
        }

        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);
        require __DIR__ . '/../Views/vacancies/form.php';
    }

    public function update(): void
    {
        Auth::requireRole('hr');
        $id = (int) ($_POST['id'] ?? 0);
        $data = $this->validate($_POST, true);

        if ($data === null) {
            header('Location: index.php?route=vacancies.edit&id=' . $id);
            exit;
        }

        $imagePath = $this->handleImageUpload();
        if ($imagePath === false) {
            header('Location: index.php?route=vacancies.edit&id=' . $id);
            exit;
        }
        // null (no new file chosen) means "keep the existing image" — Vacancy::update() uses COALESCE for this.
        $data['image_path'] = $imagePath;

        Vacancy::update($id, $data);
        header('Location: index.php?route=vacancies.manage');
        exit;
    }

    public function destroy(): void
    {
        Auth::requireRole('hr');
        $id = (int) ($_POST['id'] ?? 0);

        if (Vacancy::hasApplications($id)) {
            $_SESSION['errors'] = ['This vacancy has applications against it and cannot be deleted. Close it instead.'];
            header('Location: index.php?route=vacancies.manage');
            exit;
        }

        Vacancy::delete($id);
        header('Location: index.php?route=vacancies.manage');
        exit;
    }

    // Returns: string filename on a valid new upload, null when no file was chosen
    // (meaning "no image" on create, or "keep the existing one" on update),
    // or false when a file was chosen but failed validation (errors are stashed in session).
    private function handleImageUpload()
    {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $file = $_FILES['image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['errors'] = ['The image failed to upload. Please try again.'];
            return false;
        }

        if ($file['size'] > self::MAX_SIZE_BYTES) {
            $_SESSION['errors'] = ['The image must be 2MB or smaller.'];
            return false;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $_SESSION['errors'] = ['Only JPG, PNG, or WEBP images are accepted.'];
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            $_SESSION['errors'] = ['The uploaded file does not look like a valid image.'];
            return false;
        }

        // Generated filename: never trust the original name supplied by the browser,
        // same principle as the CV upload in ApplicationController.
        $generatedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $this->uploadsPath() . '/' . $generatedName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $_SESSION['errors'] = ['Could not save the uploaded image. Please try again.'];
            return false;
        }

        return $generatedName;
    }

    private function validate(array $input, bool $withStatus = false): ?array
    {
        $errors = [];
        $title = trim($input['title'] ?? '');
        $department = trim($input['department'] ?? '');
        $description = trim($input['description'] ?? '');
        $requirements = trim($input['requirements'] ?? '');
        $status = $input['status'] ?? 'open';

        if ($title === '' || $department === '' || $description === '') {
            $errors[] = 'Title, department, and description are required.';
        }
        if ($withStatus && !in_array($status, ['open', 'closed'], true)) {
            $errors[] = 'Invalid status.';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $input;
            return null;
        }

        return [
            'title' => $title,
            'department' => $department,
            'description' => $description,
            'requirements' => $requirements,
            'status' => $withStatus ? $status : 'open',
        ];
    }
}
