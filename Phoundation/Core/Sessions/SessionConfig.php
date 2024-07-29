<?php

/**
 * Class Config
 *
 * This class will try to return configuration data from the user or if missing, the system
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Core\Sessions;

use Phoundation\Core\Sessions\Interfaces\SessionConfigInterface;
use Phoundation\Utils\Config;

class SessionConfig extends Config implements SessionConfigInterface
{
    /**
     * Singleton variable for the main config object
     *
     * @var SessionConfigInterface|null $session_instance
     */
    protected static ?SessionConfigInterface $session_instance = null;


    /**
     * Singleton, ensure to always return the same Log object.
     *
     * @return SessionConfigInterface
     */
    public static function getInstance(): SessionConfigInterface
    {
        if (!isset(static::$session_instance)) {
            static::$session_instance = new static();
        }

        return static::$session_instance;
    }


    /**
     * Gets session configuration if available, or default configuration if not
     *
     * @param string|array $path
     * @param mixed|null   $default
     * @param mixed|null   $specified
     *
     * @return mixed
     */
    public static function get(string|array $path = '', mixed $default = null, mixed $specified = null): mixed
    {
        // TODO Add support for user configuration
        return parent::get($path, $default, $specified);
    }
}
