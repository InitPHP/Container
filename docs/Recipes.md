# Recipes

Practical, copy-pasteable patterns built on top of the public API. Each recipe is
self-contained and links back into the reference material for the methods it
uses.

| Recipe | Solves |
| --- | --- |
| [Interface Binding](Recipe-Interface-Binding.md) | Depending on abstractions and swapping implementations per environment. |
| [Service Factories](Recipe-Service-Factories.md) | Building services that need DSNs, API keys, or other runtime configuration. |
| [Application Bootstrap](Recipe-Application-Bootstrap.md) | Wiring a small application's services into one container at boot. |

If you have built something on top of the container that other users might
benefit from, please
[open an issue](https://github.com/InitPHP/Container/issues/new) with a sketch —
recipe contributions are welcomed.

## When NOT to use this container

The container is intentionally minimal. Reach for a dedicated library when:

- **You need per-call (transient) instances.** Everything resolved here is a
  shared singleton. See
  [Resolution & Caching](Resolution-And-Caching.md#need-a-fresh-instance-every-time).
- **You need contextual or tagged bindings.** One identifier maps to one
  definition; there is no "inject X here, Y there".
- **You rely on attributes/annotations for wiring.** Resolution is driven by
  constructor type hints and explicit `set()` calls only.
- **You need a compiled container for a hot path.** Resolution uses runtime
  reflection without a dumped/compiled cache.

See [Limitations](Limitations.md) for the full list.

## Pattern sketches

These are not full recipes, but the public API is enough to implement them in a
handful of lines.

### A typed accessor over the container

Wrap `get()` in a small typed facade so call sites stay readable and IDE-friendly:

```php
use Psr\Container\ContainerInterface;

final class Services
{
    public function __construct(private ContainerInterface $c) {}

    public function pdo(): \PDO          { return $this->c->get('pdo'); }
    public function logger(): LoggerInterface { return $this->c->get(LoggerInterface::class); }
}
```

### Fallback chain across several identifiers

```php
use Psr\Container\ContainerInterface;

function firstAvailable(ContainerInterface $c, array $ids): mixed
{
    foreach ($ids as $id) {
        if ($c->has($id)) {
            return $c->get($id);
        }
    }
    throw new RuntimeException('None of the candidates are registered.');
}

$cache = firstAvailable($container, ['cache.redis', 'cache.file', 'cache.array']);
```

### Swapping a service in a test

Because re-registering clears the cache, a test can replace a real service with a
fake before the code under test resolves it:

```php
$container->set(LoggerInterface::class, fn () => new InMemoryLogger());
// ... exercise the subject, then assert against the in-memory logger ...
```

See [Binding & Factories → Re-registering an entry](Binding-And-Factories.md#re-registering-an-entry).
