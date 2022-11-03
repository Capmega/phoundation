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



    /**
     * Write the specified page to cache
     *
     * @return string
     */
    public static function writePage(string $hash, string $data)
    {
    }



    /**
     * Read the specified page from cache.
     *
     * @note: NULL will be returned if the specified hash does not exist in cache
     * @return string|null
     */
    public static function readPage(string $hash): ?string
    {
        return null;
    }
}