<?php

/**
 * CircularDependencyException.php
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
 * Thrown when a class depends on itself either directly or through a chain of
 * other classes, which would otherwise cause unbounded recursion.
 */
class CircularDependencyException extends ContainerException
{
}
