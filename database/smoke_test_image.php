<?php

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/../app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) require $path;
});

use App\Core\Database;
use App\Models\User;
use App\Models\Vacancy;

function check(string $label, bool $cond): void {
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
