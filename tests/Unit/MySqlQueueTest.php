<?php

declare(strict_types=1);

namespace OpenPulse\Tests\Unit;

use OpenPulse\Repositories\MySqlQueue;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MySqlQueueTest extends TestCase
{
    public function testPushStoresJobAndReturnsItsId(): void
    {
        $statement = $this->createMock(PDOStatement::class);

        $statement
            ->expects(self::once())
            ->method('execute')
            ->with(self::callback(
                static function (array $parameters): bool {
                    $payload = json_decode(
                        $parameters['payload'],
                        true,
                        512,
                        JSON_THROW_ON_ERROR,
                    );

                    return $parameters['queue'] === 'default'
                        && $payload === [
                            'type' => 'country.import',
                            'data' => ['source' => 'world-bank'],
                        ];
                }
            ))
            ->willReturn(true);

        $pdo = $this->createMock(PDO::class);

        $pdo
            ->expects(self::once())
            ->method('prepare')
            ->with(
                'INSERT INTO jobs (queue, payload) VALUES (:queue, :payload)'
            )
            ->willReturn($statement);

        $pdo
            ->expects(self::once())
            ->method('lastInsertId')
            ->willReturn('42');

        $queue = new MySqlQueue($pdo);

        $jobId = $queue->push(
            'country.import',
            ['source' => 'world-bank'],
        );

        self::assertSame(42, $jobId);
    }

    public function testPushRejectsAnEmptyJobType(): void
    {
        $pdo = $this->createMock(PDO::class);

        $pdo
            ->expects(self::never())
            ->method('prepare');

        $queue = new MySqlQueue($pdo);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Job type cannot be empty.');

        $queue->push('', []);
    }

    public function testPushRejectsAnEmptyQueueName(): void
    {
        $pdo = $this->createMock(PDO::class);

        $pdo
            ->expects(self::never())
            ->method('prepare');

        $queue = new MySqlQueue($pdo);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Queue name cannot be empty.');

        $queue->push('country.import', [], ' ');
    }

    public function testReserveReturnsNullWhenNoJobIsAvailable(): void
    {
        $statement = $this->createMock(PDOStatement::class);

        $statement
            ->expects(self::once())
            ->method('execute')
            ->with(['queue' => 'default'])
            ->willReturn(true);

        $statement
            ->expects(self::once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $pdo = $this->createMock(PDO::class);

        $pdo->expects(self::once())
            ->method('beginTransaction')
            ->willReturn(true);

        $pdo->expects(self::once())
            ->method('prepare')
            ->willReturn($statement);

        $pdo->expects(self::once())
            ->method('commit')
            ->willReturn(true);

        $pdo->expects(self::never())->method('rollBack');

        $queue = new MySqlQueue($pdo);

        self::assertNull($queue->reserve());
    }

    public function testReserveRollsBackWhenQueryFails(): void
    {
        $pdo = $this->createMock(PDO::class);

        $pdo->expects(self::once())
            ->method('beginTransaction')
            ->willReturn(true);

        $pdo->expects(self::once())
            ->method('prepare')
            ->willThrowException(new RuntimeException('Database unavailable'));

        $pdo->expects(self::once())
            ->method('inTransaction')
            ->willReturn(true);

        $pdo->expects(self::once())
            ->method('rollBack')
            ->willReturn(true);

        $pdo->expects(self::never())->method('commit');

        $queue = new MySqlQueue($pdo);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database unavailable');

        $queue->reserve();
    }

    public function testReserveReturnsJobAndIncrementsAttempts(): void
    {
        $select = $this->createMock(PDOStatement::class);

        $select
            ->expects(self::once())
            ->method('execute')
            ->with(['queue' => 'default'])
            ->willReturn(true);

        $select
            ->expects(self::once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => '42',
                'queue' => 'default',
                'payload' => json_encode([
                    'type' => 'country.import',
                    'data' => ['source' => 'world-bank'],
                ], JSON_THROW_ON_ERROR),
                'attempts' => '2',
            ]);

        $update = $this->createMock(PDOStatement::class);

        $update
            ->expects(self::once())
            ->method('execute')
            ->with(['id' => 42])
            ->willReturn(true);

        $pdo = $this->createMock(PDO::class);

        $pdo
            ->expects(self::once())
            ->method('beginTransaction')
            ->willReturn(true);

        $pdo
            ->expects(self::exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($select, $update);

        $pdo
            ->expects(self::once())
            ->method('commit')
            ->willReturn(true);

        $pdo->expects(self::never())->method('rollBack');

        $queue = new MySqlQueue($pdo);

        $job = $queue->reserve();

        self::assertSame([
            'id' => 42,
            'queue' => 'default',
            'attempts' => 3,
            'payload' => [
                'type' => 'country.import',
                'data' => ['source' => 'world-bank'],
            ],
        ], $job);
    }
    public function testReleaseSchedulesJobForRetry(): void
    {
        $statement = $this->createMock(PDOStatement::class);

        $statement
            ->expects(self::exactly(2))
            ->method('bindValue')
            ->willReturnCallback(
                static function (
                    string $parameter,
                    mixed $value,
                    int $type = PDO::PARAM_STR,
                ): bool {
                    if ($parameter === ':delay') {
                        self::assertSame(30, $value);
                        self::assertSame(PDO::PARAM_INT, $type);

                        return true;
                    }

                    if ($parameter === ':id') {
                        self::assertSame(42, $value);
                        self::assertSame(PDO::PARAM_INT, $type);

                        return true;
                    }

                    self::fail('Unexpected parameter: ' . $parameter);
                }
            );

        $statement
            ->expects(self::once())
            ->method('execute')
            ->with()
            ->willReturn(true);

        $pdo = $this->createMock(PDO::class);

        $pdo
            ->expects(self::once())
            ->method('prepare')
            ->with(self::stringContains('reserved_at = NULL'))
            ->willReturn($statement);

        $queue = new MySqlQueue($pdo);

        $queue->release(42, 30);
    }

    public function testFailMovesJobToFailedJobsTransactionally(): void
    {
        $select = $this->createMock(PDOStatement::class);
        $insert = $this->createMock(PDOStatement::class);
        $delete = $this->createMock(PDOStatement::class);

        $select
            ->expects(self::once())
            ->method('execute')
            ->with(['id' => 42])
            ->willReturn(true);

        $select
            ->expects(self::once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'queue' => 'default',
                'payload' => '{"type":"country.import","data":[]}',
            ]);

        $insert
            ->expects(self::once())
            ->method('execute')
            ->with(self::callback(
                static function (array $parameters): bool {
                    return preg_match(
                        '/^[0-9a-f-]{36}$/',
                        $parameters['uuid'] ?? '',
                    ) === 1
                        && $parameters['queue'] === 'default'
                        && $parameters['payload']
                            === '{"type":"country.import","data":[]}'
                        && $parameters['exception'] === 'Import failed';
                }
            ))
            ->willReturn(true);

        $delete
            ->expects(self::once())
            ->method('execute')
            ->with(['id' => 42])
            ->willReturn(true);

        $pdo = $this->createMock(PDO::class);

        $pdo
            ->expects(self::once())
            ->method('beginTransaction')
            ->willReturn(true);

        $pdo
            ->expects(self::exactly(3))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls(
                $select,
                $insert,
                $delete,
            );

        $pdo
            ->expects(self::once())
            ->method('commit')
            ->willReturn(true);

        $pdo
            ->expects(self::never())
            ->method('rollBack');

        $queue = new MySqlQueue($pdo);

        $queue->fail(42, 'Import failed');
    }


    public function testFindFailedReturnsOriginalQueueAndPayload(): void
    {
        $statement = $this->createMock(PDOStatement::class);

        $statement
            ->expects(self::once())
            ->method('execute')
            ->with(['id' => 42])
            ->willReturn(true);

        $statement
            ->expects(self::once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => '42',
                'queue' => 'default',
                'payload' => json_encode([
                    'type' => 'openalex.import',
                    'data' => [
                        'cursor' => 'cursor-example',
                        'per_page' => 100,
                        'pages_remaining' => 3,
                    ],
                ], JSON_THROW_ON_ERROR),
            ]);

        $pdo = $this->createMock(PDO::class);

        $pdo
            ->expects(self::once())
            ->method('prepare')
            ->with(
                'SELECT id, queue, payload FROM failed_jobs WHERE id = :id'
            )
            ->willReturn($statement);

        $queue = new MySqlQueue($pdo);

        self::assertSame([
            'id' => 42,
            'queue' => 'default',
            'payload' => [
                'type' => 'openalex.import',
                'data' => [
                    'cursor' => 'cursor-example',
                    'per_page' => 100,
                    'pages_remaining' => 3,
                ],
            ],
        ], $queue->findFailed(42));
    }

}