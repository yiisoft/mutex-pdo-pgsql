<?php

declare(strict_types=1);

namespace Yiisoft\Mutex;

use PDO;

/**
 * PgsqlMutex implements mutex "lock" mechanism via PgSQL locks.
 */
final class PgsqlMutex implements MutexInterface
{
    use RetryAcquireTrait;

    private string $name;
    private PDO $connection;
    private bool $released = false;

    /**
     * @param string $name Mutex name.
     * @param PDO $connection PDO connection instance to use.
     */
    public function __construct(string $name, PDO $connection)
    {
        $this->name = $name;
        $this->connection = $connection;
        $driverName = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driverName !== 'pgsql') {
            throw new \InvalidArgumentException(
                'Connection must be configured to use PgSQL database. Got ' . $driverName . '.'
            );
        }
    }

    public function __destruct()
    {
        if (!$this->released) {
            $this->release();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see http://www.postgresql.org/docs/9.0/static/functions-admin.html
     */
    public function acquire(int $timeout = 0): bool
    {
        [$key1, $key2] = $this->getKeysFromName($this->name);

        return $this->retryAcquire($timeout, function () use ($key1, $key2) {
            $statement = $this->connection->prepare('SELECT pg_try_advisory_lock(:key1, :key2)');
            $statement->bindValue(':key1', $key1);
            $statement->bindValue(':key2', $key2);
            $statement->execute();

            if ($statement->fetchColumn()) {
                $this->released = false;
                return true;
            }

            return false;
        });
    }

    /**
     * {@inheritdoc}
     *
     * @see http://www.postgresql.org/docs/9.0/static/functions-admin.html
     */
    public function release(): void
    {
        [$key1, $key2] = $this->getKeysFromName($this->name);

        $statement = $this->connection->prepare('SELECT pg_advisory_unlock(:key1, :key2)');
        $statement->bindValue(':key1', $key1);
        $statement->bindValue(':key2', $key2);
        $statement->execute();

        if (!$statement->fetchColumn()) {
            throw new RuntimeExceptions("Unable to release lock \"$this->name\".");
        }

        $this->released = true;
    }

    /**
     * Converts a string into two 16 bit integer keys using the SHA1 hash function.
     *
     * @param string $name
     *
     * @return array contains two 16 bit integer keys
     */
    private function getKeysFromName(string $name): array
    {
        return array_values(unpack('n2', sha1($name, true)));
    }
}
