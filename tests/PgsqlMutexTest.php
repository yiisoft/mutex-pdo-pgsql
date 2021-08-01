<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\Tests;

use PDO;
use Yiisoft\Mutex\MutexInterface;
use Yiisoft\Mutex\PgsqlMutex;

final class PgsqlMutexTest
{
    use MutexTestTrait;

    /**
     * @return PgsqlMutex
     */
    protected function createMutex(): MutexInterface
    {
        return new PgsqlMutex($this->getConnection());
    }

    private function getConnection(): PDO
    {
        // TODO: create MySQL connection here
    }
}
