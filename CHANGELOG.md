# Yii Postgres Mutex Change Log

## 1.1.1 under development

- Bug #17: Use the full 64-bit PostgreSQL advisory-lock keyspace to reduce contention between unrelated mutex names;
  require a coordinated worker rollout as described in [UPGRADE.md](UPGRADE.md) (@batyrmastyr, @iliaal, @samdark)

## 1.1.0 October 19, 2021

- Chg #19: Update the version of the `yiisoft/mutex` package to `1.1` in the `require` section of `composer.json` (devanych)

## 1.0.0 August 18, 2021

- Initial release.
