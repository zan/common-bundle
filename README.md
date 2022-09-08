# common-bundle
Common code shared among many Symfony applications

### Running tests

```bash
$ vendor/bin/phpunit tests/
```

```bash
vendor/bin/phpunit tests/Util/ZanArrayTest.php --filter testCreateFromStringHandlesArrays
```

### Code quality

Before pushing:

```bash
vendor/bin/phpstan analyse
```