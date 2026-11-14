<?php

// Tests the Super Admin features at model level: creating users with a role,
// editing, deactivating, the "cannot delete a user with records" rule, and the
// stage-by-stage report. Test rows are deleted at the end.

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Vacancy.php';
require_once __DIR__ . '/../app/Models/Application.php';
require_once __DIR__ . '/../app/Models/ApplicationStageHistory.php';
require_once __DIR__ . '/../app/Models/Report.php';

function check($label, $cond) {
    echo ($cond ? '[PASS] ' : '[FAIL] ') . $label . "\n";
}

$db = Database::connection();
$stamp = time();
$hrId = null;
$applicantId = null;
$vacancyId = null;
$applicationId = null;

try {
    // --- create / role ---
    $hrId = User::create('Admin Test HR', "admintest_hr_$stamp@example.com", 'Password123!', 'hr');
    $hr = User::findById($hrId);
    check('User::create() makes an HR user', $hr['role'] === 'hr');
    check('New users are active by default', $hr['is_active'] == 1);

    $adminTestId = User::create('Admin Test Admin', "admintest_admin_$stamp@example.com", 'Password123!', 'admin');
    check('User::create() can make a Super Admin', User::findById($adminTestId)['role'] === 'admin');
    User::delete($adminTestId);

    // --- edit ---
    User::update($hrId, 'Admin Test HR (edited)', "admintest_hr2_$stamp@example.com", 'hr', '');
    $hr = User::findById($hrId);
    check('User::update() changes name and email', $hr['name'] === 'Admin Test HR (edited)' && $hr['email'] === "admintest_hr2_$stamp@example.com");
    check('Empty new password keeps the old password', password_verify('Password123!', $hr['password_hash']));

    User::update($hrId, $hr['name'], $hr['email'], 'hr', 'NewPassword456!');
    check('A new password replaces the old one', password_verify('NewPassword456!', User::findById($hrId)['password_hash']));

    check('emailTakenByOther() ignores the user themselves', User::emailTakenByOther($hr['email'], $hrId) === false);
    check('emailTakenByOther() spots another user\'s email', User::emailTakenByOther($hr['email'], $hrId + 1000000) === true);

    // --- deactivate ---
    User::setActive($hrId, 0);
    check('setActive(0) deactivates the user', User::findById($hrId)['is_active'] == 0);
    User::setActive($hrId, 1);
    check('setActive(1) re-activates the user', User::findById($hrId)['is_active'] == 1);

    // --- list + counts ---
    $hrList = User::all('hr');
    check('User::all(\'hr\') lists the HR user', in_array($hrId, array_column($hrList, 'id')));
    check('User::all() never returns password hashes', !isset($hrList[0]['password_hash']));
    check('countByRole() has all three roles', isset(User::countByRole()['admin'], User::countByRole()['hr'], User::countByRole()['applicant']));

    // --- delete rules ---
    check('A user with no records has no records', User::hasRecords($hrId) === false);

    $vacancyId = Vacancy::create([
        'title' => 'Admin Smoke Vacancy', 'department' => 'QA',
        'description' => 'Temporary.', 'requirements' => '',
    ], $hrId);
    check('A user who created a vacancy now has records (cannot be deleted)', User::hasRecords($hrId) === true);

    $applicantId = User::create('Admin Test Applicant', "admintest_app_$stamp@example.com", 'Password123!', 'applicant');
    $applicationId = Application::create($vacancyId, $applicantId, 'fake.pdf', 'Cover.');
    check('An applicant with an application has records', User::hasRecords($applicantId) === true);

    // --- registration approval ---
    $pendingId = User::create('Admin Test Pending', "admintest_pend_$stamp@example.com", 'Password123!', 'applicant', "ADM-$stamp", 0);
    $pendingId2 = User::create('Admin Test Pending Two', "admintest_pend2_$stamp@example.com", 'Password123!', 'applicant', "ADM2-$stamp", 0);
    check('A registration is saved as pending (is_approved = 0)', User::findById($pendingId)['is_approved'] == 0);
    check('Employee ID is stored', User::findById($pendingId)['employee_id'] === "ADM-$stamp");
    check('pending() lists the registration', in_array($pendingId, array_column(User::pending(), 'id')));
    check('countPending() counts at least our 2 registrations', User::countPending() >= 2);
    check('Pending users are NOT in the normal user list', !in_array($pendingId, array_column(User::all(''), 'id')));
    check('Pending users are NOT counted in countByRole()', array_sum(User::countByRole()) === (int) $db->query('SELECT COUNT(*) FROM users WHERE is_approved = 1')->fetchColumn());
    check('employeeIdTaken() spots a used Employee ID', User::employeeIdTaken("ADM-$stamp") === true);
    check('employeeIdTaken() ignores the user themselves', User::employeeIdTaken("ADM-$stamp", $pendingId) === false);
    check('An unused Employee ID is free', User::employeeIdTaken("NOPE-$stamp") === false);
    try {
        User::create('Dup', "admintest_dup_$stamp@example.com", 'Password123!', 'applicant', "ADM-$stamp", 0);
        check('The database refuses a duplicate Employee ID', false);
    } catch (Exception $dup) {
        check('The database refuses a duplicate Employee ID', true);
    }
    User::approve($pendingId);
    check('approve() lets the user in (is_approved = 1)', User::findById($pendingId)['is_approved'] == 1);
    check('An approved user appears in the normal list', in_array($pendingId, array_column(User::all(''), 'id')));
    User::rejectPending($pendingId);
    check('rejectPending() never deletes an APPROVED user', User::findById($pendingId) !== null);
    User::rejectPending($pendingId2);
    check('rejectPending() deletes a pending registration', User::findById($pendingId2) === null);
    User::delete($pendingId);

    // --- report ---
    Application::changeStatus($applicationId, 'Screening', $hrId, 'Interview on 20 Nov at 10am, online (Teams link).');
    $rows = Report::stageReport(['vacancy_id' => $vacancyId]);
    check('Report returns the 2 history rows of the test vacancy', count($rows) === 2);
    check('Report rows carry applicant name, email and HR note',
        $rows[0]['applicant_name'] === 'Admin Test Applicant'
        && $rows[0]['applicant_email'] === "admintest_app_$stamp@example.com"
        && strpos($rows[0]['note'], 'online (Teams link)') !== false);
    check('Report filter "passed" returns the Applied -> Screening move', count(Report::stageReport(['vacancy_id' => $vacancyId, 'result' => 'passed'])) === 1);
    check('Report filter "failed" returns nothing yet', count(Report::stageReport(['vacancy_id' => $vacancyId, 'result' => 'failed'])) === 0);

    check('outcome(): first row is "Applied"', Report::outcome(null, 'Applied') === 'Applied');
    check('outcome(): moving on is "Passed"', Report::outcome('Screening', 'InterviewScheduled') === 'Passed');
    check('outcome(): Rejected is "Failed"', Report::outcome('DecisionPending', 'Rejected') === 'Failed');
    check('outcome(): Withdrawn is "Withdrawn"', Report::outcome('Applied', 'Withdrawn') === 'Withdrawn');

    // --- dashboard numbers ---
    $perDay = Report::applicationsPerDay(30);
    check('applicationsPerDay(30) returns 30 labelled days', count($perDay['labels']) === 30 && count($perDay['values']) === 30);
    check('applicationsPerDay() counted the test application today', end($perDay['values']) >= 1);
    $byStatus = Report::applicationsByStatus();
    check('applicationsByStatus() lists all 8 stages', count($byStatus['labels']) === 8);
    $stats = Report::dashboardStats();
    check('dashboardStats() has the user and application totals', $stats['total_users'] > 0 && $stats['total_applications'] >= 1);
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
} finally {
    // delete in foreign-key order
    if ($applicationId) {
        $db->prepare('DELETE FROM application_stage_history WHERE application_id = :id')->execute(['id' => $applicationId]);
        $db->prepare('DELETE FROM applications WHERE id = :id')->execute(['id' => $applicationId]);
    }
    if ($vacancyId) {
        $db->prepare('DELETE FROM vacancies WHERE id = :id')->execute(['id' => $vacancyId]);
    }
    if ($applicantId) {
        User::delete($applicantId);
    }
    if ($hrId) {
        User::delete($hrId);
    }
    echo "\nCleanup done - no test data left behind.\n";
}
