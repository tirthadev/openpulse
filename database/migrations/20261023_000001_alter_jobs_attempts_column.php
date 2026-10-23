<?php

declare(strict_types=1);

return <<<'SQL'
ALTER TABLE jobs
    MODIFY COLUMN `attempts` INT UNSIGNED NOT NULL DEFAULT 0;
SQL;
