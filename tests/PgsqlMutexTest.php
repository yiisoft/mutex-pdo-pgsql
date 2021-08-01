<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\Tests;

use PDO;
use Yiisoft\Mutex\PgsqlMutex;

/**
 * Class PgsqlMutexTest.
 *
 * @group mutex
 * @group db
 * @group pgsql
 */
final class PgsqlMutexTest
{
    use MutexTestTrait;

    /**
     * @return PgsqlMutex
     */
    protected function createMutex()
    {
        return new PgsqlMutex($this->getConnection());
    }

    private function getConnection(): PDO
    {
        // TODO: create MySQL connection here
    }
}
