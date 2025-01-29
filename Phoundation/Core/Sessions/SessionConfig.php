<?php

/**
 * Class Config
 *
 * This class will try to return configuration data from the user or if missing, the system
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Core\Sessions;

use Phoundation\Core\Sessions\Interfaces\SessionConfigInterface;
use Phoundation\Utils\Config;


class SessionConfig extends Config implements SessionConfigInterface
{
    /**
     * Gets session configuration if available, or default configuration if not
     *
     * @param string|array $path
     * @param mixed|null   $default
     *
     * @return mixed
     */
    public function get(string|array $path = '', mixed $default = null): mixed
    {
        // TODO Add support for user configuration
        return parent::get($path, $default);
    }
}
