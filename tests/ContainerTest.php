<?php

/**
 * ContainerTest.php
 *
 * This file is part of InitPHP Container.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 InitPHP Container
 * @license    https://github.com/InitPHP/Container/blob/main/LICENSE  MIT
 */

declare(strict_types=1);

namespace InitPHP\Container\Tests;

use InitPHP\Container\Container;
use InitPHP\Container\Exception\CircularDependencyException;
use InitPHP\Container\Exception\ContainerException;
use InitPHP\Container\Exception\DependencyHasNoDefaultValueException;
use InitPHP\Container\Exception\DependencyIsNotInstantiableException;
use InitPHP\Container\Exception\NotFoundException;
use InitPHP\Container\Tests\Fixtures\AbstractClass;
use InitPHP\Container\Tests\Fixtures\CircularA;
use InitPHP\Container\Tests\Fixtures\NeedsInterface;
use InitPHP\Container\Tests\Fixtures\NeedsNoConstructor;
use InitPHP\Container\Tests\Fixtures\NoConstructor;
use InitPHP\Container\Tests\Fixtures\NullableBuiltinWithoutDefault;
use InitPHP\Container\Tests\Fixtures\NullableDependency;
use InitPHP\Container\Tests\Fixtures\OptionalInterface;
use InitPHP\Container\Tests\Fixtures\OptionalUnbuildableDependency;
use InitPHP\Container\Tests\Fixtures\PrivateConstructor;
use InitPHP\Container\Tests\Fixtures\RequiredUnbuildableDependency;
use InitPHP\Container\Tests\Fixtures\ScalarWithDefault;
use InitPHP\Container\Tests\Fixtures\ScalarWithoutDefault;
use InitPHP\Container\Tests\Fixtures\SelfReferencing;
use InitPHP\Container\Tests\Fixtures\ServiceImplementation;
use InitPHP\Container\Tests\Fixtures\ServiceInterface;
use InitPHP\Container\Tests\Fixtures\UnboundInterface;
use InitPHP\Container\Tests\Fixtures\UnionTypeWithDefault;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;

