<?php

/**
 * DependencyIsNotInstantiableException.php
 *
 * This file is part of InitPHP Container.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com>
 * @copyright  Copyright © 2022 InitPHP Container
 * @license    https://github.com/InitPHP/Container/blob/main/LICENSE  MIT
 */

declare(strict_types=1);

namespace InitPHP\Container\Exception;

/**
 * Thrown when the container has to build a class that cannot be instantiated:
 * an abstract class, an enum, or a class with a non-public constructor. It
 * covers every route into that class (get(), a required constructor dependency,
 * a binding that names it as the concrete class). An interface with no binding
 * never reaches this exception: get() throws NotFoundException and a required
 * dependency throws DependencyHasNoDefaultValueException.
 */
class DependencyIsNotInstantiableException extends ContainerException {}
