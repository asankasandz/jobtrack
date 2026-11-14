<?php

// Model for the "vacancies" table.
class Vacancy
{
    // Open vacancies only - shown on the public landing page.
    public static function allOpen()
    {
        $stmt = Database::connection()->query(
            "SELECT * FROM vacancies WHERE status = 'open' ORDER BY created_at DESC"
        );
        return $stmt->fetchAll();
    }

    // Every vacancy (open and closed) - for the HR "Manage Vacancies" page.
    public static function allForHr()
    {
        $stmt = Database::connection()->query('SELECT * FROM vacancies ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public static function find($id)
    {
        $stmt = Database::connection()->prepare('SELECT * FROM vacancies WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $vacancy = $stmt->fetch();

        if ($vacancy === false) {
            return null;
        }
        return $vacancy;
    }

    public static function create($data, $createdBy)
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

        return Database::connection()->lastInsertId();
    }

    public static function update($id, $data)
    {
        $db = Database::connection();

        $stmt = $db->prepare(
            'UPDATE vacancies
             SET title = :title, department = :department, description = :description,
                 requirements = :requirements, status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            'title' => $data['title'],
            'department' => $data['department'],
            'description' => $data['description'],
            'requirements' => $data['requirements'],
            'status' => $data['status'],
            'id' => $id,
        ]);

        // Only replace the image if HR uploaded a new one; otherwise keep the old image.
        if (!empty($data['image_path'])) {
            $stmt = $db->prepare('UPDATE vacancies SET image_path = :image_path WHERE id = :id');
            $stmt->execute(['image_path' => $data['image_path'], 'id' => $id]);
        }

        // Remember when the vacancy was first closed.
        if ($data['status'] === 'closed') {
            $stmt = $db->prepare('UPDATE vacancies SET closed_at = NOW() WHERE id = :id AND closed_at IS NULL');
            $stmt->execute(['id' => $id]);
        }
    }

    public static function delete($id)
    {
        $stmt = Database::connection()->prepare('DELETE FROM vacancies WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    // A vacancy that already has applications must not be deleted (it can be closed instead).
    public static function hasApplications($id)
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM applications WHERE vacancy_id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetchColumn() > 0;
    }
}
