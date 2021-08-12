<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\Pgsql\Tests;

use InvalidArgumentException;
use PDO;
use Yiisoft\Mutex\Pgsql\PgsqlMutex;

use function array_values;
use function microtime;
use function sha1;
use function unpack;

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

        unset($mutex);

        [$key1, $key2] = array_values(unpack('n2', sha1($mutexName, true)));
        $statement = $this->connection()->prepare('SELECT pg_advisory_unlock(:key1, :key2)');
        $statement->bindValue(':key1', $key1);
        $statement->bindValue(':key2', $key2);
        $statement->execute();

        $this->assertFalse((bool) $statement->fetchColumn());
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
