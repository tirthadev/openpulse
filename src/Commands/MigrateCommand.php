<?php
declare(strict_types=1);

namespace OpenPulse\Commands;

use OpenPulse\Contracts\CommandInterface;
use OpenPulse\Core\MigrationRunner;
use PDO;

final class MigrateCommand implements CommandInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $migrationPath
    ) {
    }

    public function execute(array $arguments = []): int
    {
        $runner = new MigrationRunner(
            $this->pdo,
            $this->migrationPath
        );

        $runner->migrate();

        fwrite(STDOUT, "Migrations completed successfully.\n");

        return 0;
    }
}
