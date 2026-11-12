<?php

namespace App\Models;

use App\Core\Database;

class Vacancy
{
    public static function allOpen(): array
    {
        $stmt = Database::connection()->query(
            "SELECT * FROM vacancies WHERE status = 'open' ORDER BY created_at DESC"
        );
        return $stmt->fetchAll();
    }

    public static function allForHr(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM vacancies ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM vacancies WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $vacancy = $stmt->fetch();
        return $vacancy ?: null;
    }

    public static function create(array $data, int $createdBy): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO vacancies (title, department, description, requirements, image_path, status, created_by)
             VALUES (:title, :department, :description, :requirements, :image_path, :status, :created_by)'
        );
        $stmt->execute([
            'title' => $data['title'],
            'department' => $data['department'],
            'description' => $data['description'],
            'requirements' => $data['requirements'],
            'image_path' => $data['image_path'] ?? null,
            'status' => 'open',
            'created_by' => $createdBy,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        // image_path is only overwritten when a new image was actually uploaded
        // (handled by the controller); otherwise the existing value is kept.
        $stmt = Database::connection()->prepare(
            'UPDATE vacancies
             SET title = :title, department = :department, description = :description,
                 requirements = :requirements, status = :status,
                 image_path = COALESCE(:image_path, image_path),
                 closed_at = CASE WHEN :status2 = "closed" AND closed_at IS NULL THEN NOW() ELSE closed_at END
             WHERE id = :id'
        );
        $stmt->execute([
            'title' => $data['title'],
            'department' => $data['department'],
            'description' => $data['description'],
            'requirements' => $data['requirements'],
            'status' => $data['status'],
            'image_path' => $data['image_path'] ?? null,
            'status2' => $data['status'],
            'id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM vacancies WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function hasApplications(int $id): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM applications WHERE vacancy_id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetchColumn();
    }
}
