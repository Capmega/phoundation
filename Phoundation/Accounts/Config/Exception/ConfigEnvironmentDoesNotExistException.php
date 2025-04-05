<?php

/**
 * Class ConfigEnvironmentDoesNotExistException
 *
 * This exception is thrown when the configuration class is accessed with an environment that does not exist
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Config\Exception;


class ConfigEnvironmentDoesNotExistException extends ConfigException
{
}
