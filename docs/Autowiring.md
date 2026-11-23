# Autowiring

Autowiring is the container's ability to build a class **without an explicit
registration**, by inspecting its constructor and resolving each dependency for
you. It is what makes `get(SomeClass::class)` work out of the box.

## Resolving a class by name

Pass any existing class name to [`get()`](API-Reference.md#get):

```php
use InitPHP\Container\Container;

class Engine {}

class Car
{
    public function __construct(public Engine $engine) {}
}

$container = new Container();
$car = $container->get(Car::class);

$car instanceof Car;            // true
$car->engine instanceof Engine; // true
```

The container reads `Car::__construct()`, sees it needs an `Engine`, resolves
`Engine` (which has no dependencies of its own) and injects it.

## Recursive resolution

Dependencies are resolved recursively to any depth:

```php
class Db {}

class Repository
{
    public function __construct(public Db $db) {}
}

class Controller
{
    public function __construct(public Repository $repository) {}
}

$controller = $container->get(Controller::class);
$controller->repository->db instanceof Db; // true
```

Because every entry is cached (see [Resolution & Caching](Resolution-And-Caching.md)),
a dependency shared by several classes is the **same instance** everywhere:

```php
$repository = $container->get(Repository::class);
$controller = $container->get(Controller::class);

$controller->repository === $repository; // true
```

## How each constructor parameter is resolved

For every constructor parameter, the container applies these rules **in order**:

1. **Class-typed parameter** — a single, non-builtin type the container knows
   about (an existing class, or a registered identifier): resolved through
   `get()`.
2. **Build failed but parameter is optional** — if step 1 throws a container
   error and the parameter has a default value or is nullable, the fallback from
   steps 3–4 is used instead of propagating the error.
3. **Default value available** — the parameter's default value is used.
4. **Nullable parameter** — `null` is injected.
5. **None of the above** — a
   [`DependencyHasNoDefaultValueException`](Exceptions.md#dependencyhasnodefaultvalueexception)
   is thrown.

### Worked examples

```php
class Service
{
    public function __construct(
        public Engine $engine,         // rule 1: autowired
        public string $name = 'svc',   // rule 3: uses 'svc'
        public ?Engine $spare = null,  // rule 1 if resolvable, else rule 4
    ) {}
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
    public function __construct(public string $value) {}
}

$container->get(NeedsString::class); // throws DependencyHasNoDefaultValueException
```

Register the value or provide a factory instead — see
[Binding & Factories](Binding-And-Factories.md).

## Optional dependencies

A parameter that is **nullable or has a default** is treated as optional. If the
container cannot build its type — for any reason: the class needs a scalar, an
abstract class, a missing binding — the fallback is used rather than failing:

```php
class Connection
{
    public function __construct(public string $dsn) {} // not autowirable
}

class Cache
{
    // Connection can't be built (needs $dsn), but the parameter is optional,
    // so the container falls back to null.
    public function __construct(public ?Connection $connection = null) {}
}

$container->get(Cache::class)->connection; // null
```

If the same parameter were **required** (`Connection $connection`, no default,
not nullable), the underlying failure would propagate instead.

## Union and intersection types

A union (`A|B`) or intersection (`A&B`) typed parameter is **not** autowired —
the container cannot decide which type to build. It falls back to the default
value or `null`:

```php
class WithUnion
{
    public function __construct(public int|string $value = 1) {}
}

$container->get(WithUnion::class)->value; // 1
```

If such a parameter has no default and is not nullable, a
`DependencyHasNoDefaultValueException` is thrown. Provide a factory for these
cases (see [Service Factories](Recipe-Service-Factories.md)).

## Classes without a constructor

A class with no constructor is simply instantiated:

```php
class Plain {}

$container->get(Plain::class) instanceof Plain; // true
```

## What cannot be autowired

| Target | Result | Why |
| --- | --- | --- |
| **Interface** (unbound) | `NotFoundException` when requested directly; `DependencyHasNoDefaultValueException` as a required dependency; `null` or the default as an optional one | `class_exists()` does not report interfaces, so the container treats an unbound interface as unknown. [Bind it first.](Binding-And-Factories.md#binding-interfaces-to-implementations) |
| **Abstract class** or **enum** | `DependencyIsNotInstantiableException` | `class_exists()` reports it, so the container tries to build it and fails because it is not instantiable. As an optional dependency (nullable or with a default) the fallback is used instead. |
| **Non-public constructor** | `DependencyIsNotInstantiableException` | Reflection reports the class as not instantiable. |
| **Circular dependency** | `CircularDependencyException` | A → B → A would otherwise recurse forever. |

See [Exceptions](Exceptions.md) for the full hierarchy and messages.

## Related pages

- [Binding & Factories](Binding-And-Factories.md) — make the un-autowirable cases work.
- [Resolution & Caching](Resolution-And-Caching.md) — the lifecycle and singleton behaviour.
- [Limitations](Limitations.md) — what autowiring intentionally does not do.
