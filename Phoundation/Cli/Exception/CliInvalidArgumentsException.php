<?php

/**
 * Class CliInvalidArgumentsException
 *
 * This exception is thrown when the running CLI script detects unknown arguments
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cli
 */

declare(strict_types=1);

namespace Phoundation\Cli\Exception;

use Phoundation\Exception\OutOfBoundsException;

class CliInvalidArgumentsException extends OutOfBoundsException
{
}
