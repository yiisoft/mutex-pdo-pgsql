<?php

namespace Yiisoft\Mutex\Tests;

use Yiisoft\Mutex\PgsqlMutex;

/**
 * Class PgsqlMutexTest.
 *
 * @group mutex
 * @group db
 * @group pgsql
 */
class PgsqlMutexTest
{
    use MutexTestTrait;

    /**
     * @return PgsqlMutex
     */
    protected function createMutex()
    {
        return new PgsqlMutex($this->getConnection());
    }

    private function getConnection()
    {
        // TODO: create MySQL connection here
    }
}
