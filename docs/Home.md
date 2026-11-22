# InitPHP Container — Wiki

Welcome to the official documentation for **`initphp/container`** — a small,
[PSR-11](https://www.php-fig.org/psr/psr-11/) dependency injection container
that resolves entries on demand and autowires class dependencies through
reflection.

The package ships two public types:

| Type | Purpose |
| --- | --- |
| [`Container`](API-Reference.md#container) | The concrete container; register entries and retrieve them. |
| [`Psr\Container\ContainerInterface`](API-Reference.md#psr-11-contract) | The PSR-11 contract `Container` implements; depend on this in your services. |

`Container` implements `Psr\Container\ContainerInterface`, so it drops into any
code that consumes the standard `get()` / `has()` contract.

```bash
composer require initphp/container
```

```php
use InitPHP\Container\Container;

class Mailer {}

class UserService
{
    public function __construct(public Mailer $mailer) {}
}

$container = new Container();

// No registration needed — the container reads UserService's constructor,
// builds the Mailer dependency and injects it.
$service = $container->get(UserService::class);

$service instanceof UserService;        // true
$service->mailer instanceof Mailer;     // true
```

## Start here

- **New to the package?** Read [Installation](Installation.md), then [Quick Start](Quick-Start.md).
- **Letting the container build your classes?** See [Autowiring](Autowiring.md).
- **Registering values, factories or interfaces?** Read [Binding & Factories](Binding-And-Factories.md).
- **Wondering how `get` / `has` / caching behave?** Read [Resolution & Caching](Resolution-And-Caching.md).
- **Handling failures?** Read [Exceptions](Exceptions.md).
- **Hitting a wall?** Read [Limitations](Limitations.md) before filing an issue.
- **Upgrading from an early `dev-main` snapshot?** Read the [Migration Guide](Migration-Guide.md).
- **Looking for a specific method?** The full [API Reference](API-Reference.md) lists every member.

## At a glance — feature matrix

| Capability | Supported |
| --- | :---: |
| PSR-11 `get()` / `has()` | ✅ |
| Constructor autowiring (reflection) | ✅ |
| Recursive dependency resolution | ✅ |
| Lazy `Closure` factories | ✅ |
| Binding an interface/identifier to a class | ✅ |
| Storing arbitrary values (scalars, arrays, objects) | ✅ |
| Shared (singleton) instances | ✅ (always) |
| Circular dependency detection | ✅ |
| Optional-dependency fallback (default / `null`) | ✅ |
| Union / intersection type autowiring | ❌ (falls back to default / `null`) |
| Transient (per-call) instances | ❌ |
| Contextual / tagged bindings | ❌ |
| Method / property / attribute injection | ❌ |

See [Limitations](Limitations.md) for the rationale behind the unsupported rows.

## Package metadata

- **License**: MIT
- **Minimum PHP**: 8.2 (tested on 8.2 – 8.5)
- **Runtime dependencies**: [`psr/container`](https://packagist.org/packages/psr/container) `^2.0`
- **Provides**: `psr/container-implementation` `2.0`
- **Packagist**: [`initphp/container`](https://packagist.org/packages/initphp/container)
- **Source**: [github.com/InitPHP/Container](https://github.com/InitPHP/Container)
- **Issues**: [github.com/InitPHP/Container/issues](https://github.com/InitPHP/Container/issues)
- **Discussions**: [github.com/orgs/InitPHP/discussions](https://github.com/orgs/InitPHP/discussions)
- **Security**: [`SECURITY.md`](https://github.com/InitPHP/.github/blob/main/SECURITY.md)

If something in this wiki is unclear, ambiguous, or wrong, please
[open an issue](https://github.com/InitPHP/Container/issues/new) —
documentation fixes are reviewed eagerly.
