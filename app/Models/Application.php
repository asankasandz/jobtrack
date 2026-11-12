<?php

namespace App\Models;

use App\Core\Database;

class Application
{
    public const FINAL_STATUSES = ['Offered', 'Rejected', 'Withdrawn'];

    // The controlled state machine from Chapter 1's State Diagram.
    private const VALID_TRANSITIONS = [
        'Applied' => ['Screening', 'Withdrawn'],
        'Screening' => ['InterviewScheduled', 'Withdrawn'],
        'InterviewScheduled' => ['InterviewCompleted', 'Withdrawn'],
        'InterviewCompleted' => ['DecisionPending', 'Withdrawn'],
        'DecisionPending' => ['Offered', 'Rejected', 'Withdrawn'],
    ];

    public static function isValidTransition(string $from, string $to): bool
    {
        return in_array($to, self::VALID_TRANSITIONS[$from] ?? [], true);
    }

    public static function nextStatuses(string $from): array
    {
        return self::VALID_TRANSITIONS[$from] ?? [];
    }

    public static function existsActiveForVacancyAndApplicant(int $vacancyId, int $applicantId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM applications WHERE vacancy_id = :vacancy_id AND applicant_id = :applicant_id LIMIT 1'
        );
        $stmt->execute(['vacancy_id' => $vacancyId, 'applicant_id' => $applicantId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function create(int $vacancyId, int $applicantId, string $resumePath, ?string $coverLetter): int
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare(
                'INSERT INTO applications (vacancy_id, applicant_id, resume_path, cover_letter, status)
                 VALUES (:vacancy_id, :applicant_id, :resume_path, :cover_letter, "Applied")'
            );
            $stmt->execute([
                'vacancy_id' => $vacancyId,
                'applicant_id' => $applicantId,
                'resume_path' => $resumePath,
                'cover_letter' => $coverLetter,
            ]);
            $applicationId = (int) $db->lastInsertId();

            ApplicationStageHistory::record($applicationId, null, 'Applied', $applicantId, 'Application submitted.');

            $db->commit();
            return $applicationId;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM applications WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $application = $stmt->fetch();
        return $application ?: null;
    }

    public static function forApplicant(int $applicantId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT a.*, v.title AS vacancy_title, v.department
             FROM applications a
             JOIN vacancies v ON v.id = a.vacancy_id
             WHERE a.applicant_id = :applicant_id
             ORDER BY a.applied_at DESC'
        );
        $stmt->execute(['applicant_id' => $applicantId]);
        return $stmt->fetchAll();
    }

    public static function forHr(): array
    {
        $stmt = Database::connection()->query(
            'SELECT a.*, v.title AS vacancy_title, u.name AS applicant_name
             FROM applications a
             JOIN vacancies v ON v.id = a.vacancy_id
             JOIN users u ON u.id = a.applicant_id
             ORDER BY a.applied_at DESC'
        );
        return $stmt->fetchAll();
    }

    public static function historyFor(int $applicationId): array
    {
        return ApplicationStageHistory::forApplication($applicationId);
    }

    public static function changeStatus(int $id, string $newStatus, int $changedBy, ?string $note): bool
    {
        $application = self::findById($id);
        if ($application === null) {
            return false;
        }

        if (!self::isValidTransition($application['status'], $newStatus)) {
            return false;
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare('UPDATE applications SET status = :status WHERE id = :id');
            $stmt->execute(['status' => $newStatus, 'id' => $id]);

            ApplicationStageHistory::record($id, $application['status'], $newStatus, $changedBy, $note);

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function withdraw(int $id, int $applicantId): bool
    {
        $application = self::findById($id);
        if ($application === null || (int) $application['applicant_id'] !== $applicantId) {
            return false;
        }

        if (in_array($application['status'], self::FINAL_STATUSES, true)) {
            return false;
        }

        return self::changeStatus($id, 'Withdrawn', $applicantId, 'Withdrawn by applicant.');
    }

    public static function summaryStats(): array
    {
        $db = Database::connection();
        $total = (int) $db->query('SELECT COUNT(*) FROM applications')->fetchColumn();
        $screening = (int) $db->query("SELECT COUNT(*) FROM applications WHERE status = 'Screening'")->fetchColumn();
        $interview = (int) $db->query(
            "SELECT COUNT(*) FROM applications WHERE status IN ('InterviewScheduled','InterviewCompleted')"
        )->fetchColumn();
        $offers = (int) $db->query("SELECT COUNT(*) FROM applications WHERE status = 'Offered'")->fetchColumn();
        $openVacancies = (int) $db->query("SELECT COUNT(*) FROM vacancies WHERE status = 'open'")->fetchColumn();

        return [
            'open_vacancies' => $openVacancies,
            'total_applications' => $total,
            'in_screening' => $screening,
            'interview_stage' => $interview,
            'offers_made' => $offers,
        ];
    }
}
