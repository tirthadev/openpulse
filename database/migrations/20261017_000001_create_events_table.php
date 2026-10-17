<?php
declare(strict_types=1);

return <<<'SQL'
CREATE TABLE IF NOT EXISTS events (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event` VARCHAR(255) NOT NULL,
    `user_id` VARCHAR(255) NULL,
    `properties` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
SQL;
