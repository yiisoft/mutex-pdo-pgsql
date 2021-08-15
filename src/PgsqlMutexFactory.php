<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\Pgsql;

use PDO;
use Yiisoft\Mutex\MutexFactory;
use Yiisoft\Mutex\MutexInterface;

/**
 * Allows creating {@see PgsqlMutex} mutex objects.
 */
final class PgsqlMutexFactory extends MutexFactory
{
    private PDO $connection;

    /**
     * @param PDO $connection PDO connection instance to use.
     */
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function create(string $name): MutexInterface
    {
        return new PgsqlMutex($name, $this->connection);
    }
}
