# Upgrading Yii PostgreSQL PDO Mutex

## Full-width advisory lock keys

`PgsqlMutex` now derives two signed 32-bit PostgreSQL advisory-lock keys from the first eight bytes of the SHA-1
hash of the mutex name. Previously it used two unsigned 16-bit keys from the first four bytes. The larger
64-bit keyspace reduces accidental contention between unrelated mutex names; hash collisions are still possible.

This changes the PostgreSQL keys for existing mutex names. Workers using the old and new mappings do not
coordinate: they can hold different advisory locks for the same mutex name at the same time.

Before starting any workers with the new mapping, stop or drain **all** workers using the old mapping that
coordinate through these mutexes, and let them finish their protected operations and release their locks.
Include web processes, queue workers, scheduled jobs and other applications sharing the locks. Restart them
with the same mapping. Do not overlap old and new workers during a rolling deployment. Apply the same
coordinated transition when rolling back.

The public API is unchanged. No persistent database data needs migrating; the change affects session-level locks.
