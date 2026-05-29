# Binding & Factories

Autowiring covers concrete classes, but real applications also need to register values, bind interfaces to implementations and build entries that need configuration. That is what `set()` is for.

```php
public function set(string $id, mixed $concrete = null): void;
```

The container interprets the `$concrete` definition lazily when the entry is first requested:

| `$concrete` is… | Behaviour on `get()` |
| --- | --- |
| `null` (omitted) | The identifier is used as the definition. |
| a `Closure` | The closure is invoked with the container; its return value is cached. |
| an existing class name (string) | The class is autowired. |
| any other value (object, scalar, array, non-class string) | The value is returned as is. |

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

## Binding a class name

Register a class so it is built on first use. Passing only the identifier uses it as its own definition:

```php
$container->set(App\Service::class);
$container->get(App\Service::class); // autowired App\Service instance
```

You rarely need to do this for plain classes — autowiring already handles them. It matters when you want to alias one identifier to another class.

## Binding interfaces to implementations

Type-hinting an interface is the idiomatic way to depend on abstractions. Bind the interface to a concrete class so both direct lookups and autowired dependencies resolve to it:

```php
interface CacheInterface
{
}

class RedisCache implements CacheInterface
{
}

class PageRenderer
{
    public function __construct(public CacheInterface $cache)
    {
    }
}

$container->set(CacheInterface::class, RedisCache::class);

$container->get(CacheInterface::class);           // RedisCache instance
$container->get(PageRenderer::class)->cache;      // the same RedisCache instance
```

Without the binding, `get(PageRenderer::class)` would fail because the container cannot build an interface on its own.

## Factories (closures)

When an entry needs constructor arguments the container cannot guess — a DSN, an API key, a file path — register a closure. It receives the container and runs lazily, only on the first `get()`:

```php
use Psr\Container\ContainerInterface;

$container->set('pdo', function (ContainerInterface $c) {
    return new PDO('mysql:host=localhost;dbname=app', 'user', 'secret');
});

$pdo = $container->get('pdo'); // closure runs here
$container->get('pdo') === $pdo; // true — the result is cached
```

The container is passed in so a factory can pull other entries:

```php
$container->set('config', ['dsn' => 'sqlite::memory:']);

$container->set('pdo', function (ContainerInterface $c) {
    $config = $c->get('config');
    return new PDO($config['dsn']);
});
```

### Factories run once

The closure's return value is cached just like any other entry. If you need it to run on every call, that is outside this container's model — build the object directly where you need it.

## Re-registering an entry

Calling `set()` again replaces the definition and discards the cached instance, so the next `get()` rebuilds it:

```php
$container->set('mode', 'production');
$container->get('mode'); // 'production'

$container->set('mode', 'testing');
$container->get('mode'); // 'testing'
```
