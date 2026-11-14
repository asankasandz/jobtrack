<?php
// One-off migration: users log in with their Employee ID, so every existing account needs one.
// Accounts without an ID get the next free one in the style super2024 / hr2024 / emp2024, emp2025 ...
// (oldest account first). Safe to re-run: accounts that already have an ID are left alone.
// The Super Admin can change any of these IDs afterwards on the Users page.

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Models/User.php';

$pdo = Database::connection();

$users = $pdo->query('SELECT id, name, email, role FROM users WHERE employee_id IS NULL AND is_approved = 1 ORDER BY id')->fetchAll();
if (empty($users)) {
    echo "Every approved user already has an Employee ID. Nothing to do.\n";
    exit;
}

$update = $pdo->prepare('UPDATE users SET employee_id = :employee_id WHERE id = :id');
foreach ($users as $user) {
    $employeeId = User::nextEmployeeId($user['role']);
    $update->execute(['employee_id' => $employeeId, 'id' => $user['id']]);
    echo "Employee ID $employeeId assigned to {$user['name']} <{$user['email']}> ({$user['role']})\n";
}