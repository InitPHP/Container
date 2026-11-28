# Installation

## Requirements

| | |
| --- | --- |
| PHP | `^8.2` (tested on 8.2 – 8.6) |
| Extensions | None |
| Runtime dependencies | [`psr/container`](https://packagist.org/packages/psr/container) `^2.0` |

The only runtime dependency is the PSR-11 interface package, which Composer
installs automatically. Development dependencies (PHPUnit, PHPStan,
PHP-CS-Fixer) live under `require-dev` and are **not** installed when you
depend on `initphp/container` from another project.

## Install via Composer

```bash
composer require initphp/container
```

The classes are autoloaded under the `InitPHP\Container` namespace via PSR-4:

```json
{
    "autoload": {
        "psr-4": {
            "InitPHP\\Container\\": "src/"
        }
    }
}
```

Because the package declares `provide: { "psr/container-implementation": "2.0" }`,
any library that requires the virtual `psr/container-implementation` package is
satisfied by installing `initphp/container`.

## Verifying the install

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use InitPHP\Container\Container;

$container = new Container();

var_dump($container instanceof \Psr\Container\ContainerInterface); // bool(true)
```

If the autoloader cannot locate the class, double-check that you ran
`composer install` (or `composer dump-autoload`) and that your script requires
`vendor/autoload.php` before instantiating the container.

## Supported PHP versions in CI

Every push and pull request runs the test matrix against:

- PHP 8.2
- PHP 8.3
- PHP 8.4
- PHP 8.5
- PHP 8.6

…each with both the lowest and highest allowed dependency versions. A separate
job runs PHPStan (`max` level) and PHP-CS-Fixer (PER-CS). If you discover a
regression on a supported version, please
[file an issue](https://github.com/InitPHP/Container/issues/new) and include the
PHP version reported by `php -v`.

## Next steps

- Take the five-minute tour in [Quick Start](Quick-Start.md).
- Learn how class dependencies are resolved in [Autowiring](Autowiring.md).
- See every registration style in [Binding & Factories](Binding-And-Factories.md).
