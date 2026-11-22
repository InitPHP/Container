# Recipe: Interface Binding

**Goal:** depend on interfaces in your services, bind each interface to a concrete
implementation in one place, and swap implementations per environment without
touching consumers.

## Why bind to an interface?

Type-hinting an interface (not a concrete class) lets you:

- Substitute a different implementation (a fake in tests, a no-op in CLI, a
  decorated variant in production) without rewriting consumers.
- Keep wiring decisions in one bootstrap file instead of scattered across `new`
  statements.
- Stay aligned with the Dependency Inversion Principle — high-level code depends
  on abstractions.

The container cannot autowire an interface on its own (see
[Autowiring → What cannot be autowired](Autowiring.md#what-cannot-be-autowired)),
so you bind it explicitly with [`set()`](API-Reference.md#set).

## The basic binding

```php
use InitPHP\Container\Container;

interface MailerInterface
{
    public function send(string $to, string $body): void;
}

final class SmtpMailer implements MailerInterface
{
    public function send(string $to, string $body): void { /* ... */ }
}

final class NotificationService
{
    public function __construct(private MailerInterface $mailer) {}

    public function notify(string $user): void
    {
        $this->mailer->send($user, 'Hello!');
    }
}

$container = new Container();
$container->set(MailerInterface::class, SmtpMailer::class);

// The dependency is resolved through the binding:
$service = $container->get(NotificationService::class);
$service->notify('a@b.test');
```

`NotificationService` never names `SmtpMailer`. Only the bootstrap line does.

## Swapping implementations per environment

Decide the binding once, based on configuration or environment:

```php
$container = new Container();

if (getenv('APP_ENV') === 'testing') {
    $container->set(MailerInterface::class, fn () => new InMemoryMailer());
} else {
    $container->set(MailerInterface::class, SmtpMailer::class);
}
```

Everything that depends on `MailerInterface` now receives the right
implementation automatically.

## Binding an implementation that itself needs configuration

If the concrete class cannot be autowired (it needs a DSN, credentials, etc.),
bind the interface to a **closure** instead of a class name:

```php
$container->set('mailer.dsn', 'smtp://user:pass@localhost:25');

$container->set(MailerInterface::class, function (\Psr\Container\ContainerInterface $c) {
    return new SmtpMailer($c->get('mailer.dsn'));
});
```

The closure runs lazily and its result is cached, so `SmtpMailer` is built once
and shared. See [Service Factories](Recipe-Service-Factories.md) for more on this.

## Several interfaces, one implementation

A class can satisfy more than one interface. Bind each interface to the **same**
shared instance by resolving through one canonical key:

```php
final class ArrayStore implements CacheInterface, SessionStoreInterface {}

$container->set(ArrayStore::class);                                  // the shared instance
$container->set(CacheInterface::class, fn ($c) => $c->get(ArrayStore::class));
$container->set(SessionStoreInterface::class, fn ($c) => $c->get(ArrayStore::class));

$container->get(CacheInterface::class) === $container->get(SessionStoreInterface::class); // true
```

Because the container caches `ArrayStore::class`, both interface lookups return
the very same object.

## Common pitfalls

- **Forgetting the binding.** Requesting an unbound interface throws
  `NotFoundException`; requiring it as a non-optional dependency throws
  `DependencyHasNoDefaultValueException`. Bind it during bootstrap.
- **Type-hinting the concrete class.** Type-hint the interface in consumers; name
  the concrete class only in the binding. Otherwise the indirection buys you
  nothing.
- **Expecting distinct instances per interface.** Binding two interfaces to the
  same key yields the same shared object (see above). Use separate keys/closures
  if you truly want separate instances.

## Related pages

- [Binding & Factories](Binding-And-Factories.md) — all registration styles.
- [Service Factories](Recipe-Service-Factories.md) — closures for configured services.
- [Application Bootstrap](Recipe-Application-Bootstrap.md) — wiring many bindings at once.
