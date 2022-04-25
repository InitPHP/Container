<?php
/**
 * Container.php
 *
 * This file is part of InitPHP Container.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 InitPHP Container
 * @license    http://initphp.github.io/license.txt  MIT
 * @version    pre-0.1
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
            $dependency = $argument->getClass();
            if(is_null($dependency)){
                if($argument->isDefaultValueAvailable()){
                    $dependencies[] = $argument->getDefaultValue();
                    continue;
                }
                throw new DependencyHasNoDefaultValueException('Sorry cannot resolve class dependency ' . $argument->name);
                continue;
            }
            $dependencies[] = $this->get($dependency->name);
        }
        return $dependencies;
    }

}
