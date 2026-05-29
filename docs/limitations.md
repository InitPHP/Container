# Limitations

This container is deliberately minimal. Knowing what it does *not* do helps you decide when it is the right tool and when you should reach for a fuller-featured container.

## Shared instances only

Every resolved entry is cached and reused. There is no "transient" or "prototype" scope that returns a fresh object on each call.

```php
$container->get(Service::class) === $container->get(Service::class); // always true
```

If you need a new instance every time, build it yourself — either `new Service(...)` directly, or call a factory you keep outside the container.

## No contextual or tagged bindings

You cannot say "inject implementation X here but implementation Y there", and there is no concept of tagging multiple services and retrieving them as a group. A given identifier resolves to exactly one definition.

## Autowiring resolves single class types only

Constructor autowiring works for parameters typed with a single class or interface name. It does **not** pick a type out of a union or intersection:

```php
public function __construct(Cache|Store $backend) { /* not autowired */ }
```

Such a parameter falls back to its default value or `null`; otherwise resolution fails. Provide a factory for these cases.

## Scalar dependencies are not guessed

The container never invents scalar values. A constructor that needs a `string`, `int`, etc. without a default must be satisfied through a factory or by storing the value:

```php
public function __construct(string $apiKey) { /* needs a factory */ }
```

## No attribute-based configuration

Resolution is driven entirely by constructor type hints and the definitions you register. There is no support for PHP attributes, annotations, or configuration files to influence how a class is built.

## No method or property injection

Only constructor injection is performed. Setter methods and properties are never populated by the container.

## `has()` and autowiring

`has($id)` returns `true` for any existing class name, because such a class is potentially autowirable. It does **not** prove that resolution will succeed — building the class may still fail (for example a constructor needs a scalar). `has()` only guarantees that `get()` will not throw a `NotFoundException`.

```php
class NeedsApiKey
{
    public function __construct(string $apiKey)
    {
    }
}

$container->has(NeedsApiKey::class); // true
$container->get(NeedsApiKey::class); // throws DependencyHasNoDefaultValueException
```

## When to use a larger container

If you need scopes, tagging, contextual bindings, lazy proxies, compiled containers, or attribute configuration, consider a full-featured container such as PHP-DI or the Symfony DependencyInjection component. InitPHP Container aims to stay small and predictable for projects that only need PSR-11 lookups and straightforward autowiring.
