CREATE TABLE users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('applicant','hr') NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE vacancies (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(150) NOT NULL,
    department      VARCHAR(100) NOT NULL,
    description     TEXT NOT NULL,
    requirements    TEXT,
    image_path      VARCHAR(255) NULL,
    status          ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_by      INT NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at       DATETIME NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE applications (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    vacancy_id      INT NOT NULL,
    applicant_id    INT NOT NULL,
    resume_path     VARCHAR(255) NOT NULL,
    cover_letter    TEXT,
    status          ENUM('Applied','Screening','InterviewScheduled','InterviewCompleted',
                         'DecisionPending','Offered','Rejected','Withdrawn')
                    NOT NULL DEFAULT 'Applied',
    applied_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vacancy_id) REFERENCES vacancies(id),
    FOREIGN KEY (applicant_id) REFERENCES users(id),
    UNIQUE KEY uq_active_application (vacancy_id, applicant_id)
);

CREATE TABLE application_stage_history (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    application_id  INT NOT NULL,
    from_status     VARCHAR(30) NULL,
    to_status       VARCHAR(30) NOT NULL,
    changed_by      INT NOT NULL,
    note            TEXT,
    changed_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id),
    FOREIGN KEY (changed_by) REFERENCES users(id)
);
