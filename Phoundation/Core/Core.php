<?php

namespace Phoundation\Core;

/**
 * Class Core
 *
 * This is the
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2021 <copyright@capmega.com>
 * @package Phoundation\Core
 */
class Core{
    /**
     * The generic system register to store data
     *
     * @var bool $debug
     */
    protected static bool $debug = false;



    /**
     * Returns if the system is running in debug mode or not
     *
     * @return bool
     */
    public static function debug(): bool
    {
        return self::$debug;
    }
}