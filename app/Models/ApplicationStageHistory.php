<?php

// Model for the "application_stage_history" table.
// Every status change is saved here, so there is a full audit trail.
class ApplicationStageHistory
{
    public static function record($applicationId, $from, $to, $changedBy, $note)
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

    public static function forApplication($applicationId)
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
