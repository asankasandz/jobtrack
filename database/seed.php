<?php

// HR and Super Admin accounts are created directly, not through the public
// sign-up form (see app/Controllers/AuthController.php for why). Run once from the CLI:
//
//   php database/seed.php <email> <password> ["Full Name"] [hr|admin] [EmployeeID]
//
// People log in with their Employee ID. If you leave it out, the next free one is used
// (hr2024 for HR, super2024 for the Super Admin). The role defaults to "hr". Examples:
//   php database/seed.php hr@example.com "StrongPass1" "HR Admin"
//   php database/seed.php boss@example.com "StrongPass1" "Super Admin" admin super2030
//
// Credentials are passed as arguments so no real password ever lives in the
// repository. If an account with that role already exists, nothing is created.

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Models/User.php';

$email = $argv[1] ?? '';
$password = $argv[2] ?? '';
$name = $argv[3] ?? 'HR Admin';
$role = $argv[4] ?? 'hr';
$employeeId = $argv[5] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || !in_array($role, ['hr', 'admin'])) {
    fwrite(STDERR, "Usage: php database/seed.php <email> <password (min 8 chars)> [\"Full Name\"] [hr|admin] [EmployeeID]\n");
    exit(1);
}

$pdo = Database::connection();

$stmt = $pdo->prepare('SELECT email FROM users WHERE role = :role LIMIT 1');
$stmt->execute(['role' => $role]);
$existing = $stmt->fetch();
if ($existing) {
    echo "A '$role' account already exists ({$existing['email']}). Nothing to do.\n";
    exit;
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
if ($stmt->fetch()) {
    fwrite(STDERR, "A user with $email already exists.\n");
    exit(1);
}

if ($employeeId === '') {
    $employeeId = User::nextEmployeeId($role);
}
if (!preg_match('/^[A-Za-z0-9_-]{2,30}$/', $employeeId) || User::employeeIdTaken($employeeId)) {
    fwrite(STDERR, "The Employee ID '$employeeId' is invalid or already used.\n");
    exit(1);
}

$stmt = $pdo->prepare(
    'INSERT INTO users (name, email, password_hash, role, employee_id) VALUES (:name, :email, :password_hash, :role, :employee_id)'
);
$stmt->execute([
    'name' => $name,
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'role' => $role,
    'employee_id' => $employeeId,
]);

echo "$role account created: $email - log in with Employee ID: $employeeId\n";
