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

    // Create a user with any role ('applicant' = employee, 'hr' or 'admin').
    // Only the Super Admin creates accounts - there is no public sign-up.
    public static function create($name, $email, $password, $role)
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            // Never store the real password - only a secure hash of it.
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
        ]);

        return Database::connection()->lastInsertId();
    }

    public static function emailExists($email)
    {
        return self::findByEmail($email) !== null;
    }

    // Is this email used by ANOTHER user? (Used when editing a user.)
    public static function emailTakenByOther($email, $userId)
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND id != :id');
        $stmt->execute(['email' => $email, 'id' => $userId]);
        return $stmt->fetchColumn() > 0;
    }

    // All users for the admin list, newest first. Pass a role to filter, or '' for everyone.
    public static function all($role = '')
    {
        $db = Database::connection();
        $sql = 'SELECT id, name, email, role, is_active, created_at FROM users';

        if ($role !== '') {
            $stmt = $db->prepare($sql . ' WHERE role = :role ORDER BY created_at DESC');
            $stmt->execute(['role' => $role]);
        } else {
            $stmt = $db->query($sql . ' ORDER BY created_at DESC');
        }
        return $stmt->fetchAll();
    }

    // How many users of each role, e.g. ['admin' => 1, 'hr' => 2, 'applicant' => 10].
    public static function countByRole()
    {
        $counts = ['admin' => 0, 'hr' => 0, 'applicant' => 0];
        $rows = Database::connection()->query('SELECT role, COUNT(*) AS total FROM users GROUP BY role')->fetchAll();
        foreach ($rows as $row) {
            $counts[$row['role']] = (int) $row['total'];
        }
        return $counts;
    }

    // Change a user's details. The password is only changed if a new one is given.
    public static function update($id, $name, $email, $role, $newPassword)
    {
        $db = Database::connection();

        $stmt = $db->prepare('UPDATE users SET name = :name, email = :email, role = :role WHERE id = :id');
        $stmt->execute(['name' => $name, 'email' => $email, 'role' => $role, 'id' => $id]);

        if ($newPassword !== '') {
            $stmt = $db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
            $stmt->execute(['hash' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $id]);
        }
    }

    // Deactivate (0) or re-activate (1) an account. A deactivated user cannot log in.
    public static function setActive($id, $active)
    {
        $stmt = Database::connection()->prepare('UPDATE users SET is_active = :active WHERE id = :id');
        $stmt->execute(['active' => $active, 'id' => $id]);
    }

    // Does this user appear in any other table? If yes they cannot be deleted
    // (the foreign keys would break) and should be deactivated instead.
    public static function hasRecords($id)
    {
        $db = Database::connection();
        $total = 0;

        $queries = [
            'SELECT COUNT(*) FROM vacancies WHERE created_by = :id',
            'SELECT COUNT(*) FROM applications WHERE applicant_id = :id',
            'SELECT COUNT(*) FROM application_stage_history WHERE changed_by = :id',
        ];
        foreach ($queries as $sql) {
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $total += $stmt->fetchColumn();
        }

        return $total > 0;
    }

    public static function delete($id)
    {
        $stmt = Database::connection()->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
