# Migration Guide

This guide is for early adopters who pulled the container as a **pre-release
`dev-main` snapshot** before its first tagged, PSR-11-compliant release. The
stable API tightened several behaviours that the early snapshot got wrong or left
loose. Most call sites need only small adjustments.

If you are installing the package for the first time, you can skip this page —
the rest of the wiki documents the current behaviour.

## TL;DR

| Area | Early snapshot | Now |
| --- | --- | --- |
| Minimum PHP | 7.4 | **8.1** in 1.x; **8.2** from 2.0.0 |
| `set()` return | returned the resolved value | returns **`void`** |
| `set()` timing | resolved the class **eagerly** | stores a definition, resolved **lazily** |
| `get()` on unknown id | returned the id string | throws **`NotFoundException`** |
| Closures passed to `set()` | stored and returned as-is | invoked as **lazy factories**, result cached |
| Resolution exceptions | implemented `NotFoundExceptionInterface` | implement **`ContainerExceptionInterface`** |
| `DependencyIsNotInstantiable` | class name without `Exception` suffix | renamed **`DependencyIsNotInstantiableException`** |
| Union/intersection typed params | could fatal | safely fall back to default / `null` |
| Circular dependencies | recursed until exhaustion | throw **`CircularDependencyException`** |

## Behavioural changes

### A modern PHP is now required

The library uses `mixed` types and modern reflection handling. The first tagged
release (1.0.0) required PHP `^8.1`; version 2.0.0 raises the
minimum to `^8.2`. Update your platform (and your own `composer.json`
constraint) accordingly.

### `set()` returns `void` and is lazy

The early snapshot built the class immediately inside `set()` and returned the
instance. The stable version stores a **definition** and builds it on first
`get()`.

```php
// Early snapshot
$service = $container->set(Service::class); // returned the instance

// Now
$container->set(Service::class);            // returns void
$service = $container->get(Service::class); // build happens here, lazily
```

If you relied on `set()`'s return value, switch to a `get()` immediately after,
or simply resolve when you actually need the service.

### `get()` throws on unknown identifiers

Previously, `get('something-unregistered')` silently returned the string
`'something-unregistered'`. That violated PSR-11. The stable version throws
[`NotFoundException`](Exceptions.md#notfoundexception):

```php
// Early snapshot
$container->get('typo.key'); // 'typo.key'  (silent, surprising)

// Now
$container->get('typo.key'); // throws NotFoundException
```

Guard with `has()` if an identifier may legitimately be absent:

```php
if ($container->has('optional.key')) {
    $value = $container->get('optional.key');
}
```

### Closures are now lazy factories

Passing a `Closure` to `set()` used to store the closure itself, so `get()`
handed the closure back uninvoked. Now a closure is treated as a **factory**: it
is invoked with the container on first `get()`, and its return value is cached.

```php
$container->set('pdo', fn ($c) => new PDO('sqlite::memory:'));

// Early snapshot
$container->get('pdo'); // the Closure object

// Now
$container->get('pdo'); // a PDO instance (closure invoked once)
```

If you genuinely stored a closure as a value and want it back verbatim, wrap it
so the definition is not itself a closure — for example return it from a factory:

```php
$callable = fn () => 'hi';
$container->set('callable', fn () => $callable); // get('callable') === $callable
```

### Resolution exceptions changed their PSR interface

`DependencyIsNotInstantiableException` and `DependencyHasNoDefaultValueException`
used to implement `NotFoundExceptionInterface`, which was semantically wrong —
they are build failures, not "missing entry" errors. They now extend
`ContainerException` and implement `ContainerExceptionInterface`.

```php
// If you previously caught build failures as "not found":
catch (\Psr\Container\NotFoundExceptionInterface $e) { /* ... */ }

// Catch the base container interface (or ContainerException) instead:
catch (\Psr\Container\ContainerExceptionInterface $e) { /* ... */ }
```

`NotFoundException` still implements `NotFoundExceptionInterface`, so genuine
"missing entry" catches are unaffected. See [Exceptions](Exceptions.md).

### Exception class renamed

`InitPHP\Container\Exception\DependencyIsNotInstantiable` →
`InitPHP\Container\Exception\DependencyIsNotInstantiableException`.

Update any `use`/`catch` referencing the old name. A new base
`ContainerException` and a `CircularDependencyException` were also added.

### Union/intersection types no longer fatal

A constructor parameter typed as a union (`A|B`) or intersection (`A&B`) is no
longer passed to reflection methods that assume a single named type. Such
parameters now fall back to their default value or `null`. See
[Autowiring → Union and intersection types](Autowiring.md#union-and-intersection-types).

### Circular dependencies are detected

A dependency cycle that previously recursed until PHP exhausted memory now throws
[`CircularDependencyException`](Exceptions.md#circulardependencyexception) early with
a clear message.

## Quick audit checklist

- [ ] Bump your platform constraint to PHP `^8.2`.
- [ ] Replace any use of `set()`'s return value with a following `get()`.
- [ ] Wrap optional lookups in `has()` so they don't hit `NotFoundException`.
- [ ] Re-check anywhere you passed a closure to `set()` — it now runs as a factory.
- [ ] Update `catch` blocks: build failures are `ContainerExceptionInterface`, not `NotFoundExceptionInterface`.
- [ ] Rename `DependencyIsNotInstantiable` → `DependencyIsNotInstantiableException`.

## Related pages

- [Resolution & Caching](Resolution-And-Caching.md) — the current lifecycle in full.
- [Exceptions](Exceptions.md) — the current hierarchy and catching strategy.
- [Binding & Factories](Binding-And-Factories.md) — how definitions are interpreted now.
