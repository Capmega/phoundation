<?php

namespace Phoundation\Cache;

use Phoundation\Core\Log;




/**
 * Cache class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Cache
 */
class Cache
{
    /**
     * Clear all cache data
     *
     * @return void
     */
    public static function clear(): void
    {
        // TODO Implement
        Log::success(tr('Cleared cache'));
    }
}