<?php

// HR only: create, view, edit and delete vacancies (full CRUD),
// including the optional vacancy image.
class VacancyController
{
    // List every vacancy.
    public function manage()
    {
        Auth::requireRole('hr');
        $vacancies = Vacancy::allForHr();
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);
        require __DIR__ . '/../Views/vacancies/manage.php';
    }

    // Show the empty "New Vacancy" form.
    public function showCreate()
    {
        Auth::requireRole('hr');
        $vacancy = null;
        $errors = $_SESSION['errors'] ?? [];
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);
        require __DIR__ . '/../Views/vacancies/form.php';
    }

    // Save a new vacancy.
    public function store()
    {
        Auth::requireRole('hr');

        $data = $this->getFormData();
        $data['status'] = 'open';

        $errors = $this->validate($data);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            header('Location: index.php?route=vacancies.create');
            exit;
        }

        // The image is optional.
        $data['image_path'] = null;
        if ($this->imageWasUploaded()) {
            $error = $this->checkImage($_FILES['image']);
            if ($error !== '') {
                $_SESSION['errors'] = [$error];
                header('Location: index.php?route=vacancies.create');
                exit;
            }
            $data['image_path'] = $this->saveImage($_FILES['image']);
        }

        Vacancy::create($data, Auth::id());
        header('Location: index.php?route=vacancies.manage');
        exit;
    }

    // Show the "Edit Vacancy" form, filled with the current values.
    public function showEdit()
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

    // Save changes to an existing vacancy.
    public function update()
    {
        Auth::requireRole('hr');
        $id = (int) ($_POST['id'] ?? 0);

        $data = $this->getFormData();
        $data['status'] = $_POST['status'] ?? 'open';

        $errors = $this->validate($data);
        if ($data['status'] !== 'open' && $data['status'] !== 'closed') {
            $errors[] = 'Invalid status.';
        }
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: index.php?route=vacancies.edit&id=' . $id);
            exit;
        }

        // No new image chosen = keep the old image (Vacancy::update handles that).
        $data['image_path'] = null;
        if ($this->imageWasUploaded()) {
            $error = $this->checkImage($_FILES['image']);
            if ($error !== '') {
                $_SESSION['errors'] = [$error];
                header('Location: index.php?route=vacancies.edit&id=' . $id);
                exit;
            }
            $data['image_path'] = $this->saveImage($_FILES['image']);
        }

        Vacancy::update($id, $data);
        header('Location: index.php?route=vacancies.manage');
        exit;
    }

    // Delete a vacancy - but not if people have already applied to it.
    public function destroy()
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

    // ---------- Helper functions ----------

    // Read the form fields from $_POST.
    private function getFormData()
    {
        return [
            'title' => trim($_POST['title'] ?? ''),
            'department' => trim($_POST['department'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'requirements' => trim($_POST['requirements'] ?? ''),
        ];
    }

    // Returns a list of error messages (empty list = no errors).
    private function validate($data)
    {
        $errors = [];
        if ($data['title'] === '' || $data['department'] === '' || $data['description'] === '') {
            $errors[] = 'Title, department, and description are required.';
        }
        return $errors;
    }

    // Did HR choose an image file in the form?
    private function imageWasUploaded()
    {
        return !empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE;
    }

    // Checks the uploaded image. Returns an error message, or '' if it is OK.
    private function checkImage($file)
    {
        $maxSize = 2 * 1024 * 1024; // 2MB
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'The image failed to upload. Please try again.';
        }

        if ($file['size'] > $maxSize) {
            return 'The image must be 2MB or smaller.';
        }

        // 1) Check the file extension.
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            return 'Only JPG, PNG, or WEBP images are accepted.';
        }

        // 2) Check the REAL file type from the file's content.
        $realType = mime_content_type($file['tmp_name']);
        if (!in_array($realType, $allowedTypes)) {
            return 'The uploaded file does not look like a valid image.';
        }

        return '';
    }

    // Vacancy images are public (shown on the landing page), so unlike CVs
    // they are saved inside public/. Returns the new file name.
    private function saveImage($file)
    {
        $folder = __DIR__ . '/../../public/assets/uploads/vacancies';
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        // Never use the name the user's browser sent - make our own.
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newName = uniqid('vacancy_') . '.' . $extension;

        move_uploaded_file($file['tmp_name'], $folder . '/' . $newName);
        return $newName;
    }
}
