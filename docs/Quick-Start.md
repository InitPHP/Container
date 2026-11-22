# Quick Start

A five-minute tour of the public API. Every snippet runs as-is once you have
`require 'vendor/autoload.php'` and `use InitPHP\Container\Container;` at the top.

The container exposes three methods: `set()` to register, `get()` to retrieve,
and `has()` to check. That is the whole surface.

## Create a container

```php
use InitPHP\Container\Container;

$container = new Container();
```

No constructor arguments, no configuration, no global state.

## Autowire a class

Ask for any existing class by name and the container builds it, resolving each
constructor dependency for you:

```php
class Engine {}

class Car
{
    public function __construct(public Engine $engine) {}
}

$car = $container->get(Car::class);

$car instanceof Car;            // true
$car->engine instanceof Engine; // true
```

No registration is required for plain classes. See [Autowiring](Autowiring.md) for
the full ruleset.

## Store a value

`set()` accepts any value. Scalars, arrays and objects come back exactly as you
stored them:

```php
$container->set('app.name', 'InitPHP');
$container->set('config', ['debug' => true]);
$container->set('clock', new DateTimeImmutable('2026-01-01'));

$container->get('app.name'); // 'InitPHP'
$container->get('config');   // ['debug' => true]
$container->get('clock');    // the same DateTimeImmutable instance
```

## Register a factory

Pass a `Closure` to build an entry lazily. It receives the container and runs
only once — the first time the entry is requested:

```php
use Psr\Container\ContainerInterface;

$container->set('pdo', function (ContainerInterface $c) {
    return new PDO('sqlite::memory:');
});

$pdo = $container->get('pdo');       // the closure runs here
$container->get('pdo') === $pdo;     // true — the result is cached
```

## Bind an interface

Bind an interface to a concrete class so both direct lookups and autowired
dependencies resolve to the implementation:

```php
interface LoggerInterface {}
class FileLogger implements LoggerInterface {}

class Report
{
    public function __construct(public LoggerInterface $logger) {}
}

$container->set(LoggerInterface::class, FileLogger::class);

$container->get(LoggerInterface::class);   // FileLogger instance
$container->get(Report::class)->logger;    // the same FileLogger instance
```

## Check for an entry

`has()` returns `true` whenever `get()` would not throw a `NotFoundException`:

```php
$container->set('app.name', 'InitPHP');

$container->has('app.name');     // true (registered)
$container->has(Car::class);     // true (autowirable class)
$container->has('missing');      // false
```

## Everything is cached

Each resolved entry is stored and reused, so the container behaves as a registry
of shared instances:

```php
$container->get(Engine::class) === $container->get(Engine::class); // true
```

If you need a fresh object each time, build it inside a closure where you call
`new` explicitly, or instantiate it directly without the container. See
[Resolution & Caching](Resolution-And-Caching.md).

## Where to go next

- [Autowiring](Autowiring.md) — how constructor dependencies are resolved.
- [Binding & Factories](Binding-And-Factories.md) — values, closures and interfaces in depth.
- [Resolution & Caching](Resolution-And-Caching.md) — the exact `get` / `has` / `set` lifecycle.
- [API Reference](API-Reference.md) — the exhaustive method list.
