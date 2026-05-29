<?php

/**
 * fixtures.php
 *
 * Test doubles used across the container test suite. Keeping them in a single
 * file avoids one-class-per-file noise for tiny fixtures.
 *
 * This file is part of InitPHP Container.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 InitPHP Container
 * @license    https://github.com/InitPHP/Container/blob/main/LICENSE  MIT
 */

declare(strict_types=1);

namespace InitPHP\Container\Tests\Fixtures;

/** A plain class with no constructor. */
class NoConstructor
{
}

/** A class whose only constructor argument is another resolvable class. */
class NeedsNoConstructor
{
    public function __construct(public NoConstructor $dependency)
    {
    }
}

/** A class with a scalar constructor argument that has a default value. */
class ScalarWithDefault
{
    public function __construct(public string $name = 'default')
    {
    }
}

/** A class with a scalar constructor argument that has no default value. */
class ScalarWithoutDefault
{
    public function __construct(public string $name)
    {
    }
}

/** A class with a nullable, untyped-default constructor argument. */
class NullableDependency
{
    public function __construct(public ?NoConstructor $dependency)
    {
    }
}

/** A class with a nullable, builtin-typed argument that has no default. */
class NullableBuiltinWithoutDefault
{
    public function __construct(public ?int $value)
    {
    }
}

/** An interface that is never bound to a concrete implementation. */
interface UnboundInterface
{
}

/** A concrete implementation of {@see ServiceInterface}. */
interface ServiceInterface
{
}

class ServiceImplementation implements ServiceInterface
{
}

/** A class that type-hints an interface argument. */
class NeedsInterface
{
    public function __construct(public ServiceInterface $service)
    {
    }
}

/** A class that type-hints an interface argument with a null default. */
class OptionalInterface
{
    public function __construct(public ?ServiceInterface $service = null)
    {
    }
}

/** An abstract class, which cannot be instantiated. */
abstract class AbstractClass
{
}

/** A class with a private constructor, which cannot be instantiated. */
class PrivateConstructor
{
    private function __construct()
    {
    }
}

/** A class with a PHP 8 union-typed constructor argument and a default. */
class UnionTypeWithDefault
{
    public function __construct(public int|string $value = 1)
    {
    }
}

/** Optional dependency on a class that cannot itself be built. */
class OptionalUnbuildableDependency
{
    public function __construct(public ?ScalarWithoutDefault $dependency = null)
    {
    }
}

/** Required dependency on a class that cannot itself be built. */
class RequiredUnbuildableDependency
{
    public function __construct(public ScalarWithoutDefault $dependency)
    {
    }
}

/** Two classes that depend on each other to form a cycle. */
class CircularA
{
    public function __construct(public CircularB $b)
    {
    }
}

class CircularB
{
    public function __construct(public CircularA $a)
    {
    }
}

/** A class that depends on itself directly. */
class SelfReferencing
{
    public function __construct(public SelfReferencing $self)
    {
    }
}