final class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testImplementsPsrContainerInterface(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->container);
    }

    public function testHasReturnsFalseForUnknownNonClassIdentifier(): void
    {
        $this->assertFalse($this->container->has('unknown-service'));
    }

    public function testHasReturnsTrueForExistingClassName(): void
    {
        $this->assertTrue($this->container->has(NoConstructor::class));
    }

    public function testHasReturnsTrueAfterSet(): void
    {
        $this->container->set('config', ['debug' => true]);
        $this->assertTrue($this->container->has('config'));
    }

    public function testGetThrowsNotFoundForUnknownNonClassIdentifier(): void
    {
        $this->expectException(NotFoundException::class);
        $this->container->get('unknown-service');
    }

    public function testGetStoredScalarValue(): void
    {
        $this->container->set('answer', 42);
        $this->assertSame(42, $this->container->get('answer'));
    }

    public function testGetStoredArrayValue(): void
    {
        $config = ['debug' => true];
        $this->container->set('config', $config);
        $this->assertSame($config, $this->container->get('config'));
    }

    public function testGetStoredNonClassStringIsReturnedVerbatim(): void
    {
        $this->container->set('greeting', 'hello');
        $this->assertSame('hello', $this->container->get('greeting'));
    }

    public function testSetWithExistingObjectReturnsSameInstance(): void
    {
        $object = new stdClass();
        $this->container->set('obj', $object);
        $this->assertSame($object, $this->container->get('obj'));
    }

    public function testAutowireClassWithoutRegistration(): void
    {
        $instance = $this->container->get(NoConstructor::class);
        $this->assertInstanceOf(NoConstructor::class, $instance);
    }

    public function testResolvedEntriesAreCachedAsSingletons(): void
    {
        $first = $this->container->get(NoConstructor::class);
        $second = $this->container->get(NoConstructor::class);
        $this->assertSame($first, $second);
    }

    public function testAutowireResolvesClassDependency(): void
    {
        $instance = $this->container->get(NeedsNoConstructor::class);
        $this->assertInstanceOf(NeedsNoConstructor::class, $instance);
        $this->assertInstanceOf(NoConstructor::class, $instance->dependency);
    }

    public function testNestedDependencyIsTheSharedInstance(): void
    {
        $dependency = $this->container->get(NoConstructor::class);
        $consumer = $this->container->get(NeedsNoConstructor::class);
        $this->assertSame($dependency, $consumer->dependency);
    }

    public function testScalarParameterUsesDefaultValue(): void
    {
        $instance = $this->container->get(ScalarWithDefault::class);
        $this->assertSame('default', $instance->name);
    }

    public function testScalarParameterWithoutDefaultThrows(): void
    {
        $this->expectException(DependencyHasNoDefaultValueException::class);
        $this->container->get(ScalarWithoutDefault::class);
    }

    public function testNullableDependencyResolvesToInstanceWhenAvailable(): void
    {
        $instance = $this->container->get(NullableDependency::class);
        $this->assertInstanceOf(NoConstructor::class, $instance->dependency);
    }

    public function testNullableBuiltinWithoutDefaultResolvesToNull(): void
    {
        $instance = $this->container->get(NullableBuiltinWithoutDefault::class);
        $this->assertNull($instance->value);
    }

    public function testUnionTypedParameterUsesDefaultValue(): void
    {
        $instance = $this->container->get(UnionTypeWithDefault::class);
        $this->assertSame(1, $instance->value);
    }

    public function testBoundInterfaceIsResolvedForDependency(): void
    {
        $this->container->set(ServiceInterface::class, ServiceImplementation::class);
        $instance = $this->container->get(NeedsInterface::class);
        $this->assertInstanceOf(ServiceImplementation::class, $instance->service);
    }

    public function testGetBoundInterfaceReturnsImplementation(): void
    {
        $this->container->set(ServiceInterface::class, ServiceImplementation::class);
        $this->assertInstanceOf(ServiceImplementation::class, $this->container->get(ServiceInterface::class));
    }

    public function testUnboundInterfaceDependencyWithNullDefaultResolvesToNull(): void
    {
        $instance = $this->container->get(OptionalInterface::class);
        $this->assertNull($instance->service);
    }

    public function testUnboundInterfaceDependencyWithoutFallbackThrows(): void
    {
        // An interface dependency with neither a binding, a default value nor a
        // nullable type cannot be resolved; the failure points at the parameter.
        $this->expectException(DependencyHasNoDefaultValueException::class);
        $this->container->get(NeedsInterface::class);
    }

    public function testGetUnboundInterfaceThrowsNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->container->get(UnboundInterface::class);
    }

    public function testAbstractClassIsNotInstantiable(): void
    {
        $this->expectException(DependencyIsNotInstantiableException::class);
        $this->container->get(AbstractClass::class);
    }

    public function testPrivateConstructorIsNotInstantiable(): void
    {
        $this->expectException(DependencyIsNotInstantiableException::class);
        $this->container->get(PrivateConstructor::class);
    }

    public function testCircularDependencyIsDetected(): void
    {
        $this->expectException(CircularDependencyException::class);
        $this->container->get(CircularA::class);
    }

    public function testSelfReferencingDependencyIsDetected(): void
    {
        $this->expectException(CircularDependencyException::class);
        $this->container->get(SelfReferencing::class);
    }

    public function testOptionalDependencyFallsBackToNullWhenClassCannotBeBuilt(): void
    {
        // ScalarWithoutDefault cannot be autowired (needs a string), but the
        // parameter is nullable with a default, so the container falls back.
        $instance = $this->container->get(OptionalUnbuildableDependency::class);
        $this->assertNull($instance->dependency);
    }

    public function testRequiredDependencyRethrowsWhenClassCannotBeBuilt(): void
    {
        $this->expectException(DependencyHasNoDefaultValueException::class);
        $this->container->get(RequiredUnbuildableDependency::class);
    }

    public function testClosureFactoryIsInvokedLazily(): void
    {
        $calls = 0;
        $this->container->set('service', function () use (&$calls) {
            $calls++;
            return new stdClass();
        });

        $this->assertSame(0, $calls, 'Factory must not run before the entry is requested.');
        $this->container->get('service');
        $this->container->get('service');
        $this->assertSame(1, $calls, 'Factory result must be cached after the first call.');
    }

    public function testClosureFactoryReceivesContainer(): void
    {
        $this->container->set('inner', new stdClass());
        $this->container->set('outer', fn (ContainerInterface $c) => $c->get('inner'));

        $this->assertSame($this->container->get('inner'), $this->container->get('outer'));
    }

    public function testSetWithoutConcreteUsesIdentifierAsDefinition(): void
    {
        $this->container->set(NoConstructor::class);
        $this->assertInstanceOf(NoConstructor::class, $this->container->get(NoConstructor::class));
    }

    public function testReSettingReplacesCachedInstance(): void
    {
        $this->container->set('value', 'first');
        $this->assertSame('first', $this->container->get('value'));

        $this->container->set('value', 'second');
        $this->assertSame('second', $this->container->get('value'));
    }

    public function testNotFoundExceptionIsAContainerException(): void
    {
        $this->expectException(ContainerException::class);
        $this->container->get('unknown-service');
    }
}
