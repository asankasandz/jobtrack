<?php
// One-off migration: adds image_path to an already-created vacancies table.
// Safe to re-run — it checks first and does nothing if the column already exists.

require __DIR__ . '/../app/Core/Database.php';
use App\Core\Database;

$pdo = Database::connection();

$check = $pdo->query("SHOW COLUMNS FROM vacancies LIKE 'image_path'");
if ($check->fetch()) {
    echo "Column image_path already exists. Nothing to do.\n";
    exit;
}

$pdo->exec("ALTER TABLE vacancies ADD COLUMN image_path VARCHAR(255) NULL AFTER requirements");
echo "Migration applied: vacancies.image_path added.\n";
