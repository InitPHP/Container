<?php
/**
 * Container.php
 *
 * This file is part of InitPHP Container.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 InitPHP Container
 * @license    http://initphp.github.io/license.txt  MIT
 * @version    0.2
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Container;

use \InitPHP\Container\Exception\{DependencyHasNoDefaultValueException, DependencyIsNotInstantiable};
use \Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{

    protected array $instance = [];

    public function set(string $id, $concrete = null)
    {
        if($concrete === null){
            $concrete = $id;
        }
        $this->instance[$id] = $concrete;
    }

    /**
     * @inheritDoc
     */
    public function get(string $id)
    {
        if(!$this->has($id)){
            $this->set($id);
        }
        return $this->resolve($this->instance[$id]);
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return isset($this->instance[$id]);
    }

    /**
     * @param $concrete
     * @return object|null
     * @throws DependencyHasNoDefaultValueException
     * @throws DependencyIsNotInstantiable
     * @throws \ReflectionException
     */
    private function resolve($concrete): ?object
    {
        $reflection = new \ReflectionClass($concrete);
        if(!$reflection->isInstantiable()){
            throw new DependencyIsNotInstantiable('Class "' . $concrete . '" is not instantiable.');
        }

        if(($constructor = $reflection->getConstructor()) === null){
            return $reflection->newInstance();
        }

        $arguments = $constructor->getParameters();
        $dependencies = $this->getDependencies($arguments, $reflection);
        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * @param \ReflectionParameter[] $arguments
     * @param \ReflectionClass $reflection
     * @return array
     * @throws DependencyHasNoDefaultValueException
     */
    private function getDependencies(array $arguments, \ReflectionClass $reflection): array
    {
        $dependencies = [];
        foreach ($arguments as $argument) {
            if($argument->isDefaultValueAvailable()){
                $dependencies[] = $argument->getDefaultValue();
                continue;
            }
            if($argument->hasType()){
                if (($type = $argument->getType()) !== null) {
                    if(!$type->isBuiltin()){
                        $dependencies[] = $this->get($type->getName());
                    }
                }
            }
            if($argument->allowsNull()){
                $dependencies[] = null;
                continue;
            }
            throw new DependencyHasNoDefaultValueException('Sorry cannot resolve class dependency ' . $argument->name);
        }
        return $dependencies;
    }

}
