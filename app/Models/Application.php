<?php

// Model for the "applications" table.
class Application
{
    // Statuses where the process has ended - nothing can change after these.
    public static function finalStatuses()
    {
        return ['Offered', 'Rejected', 'Withdrawn'];
    }

    // The allowed next steps for each status (the State Diagram in Chapter 1).
    // Example: from "Applied" you can only go to "Screening" or "Withdrawn".
    public static function nextStatuses($currentStatus)
    {
        $transitions = [
            'Applied'            => ['Screening', 'Withdrawn'],
            'Screening'          => ['InterviewScheduled', 'Withdrawn'],
            'InterviewScheduled' => ['InterviewCompleted', 'Withdrawn'],
            'InterviewCompleted' => ['DecisionPending', 'Withdrawn'],
            'DecisionPending'    => ['Offered', 'Rejected', 'Withdrawn'],
        ];

        if (isset($transitions[$currentStatus])) {
            return $transitions[$currentStatus];
        }
        return []; // final status: no next steps
    }

    public static function isValidTransition($from, $to)
    {
        return in_array($to, self::nextStatuses($from));
    }

    // One application per applicant per vacancy.
    public static function existsActiveForVacancyAndApplicant($vacancyId, $applicantId)
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM applications WHERE vacancy_id = :vacancy_id AND applicant_id = :applicant_id'
        );
        $stmt->execute(['vacancy_id' => $vacancyId, 'applicant_id' => $applicantId]);
        return $stmt->fetchColumn() > 0;
    }

    public static function create($vacancyId, $applicantId, $resumePath, $coverLetter)
    {
        $db = Database::connection();

        // Transaction: the application AND its first history row are saved
        // together. If one fails, neither is saved.
        $db->beginTransaction();

        try {
            $stmt = $db->prepare(
                "INSERT INTO applications (vacancy_id, applicant_id, resume_path, cover_letter, status)
                 VALUES (:vacancy_id, :applicant_id, :resume_path, :cover_letter, 'Applied')"
            );
            $stmt->execute([
                'vacancy_id' => $vacancyId,
                'applicant_id' => $applicantId,
                'resume_path' => $resumePath,
                'cover_letter' => $coverLetter,
            ]);
            $applicationId = $db->lastInsertId();

            ApplicationStageHistory::record($applicationId, null, 'Applied', $applicantId, 'Application submitted.');

            $db->commit();
            return $applicationId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function findById($id)
    {
        $stmt = Database::connection()->prepare('SELECT * FROM applications WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $application = $stmt->fetch();

        if ($application === false) {
            return null;
        }
        return $application;
    }

    // All applications of one applicant, with the vacancy title.
    public static function forApplicant($applicantId)
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

    // All applications, with vacancy title and applicant name - for HR.
    public static function forHr()
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

    public static function historyFor($applicationId)
    {
        return ApplicationStageHistory::forApplication($applicationId);
    }

    // Move an application to a new status. Returns true on success,
    // false if the change is not allowed by the state machine.
    public static function changeStatus($id, $newStatus, $changedBy, $note)
    {
        $application = self::findById($id);
        if ($application === null) {
            return false;
        }

        // Server-side check: even if someone sends a fake request,
        // an invalid jump (e.g. Applied -> Offered) is refused here.
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
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    // An applicant can withdraw only their OWN application, and only before a final decision.
    public static function withdraw($id, $applicantId)
    {
        $application = self::findById($id);
        if ($application === null || $application['applicant_id'] != $applicantId) {
            return false;
        }

        if (in_array($application['status'], self::finalStatuses())) {
            return false;
        }

        return self::changeStatus($id, 'Withdrawn', $applicantId, 'Withdrawn by applicant.');
    }

    // Numbers for the HR dashboard cards.
    public static function summaryStats()
    {
        $db = Database::connection();

        return [
            'open_vacancies'     => $db->query("SELECT COUNT(*) FROM vacancies WHERE status = 'open'")->fetchColumn(),
            'total_applications' => $db->query('SELECT COUNT(*) FROM applications')->fetchColumn(),
            'in_screening'       => $db->query("SELECT COUNT(*) FROM applications WHERE status = 'Screening'")->fetchColumn(),
            'interview_stage'    => $db->query("SELECT COUNT(*) FROM applications WHERE status IN ('InterviewScheduled', 'InterviewCompleted')")->fetchColumn(),
            'offers_made'        => $db->query("SELECT COUNT(*) FROM applications WHERE status = 'Offered'")->fetchColumn(),
        ];
    }
}
