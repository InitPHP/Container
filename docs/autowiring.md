# Autowiring

Autowiring is the container's ability to build a class without an explicit registration by inspecting its constructor and resolving each dependency for you.

## Resolving a class by name

Pass any existing class name to `get()`:

```php
use InitPHP\Container\Container;

class Engine
{
}

class Car
{
    public function __construct(public Engine $engine)
    {
    }
}

$container = new Container();
$car = $container->get(Car::class);

$car instanceof Car;            // true
$car->engine instanceof Engine; // true
```

The container reads `Car::__construct()`, sees it needs an `Engine`, resolves `Engine` (which has no dependencies of its own) and injects it.

## Recursive resolution

Dependencies are resolved recursively to any depth:

```php
class Db
{
}

class Repository
{
    public function __construct(public Db $db)
    {
    }
}

class Controller
{
    public function __construct(public Repository $repository)
    {
    }
}

$controller = $container->get(Controller::class);
$controller->repository->db instanceof Db; // true
```

Because every entry is cached, a dependency shared by several classes is the same instance everywhere:

```php
$repository = $container->get(Repository::class);
$controller = $container->get(Controller::class);

$controller->repository === $repository; // true
```

## How each constructor parameter is resolved

For every constructor parameter, the container applies these rules in order:

1. **Class-typed parameter** (a single, non-builtin type): the container resolves it through `get()`.
2. **Default value available**: the parameter's default value is used.
3. **Nullable parameter**: `null` is injected.
4. **None of the above**: a `DependencyHasNoDefaultValueException` is thrown.

### Examples

```php
class Service
{
    public function __construct(
        public Engine $engine,        // rule 1: autowired
        public string $name = 'svc',  // rule 2: uses 'svc'
        public ?Engine $spare = null,  // rule 1 if resolvable, else rule 3
    ) {
    }
}

$service = $container->get(Service::class);
$service->engine instanceof Engine; // true
$service->name;                     // 'svc'
$service->spare instanceof Engine;  // true (Engine is resolvable)
```

A scalar parameter without a default cannot be guessed and fails:

```php
class NeedsString
{
    public function __construct(public string $value)
    {
    }
}

$container->get(NeedsString::class); // throws DependencyHasNoDefaultValueException
```

Register the value or provide a factory instead — see [Binding & Factories](./binding-and-factories.md).

## Union and intersection types

A union or intersection typed parameter is not autowired (the container cannot decide which type to build). It falls back to the default value or `null`:

```php
class WithUnion
{
    public function __construct(public int|string $value = 1)
    {
    }
}

$container->get(WithUnion::class)->value; // 1
```

If such a parameter has no default and is not nullable, a `DependencyHasNoDefaultValueException` is thrown.

## Classes without a constructor

A class with no constructor is simply instantiated:

```php
class Plain
{
}

$container->get(Plain::class) instanceof Plain; // true
```

## What cannot be autowired

- Interfaces — not instantiable, and `class_exists()` does not report them, so the container treats an unbound interface as unknown. Requesting one directly throws `NotFoundException`; requiring one as an unbound, non-nullable dependency throws `DependencyHasNoDefaultValueException`. Bind it to a concrete class first (see [Binding & Factories](./binding-and-factories.md)).
- Abstract classes — `class_exists()` reports them, so the container tries to build one and fails with `DependencyIsNotInstantiableException`, both when requested directly and as a dependency.
- Classes with a non-public constructor — they throw `DependencyIsNotInstantiableException`.
- Circular dependencies — they throw `CircularDependencyException`.

See [Exceptions & Error Handling](./exceptions.md) for details.
