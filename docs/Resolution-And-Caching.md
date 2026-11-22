# Resolution & Caching

This page describes exactly how the container turns an identifier into a value,
how `has()` decides what exists, and why every entry behaves as a shared
(singleton) instance. If you have read [Autowiring](Autowiring.md) and
[Binding & Factories](Binding-And-Factories.md), this is the mental model that ties
them together.

## The `get()` algorithm

`get($id)` resolves in this strict order:

1. **Cache hit** — if `$id` was resolved before, return the cached value.
2. **Registered definition** — if `$id` was registered with `set()`, build it
   (invoke the closure, autowire the class name, or return the stored value),
   cache the result, and return it.
3. **Autowirable class** — if `$id` is an existing class name (`class_exists`),
   autowire it, cache the instance, and return it.
4. **Not found** — otherwise throw
   [`NotFoundException`](Exceptions.md#notfoundexception).

```php
use InitPHP\Container\Container;

$container = new Container();

$container->set('greeting', 'hello');

$container->get('greeting');        // step 2 → 'hello'
$container->get(DateTime::class);   // step 3 → autowired DateTime
$container->get('nope');            // step 4 → NotFoundException
```

A registered definition always wins over autowiring. If you `set()` a class name
to an alternative implementation, that binding takes precedence over building the
requested class directly.

## Building a definition

Step 2 above hands the stored definition to an internal builder that picks a
branch based on its type:

| Definition type | What happens |
| --- | --- |
| `Closure` | Invoked as `$definition($container)`; the return value is the entry. |
| `string` that is an existing class | Autowired via reflection. |
| anything else (object, scalar, array, non-class string) | Returned unchanged. |

This is why a closure is the tool of choice for entries that need runtime
configuration, and why storing a ready-made object simply hands it back later.

## The `has()` contract

```php
public function has(string $id): bool;
```

`has($id)` returns `true` when `get($id)` would **not** throw a
`NotFoundException` — that is, when `$id` is:

- already resolved (cache hit), **or**
- a registered definition, **or**
- an existing class name (`class_exists`, with autoloading).

```php
$container->set('app.name', 'InitPHP');

$container->has('app.name');       // true (registered)
$container->has(DateTime::class);  // true (autowirable class)
$container->has('missing');        // false
```

> **`has()` is not a guarantee of success.** A class can exist and still fail to
> build — for example if its constructor needs a scalar. `has()` only promises
> that `get()` will not raise `NotFoundException`; it may still raise another
> [container exception](Exceptions.md). See
> [Limitations → has() and autowiring](Limitations.md#has-and-autowiring).

Note also that an **unbound interface** is reported as `false` by `has()`,
because `class_exists()` does not report interfaces. Bind it first (see
[Binding & Factories](Binding-And-Factories.md#binding-interfaces-to-implementations)).

## Caching and singletons

Every value the container resolves — whether from a definition or from
autowiring — is stored in an internal cache and reused. The practical
consequence is that the container hands out **shared instances**:

```php
$a = $container->get(Engine::class);
$b = $container->get(Engine::class);

$a === $b; // true
```

The same applies to closure factories: the closure runs once, and its result is
cached.

```php
$count = 0;
$container->set('svc', function () use (&$count) {
    $count++;
    return new stdClass();
});

$container->get('svc');
$container->get('svc');
$count; // 1 — the factory ran only once
```

### Invalidating the cache

Re-registering an identifier with `set()` clears its cached instance, so the
next `get()` rebuilds it:

```php
$container->set('mode', 'production');
$container->get('mode');   // 'production'

$container->set('mode', 'testing');
$container->get('mode');   // 'testing' — old cached value discarded
```

There is no method to clear a single entry without re-registering it, and no
"flush all" operation. The container is designed to live for the duration of a
request or process.

### Need a fresh instance every time?

The container has no transient/prototype scope. When you genuinely need a new
object per call, do not route it through `get()`. Either construct it directly:

```php
$mail = new Mailer($container->get('mailer.transport'));
```

…or expose a factory **object** whose method returns a new instance, and
register that factory in the container:

```php
class MailerFactory
{
    public function __construct(private Transport $transport) {}

    public function create(): Mailer
    {
        return new Mailer($this->transport); // fresh each call
    }
}

$container->set(MailerFactory::class); // the factory is the shared singleton
$mailer = $container->get(MailerFactory::class)->create(); // mailer is fresh
```

## Circular dependencies

While building a class, the container records that it is in progress. If
resolving its dependencies leads back to the same class — directly or through a
chain — it raises [`CircularDependencyException`](Exceptions.md#circulardependencyexception)
instead of recursing until the process runs out of memory:

```php
class A { public function __construct(public B $b) {} }
class B { public function __construct(public A $a) {} }

$container->get(A::class); // CircularDependencyException
```

The in-progress marker is cleared whether the build succeeds or fails, so a
later, legitimate resolution of the same class is unaffected.

## Related pages

- [Autowiring](Autowiring.md) — how class dependencies are discovered and built.
- [Binding & Factories](Binding-And-Factories.md) — the registration styles feeding step 2.
- [Exceptions](Exceptions.md) — every error this lifecycle can raise.
