<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands\Exception;

/**
 * Class NoSudoException
 *
 * This exception is thrown if the current process owner has no sudo privileges for the specified command
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */
class NoSudoException extends CommandsException
{
}
