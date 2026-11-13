<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\Pgsql\Tests;

use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Yiisoft\Mutex\Pgsql\PgsqlMutex;

final class PgsqlMutexKeyTest extends TestCase
{
    public static function nameProvider(): array
    {
        return [
            'positive and negative' => ['app:job:send-mail', 1132950929, -43075096],
            'negative and positive' => ['', -633756690, 1584089869],
            'both negative' => ['a', -2030574537, -89806852],
        ];
    }

    /**
     * @dataProvider nameProvider
     */
    public function testAcquireAndReleaseUseFullWidthKeys(string $name, int $key1, int $key2): void
    {
        $keys = [':key1' => $key1, ':key2' => $key2];
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->exactly(4))
            ->method('bindValue')
            ->willReturnCallback(function (string $parameter, $value) use ($keys): bool {
                $this->assertSame($keys[$parameter], $value);
                return true;
            });
        $statement->expects($this->exactly(2))->method('execute')->willReturn(true);
        $statement->expects($this->exactly(2))->method('fetchColumn')->willReturn(true);
        $connection = $this->createMock(PDO::class);
        $connection->method('getAttribute')->with(PDO::ATTR_DRIVER_NAME)->willReturn('pgsql');
        $connection->expects($this->exactly(2))->method('prepare')->willReturnMap([
            ['SELECT pg_try_advisory_lock(:key1, :key2)', [], $statement],
            ['SELECT pg_advisory_unlock(:key1, :key2)', [], $statement],
        ]);
        $mutex = new PgsqlMutex($name, $connection);

        $this->assertTrue($mutex->acquire());
        $mutex->release();
    }
}
