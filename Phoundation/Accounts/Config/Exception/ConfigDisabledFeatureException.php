<?php

/**
 * Class ConfigDisabledFeatureException
 *
 * This exception is thrown when a feature is requested that was disabled by configuration
 *
 * @author    Sven Olaf Oostenbrink
 * @copyright Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Config\Exception;


class ConfigDisabledFeatureException extends ConfigException
{
}
