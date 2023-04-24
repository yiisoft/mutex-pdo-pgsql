<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\Pgsql;

use InvalidArgumentException;
use PDO;
use Yiisoft\Mutex\Mutex;

use function array_values;
use function sha1;
use function unpack;

/**
 * PgsqlMutex implements mutex "lock" mechanism via PostgreSQL locks.
 */
final class PgsqlMutex extends Mutex
{
    private array $lockKeys;

    /**
     * @param string $name Mutex name.
     * @param PDO $connection PDO connection instance to use.
     */
    public function __construct(string $name, private PDO $connection)
    {
        // Converts a string into two 16-bit integer keys using the SHA1 hash function.
        $this->lockKeys = array_values(unpack('n2', sha1($name, true)));

        /** @var string $driverName */
        $driverName = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driverName !== 'pgsql') {
            throw new InvalidArgumentException("PostgreSQL connection instance should be passed. Got \"$driverName\".");
        }

        parent::__construct(self::class, $name);
    }

    /**
     * {@inheritdoc}
     *
     * @see https://www.postgresql.org/docs/13/functions-admin.html
     */
    protected function acquireLock(int $timeout = 0): bool
    {
        $statement = $this->connection->prepare('SELECT pg_try_advisory_lock(:key1, :key2)');
        $statement->bindValue(':key1', $this->lockKeys[0]);
        $statement->bindValue(':key2', $this->lockKeys[1]);
        $statement->execute();

        return (bool) $statement->fetchColumn();
    }

    /**
     * {@inheritdoc}
     *
     * @see https://www.postgresql.org/docs/13/functions-admin.html
     */
    protected function releaseLock(): bool
    {
        $statement = $this->connection->prepare('SELECT pg_advisory_unlock(:key1, :key2)');
        $statement->bindValue(':key1', $this->lockKeys[0]);
        $statement->bindValue(':key2', $this->lockKeys[1]);
        $statement->execute();

        return (bool) $statement->fetchColumn();
    }
}
