<?php

declare(strict_types=1);

namespace Phoundation\Core\Exception;

use Phoundation\Core\Exception\Interfaces\CoreReadonlyExceptionInterface;

/**
 * Class CoreReadonlyException
 *
 * This exception is thrown when the core is in readonly mode and a write is attempted
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */
class CoreReadonlyException extends CoreException implements CoreReadonlyExceptionInterface
{
}
