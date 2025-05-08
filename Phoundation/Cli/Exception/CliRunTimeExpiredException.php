<?php

/**
 * Class CliRunTimeExpiredException
 *
 * This exception is thrown when the maximum runtime for a CLICommand is surpassed
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Cli\Exception;


use Phoundation\Core\Exception\CoreException;

class CliRunTimeExpiredException extends CoreException
{
}
