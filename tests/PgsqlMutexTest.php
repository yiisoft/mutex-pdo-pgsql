<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\Pgsql\Tests;

use InvalidArgumentException;
use PDO;
use Yiisoft\Mutex\Pgsql\PgsqlMutex;

use function microtime;

final class PgsqlMutexTest extends TestCase
{
    public function testMutexAcquire(): void
    {
        $mutex = $this->createMutex('testMutexAcquire');

        $this->assertTrue($mutex->acquire());
        $mutex->release();
    }

    public function testThatMutexLockIsWorking(): void
    {
        $mutexOne = $this->createMutex('testThatMutexLockIsWorking');
        $mutexTwo = $this->createMutex('testThatMutexLockIsWorking');

        $this->assertTrue($mutexOne->acquire());
        $this->assertFalse($mutexTwo->acquire());
        $mutexOne->release();
        $mutexTwo->release();

        $this->assertTrue($mutexTwo->acquire());
        $mutexTwo->release();
    }

    public function testThatMutexLockIsWorkingOnTheSameComponent(): void
    {
        $mutex = $this->createMutex('testThatMutexLockIsWorkingOnTheSameComponent');

        $this->assertTrue($mutex->acquire());
        $this->assertFalse($mutex->acquire());

        $mutex->release();
        $mutex->release();
    }

    public function testTimeout(): void
    {
        $mutexName = __METHOD__;
        $mutexOne = $this->createMutex($mutexName);
        $mutexTwo = $this->createMutex($mutexName);

        $this->assertTrue($mutexOne->acquire());
        $microtime = microtime(true);
        $this->assertFalse($mutexTwo->acquire(1));
        $diff = microtime(true) - $microtime;
        $this->assertTrue($diff >= 1 && $diff < 2);
        $mutexOne->release();
        $mutexTwo->release();
    }

    public function testFreeLock(): void
    {
        $mutexName = 'testFreeLock';
        $mutex = $this->createMutex($mutexName);

        $mutex->acquire();
        $this->assertFalse($this->isFreeLock($mutex, $mutexName));

        $mutex->release();
        $this->assertTrue($this->isFreeLock($mutex, $mutexName));
    }

    public function testDestruct(): void
    {
        $mutexName = 'testDestruct';
        $mutex = $this->createMutex($mutexName);

        $this->assertTrue($mutex->acquire());
        $this->assertFalse($this->isFreeLock($mutex, $mutexName));

        $observer = $this->createConnection();
        try {
            $statement = $observer->prepare('SELECT pg_try_advisory_lock(-1579030975, -145821831)');
            $statement->execute();
            $this->assertFalse($statement->fetchColumn());

            unset($mutex);

            $statement->execute();
            $this->assertTrue($statement->fetchColumn());
        } finally {
            $observer->query('SELECT pg_advisory_unlock_all()');
        }
    }

    public static function prepareModeProvider(): array
    {
        return [
            'native prepares' => [false],
            'emulated prepares' => [true],
        ];
    }

    /**
     * @dataProvider prepareModeProvider
     */
    public function testFullWidthLockExcludesAnotherSessionUntilRelease(bool $emulatePrepares): void
    {
        $this->connection()->setAttribute(PDO::ATTR_EMULATE_PREPARES, $emulatePrepares);
        $mutex = $this->createMutex('app:job:send-mail');
        $observer = $this->createConnection();

        try {
            $this->assertTrue($mutex->acquire());
            $statement = $observer->prepare('SELECT pg_try_advisory_lock(1132950929, -43075096)');
            $statement->execute();
            $this->assertFalse($statement->fetchColumn());

            $mutex->release();

            $statement->execute();
            $this->assertTrue($statement->fetchColumn());
        } finally {
            $mutex->release();
            $observer->query('SELECT pg_advisory_unlock_all()');
        }
    }

    public function testConstructorFailure(): void
    {
        $connection = $this->createConfiguredMock(PDO::class, ['getAttribute' => 'mysql']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PostgreSQL connection instance should be passed. Got "mysql".');

        new PgsqlMutex('testConstructorFailure', $connection);
    }

    private function createMutex(string $name): PgsqlMutex
    {
        return new PgsqlMutex($name, $this->connection());
    }
}
