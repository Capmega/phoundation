<?php

declare(strict_types=1);

namespace Phoundation\Cli\Exception;

/**
 * Class CommandNotFoundException
 *
 * This exception is thrown when the specified script method does not lead to an executable script
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cli
 */
class CliCommandNotFoundException extends CliException
{
}
