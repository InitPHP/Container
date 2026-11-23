# Exceptions

Every container exception lives in the `InitPHP\Container\Exception` namespace
and implements a PSR-11 interface, so you can also catch them through the
standard `Psr\Container\ContainerExceptionInterface` /
`NotFoundExceptionInterface` contracts.

## Hierarchy

```
Psr\Container\ContainerExceptionInterface
└── InitPHP\Container\Exception\ContainerException
    ├── NotFoundException            (also implements NotFoundExceptionInterface)
    ├── DependencyIsNotInstantiableException
    ├── DependencyHasNoDefaultValueException
    └── CircularDependencyException
```

`ContainerException` is the common base, so a single catch handles every
container error:

```php
use InitPHP\Container\Exception\ContainerException;

try {
    $service = $container->get(App\Service::class);
} catch (ContainerException $e) {
    // any container failure
}
```

To distinguish a missing entry specifically, catch `NotFoundException` (or the
PSR interface) first.

## When each is thrown

| Exception | Trigger |
| --- | --- |
| `NotFoundException` | `get($id)` where `$id` is neither registered nor an existing class. |
| `DependencyIsNotInstantiableException` | Building an abstract class, an enum, or a class with a non-public constructor, whether it was requested directly, reached as a required dependency, or named as the concrete class of a binding. |
| `DependencyHasNoDefaultValueException` | A required constructor parameter cannot be autowired and has no default or nullable fallback (a scalar, a union type, an unbound interface). |
| `CircularDependencyException` | A class depends on itself directly or through a chain. |

---

## `NotFoundException`

Thrown by `get()` when the identifier is neither a registered entry nor an
existing class name. Implements `Psr\Container\NotFoundExceptionInterface`.

```php
use InitPHP\Container\Exception\NotFoundException;

try {
    $container->get('does-not-exist');
} catch (NotFoundException $e) {
    echo $e->getMessage();
    // No entry was found for identifier "does-not-exist".
}
```

This is also thrown when you request an **unbound interface** directly, because
`class_exists()` does not report interfaces:

```php
interface PaymentGatewayInterface {}

$container->get(PaymentGatewayInterface::class); // NotFoundException
```

`NotFoundException` is only the answer for that one route. What an interface
produces depends on how the container reaches it:

| Route | Result |
| --- | --- |
| `get(I::class)`, no binding | `NotFoundException` |
| Required dependency `I $i` of a class being built | `DependencyHasNoDefaultValueException` |
| Optional dependency `?I $i` or `?I $i = null` | no exception; `null` is injected |
| `set(I::class, Impl::class)` | resolves to `Impl`, for `get()` and for dependencies |
| `set(I::class, fn () => new Impl())` | resolves to what the closure returns |
| `set(I::class)` with no concrete | no exception; `get(I::class)` returns the string `'I'` and `has()` is `true`. A class that requires `I` then fails with a PHP `TypeError`, which is not a container exception |
| `set(I::class, J::class)` where `J` is another interface | no exception; `get(I::class)` returns the string `'J'`, and a class that requires `I` fails with a `TypeError` |

The last two rows are not errors the container reports: an interface name is not
a class name for `class_exists()`, so such a definition is stored and returned
as a plain string. Always bind an interface to a concrete class or a closure —
see [Binding & Factories](Binding-And-Factories.md#binding-interfaces-to-implementations).

---

## `DependencyIsNotInstantiableException`

Thrown when the container has to build a class it cannot instantiate: an
abstract class, an enum, or a class with a non-public constructor.

```php
use InitPHP\Container\Exception\DependencyIsNotInstantiableException;

abstract class AbstractRepository {}

try {
    $container->get(AbstractRepository::class);
} catch (DependencyIsNotInstantiableException $e) {
    echo $e->getMessage();
    // Class "AbstractRepository" is not instantiable.
}
```

This applies on every route into the class: requested directly with `get()`,
reached as a **required** dependency of another class, or named as the concrete
class of a binding (`set(Interface::class, AbstractRepository::class)`). It does
not apply to a dependency that is optional: when the parameter has a default or
is nullable, the container falls back to it instead of throwing — see
[Autowiring → Optional dependencies](Autowiring.md#optional-dependencies).

---

## `DependencyHasNoDefaultValueException`

Thrown when a constructor parameter cannot be autowired and has neither a default
value nor a nullable type to fall back on. Typical cases: a scalar without a
default, a union or intersection type, or a required, unbound interface
dependency (see [`NotFoundException`](#notfoundexception) for the other routes
an interface can take).

```php
use InitPHP\Container\Exception\DependencyHasNoDefaultValueException;

class Connection
{
    public function __construct(public string $dsn) {}
}

try {
    $container->get(Connection::class);
} catch (DependencyHasNoDefaultValueException $e) {
    echo $e->getMessage();
    // Unable to resolve the value of parameter "$dsn".
}
```

The fix is to give the container what it needs — register the value, provide a
default, or bind the interface:

```php
$container->set('connection', fn () => new Connection('sqlite::memory:'));
```

> If the same parameter were **optional** (had a default or were nullable), the
> container would use that fallback instead of throwing. See
> [Autowiring → Optional dependencies](Autowiring.md#optional-dependencies).

---

## `CircularDependencyException`

Thrown when a class depends on itself, directly or through a chain, which would
otherwise cause unbounded recursion.

```php
use InitPHP\Container\Exception\CircularDependencyException;

class A { public function __construct(public B $b) {} }
class B { public function __construct(public A $a) {} }

try {
    $container->get(A::class);
} catch (CircularDependencyException $e) {
    echo $e->getMessage();
    // Circular dependency detected while resolving "...".
}
```

Break the cycle by introducing an interface and a factory, or by injecting one
of the classes lazily (for example, passing the container or a callable and
resolving the dependency after construction).

---

## Catching by PSR interface

Because the exceptions implement the PSR-11 interfaces, code that depends only on
`psr/container` can catch them without referencing InitPHP types:

```php
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

try {
    $container->get($id);
} catch (NotFoundExceptionInterface $e) {
    // unknown identifier
} catch (ContainerExceptionInterface $e) {
    // any other resolution error
}
```

Order matters: `NotFoundException` satisfies both interfaces (it implements
`NotFoundExceptionInterface`, which extends `ContainerExceptionInterface`), so
catch the more specific `NotFoundExceptionInterface` first.

## Re-throwing from your own boundary

If you wrap the container in a service of your own and want callers to catch a
single domain-specific type, re-throw the exception as your own and preserve the
original as `$previous`:

```php
use InitPHP\Container\Exception\ContainerException;

final class ServiceLocator
{
    public function __construct(private \Psr\Container\ContainerInterface $container) {}

    public function load(string $id): object
    {
        try {
            return $this->container->get($id);
        } catch (ContainerException $e) {
            throw new \App\BootException(
                'Service "' . $id . '" could not be built: ' . $e->getMessage(),
                0,
                $e,
            );
        }
    }
}
```

## Related pages

- [Resolution & Caching](Resolution-And-Caching.md) — when each exception fires in the lifecycle.
- [Autowiring](Autowiring.md) — the rules that lead to `DependencyHasNoDefaultValueException`.
- [Limitations](Limitations.md) — behaviours that look like errors but are intentional.
