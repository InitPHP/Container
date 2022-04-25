<?php
/**
 * DependencyHasNoDefaultValueException.php
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

namespace InitPHP\Container\Exception;

use \Psr\Container\NotFoundExceptionInterface;

class DependencyHasNoDefaultValueException extends \Exception implements NotFoundExceptionInterface
{
}
