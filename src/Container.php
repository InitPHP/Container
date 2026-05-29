<?php

/**
 * Container.php
 *
 * This file is part of InitPHP Container.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 InitPHP Container
 * @license    https://github.com/InitPHP/Container/blob/main/LICENSE  MIT
 */

declare(strict_types=1);

namespace InitPHP\Container;

use Closure;
use InitPHP\Container\Exception\CircularDependencyException;
use InitPHP\Container\Exception\DependencyHasNoDefaultValueException;
use InitPHP\Container\Exception\DependencyIsNotInstantiableException;
use InitPHP\Container\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

use function array_key_exists;
use function class_exists;
use function is_string;

/**
 * A minimal PSR-11 container that resolves entries on demand.
 *
 * Entries may be registered explicitly with {@see Container::set()} or, when an
 * identifier maps to an existing class name, resolved automatically through
 * constructor autowiring. Every entry is built lazily on first access and the
 * resulting value is cached, so repeated calls to {@see Container::get()} with
 * the same identifier return the very same instance.
 */
class Container implements ContainerInterface
{
    /**
     * Registered definitions keyed by identifier.
     *
     * A definition is whatever was passed to {@see Container::set()}: a class
     * name, a {@see Closure} factory, an already built object or any scalar.
     *
     * @var array<string, mixed>
     */
    protected array $definitions = [];

    /**
     * Fully resolved entries keyed by identifier, used as a build cache.
     *
     * @var array<string, mixed>
     */
    protected array $resolved = [];

    /**
     * Identifiers currently being resolved, used to detect circular graphs.
     *
     * @var array<string, true>
     */
    private array $building = [];

    /**
     * Registers an entry on the container.
     *
     * The entry is stored as a definition and resolved lazily the first time it
     * is requested. When `$concrete` is `null` the identifier itself is used as
     * the definition, which is the common case for autowiring a class by its
     * own name. Registering an identifier again replaces any previously cached
     * instance.
     *
     * @param string $id       Identifier of the entry.
     * @param mixed  $concrete  A class name, a Closure factory invoked with the
     *                          container, an object, or any value to store as is.
     *                          Defaults to `$id` when omitted.
     * @return void
     */
    public function set(string $id, mixed $concrete = null): void
    {
        $this->definitions[$id] = $concrete ?? $id;
        unset($this->resolved[$id]);
    }

    /**
     * @inheritDoc
     *
     * @return mixed
     * @throws NotFoundException               When no entry or class matches `$id`.
     * @throws DependencyIsNotInstantiableException
     * @throws DependencyHasNoDefaultValueException
     * @throws CircularDependencyException
     */
    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->resolved)) {
            return $this->resolved[$id];
        }

        if (array_key_exists($id, $this->definitions)) {
            return $this->resolved[$id] = $this->build($this->definitions[$id]);
        }

        if (class_exists($id)) {
            return $this->resolved[$id] = $this->resolve($id);
        }

        throw new NotFoundException('No entry was found for identifier "' . $id . '".');
    }

    /**
     * @inheritDoc
     *
     * Returns `true` when {@see Container::get()} would not throw a
     * {@see NotFoundException} for the given identifier, i.e. the identifier is
     * a registered entry or an existing, loadable class name. A `true` result
     * does not guarantee that resolution succeeds; building the entry may still
     * fail with another container exception.
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->resolved)
            || array_key_exists($id, $this->definitions)
            || class_exists($id);
    }

    /**
     * Turns a registered definition into a concrete value.
     *
     * @param mixed $definition
     * @return mixed
     * @throws DependencyIsNotInstantiableException
     * @throws DependencyHasNoDefaultValueException
     * @throws CircularDependencyException
     * @throws NotFoundException
     */
    private function build(mixed $definition): mixed
    {
        if ($definition instanceof Closure) {
            return $definition($this);
        }

        if (is_string($definition) && class_exists($definition)) {
            return $this->resolve($definition);
        }

        return $definition;
    }

    /**
     * Instantiates a class, recursively autowiring its constructor arguments.
     *
     * @param class-string $concrete
     * @return object
     * @throws DependencyIsNotInstantiableException
     * @throws DependencyHasNoDefaultValueException
     * @throws CircularDependencyException
     * @throws NotFoundException
     */
    private function resolve(string $concrete): object
    {
        if (isset($this->building[$concrete])) {
            throw new CircularDependencyException(
                'Circular dependency detected while resolving "' . $concrete . '".'
            );
        }

        $reflection = new ReflectionClass($concrete);
        if (!$reflection->isInstantiable()) {
            throw new DependencyIsNotInstantiableException('Class "' . $concrete . '" is not instantiable.');
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $this->building[$concrete] = true;
        try {
            $dependencies = $this->getDependencies($constructor->getParameters());
        } finally {
            unset($this->building[$concrete]);
        }

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Resolves the argument list for a constructor.
     *
     * Each parameter is resolved in the following order: a class-typed
     * parameter is autowired from the container; otherwise a default value is
     * used when available; otherwise `null` is supplied for nullable
     * parameters. When none of these apply the parameter cannot be resolved.
     *
     * @param ReflectionParameter[] $parameters
     * @return list<mixed>
     * @throws DependencyHasNoDefaultValueException
     * @throws DependencyIsNotInstantiableException
     * @throws CircularDependencyException
     * @throws NotFoundException
     */
    private function getDependencies(array $parameters): array
    {
        $dependencies = [];
        foreach ($parameters as $parameter) {
            $dependencies[] = $this->resolveParameter($parameter);
        }

        return $dependencies;
    }

    /**
     * Resolves a single constructor parameter to a concrete value.
     *
     * A class-typed parameter the container knows about is autowired. If that
     * build fails but the parameter is optional (it has a default value or is
     * nullable), the optional fallback is used instead of propagating the
     * error; otherwise the failure is re-thrown.
     *
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws DependencyHasNoDefaultValueException
     * @throws DependencyIsNotInstantiableException
     * @throws CircularDependencyException
     * @throws NotFoundException
     */
    private function resolveParameter(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin() && $this->has($type->getName())) {
            try {
                return $this->get($type->getName());
            } catch (ContainerExceptionInterface $e) {
                if (!$parameter->isDefaultValueAvailable() && !$parameter->allowsNull()) {
                    throw $e;
                }
            }
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new DependencyHasNoDefaultValueException(
            'Unable to resolve the value of parameter "$' . $parameter->getName() . '".'
        );
    }
}
