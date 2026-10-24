<?php

declare(strict_types=1);

namespace OpenPulse\Tests\Unit\Repositories;

use InvalidArgumentException;
use OpenPulse\Repositories\MySqlOpenAlexSearchRepository;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class MySqlOpenAlexSearchRepositoryTest extends TestCase
{
    public function testFirstPageReturnsCursorWhenMoreResultsExist(): void
    {
        $repository = $this->createRepository([
            ['id' => 10, 'title' => 'First result'],
            ['id' => 9, 'title' => 'Second result'],
        ]);

        $result = $repository->search(perPage: 1);

        self::assertCount(1, $result['data']);
        self::assertSame(10, $result['data'][0]['id']);
        self::assertTrue($result['has_more']);
        self::assertNotNull($result['next_cursor']);
        self::assertSame(1, $result['per_page']);
    }

    public function testLastPageHasNoNextCursor(): void
    {
        $repository = $this->createRepository([
            ['id' => 10, 'title' => 'Only result'],
        ]);

        $result = $repository->search(perPage: 10);

        self::assertCount(1, $result['data']);
        self::assertFalse($result['has_more']);
        self::assertNull($result['next_cursor']);
    }

    public function testQueryUsesKeysetPaginationWithoutOffset(): void
    {
        $repository = $this->createRepository(
            [
                ['id' => 10, 'title' => 'First result'],
                ['id' => 9, 'title' => 'Second result'],
            ],
            static function (string $sql): bool {
                self::assertStringNotContainsString('OFFSET', strtoupper($sql));
                self::assertStringContainsString('LIMIT :limit', $sql);
                self::assertStringContainsString('ORDER BY id DESC', $sql);

                return true;
            },
        );

        $repository->search(perPage: 1);
    }

    public function testCursorCannotBeReusedWithDifferentSortOrder(): void
    {
        $repository = $this->createRepository([
            ['id' => 10, 'title' => 'First result'],
            ['id' => 9, 'title' => 'Second result'],
        ]);

        $firstPage = $repository->search(
            sort: [['field' => 'title', 'direction' => 'ASC']],
            perPage: 1,
        );

        self::assertNotNull($firstPage['next_cursor']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Cursor does not match the requested sort order.',
        );

        $repository->search(
            sort: [['field' => 'title', 'direction' => 'DESC']],
            cursor: $firstPage['next_cursor'],
            perPage: 1,
        );
    }

    public function testInvalidCursorIsRejected(): void
    {
        $repository = $this->createRepository([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid pagination cursor.');

        $repository->search(cursor: 'not-a-valid-cursor');
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param callable(string): bool|null $sqlAssertion
     */
    private function createRepository(
        array $rows,
        ?callable $sqlAssertion = null,
    ): MySqlOpenAlexSearchRepository {
        $statement = $this->createMock(PDOStatement::class);

        $statement->method('bindValue')->willReturn(true);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetchAll')->willReturn($rows);

        $pdo = $this->createMock(PDO::class);

        $prepare = $pdo->expects(self::any())
            ->method('prepare')
            ->willReturn($statement);

        if ($sqlAssertion !== null) {
            $prepare->with(self::callback($sqlAssertion));
        }

        return new MySqlOpenAlexSearchRepository($pdo);
    }
}