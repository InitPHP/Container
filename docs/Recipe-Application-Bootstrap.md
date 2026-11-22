# Recipe: Application Bootstrap

**Goal:** wire a small application's services into a single container at startup,
then resolve the entry point and run it. This recipe ties together
[autowiring](Autowiring.md), [interface binding](Recipe-Interface-Binding.md), and
[factories](Recipe-Service-Factories.md) in one realistic file.

## The shape of a bootstrap

A bootstrap file does three things:

1. Create the container.
2. Register configuration, factories, and interface bindings.
3. Resolve the top-level service and hand off to it.

Everything in between — the dependency graph — is left to autowiring.

## A worked example

Suppose a CLI app sends a daily digest. The classes:

```php
interface MailerInterface
{
    public function send(string $to, string $subject, string $body): void;
}

final class SmtpMailer implements MailerInterface
{
    public function __construct(private string $dsn) {}
    public function send(string $to, string $subject, string $body): void { /* ... */ }
}

final class UserRepository
{
    public function __construct(private \PDO $pdo) {}
    /** @return string[] */
    public function recipients(): array { /* ... */ return []; }
}

final class DigestService
{
    public function __construct(
        private UserRepository $users,
        private MailerInterface $mailer,
    ) {}

    public function run(): void
    {
        foreach ($this->users->recipients() as $email) {
            $this->mailer->send($email, 'Your digest', '...');
        }
    }
}
```

### The bootstrap file

```php
// bootstrap.php
require __DIR__ . '/vendor/autoload.php';

use InitPHP\Container\Container;
use Psr\Container\ContainerInterface;

$container = new Container();

// 1. Configuration as a plain value.
$container->set('config', [
    'db'     => ['dsn' => 'mysql:host=localhost;dbname=app', 'user' => 'app', 'pass' => 'secret'],
    'mailer' => ['dsn' => 'smtp://localhost:25'],
]);

// 2. Services that need scalars → factories.
$container->set(\PDO::class, function (ContainerInterface $c) {
    $db = $c->get('config')['db'];
    return new \PDO($db['dsn'], $db['user'], $db['pass']);
});

$container->set(MailerInterface::class, function (ContainerInterface $c) {
    return new SmtpMailer($c->get('config')['mailer']['dsn']);
});

// 3. UserRepository and DigestService need no registration — their dependencies
//    (PDO, MailerInterface) are now resolvable, so autowiring handles them.

return $container;
```

### The entry point

```php
// run.php
/** @var \Psr\Container\ContainerInterface $container */
$container = require __DIR__ . '/bootstrap.php';

$container->get(DigestService::class)->run();
```

When `DigestService` is resolved, the container autowires `UserRepository`
(which pulls the `PDO` factory) and `MailerInterface` (which pulls the
`SmtpMailer` factory). Each is built once and shared.

## Keeping bootstrap readable as it grows

### Group registrations into provider functions

```php
function registerDatabase(Container $c): void
{
    $c->set(\PDO::class, fn (ContainerInterface $ci) => /* ... */);
    $c->set(UserRepository::class); // explicit, for discoverability
}

function registerMailing(Container $c): void
{
    $c->set(MailerInterface::class, fn (ContainerInterface $ci) => /* ... */);
}

$container = new Container();
registerDatabase($container);
registerMailing($container);
```

This mirrors the "service provider" pattern without adding any framework — each
function owns one slice of the wiring.

### Depend on `ContainerInterface`, not `Container`

Anything that needs the container (a router, a command bus, a controller
resolver) should type-hint `Psr\Container\ContainerInterface`. That keeps your
code portable across PSR-11 containers and makes it trivial to substitute a test
double.

```php
final class CommandBus
{
    public function __construct(private ContainerInterface $container) {}

    public function handle(string $commandHandlerId, object $command): mixed
    {
        return $this->container->get($commandHandlerId)->handle($command);
    }
}
```

## Testing the wiring

Because re-registering clears the cache, an integration test can boot the real
container and override just the leaf services that touch the outside world:

```php
$container = require __DIR__ . '/bootstrap.php';

// Replace I/O boundaries with fakes; the rest of the graph is the real thing.
$container->set(\PDO::class, fn () => new \PDO('sqlite::memory:'));
$container->set(MailerInterface::class, fn () => new InMemoryMailer());

$container->get(DigestService::class)->run();
// assert against the in-memory mailer
```

## Common pitfalls

- **Resolving during bootstrap.** Prefer registering definitions and resolving
  the entry point last. Calling `get()` mid-bootstrap caches instances before all
  bindings exist, which can capture a half-configured graph.
- **One giant closure.** Split wiring into provider functions (above) before the
  bootstrap file becomes unreadable.
- **Leaking the concrete `Container` type.** Pass `ContainerInterface` around so
  the application is not coupled to this package.

## Related pages

- [Interface Binding](Recipe-Interface-Binding.md) — depend on abstractions.
- [Service Factories](Recipe-Service-Factories.md) — services that need configuration.
- [Resolution & Caching](Resolution-And-Caching.md) — why resolving last matters.
