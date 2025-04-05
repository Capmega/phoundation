<?php

/**
 * Class ConfigParseFailedException
 *
 * This exception is thrown when the specified configuration file could not be parsed (likely due to syntax error)
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Config\Exception;

class ConfigParseFailedException extends ConfigException
{
}
