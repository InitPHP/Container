# InitPHP Container

Minimal [PSR-11](https://www.php-fig.org/psr/psr-11/) dependency injection container with reflection-based autowiring.

[![Latest Stable Version](https://poser.pugx.org/initphp/container/v/stable)](https://packagist.org/packages/initphp/container)
[![Total Downloads](https://poser.pugx.org/initphp/container/downloads)](https://packagist.org/packages/initphp/container)
[![License](https://poser.pugx.org/initphp/container/license)](https://packagist.org/packages/initphp/container)
[![PHP Version Require](https://poser.pugx.org/initphp/container/require/php)](https://packagist.org/packages/initphp/container)

The container resolves entries lazily. You can register values, factories and class bindings explicitly, or let the container autowire a class straight from its name by reading its constructor signature through reflection. Every resolved entry is cached, so the container behaves as a shared (singleton) registry.

## Requirements

- PHP 8.1 or higher
- [`psr/container`](https://packagist.org/packages/psr/container) `^2.0`

## Installation

```bash
composer require initphp/container
```

## Quick Start

```php
require_once 'vendor/autoload.php';

use InitPHP\Container\Container;

class Mailer
{
}

class UserService
{
    public function __construct(public Mailer $mailer)
    {
    }
}

$container = new Container();

// No registration needed: the container reads UserService's constructor,
// builds the Mailer dependency automatically and injects it.
$service = $container->get(UserService::class);

var_dump($service instanceof UserService); // bool(true)
var_dump($service->mailer instanceof Mailer); // bool(true)
```

## Usage

### Autowiring

When you call `get()` with an existing class name, the container instantiates it and recursively resolves every class-typed constructor argument:

```php
$service = $container->get(UserService::class);
```

The resolved instance is cached. Asking for the same identifier again returns the exact same object:

```php
$container->get(Mailer::class) === $container->get(Mailer::class); // true
```

### Storing values

`set()` accepts any value. Scalars, arrays and objects are returned as they were stored:

```php
$container->set('app.name', 'InitPHP');
$container->set('config', ['debug' => true]);
$container->set('logger', new FileLogger('/var/log/app.log'));

$container->get('app.name'); // 'InitPHP'
$container->get('config');   // ['debug' => true]
$container->get('logger');   // the same FileLogger instance
```

### Factories (closures)

Register a `Closure` to build an entry lazily. The closure receives the container and runs only once, the first time the entry is requested:

```php
use Psr\Container\ContainerInterface;

$container->set('pdo', function (ContainerInterface $c) {
    return new PDO('sqlite::memory:');
});

$pdo = $container->get('pdo'); // closure runs here, result is cached
```

### Binding interfaces to implementations

Bind an interface (or any identifier) to a concrete class name so that both direct lookups and autowired dependencies resolve to the implementation:

```php
interface LoggerInterface
{
}

class FileLogger implements LoggerInterface
{
}

class Report
{
    public function __construct(public LoggerInterface $logger)
    {
    }
}

$container->set(LoggerInterface::class, FileLogger::class);

$container->get(LoggerInterface::class);      // FileLogger instance
$container->get(Report::class)->logger;       // the same FileLogger instance
```

### Checking for an entry

`has()` returns `true` when `get()` would not throw a `NotFoundException` — that is, the identifier is a registered entry or an existing class name:

```php
$container->has('app.name');         // true after set()
$container->has(UserService::class); // true (autowirable class)
$container->has('missing');          // false
```

## How resolution works

`get($id)` resolves in this order:

1. Return the cached instance if `$id` was resolved before.
2. If `$id` was registered with `set()`, build it (invoke the closure, instantiate the class name, or return the stored value) and cache it.
3. If `$id` is an existing class name, autowire it and cache it.
4. Otherwise throw `NotFoundException`.

Constructor parameters are resolved as follows: a class-typed parameter is fetched from the container; otherwise its default value is used; otherwise `null` is supplied for nullable parameters. If none of these apply, resolution fails.

## Exceptions

All exceptions live in `InitPHP\Container\Exception` and implement the relevant PSR-11 interface.

| Exception | Implements | Thrown when |
| --- | --- | --- |
| `NotFoundException` | `Psr\Container\NotFoundExceptionInterface` | The identifier is neither registered nor an existing class. |
| `DependencyIsNotInstantiableException` | `Psr\Container\ContainerExceptionInterface` | The target is an interface, abstract class, or has a non-public constructor. |
| `DependencyHasNoDefaultValueException` | `Psr\Container\ContainerExceptionInterface` | A constructor parameter cannot be autowired and has no default or nullable fallback. |
| `CircularDependencyException` | `Psr\Container\ContainerExceptionInterface` | A class depends on itself directly or through a cycle. |

`ContainerException` is the base class for `NotFoundException` and the three resolution exceptions, so you can catch every container error with a single `catch (ContainerException $e)`.

## Documentation

In-depth guides with runnable examples live in the [`docs/`](./docs) directory:

- [Getting Started](./docs/getting-started.md)
- [Autowiring](./docs/autowiring.md)
- [Binding & Factories](./docs/binding-and-factories.md)
- [Exceptions & Error Handling](./docs/exceptions.md)
- [Limitations](./docs/limitations.md)

## Testing

```bash
composer test      # PHPUnit
composer stan      # PHPStan (max level)
composer cs-check  # PHP-CS-Fixer (dry run)
```

## Contributing

Contributions are welcome. Please read the org-wide [CONTRIBUTING guide](https://github.com/InitPHP/.github/blob/main/CONTRIBUTING.md) before opening a pull request.

## Credits

- [Muhammet ŞAFAK](https://www.muhammetsafak.com.tr) <<info@muhammetsafak.com.tr>>

## License

Released under the [MIT License](./LICENSE).
