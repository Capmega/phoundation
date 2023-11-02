<?php

declare(strict_types=1);

namespace Phoundation\Core\Exception;

use Phoundation\Core\Exception\Interfaces\CoreStartupFailedExceptionInterface;


/**
 * Class CoreStartupFailedException
 *
 * This exception is thrown when the core startup fails
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class CoreStartupFailedException extends CoreException implements CoreStartupFailedExceptionInterface
{
}
