<?php

/**
 * Class ConfigEnvironmentNotSetException
 *
 * This exception is thrown when the configuration class is accessed while no environment has been specified yet
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Utils\Exception;


class ConfigEnvironmentNotSetException extends ConfigException
{
}
