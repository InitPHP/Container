# API Reference

The full public surface of `InitPHP\Container\Container` and the PSR-11 contract
it implements. Behavioural deep-dives are linked into the
[Core Usage](Autowiring.md) pages.

> The container requires PHP 8.2+.

---

## PSR-11 contract

`Container` implements `Psr\Container\ContainerInterface`, which declares the two
read methods every PSR-11 consumer relies on:

```php
namespace Psr\Container;

interface ContainerInterface
{
    public function get(string $id): mixed;
    public function has(string $id): bool;
}
```

Depend on `ContainerInterface` (not the concrete `Container`) in your service
signatures so callers can substitute any PSR-11 container without touching
consumer code.

---

## `Container`

```php
namespace InitPHP\Container;

use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    public function set(string $id, mixed $concrete = null): void;
    public function get(string $id): mixed;
    public function has(string $id): bool;
}
```

The container takes no constructor arguments:

```php
$container = new InitPHP\Container\Container();
```

### `set`

```php
public function set(string $id, mixed $concrete = null): void;
```

Registers an entry. The `$concrete` definition is stored and resolved **lazily**
the first time the entry is requested. When `$concrete` is `null`, the
identifier itself becomes the definition (the common case for autowiring a class
by its own name, or aliasing an interface).

Calling `set()` again with the same `$id` replaces the definition **and discards
any cached instance**, so the next `get()` rebuilds it.

How the definition is interpreted on resolution:

| `$concrete` | Resolved to |
| --- | --- |
| `null` | `$id` is used as the definition (then interpreted by the rows below). |
| `Closure` | The return value of `$concrete($container)`, cached. |
| existing class name (`string`) | An autowired instance of that class. |
| object / scalar / array / non-class string | The value itself, unchanged. |

```php
$container->set('app.name', 'InitPHP');                 // value
$container->set('pdo', fn ($c) => new PDO('sqlite::memory:')); // factory
$container->set(LoggerInterface::class, FileLogger::class);    // interface binding
$container->set(App\Service::class);                    // explicit class registration
```

Returns `void`. See [Binding & Factories](Binding-And-Factories.md).

### `get`

```php
public function get(string $id): mixed;
```

Returns the entry for `$id`, building and caching it on first access. Resolution
order:

1. Return the cached value if `$id` was resolved before.
2. If `$id` was registered with `set()`, build the definition and cache it.
3. If `$id` is an existing class name, autowire it and cache it.
4. Otherwise throw `NotFoundException`.

```php
$container->get('app.name');      // a registered value
$container->get(App\Service::class); // an autowired instance
```

**Throws:**

| Exception | When |
| --- | --- |
| [`NotFoundException`](Exceptions.md#notfoundexception) | `$id` is neither registered nor an existing class. |
| [`DependencyIsNotInstantiableException`](Exceptions.md#dependencyisnotinstantiableexception) | The target (or a required dependency) is an abstract class, an enum, or has a non-public constructor. |
| [`DependencyHasNoDefaultValueException`](Exceptions.md#dependencyhasnodefaultvalueexception) | A required constructor parameter cannot be autowired and has no default or nullable fallback. |
| [`CircularDependencyException`](Exceptions.md#circulardependencyexception) | The dependency graph contains a cycle. |

All four implement `Psr\Container\ContainerExceptionInterface`. See
[Resolution & Caching](Resolution-And-Caching.md) and [Exceptions](Exceptions.md).

### `has`

```php
public function has(string $id): bool;
```

Returns `true` when `get($id)` would not throw a `NotFoundException` — i.e. when
`$id` is a resolved entry, a registered definition, or an existing class name
(`class_exists`, with autoloading).

```php
$container->has('app.name');         // true once registered
$container->has(App\Service::class); // true (autowirable class)
$container->has('missing');          // false
```

A `true` result does **not** guarantee that resolution succeeds; building the
entry may still fail with another container exception (for example, a class that
needs a scalar). An **unbound interface** reports `false`, because
`class_exists()` does not report interfaces. See
[Resolution & Caching → The has() contract](Resolution-And-Caching.md#the-has-contract).

---

## Internals & extension

The container is intentionally closed for behavioural extension: the resolution
logic (`resolve`, `build`, dependency analysis) is `private`. The only protected
members are the two backing stores:

| Member | Type | Role |
| --- | --- | --- |
| `$definitions` | `array<string, mixed>` | Registered definitions keyed by identifier. |
| `$resolved` | `array<string, mixed>` | Build cache keyed by identifier. |

These are protected so a subclass can inspect or seed them, but they are
implementation detail — their shape may change in a minor release. Prefer
composition (wrap the container behind your own `ContainerInterface`
implementation) over subclassing if you need to alter behaviour.

---

## Exceptions

All exceptions live in `InitPHP\Container\Exception`.

| Class | Extends / implements | Thrown when |
| --- | --- | --- |
| `ContainerException` | implements `ContainerExceptionInterface` | Base class for all of the below. |
| `NotFoundException` | extends `ContainerException`, implements `NotFoundExceptionInterface` | `get()` cannot find the identifier. |
| `DependencyIsNotInstantiableException` | extends `ContainerException` | Target is an abstract class, an enum, or has a non-public constructor. |
| `DependencyHasNoDefaultValueException` | extends `ContainerException` | A required parameter cannot be resolved. |
| `CircularDependencyException` | extends `ContainerException` | The dependency graph contains a cycle. |

See [Exceptions](Exceptions.md) for messages, examples, and catching strategy.
