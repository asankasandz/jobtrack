<?php

// Read-only queries for the Super Admin dashboard and reports.
class Report
{
    // Run a "SELECT COUNT(*) ..." query and return the number.
    private static function count($sql)
    {
        return (int) Database::connection()->query($sql)->fetchColumn();
    }

    // The numbers on the dashboard cards.
    public static function dashboardStats()
    {
        return [
            'total_users'        => self::count('SELECT COUNT(*) FROM users'),
            'hr_staff'           => self::count("SELECT COUNT(*) FROM users WHERE role = 'hr'"),
            'applicants'         => self::count("SELECT COUNT(*) FROM users WHERE role = 'applicant'"),
            'inactive_users'     => self::count('SELECT COUNT(*) FROM users WHERE is_active = 0'),
            'total_vacancies'    => self::count('SELECT COUNT(*) FROM vacancies'),
            'open_vacancies'     => self::count("SELECT COUNT(*) FROM vacancies WHERE status = 'open'"),
            'total_applications' => self::count('SELECT COUNT(*) FROM applications'),
            'offers'             => self::count("SELECT COUNT(*) FROM applications WHERE status = 'Offered'"),
            'rejected'           => self::count("SELECT COUNT(*) FROM applications WHERE status = 'Rejected'"),
        ];
    }

    // Applications received on each of the last N days (days with none count as 0),
    // for the line chart. Returns ['labels' => [...], 'values' => [...]].
    public static function applicationsPerDay($days)
    {
        $start = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));

        $stmt = Database::connection()->prepare(
            'SELECT DATE(applied_at) AS day, COUNT(*) AS total
             FROM applications
             WHERE applied_at >= :start
             GROUP BY DATE(applied_at)'
        );
        $stmt->execute(['start' => $start]);

        $perDay = [];
        foreach ($stmt->fetchAll() as $row) {
            $perDay[$row['day']] = (int) $row['total'];
        }

        $labels = [];
        $values = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime('-' . $i . ' days'));
            $labels[] = date('j M', strtotime($day));
            $values[] = isset($perDay[$day]) ? $perDay[$day] : 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    // How many applications are in each status, in pipeline order, for the bar chart.
    public static function applicationsByStatus()
    {
        $order = ['Applied', 'Screening', 'InterviewScheduled', 'InterviewCompleted',
                  'DecisionPending', 'Offered', 'Rejected', 'Withdrawn'];

        $counts = [];
        foreach ($order as $status) {
            $counts[$status] = 0;
        }

        $rows = Database::connection()->query('SELECT status, COUNT(*) AS total FROM applications GROUP BY status')->fetchAll();
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return ['labels' => array_keys($counts), 'values' => array_values($counts)];
    }

    // The stage-by-stage report: every status change, with the applicant's name and
    // email, the vacancy, who made the decision, and the note HR wrote.
    //
    // $filters can contain:  vacancy_id, result (passed / failed / withdrawn),
    //                        date_from and date_to (YYYY-MM-DD)
    // $limit = 0 means "no limit".
    public static function stageReport($filters, $limit = 0)
    {
        $sql = 'SELECT h.id, h.from_status, h.to_status, h.note, h.changed_at,
                       v.title AS vacancy_title,
                       u.name AS applicant_name, u.email AS applicant_email,
                       cb.name AS changed_by_name
                FROM application_stage_history h
                JOIN applications a ON a.id = h.application_id
                JOIN vacancies v ON v.id = a.vacancy_id
                JOIN users u ON u.id = a.applicant_id
                JOIN users cb ON cb.id = h.changed_by
                WHERE 1 = 1';
        $params = [];

        if (!empty($filters['vacancy_id'])) {
            $sql .= ' AND a.vacancy_id = :vacancy_id';
            $params['vacancy_id'] = $filters['vacancy_id'];
        }

        // Same rules as outcome() below.
        if (($filters['result'] ?? '') === 'failed') {
            $sql .= " AND h.to_status = 'Rejected'";
        } elseif (($filters['result'] ?? '') === 'withdrawn') {
            $sql .= " AND h.to_status = 'Withdrawn'";
        } elseif (($filters['result'] ?? '') === 'passed') {
            $sql .= " AND h.from_status IS NOT NULL AND h.to_status NOT IN ('Rejected', 'Withdrawn')";
        }

        if (!empty($filters['date_from'])) {
            $sql .= ' AND h.changed_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $sql .= ' AND h.changed_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $sql .= ' ORDER BY h.changed_at DESC, h.id DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Turn one status change into a plain result for the report:
    //   first row (no "from" stage)    -> Applied
    //   moved on to the next stage     -> Passed   (the stage it LEFT was passed)
    //   Rejected after the last stage  -> Failed
    //   Withdrawn                      -> Withdrawn
    public static function outcome($from, $to)
    {
        if ($from === null) {
            return 'Applied';
        }
        if ($to === 'Rejected') {
            return 'Failed';
        }
        if ($to === 'Withdrawn') {
            return 'Withdrawn';
        }
        return 'Passed';
    }
}
