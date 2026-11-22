# Recipe: Service Factories

**Goal:** build services that the container cannot autowire on its own — anything
that needs a DSN, an API key, a file path, or another value the reflector cannot
infer — using lazy `Closure` factories.

## Why a factory?

Autowiring works when every constructor argument is itself a resolvable class. As
soon as a constructor needs a **scalar** (or a union type, or any value), the
container has nothing to infer and would throw
[`DependencyHasNoDefaultValueException`](Exceptions.md#dependencyhasnodefaultvalueexception).
A factory closure supplies those values explicitly.

```php
use InitPHP\Container\Container;
use Psr\Container\ContainerInterface;

class Database
{
    public function __construct(public string $dsn, public string $user) {}
}

$container = new Container();

$container->set('db', function (ContainerInterface $c) {
    return new Database('mysql:host=localhost;dbname=app', 'root');
});

$db = $container->get('db');          // closure runs here
$container->get('db') === $db;        // true — cached, the closure runs once
```

See [Binding & Factories → Factories](Binding-And-Factories.md#factories-closures).

## Pulling configuration from the container

A factory receives the container, so it can read other entries. Keep raw
configuration in the container and let factories assemble services from it:

```php
$container->set('config', [
    'db'  => ['dsn' => 'pgsql:host=localhost;dbname=app', 'user' => 'app'],
    'api' => ['key' => 'sk_live_123'],
]);

$container->set('db', function (ContainerInterface $c) {
    $cfg = $c->get('config')['db'];
    return new Database($cfg['dsn'], $cfg['user']);
});

$container->set(ApiClient::class, function (ContainerInterface $c) {
    return new ApiClient($c->get('config')['api']['key']);
});
```

## Composing services from other services

Factories can resolve dependencies that are themselves autowired or
factory-built, mixing both styles freely:

```php
class Logger {}                       // autowirable
class AuditLog
{
    public function __construct(public Logger $logger, public string $channel) {}
}

$container->set('audit', function (ContainerInterface $c) {
    // Logger is autowired; the scalar channel is supplied here.
    return new AuditLog($c->get(Logger::class), 'security');
});
```

## Binding an interface to a factory

When the implementation needs configuration, bind the **interface** to a closure
so consumers still depend on the abstraction:

```php
interface QueueInterface {}
final class RedisQueue implements QueueInterface
{
    public function __construct(public string $host, public int $port) {}
}

$container->set(QueueInterface::class, function (ContainerInterface $c) {
    [$host, $port] = $c->get('config')['queue'] ?? ['127.0.0.1', 6379];
    return new RedisQueue($host, $port);
});

$queue = $container->get(QueueInterface::class); // RedisQueue, configured
```

This is the most common real-world binding: an interface key, a closure value.
See [Interface Binding](Recipe-Interface-Binding.md).

## Factories run once — by design

The closure's return value is cached, so the factory executes a single time and
every subsequent `get()` returns the same instance:

```php
$calls = 0;
$container->set('heavy', function () use (&$calls) {
    $calls++;
    return new HeavyService();
});

$container->get('heavy');
$container->get('heavy');
$calls; // 1
```

### Need a new instance per call?

Do not route per-call objects through `get()`. Register a **factory object** and
call its method each time you need a fresh instance:

```php
final class ReportFactory
{
    public function __construct(private Database $db) {}

    public function create(string $period): Report
    {
        return new Report($this->db, $period); // fresh every call
    }
}

$container->set(ReportFactory::class);                 // the factory is shared
$report = $container->get(ReportFactory::class)->create('2026-Q1'); // report is new
```

See [Resolution & Caching → Need a fresh instance every time?](Resolution-And-Caching.md#need-a-fresh-instance-every-time).

## Common pitfalls

- **Heavy work at registration time.** `set()` only stores the closure; nothing
  runs until `get()`. Do not call the closure yourself in the bootstrap — let the
  first `get()` trigger it so the cost is paid lazily.
- **Expecting fresh objects from a plain factory.** A closure entry is cached.
  Use a factory object (above) for per-call instances.
- **Catching the wrong exception.** A throwing factory propagates its own
  exception; if you want a uniform type, wrap and re-throw — see
  [Exceptions → Re-throwing from your own boundary](Exceptions.md#re-throwing-from-your-own-boundary).

## Related pages

- [Binding & Factories](Binding-And-Factories.md) — the registration reference.
- [Interface Binding](Recipe-Interface-Binding.md) — depend on abstractions.
- [Application Bootstrap](Recipe-Application-Bootstrap.md) — assemble it all at startup.
