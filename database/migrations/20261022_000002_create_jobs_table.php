<?php

declare(strict_types=1);

return <<<'SQL'
CREATE TABLE IF NOT EXISTS jobs (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `queue` VARCHAR(100) NOT NULL DEFAULT 'default',
    `payload` JSON NOT NULL,
    `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `available_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reserved_at` DATETIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_jobs_queue_available` (
        `queue`,
        `reserved_at`,
        `available_at`,
        `id`
    )
);
SQL;
