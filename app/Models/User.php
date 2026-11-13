<?php

// Model for the "users" table.
class User
{
    // Returns the user as an array, or null if no user has that email.
    public static function findByEmail($email)
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user === false) {
            return null;
        }
        return $user;
    }

    public static function findById($id)
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        if ($user === false) {
            return null;
        }
        return $user;
    }

    // Public sign-up always creates an "applicant". HR accounts are only
    // created with database/seed.php, so nobody can make themselves HR.
    public static function createApplicant($name, $email, $password)
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            // Never store the real password - only a secure hash of it.
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'applicant',
        ]);

        return Database::connection()->lastInsertId();
    }

    public static function emailExists($email)
    {
        return self::findByEmail($email) !== null;
    }
}
