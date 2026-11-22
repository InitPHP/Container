# Limitations

This container is deliberately minimal. Knowing what it does **not** do helps you
decide when it is the right tool and when to reach for a fuller-featured
container. None of the items below are bugs — they are scope boundaries.

## Shared instances only

Every resolved entry is cached and reused. There is no transient or prototype
scope that returns a fresh object on each call.

```php
$container->get(Service::class) === $container->get(Service::class); // always true
```

If you need a new instance every time, build it yourself (`new Service(...)`) or
expose a factory object whose method returns a fresh instance. See
[Resolution & Caching → Need a fresh instance every time?](Resolution-And-Caching.md#need-a-fresh-instance-every-time).

## No contextual or tagged bindings

You cannot say "inject implementation X here but implementation Y there", and
there is no concept of tagging several services and retrieving them as a group. A
given identifier resolves to exactly one definition.

## Autowiring resolves single class types only

Constructor autowiring works for parameters typed with a single class or
interface name. It does **not** pick a type out of a union or intersection:

```php
public function __construct(Cache|Store $backend) { /* not autowired */ }
```

Such a parameter falls back to its default value or `null`; otherwise resolution
fails. Provide a [factory](Recipe-Service-Factories.md) for these cases.

## Scalar dependencies are not guessed

The container never invents scalar values. A constructor that needs a `string`,
`int`, etc. without a default must be satisfied through a factory or by storing
the value:

```php
public function __construct(string $apiKey) { /* needs a factory */ }
```

## No attribute-based configuration

Resolution is driven entirely by constructor type hints and the definitions you
register. There is no support for PHP attributes, annotations, or configuration
files to influence how a class is built.

## No method or property injection

Only **constructor** injection is performed. Setter methods and properties are
never populated by the container.

## No autowiring of interfaces without a binding

Because `class_exists()` does not report interfaces, an unbound interface is
unknown to the container: requesting it directly raises `NotFoundException`, and
requiring it as a non-optional dependency raises
`DependencyHasNoDefaultValueException`. Always
[bind interfaces](Binding-And-Factories.md#binding-interfaces-to-implementations)
to a concrete class.

## `has()` and autowiring

`has($id)` returns `true` for any existing class name, because such a class is
*potentially* autowirable. It does **not** prove that resolution will succeed —
building the class may still fail (for example, a constructor needs a scalar).
`has()` only guarantees that `get()` will not throw a `NotFoundException`.

```php
class NeedsApiKey
{
    public function __construct(string $apiKey) {}
}

$container->has(NeedsApiKey::class); // true
$container->get(NeedsApiKey::class); // throws DependencyHasNoDefaultValueException
```

## No compilation or caching of the reflection graph

Each first-time resolution reads constructor metadata via reflection. There is no
compiled container, no dumped factory code, and no persisted reflection cache.
For most applications the cost is negligible (and resolved entries are cached for
the container's lifetime anyway), but a very large graph resolved in a
performance-critical hot path may prefer a compiled container.

## When to use a larger container

If you need scopes, tagging, contextual bindings, lazy proxies, compiled
containers, or attribute configuration, consider a full-featured container such
as [PHP-DI](https://php-di.org/) or the
[Symfony DependencyInjection](https://symfony.com/doc/current/components/dependency_injection.html)
component. InitPHP Container aims to stay small and predictable for projects that
only need PSR-11 lookups and straightforward autowiring.

## Related pages

- [Autowiring](Autowiring.md) — what the resolver can and cannot infer.
- [Resolution & Caching](Resolution-And-Caching.md) — the singleton model in detail.
- [FAQ](FAQ.md) — quick answers to common "can it…?" questions.
