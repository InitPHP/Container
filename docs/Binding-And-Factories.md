# Binding & Factories

[Autowiring](Autowiring.md) covers concrete classes, but real applications also
need to register values, bind interfaces to implementations, and build entries
that require configuration. That is what [`set()`](API-Reference.md#set) is for.

```php
public function set(string $id, mixed $concrete = null): void;
```

The container interprets the `$concrete` **definition lazily** — only when the
entry is first requested through `get()`:

| `$concrete` is… | Behaviour on `get()` |
| --- | --- |
| `null` (omitted) | The identifier itself is used as the definition. |
| a `Closure` | The closure is invoked with the container; its return value is cached. |
| an existing class name (string) | The class is autowired. |
| any other value (object, scalar, array, non-class string) | The value is returned as is. |

`set()` returns `void`. It is a registration call, not a resolver — nothing is
built until you ask for it.

## Storing values

```php
use InitPHP\Container\Container;

$container = new Container();

$container->set('app.name', 'InitPHP');
$container->set('app.debug', true);
$container->set('app.paths', ['cache' => '/tmp/cache']);

$container->get('app.name');  // 'InitPHP'
$container->get('app.debug'); // true
$container->get('app.paths'); // ['cache' => '/tmp/cache']
```

Objects are stored and returned unchanged:

```php
$logger = new FileLogger('/var/log/app.log');
$container->set('logger', $logger);

$container->get('logger') === $logger; // true
```

> A string that is **not** an existing class name is treated as a plain value
> and returned verbatim. Only strings that pass `class_exists()` are autowired.

## Binding a class name

Register a class so it is built on first use. Passing only the identifier uses
it as its own definition:

```php
$container->set(App\Service::class);
$container->get(App\Service::class); // autowired App\Service instance
```

You rarely need this for plain classes — autowiring already handles them. It
matters when you want to **alias** one identifier to another class, which is the
basis of interface binding below.

> This only works for class names. An interface is not a class name for
> `class_exists()`, so `set(CacheInterface::class)` (or aliasing it to another
> interface) registers a plain string: `get(CacheInterface::class)` returns the
> interface name itself, not an object.

## Binding interfaces to implementations

Type-hinting an interface is the idiomatic way to depend on an abstraction. The
container cannot build an interface on its own, so bind it to a concrete class.
Both direct lookups and autowired dependencies then resolve to it:

```php
interface CacheInterface {}
class RedisCache implements CacheInterface {}

class PageRenderer
{
    public function __construct(public CacheInterface $cache) {}
}

$container->set(CacheInterface::class, RedisCache::class);

$container->get(CacheInterface::class);       // RedisCache instance
$container->get(PageRenderer::class)->cache;  // the same RedisCache instance
```

Without the binding, `get(PageRenderer::class)` fails because `CacheInterface`
cannot be autowired. See the [Interface Binding recipe](Recipe-Interface-Binding.md)
for swapping implementations and per-environment wiring.

## Factories (closures)

When an entry needs constructor arguments the container cannot guess — a DSN, an
API key, a file path — register a `Closure`. It receives the container and runs
**lazily**, only on the first `get()`:

```php
use Psr\Container\ContainerInterface;

$container->set('pdo', function (ContainerInterface $c) {
    return new PDO('mysql:host=localhost;dbname=app', 'user', 'secret');
});

$pdo = $container->get('pdo');    // the closure runs here
$container->get('pdo') === $pdo;  // true — the result is cached
```

The container is passed in, so a factory can pull other entries:

```php
$container->set('config', ['dsn' => 'sqlite::memory:']);

$container->set('pdo', function (ContainerInterface $c) {
    $config = $c->get('config');
    return new PDO($config['dsn']);
});
```

See the [Service Factories recipe](Recipe-Service-Factories.md) for larger wiring
examples.

### Factories run once

A closure's return value is cached just like any other entry. The closure is
**not** invoked again on subsequent `get()` calls. If you need a fresh object
every time, that is outside this container's model — build the object directly
where you need it. See [Limitations](Limitations.md#shared-instances-only).

## Re-registering an entry

Calling `set()` again with the same identifier replaces the definition **and
discards the cached instance**, so the next `get()` rebuilds it:

```php
$container->set('mode', 'production');
$container->get('mode'); // 'production'

$container->set('mode', 'testing');
$container->get('mode'); // 'testing'
```

This is handy in tests, where you can swap a real service for a fake before the
code under test resolves it.

## Choosing a registration style

| You want to… | Use |
| --- | --- |
| Inject a plain class with class-typed dependencies | Nothing — [autowiring](Autowiring.md) handles it |
| Resolve an interface to a concrete class | `set(Interface::class, Concrete::class)` |
| Build something that needs scalars / config | `set('id', fn ($c) => new …)` |
| Share a pre-built object | `set('id', $object)` |
| Store configuration / constants | `set('id', $scalarOrArray)` |

## Related pages

- [Autowiring](Autowiring.md) — automatic resolution of class dependencies.
- [Resolution & Caching](Resolution-And-Caching.md) — the exact lifecycle of `set` / `get`.
- [Recipes](Recipes.md) — end-to-end wiring patterns.
