<?php

/**
 * Class NoTtyException
 *
 * This exception is thrown in case we are not on a console or don't have an (interactive) TTY
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cli
 */


declare(strict_types=1);

namespace Phoundation\Cli\Exception;

class CliNoTtyException extends CliException
{
}
