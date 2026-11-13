<?php

// HR accounts are seeded directly, not created through the public sign-up form
// (see app/Controllers/AuthController.php for why). Run once from the CLI:
//
//   C:\xampp\php\php.exe database\seed.php <email> <password> ["Full Name"]
//
// Credentials are passed as arguments so no real password ever lives in the
// repository. If any HR account already exists, nothing is created.

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require_once __DIR__ . '/../app/Core/Database.php';

$email = $argv[1] ?? '';
$password = $argv[2] ?? '';
$name = $argv[3] ?? 'HR Admin';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
    fwrite(STDERR, "Usage: php database/seed.php <email> <password (min 8 chars)> [\"Full Name\"]\n");
    exit(1);
}

$pdo = Database::connection();

$existing = $pdo->query("SELECT email FROM users WHERE role = 'hr' LIMIT 1")->fetch();
if ($existing) {
    echo "An HR account already exists ({$existing['email']}). Nothing to do.\n";
    exit;
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
if ($stmt->fetch()) {
    fwrite(STDERR, "A user with $email already exists (as a non-HR account).\n");
    exit(1);
}

$stmt = $pdo->prepare(
    'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
);
$stmt->execute([
    'name' => $name,
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'role' => 'hr',
]);

echo "HR user created: $email\n";
