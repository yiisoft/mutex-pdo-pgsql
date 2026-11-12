# Upgrading Yii PostgreSQL PDO Mutex

This file contains the upgrade notes. These notes highlight changes that could break your
application when you upgrade the package from one version to another.

> **Important!** The following upgrading instructions are cumulative. That is, if you want
> to upgrade from version A to version C and there is version B between A and C, you need
> to follow the instructions for both A and B.

## Upgrade from 1.1 to 1.1.1

1.1.1 is under development; the version number is taken from [CHANGELOG.md](CHANGELOG.md).

### Full-width advisory lock keys

`PgsqlMutex` now derives two signed 32-bit PostgreSQL advisory-lock keys from the first eight
bytes of the SHA-1 hash of the mutex name, instead of two unsigned 16-bit keys from the first
four bytes. The larger 64-bit keyspace reduces accidental contention between unrelated mutex
names; hash collisions are still possible.

The public API is unchanged. No persistent database data needs migrating; the change affects
session-level advisory locks.

#### Required coordinated transition

The PostgreSQL keys for existing mutex names change. Old and new mappings do not coordinate:
workers on the old mapping and workers on the new mapping can hold **different** advisory locks
for the same mutex name at the same time, so exclusion for the same name is broken while both
mappings run.

Before starting any process with the new mapping, stop or drain **all** old-mapping processes
that coordinate through these mutexes and let them finish their protected operations and release
their locks. This includes web processes, queue workers, scheduled jobs and other applications
sharing the same locks. Do not overlap old and new mappings during a rolling deployment.

When rolling back to the old mapping, apply the same transition in reverse: stop or drain all
new-mapping processes first, then start the old ones. Never run old and new mappings together
while same-name exclusion is required.
