<?php

declare(strict_types=1);

namespace OpenPulse\Repositories;

use OpenPulse\Contracts\EventRepositoryInterface;
use OpenPulse\Core\Database;
use Override;

final class MySqlEventRepository implements EventRepositoryInterface
{
    public function __construct(private readonly Database $database)
    {
    }

    #[Override]
    public function save(array $data): void
    {
        $event = $data['event'];
        $userId = $data['user_id'] ?? null;
        $properties = $data['properties'] ?? null;
        if ($properties !== null) {
            $properties = json_encode($properties, JSON_THROW_ON_ERROR);
        }
        $pdo = $this->database->getConnection();
        $statement = $pdo->prepare("INSERT INTO  `events` (`event`, `user_id`, `properties`) VALUES (:event, :user_id, :properties)");
        $statement->execute([
                                'event' => $event,
                                'user_id' => $userId,
                                'properties' => $properties
                            ]);
    }

    #[Override]
    public function findAll(): array
    {
        $pdo = $this->database->getConnection();

        $statement = $pdo->query(
            'SELECT `id`, `event`, `user_id`, `properties`, `created_at`
            FROM `events`
            ORDER BY `created_at` DESC, `id` DESC'
        );

        return $statement->fetchAll();
    }
}
