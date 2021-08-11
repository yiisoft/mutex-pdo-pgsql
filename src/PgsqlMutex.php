<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\Pgsql;

use InvalidArgumentException;
use PDO;
use RuntimeException;
use Yiisoft\Mutex\MutexInterface;
use Yiisoft\Mutex\RetryAcquireTrait;

use function array_values;
use function sha1;
use function unpack;

/**
 * PgsqlMutex implements mutex "lock" mechanism via PostgresSQL locks.
 */
final class PgsqlMutex implements MutexInterface
{
    use RetryAcquireTrait;

    private string $name;
    private PDO $connection;

    /**
     * @param string $name Mutex name.
     * @param PDO $connection PDO connection instance to use.
     */
    public function __construct(string $name, PDO $connection)
    {
        $this->name = $name;
        $this->connection = $connection;

        /** @var string $driverName */
        $driverName = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driverName !== 'pgsql') {
            throw new InvalidArgumentException("MySQL connection instance should be passed. Got $driverName.");
        }
    }

    public function __destruct()
    {
        $this->release();
    }

    /**
     * {@inheritdoc}
     *
     * @see https://www.postgresql.org/docs/13/functions-admin.html
     */
    public function acquire(int $timeout = 0): bool
    {
        [$key1, $key2] = $this->getKeysFromName($this->name);

        return $this->retryAcquire($timeout, function () use ($key1, $key2) {
            $statement = $this->connection->prepare('SELECT pg_try_advisory_lock(:key1, :key2)');
            $statement->bindValue(':key1', $key1);
            $statement->bindValue(':key2', $key2);
            $statement->execute();

            return (bool) $statement->fetchColumn();
        });
    }

    /**
     * {@inheritdoc}
     *
     * @see https://www.postgresql.org/docs/13/functions-admin.html
     */
    public function release(): void
    {
        [$key1, $key2] = $this->getKeysFromName($this->name);

        $statement = $this->connection->prepare('SELECT pg_advisory_unlock(:key1, :key2)');
        $statement->bindValue(':key1', $key1);
        $statement->bindValue(':key2', $key2);
        $statement->execute();

        if (!$statement->fetchColumn()) {
            throw new RuntimeException("Unable to release lock \"$this->name\".");
        }
    }

    /**
     * Converts a string into two 16-bit integer keys using the SHA1 hash function.
     *
     * @param string $name The string to convert.
     *
     * @return array Contains two 16-bit integer keys.
     */
    private function getKeysFromName(string $name): array
    {
        return array_values(unpack('n2', sha1($name, true)));
    }
}
