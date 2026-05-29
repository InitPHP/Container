# Exceptions & Error Handling

All container exceptions live in the `InitPHP\Container\Exception` namespace and implement a PSR-11 interface, so you can also catch them by the standard `Psr\Container\ContainerExceptionInterface` / `NotFoundExceptionInterface` contracts.

## Hierarchy

```
Psr\Container\ContainerExceptionInterface
└── InitPHP\Container\Exception\ContainerException
    ├── NotFoundException            (also implements NotFoundExceptionInterface)
    ├── DependencyIsNotInstantiableException
    ├── DependencyHasNoDefaultValueException
    └── CircularDependencyException
```

`ContainerException` is the common base, so a single catch handles every container error:

```php
use InitPHP\Container\Exception\ContainerException;

try {
    $service = $container->get(App\Service::class);
} catch (ContainerException $e) {
    // any container failure
}
```

To distinguish a missing entry specifically, catch `NotFoundException` (or the PSR interface) first.

## `NotFoundException`

Thrown by `get()` when the identifier is neither a registered entry nor an existing class name. Implements `Psr\Container\NotFoundExceptionInterface`.

```php
use InitPHP\Container\Exception\NotFoundException;

try {
    $container->get('does-not-exist');
} catch (NotFoundException $e) {
    echo $e->getMessage(); // No entry was found for identifier "does-not-exist".
}
```

This is also thrown when you request an unbound interface directly, because `class_exists()` does not report interfaces:

```php
interface PaymentGatewayInterface
{
}

$container->get(PaymentGatewayInterface::class); // NotFoundException
```

## `DependencyIsNotInstantiableException`

Thrown when the container reaches a class it cannot instantiate: an abstract class or a class with a non-public constructor.

```php
use InitPHP\Container\Exception\DependencyIsNotInstantiableException;

abstract class AbstractRepository
{
}

try {
    $container->get(AbstractRepository::class);
} catch (DependencyIsNotInstantiableException $e) {
    echo $e->getMessage(); // Class "AbstractRepository" is not instantiable.
}
```

## `DependencyHasNoDefaultValueException`

Thrown when a constructor parameter cannot be autowired and has neither a default value nor a nullable type to fall back on. Typical cases: a scalar without a default, or an unbound interface dependency.

```php
use InitPHP\Container\Exception\DependencyHasNoDefaultValueException;

class Connection
{
    public function __construct(public string $dsn)
    {
    }
}

try {
    $container->get(Connection::class);
} catch (DependencyHasNoDefaultValueException $e) {
    echo $e->getMessage(); // Unable to resolve the value of parameter "$dsn".
}
```

The fix is to give the container what it needs — register the value, provide a default, or bind the interface:

```php
$container->set('connection', fn () => new Connection('sqlite::memory:'));
```

## `CircularDependencyException`

Thrown when a class depends on itself, directly or through a chain, which would otherwise cause unbounded recursion.

```php
use InitPHP\Container\Exception\CircularDependencyException;

class A
{
    public function __construct(public B $b)
    {
    }
}

class B
{
    public function __construct(public A $a)
    {
    }
}

try {
    $container->get(A::class);
} catch (CircularDependencyException $e) {
    echo $e->getMessage(); // Circular dependency detected while resolving "...".
}
```

Break the cycle by introducing an interface and a factory, or by injecting one of the classes lazily after construction.

## Catching by PSR interface

Because the exceptions implement the PSR-11 interfaces, code that depends only on `psr/container` can catch them without referencing InitPHP types:

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
