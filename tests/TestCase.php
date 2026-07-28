<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\Pgsql\Tests;

use PDO;
use ReflectionClass;
use Yiisoft\Mutex\Pgsql\PgsqlMutex;

use function md5;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    private ?PDO $connection = null;

    protected function tearDown(): void
    {
        $this->connection = null;

        parent::setUp();
    }

    protected function connection(): PDO
    {
        if ($this->connection === null) {
            $this->connection = new PDO(
                'pgsql:host=127.0.0.1;dbname=yiitest',
                'root',
                'root-password',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );
        }

        return $this->connection;
    }

    protected function isFreeLock(PgsqlMutex $mutex, string $name): bool
    {
        $locks = (new ReflectionClass($mutex))
            ->getParentClass()
            ->getStaticPropertyValue('currentProcessLocks');

        return !isset($locks[md5(PgsqlMutex::class . $name)]);
    }
}
