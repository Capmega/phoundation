<?php

/**
 * Class ConfigEmptyEnvironmentException
 *
 * This exception is thrown when an empty environment is requested while this is not allowed with Config::$allow_empty_environment
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Config\Exception;

class ConfigEmptyEnvironmentException extends ConfigException
{
}
