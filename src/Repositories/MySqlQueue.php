<?php

declare(strict_types=1);

namespace OpenPulse\Repositories;

use OpenPulse\Contracts\QueueInterface;
use PDO;
use RuntimeException;
use Throwable;

final class MySqlQueue implements QueueInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly int $reservationTimeoutSeconds = 300
    ) {
        if ($this->reservationTimeoutSeconds < 1) {
            throw new \InvalidArgumentException(
                'Reservation timeout must be at least one second.'
            );
        }
    }

    public function push(
        string $jobType,
        array $payload,
        string $queue = 'default',
    ): int {
        if (trim($jobType) === '') {
            throw new RuntimeException('Job type cannot be empty.');
        }

        if (trim($queue) === '') {
            throw new RuntimeException('Queue name cannot be empty.');
        }

        $json = json_encode(
            [
                'type' => $jobType,
                'data' => $payload,
            ],
            JSON_THROW_ON_ERROR,
        );

        $statement = $this->pdo->prepare(
            'INSERT INTO jobs (queue, payload) VALUES (:queue, :payload)'
        );

        $statement->execute([
            'queue' => $queue,
            'payload' => $json,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function reserve(
        string $queue = 'default',
    ): ?array {
        $this->pdo->beginTransaction();

        try {
                $sql = <<<SQL
                SELECT id, queue, payload, attempts
                FROM jobs
                WHERE queue = :queue
                AND (
                    reserved_at IS NULL
                    OR reserved_at < DATE_SUB(NOW(), INTERVAL {$this->reservationTimeoutSeconds} SECOND)
                )
                AND available_at <= NOW()
                ORDER BY id ASC
                LIMIT 1
                FOR UPDATE
                SQL;

                $statement = $this->pdo->prepare($sql);

                $statement->execute(['queue' => $queue]);
                $job = $statement->fetch(PDO::FETCH_ASSOC);

                if ($job === false) {
                    $this->pdo->commit();

                    return null;
                }

                $update = $this->pdo->prepare(
                    <<<'SQL'
                    UPDATE jobs
                    SET reserved_at = NOW(),
                        attempts = attempts + 1
                    WHERE id = :id
                    SQL
                );

                $update->execute(['id' => $job['id']]);

                $payload = json_decode(
                    (string) $job['payload'],
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );

                if (
                    !is_array($payload)
                    || !isset($payload['type'], $payload['data'])
                    || !is_string($payload['type'])
                    || !is_array($payload['data'])
                ) {
                    throw new RuntimeException('Invalid job payload structure.');
                }

                $this->pdo->commit();

                return [
                    'id' => (int) $job['id'],
                    'queue' => (string) $job['queue'],
                    'attempts' => (int) $job['attempts'] + 1,
                    'payload' => $payload,
                ];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function delete(int $jobId): void
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM jobs WHERE id = :id'
        );

        $statement->execute(['id' => $jobId]);
    }

    public function release(
        int $jobId,
        int $delaySeconds = 0,
    ): void {
        if ($delaySeconds < 0) {
            throw new RuntimeException(
                'Retry delay cannot be negative.'
            );
        }

        $statement = $this->pdo->prepare(
            <<<'SQL'
            UPDATE jobs
            SET reserved_at = NULL,
                available_at = DATE_ADD(
                    NOW(),
                    INTERVAL :delay SECOND
                )
            WHERE id = :id
            SQL
        );

        $statement->bindValue(':delay', $delaySeconds, PDO::PARAM_INT);
        $statement->bindValue(':id', $jobId, PDO::PARAM_INT);
        $statement->execute();
    }

    public function fail(int $jobId, string $exception): void
    {
        $this->pdo->beginTransaction();

        try {
            $statement = $this->pdo->prepare(
                'SELECT queue, payload FROM jobs WHERE id = :id FOR UPDATE'
            );

            $statement->execute(['id' => $jobId]);
            $job = $statement->fetch(PDO::FETCH_ASSOC);

            if ($job === false) {
                $this->pdo->commit();

                return;
            }

            $insert = $this->pdo->prepare(
                <<<'SQL'
                INSERT INTO failed_jobs (uuid, queue, payload, exception)
                VALUES (:uuid, :queue, :payload, :exception)
                SQL
            );

            $insert->execute([
                'uuid' => self::createUuid(),
                'queue' => $job['queue'],
                'payload' => $job['payload'],
                'exception' => $exception,
            ]);

            $delete = $this->pdo->prepare(
                'DELETE FROM jobs WHERE id = :id'
            );

            $delete->execute(['id' => $jobId]);

            $this->pdo->commit();
        } catch (Throwable $throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $throwable;
        }
    }

    private static function createUuid(): string
    {
        $bytes = random_bytes(16);

        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
}