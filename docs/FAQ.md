# FAQ

Quick answers to questions that came up while writing or reviewing the package.
If your question is not here, please
[open an issue](https://github.com/InitPHP/Container/issues/new) — documentation
fixes are reviewed eagerly.

## General

### What is this container for?

A small, [PSR-11](https://www.php-fig.org/psr/psr-11/) service container that
resolves entries on demand and autowires class dependencies via reflection.
Typical uses: wiring an application's services at boot, depending on interfaces,
and sharing configured singletons across a request or process.

### What is it NOT for?

- **Per-call instances.** Everything resolved is a shared singleton. See
  [Resolution & Caching](Resolution-And-Caching.md#need-a-fresh-instance-every-time).
- **Contextual / tagged bindings.** One identifier maps to one definition.
- **Attribute- or config-driven wiring.** Resolution is driven by type hints and
  explicit `set()` calls only.
- **Compiled, hot-path containers.** Resolution uses runtime reflection without a
  dumped cache.

See [Limitations](Limitations.md) for the full picture.

### What are the runtime dependencies?

Only [`psr/container`](https://packagist.org/packages/psr/container) `^2.0`. Dev
tools (PHPUnit, PHPStan, PHP-CS-Fixer) live under `require-dev` and are not
installed transitively.

## Usage

### Do I need to register a class before resolving it?

No. If the identifier is an existing class name, the container autowires it
without registration. You only register when you need an interface binding, a
factory, or to store a value. See [Autowiring](Autowiring.md).

### Why did `get('something')` throw `NotFoundException`?

Because `'something'` is neither a registered entry nor an existing class name.
That is the PSR-11 contract. Guard optional lookups with `has()`:

```php
if ($container->has('optional')) {
    $value = $container->get('optional');
}
```

### Why does `has(SomeInterface::class)` return `false` for an unbound interface?

`has()` answers it via `class_exists()`, which does **not** report interfaces. An
unbound interface is therefore unknown to the container. Bind it first:

```php
$container->set(SomeInterface::class, SomeImplementation::class);
```

See [Binding & Factories](Binding-And-Factories.md#binding-interfaces-to-implementations).

### Why is my closure being executed instead of returned?

A `Closure` passed to `set()` is treated as a **lazy factory** — it is invoked
with the container on first `get()`, and its return value is cached. If you want
to store a closure as a plain value, return it from another closure:

```php
$callable = fn () => 'hi';
$container->set('callable', fn () => $callable);
$container->get('callable') === $callable; // true
```

See [Binding & Factories → Factories](Binding-And-Factories.md#factories-closures).

### How do I get a fresh instance every time?

The container only returns shared singletons. For per-call objects, register a
**factory object** and call its method each time, or construct the object
directly:

```php
$container->set(ReportFactory::class);
$report = $container->get(ReportFactory::class)->create(); // fresh each call
```

See [Resolution & Caching](Resolution-And-Caching.md#need-a-fresh-instance-every-time).

### How do I override a service in a test?

Re-register it — `set()` replaces the definition and clears the cached instance:

```php
$container->set(MailerInterface::class, fn () => new InMemoryMailer());
```

Do this before the code under test resolves the service. See
[Recipes → Swapping a service in a test](Recipes.md#swapping-a-service-in-a-test).

### Can it inject scalars (strings, ints) automatically?

No. The container never invents scalar values. Supply them through a factory or
by storing the value and reading it in the factory. See
[Service Factories](Recipe-Service-Factories.md).

### Does it support setter or property injection?

No — only constructor injection. See [Limitations](Limitations.md#no-method-or-property-injection).

### What happens with a union or intersection typed parameter?

It is not autowired (the container can't choose a type). It falls back to the
parameter's default value or `null`, or fails if neither is available. See
[Autowiring → Union and intersection types](Autowiring.md#union-and-intersection-types).

### Does it detect circular dependencies?

Yes. A cycle (A → B → A, or a class depending on itself) throws
[`CircularDependencyException`](Exceptions.md#circulardependencyexception) instead of
recursing until PHP runs out of memory.

## Errors

### Which exception should I catch?

- A missing entry → `NotFoundException` (or `NotFoundExceptionInterface`).
- Any build failure → `DependencyIsNotInstantiableException`,
  `DependencyHasNoDefaultValueException`, or `CircularDependencyException`.
- All of the above at once → the base `ContainerException` (or
  `ContainerExceptionInterface`).

See [Exceptions](Exceptions.md).

### `DependencyHasNoDefaultValueException` — what did I do wrong?

A required constructor parameter could not be autowired and had no default or
nullable fallback — usually a scalar with no default, or a required, unbound
interface. Register the value, add a default, or bind the interface. See
[Exceptions](Exceptions.md#dependencyhasnodefaultvalueexception).

## PHP / tooling

### Which PHP versions are supported?

PHP 8.2+, with CI running the suite on 8.2, 8.3, 8.4, and 8.5 against both the
lowest and highest dependency versions. See [Installation](Installation.md).

### What is the PHPStan level?

`max`, configured in `phpstan.neon`; CI analyses `src`.

### Where are the tests?

Under `tests/` in the source repo. The suite covers autowiring, value storage,
closures, interface binding, optional/nullable parameters, union types, abstract
and private-constructor classes, circular dependencies, and the `has` / `get` /
`set` contract.

## Versioning

### Does this container follow SemVer?

Yes — like every InitPHP package. Breaking changes to the public API (the
`Container` methods, exception types, PSR-11 behaviour) require a major bump.

### I used an early `dev-main` build — what changed?

Several behaviours were tightened for PSR-11 compliance (lazy `set()`, throwing
`get()`, closure factories, corrected exception interfaces). See the
[Migration Guide](Migration-Guide.md).

## Other

### Where do I report a security issue?

See [`SECURITY.md`](https://github.com/InitPHP/.github/blob/main/SECURITY.md) in
the InitPHP org profile. Please do not file public issues for vulnerabilities.

### Where do I open feature requests or discussions?

[InitPHP Discussions](https://github.com/orgs/InitPHP/discussions) is the right
place for open-ended threads. Concrete bug reports and feature proposals belong
in [the issue tracker](https://github.com/InitPHP/Container/issues).
