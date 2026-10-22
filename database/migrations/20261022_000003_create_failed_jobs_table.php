<?php

declare(strict_types=1);

return <<<'SQL'
CREATE TABLE IF NOT EXISTS failed_jobs (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL UNIQUE,
    `queue` VARCHAR(100) NOT NULL DEFAULT 'default',
    `payload` JSON NOT NULL,
    `exception` TEXT NOT NULL,
    `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
SQL;
