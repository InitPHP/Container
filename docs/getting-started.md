# Getting Started

InitPHP Container is a small [PSR-11](https://www.php-fig.org/psr/psr-11/) container. It stores entries you register and can also build classes for you automatically through reflection. This guide covers installation and the two methods that make up the public API: `get()` and `has()`, plus the `set()` registration method.

## Installation

```bash
composer require initphp/container
```

Requires PHP 8.1+ and `psr/container ^2.0` (pulled in automatically).

## Creating a container

```php
require_once 'vendor/autoload.php';

use InitPHP\Container\Container;

$container = new Container();
```

The container takes no constructor arguments and holds no global state. Create as many as you need.

## The public API

The container implements `Psr\Container\ContainerInterface`, which defines two methods:

```php
public function get(string $id): mixed;
public function has(string $id): bool;
```

On top of those, the container adds one registration method:

```php
public function set(string $id, mixed $concrete = null): void;
```

### `set()` — register an entry

```php
// Store a value.
$container->set('app.name', 'InitPHP');

// Store an object.
$container->set('clock', new DateTimeImmutable());

// Register a class to be built on demand.
$container->set(App\Service::class);

// Bind an identifier to a class name.
$container->set(App\LoggerInterface::class, App\FileLogger::class);

// Register a lazy factory.
$container->set('pdo', fn () => new PDO('sqlite::memory:'));
```

When the second argument is omitted, the identifier itself becomes the definition. Calling `set()` again with the same identifier replaces the previous definition and clears any cached instance.

### `get()` — retrieve an entry

```php
$name = $container->get('app.name');     // 'InitPHP'
$service = $container->get(App\Service::class);
```

If the identifier was never registered but is an existing class name, the container autowires it. If it is neither, a `NotFoundException` is thrown. See [Autowiring](./autowiring.md).

### `has()` — check for an entry

```php
$container->has('app.name');          // true once registered
$container->has(App\Service::class);  // true: it is an autowirable class
$container->has('not-registered');    // false
```

`has()` returns `true` whenever `get()` would not throw a `NotFoundException`.

## Entries are cached

Every entry the container resolves is cached and reused:

```php
$a = $container->get(App\Service::class);
$b = $container->get(App\Service::class);

$a === $b; // true
```

This makes the container behave as a registry of shared instances. If you need a fresh object every time, build it yourself inside a closure and create the instance there explicitly, or instantiate the class directly without the container.

## Next steps

- [Autowiring](./autowiring.md) — how constructor dependencies are resolved.
- [Binding & Factories](./binding-and-factories.md) — interfaces, closures and values.
- [Exceptions & Error Handling](./exceptions.md) — what can go wrong and why.
- [Limitations](./limitations.md) — what this container intentionally does not do.
