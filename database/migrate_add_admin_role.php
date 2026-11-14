<?php
// One-off migration for the Super Admin feature:
//   1. adds the "admin" value to users.role
//   2. adds users.is_active (1 = can log in, 0 = deactivated)
// Safe to re-run - each step checks first and does nothing if it is already done.
// A fresh install from schema.sql already has both, so nothing happens there.

require_once __DIR__ . '/../app/Core/Database.php';
$pdo = Database::connection();

// 1) role column must allow 'admin'
$column = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
if (strpos($column['Type'], "'admin'") === false) {
    $pdo->exec("ALTER TABLE users MODIFY role ENUM('applicant','hr','admin') NOT NULL");
    echo "Migration applied: users.role now allows 'admin'.\n";
} else {
    echo "users.role already allows 'admin'. Nothing to do.\n";
}

// 2) is_active column
$check = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'");
if ($check->fetch()) {
    echo "Column is_active already exists. Nothing to do.\n";
} else {
    $pdo->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role");
    echo "Migration applied: users.is_active added (all existing users stay active).\n";
}
