<?php

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Vacancy.php';
require_once __DIR__ . '/../app/Models/Application.php';
require_once __DIR__ . '/../app/Models/ApplicationStageHistory.php';


function check($label, $cond) {
    echo ($cond ? '[PASS] ' : '[FAIL] ') . $label . "\n";
}

$db = Database::connection();
$vacancyId = null;

try {
    $hr = $db->query("SELECT * FROM users WHERE role = 'hr' LIMIT 1")->fetch();

    $vacancyId = Vacancy::create([
        'title' => 'Image Smoke Test Vacancy',
        'department' => 'QA',
        'description' => 'Temp.',
        'requirements' => '',
        'image_path' => 'fake_image_1.jpg',
    ], $hr['id']);

    $v = Vacancy::find($vacancyId);
    check('image_path saved on create', $v['image_path'] === 'fake_image_1.jpg');

    // Simulate "edited the vacancy but did not choose a new image" -> image_path passed as null
    Vacancy::update($vacancyId, [
        'title' => 'Image Smoke Test Vacancy (edited)',
        'department' => 'QA',
        'description' => 'Temp.',
        'requirements' => '',
        'status' => 'open',
        'image_path' => null,
    ]);
    $v = Vacancy::find($vacancyId);
    check('image_path preserved when no new file uploaded (COALESCE)', $v['image_path'] === 'fake_image_1.jpg');
    check('other fields did update', $v['title'] === 'Image Smoke Test Vacancy (edited)');

    // Simulate "replaced the image"
    Vacancy::update($vacancyId, [
        'title' => 'Image Smoke Test Vacancy (edited)',
        'department' => 'QA',
        'description' => 'Temp.',
        'requirements' => '',
        'status' => 'open',
        'image_path' => 'fake_image_2.jpg',
    ]);
    $v = Vacancy::find($vacancyId);
    check('image_path replaced when a new file is uploaded', $v['image_path'] === 'fake_image_2.jpg');
} finally {
    if ($vacancyId) {
        $db->prepare('DELETE FROM vacancies WHERE id = :id')->execute(['id' => $vacancyId]);
    }
    echo "\nCleanup done.\n";
}
