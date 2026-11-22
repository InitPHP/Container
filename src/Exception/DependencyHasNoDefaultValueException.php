<?php

/**
 * DependencyHasNoDefaultValueException.php
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
 * Thrown when a constructor parameter cannot be autowired and has neither a
 * default value nor a nullable type to fall back on.
 */
class DependencyHasNoDefaultValueException extends ContainerException {}
