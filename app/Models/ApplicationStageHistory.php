<?php

namespace App\Models;

use App\Core\Database;

class ApplicationStageHistory
{
    public static function record(int $applicationId, ?string $from, string $to, int $changedBy, ?string $note): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO application_stage_history (application_id, from_status, to_status, changed_by, note)
             VALUES (:application_id, :from_status, :to_status, :changed_by, :note)'
        );
        $stmt->execute([
            'application_id' => $applicationId,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $changedBy,
            'note' => $note,
        ]);
    }

    public static function forApplication(int $applicationId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT h.*, u.name AS changed_by_name
             FROM application_stage_history h
             JOIN users u ON u.id = h.changed_by
             WHERE h.application_id = :application_id
             ORDER BY h.changed_at ASC'
        );
        $stmt->execute(['application_id' => $applicationId]);
        return $stmt->fetchAll();
    }
}
