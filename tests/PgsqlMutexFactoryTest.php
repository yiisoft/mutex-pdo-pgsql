<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\Pgsql\Tests;

use Yiisoft\Mutex\MutexInterface;
use Yiisoft\Mutex\Pgsql\PgsqlMutex;
use Yiisoft\Mutex\Pgsql\PgsqlMutexFactory;

final class PgsqlMutexFactoryTest extends TestCase
{
    public function testCreateAndAcquire(): void
    {
        $mutexName = 'testCreateAndAcquire';
        $factory = new PgsqlMutexFactory($this->connection());
        $mutex = $factory->createAndAcquire($mutexName);

        $this->assertInstanceOf(MutexInterface::class, $mutex);
        $this->assertInstanceOf(PgsqlMutex::class, $mutex);

        $this->assertFalse($this->isFreeLock($mutex, $mutexName));
        $this->assertFalse($mutex->acquire());
        $mutex->release();

        $this->assertTrue($this->isFreeLock($mutex, $mutexName));
        $this->assertTrue($mutex->acquire());
        $this->assertFalse($this->isFreeLock($mutex, $mutexName));

        $mutex->release();
        $this->assertTrue($this->isFreeLock($mutex, $mutexName));
    }
}
