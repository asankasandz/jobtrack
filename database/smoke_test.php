<?php

// Note: Application::create()/changeStatus() open their own internal transactions,
// so this script does NOT wrap everything in one outer transaction (PDO/MySQL does
// not support nested transactions). Instead, test rows are explicitly deleted at the end.

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Vacancy.php';
require_once __DIR__ . '/../app/Models/Application.php';
require_once __DIR__ . '/../app/Models/ApplicationStageHistory.php';


function check($label, $cond) {
    echo ($cond ? '[PASS] ' : '[FAIL] ') . $label . "\n";
}

$db = Database::connection();
$applicantId = null;
$vacancyId = null;
$applicationId = null;

try {
    $hr = $db->query("SELECT * FROM users WHERE role = 'hr' LIMIT 1")->fetch();
    check('An HR account exists', $hr !== false && $hr['role'] === 'hr');

    $applicantId = User::createApplicant('Smoke Test Applicant', 'smoketest_' . time() . '@example.com', 'Password123!');
    check('Applicant created via createApplicant()', $applicantId > 0);
    $applicant = User::findById($applicantId);
    check('Applicant role is "applicant"', $applicant['role'] === 'applicant');

    $vacancyId = Vacancy::create([
        'title' => 'Smoke Test Vacancy',
        'department' => 'QA',
        'description' => 'Temporary vacancy for smoke testing.',
        'requirements' => 'None.',
    ], $hr['id']);
    check('Vacancy created', $vacancyId > 0);

    $openVacancies = Vacancy::allOpen();
    check('Vacancy appears in allOpen()', in_array($vacancyId, array_column($openVacancies, 'id')));

    $applicationId = Application::create($vacancyId, $applicantId, 'fake_resume.pdf', 'Cover letter text.');
    check('Application created', $applicationId > 0);

    $dup = Application::existsActiveForVacancyAndApplicant($vacancyId, $applicantId);
    check('Duplicate-application check detects the just-created application', $dup === true);

    $invalid = Application::changeStatus($applicationId, 'Offered', $hr['id'], 'skip ahead');
    check('Invalid transition (Applied -> Offered) is rejected', $invalid === false);

    $valid1 = Application::changeStatus($applicationId, 'Screening', $hr['id'], 'looks good');
    check('Valid transition (Applied -> Screening) succeeds', $valid1 === true);

    $app = Application::findById($applicationId);
    check('Status actually updated to Screening', $app['status'] === 'Screening');

    $history = Application::historyFor($applicationId);
    check('History has 2 rows (Applied, then Screening)', count($history) === 2);

    $withdrawn = Application::withdraw($applicationId, $applicantId);
    check('Applicant can withdraw from a non-final stage', $withdrawn === true);

    $app = Application::findById($applicationId);
    check('Status is now Withdrawn', $app['status'] === 'Withdrawn');

    $withdrawAgain = Application::withdraw($applicationId, $applicantId);
    check('Cannot withdraw an already-final application', $withdrawAgain === false);

    $stats = Application::summaryStats();
    check('summaryStats() runs without error', is_array($stats) && isset($stats['total_applications']));
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
} finally {
    if ($applicationId) {
        $db->prepare('DELETE FROM application_stage_history WHERE application_id = :id')->execute(['id' => $applicationId]);
        $db->prepare('DELETE FROM applications WHERE id = :id')->execute(['id' => $applicationId]);
    }
    if ($vacancyId) {
        $db->prepare('DELETE FROM vacancies WHERE id = :id')->execute(['id' => $vacancyId]);
    }
    if ($applicantId) {
        $db->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $applicantId]);
    }
    echo "\nCleanup done — no test data left behind.\n";
}
