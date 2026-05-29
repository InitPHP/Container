<?php

/**
 * NotFoundException.php
 *
 * This file is part of InitPHP Container.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 InitPHP Container
 * @license    https://github.com/InitPHP/Container/blob/main/LICENSE  MIT
 */

declare(strict_types=1);

namespace InitPHP\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Thrown by {@see \InitPHP\Container\Container::get()} when the requested
 * identifier is neither a registered entry nor an autowirable class.
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
}
