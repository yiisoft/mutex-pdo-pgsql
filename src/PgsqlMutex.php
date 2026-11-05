<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\Pgsql;

use InvalidArgumentException;
use PDO;
use Yiisoft\Mutex\Mutex;

use function array_map;
use function array_values;
use function sha1;
use function unpack;

/**
 * PgsqlMutex implements mutex "lock" mechanism via PostgreSQL locks.
 */
final class PgsqlMutex extends Mutex
{
    private array $lockKeys;
    private PDO $connection;

    /**
     * @param string $name Mutex name.
     * @param PDO $connection PDO connection instance to use.
     */
    public function __construct(string $name, PDO $connection)
    {
        /**
         * Decode the first eight hash bytes in big-endian order and normalize to PostgreSQL signed int4 keys.
         * On 32-bit PHP, unpack() already returns signed integers for words with the high bit set.
         *
         * @psalm-suppress PossiblyFalseArgument `unpack()` never returns `false` here because `sha1(...)` with raw
         * output always returns a 20-byte string, enough for the eight bytes required by the 'N2' format.
         */
        $this->lockKeys = array_values(array_map(
            static fn(int $key): int => $key > 0x7FFFFFFF ? $key - 0x100000000 : $key,
            unpack('N2', sha1($name, true)),
        ));
        $this->connection = $connection;

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
