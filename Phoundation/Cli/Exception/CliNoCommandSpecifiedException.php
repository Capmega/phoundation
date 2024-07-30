<?php

/**
 * Class NoMethodSpecifiedException
 *
 * This exception is thrown when no method script was specified on the command line
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cli
 */

declare(strict_types=1);

namespace Phoundation\Cli\Exception;

class CliNoCommandSpecifiedException extends CliException
{
}
