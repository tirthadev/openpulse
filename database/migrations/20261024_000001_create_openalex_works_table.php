<?php

declare(strict_types=1);

return <<<'SQL'
CREATE TABLE IF NOT EXISTS openalex_works (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `openalex_id` VARCHAR(64) NOT NULL,
    `doi` VARCHAR(255) NULL,
    `title` TEXT NULL,
    `publication_year` SMALLINT UNSIGNED NULL,
    `publication_date` DATE NULL,
    `work_type` VARCHAR(50) NOT NULL,
    `cited_by_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_open_access` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uq_openalex_works_openalex_id` (`openalex_id`)
) ENGINE=InnoDB;
SQL;
