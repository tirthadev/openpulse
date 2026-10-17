<?php

declare(strict_types=1);

namespace OpenPulse\Core;

use PDO;
use RuntimeException;

final class MigrationRunner
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $path,
    ) {
    }

    public function migrate(): void
    {
        if (!is_dir($this->path)) {
            throw new RuntimeException(
                'Migration directory does not exist: ' . $this->path
            );
        }

        $migrationTableSql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS migrations (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL UNIQUE,
                `executed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
            SQL;

        $this->pdo->exec($migrationTableSql);

        $files = glob($this->path . '/*.php');

        if ($files === false) {
            throw new RuntimeException(
                'Failed to discover migration files.'
            );
        }

        sort($files, SORT_STRING);

        $lookup = $this->pdo->prepare(
            'SELECT id FROM migrations WHERE migration = :migration LIMIT 1'
        );

        $record = $this->pdo->prepare(
            'INSERT INTO migrations (migration) VALUES (:migration)'
        );

        foreach ($files as $migration) {
            $filename = basename($migration);

            $lookup->execute(['migration' => $filename]);

            if ($lookup->fetchColumn() !== false) {
                continue;
            }

            $sql = require $migration;

            if (!is_string($sql) || trim($sql) === '') {
                throw new RuntimeException(
                    'Migration must return a non-empty SQL string: ' . $filename
                );
            }

            try {
                $this->pdo->exec($sql);

                $record->execute(['migration' => $filename]);
            } catch (\Throwable $exception) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                throw $exception;
            }
        }
    }
}
