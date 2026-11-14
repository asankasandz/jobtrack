<?php
// One-off migration for "register, then wait for approval":
//   users.employee_id  - the employee number typed in at registration (unique when set)
//   users.is_approved  - 1 = approved (can log in), 0 = registered but waiting for the Super Admin
// Safe to re-run - each step checks first. Existing users stay approved (default 1),
// and a fresh install from schema.sql already has both columns.

require_once __DIR__ . '/../app/Core/Database.php';
$pdo = Database::connection();

if ($pdo->query("SHOW COLUMNS FROM users LIKE 'employee_id'")->fetch()) {
    echo "Column employee_id already exists. Nothing to do.\n";
} else {
    $pdo->exec("ALTER TABLE users ADD COLUMN employee_id VARCHAR(30) NULL AFTER email");
    echo "Migration applied: users.employee_id added.\n";
}

if ($pdo->query("SHOW INDEX FROM users WHERE Key_name = 'uq_employee_id'")->fetch()) {
    echo "Index uq_employee_id already exists. Nothing to do.\n";
} else {
    $pdo->exec("ALTER TABLE users ADD UNIQUE KEY uq_employee_id (employee_id)");
    echo "Migration applied: employee_id is now unique.\n";
}

if ($pdo->query("SHOW COLUMNS FROM users LIKE 'is_approved'")->fetch()) {
    echo "Column is_approved already exists. Nothing to do.\n";
} else {
    $pdo->exec("ALTER TABLE users ADD COLUMN is_approved TINYINT(1) NOT NULL DEFAULT 1 AFTER is_active");
    echo "Migration applied: users.is_approved added (all existing users stay approved).\n";
}
