<?php

/**
 * DependencyIsNotInstantiableException.php
 *
 * This file is part of InitPHP Container.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 InitPHP Container
 * @license    https://github.com/InitPHP/Container/blob/main/LICENSE  MIT
 */

declare(strict_types=1);

namespace InitPHP\Container\Exception;

/**
 * Thrown when the container is asked to build a class that cannot be
 * instantiated, such as an interface, an abstract class or a class with a
 * non-public constructor.
 */
class DependencyIsNotInstantiableException extends ContainerException
{
}
