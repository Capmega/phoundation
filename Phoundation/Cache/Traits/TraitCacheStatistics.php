<?php

/**
 * Trait TraitCacheStatistics
 *
 * This trait contains methods to gather and return cache statistics
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Cache\Traits;

use Phoundation\Cache\Exception\CacheNotFoundException;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Timers;
use Phoundation\Developer\Debug\Debug;
use Phoundation\Utils\Strings;
use Stringable;


trait TraitCacheStatistics
{
    /**
     * Tracks the number of time the cache was hit
     *
     * @var int $cache_hits
     */
    protected static int $cache_hits = 0;

    /**
     * Tracks the number of time the cache was missed
     *
     * @var int $cache_miss
     */
    protected static int $cache_miss = 0;

    /**
     * Tracks the number of time the cache was checked
     *
     * @var int $cache_lookups
     */
    protected static int $cache_lookups = 0;


    /**
     * Returns the number of cache hits so far
     *
     * @return int
     */
    public static function getHits(): int
    {
        return self::$cache_hits;
    }


    /**
     * Returns the number of cache misses so far
     *
     * @return int
     */
    public static function getCacheMiss(): int
    {
        return self::$cache_miss;
    }


    /**
     * Returns the number of cache checks
     *
     * @return int
     */
    public static function getLookups(): int
    {
        return self::$cache_lookups;
    }


    /**
     * Returns the efficiency of the cache, so far
     *
     * @return float
     */
    public static function getEfficiency(): float
    {
        // Avoid division by 0
        if (static::$cache_lookups) {
            return (self::$cache_hits / static::$cache_lookups) * 100;
        }

        return 0;
    }


    /**
     * Logs the cache statistics when in debug mode
     *
     * @return void
     */
    public static function logStatistics(): void
    {
        if (Debug::isEnabled() and Log::getVerbose()) {
            Log::write(ts('STATISTIC ":class" object has ":count" cached object(s) with ":checks" checks, ":hits" hits, and ":percent" effectiveness', [
                ':class'   => Strings::fromReverse(static::class, '\\'),
                ':count'   => static::geSectionCount(),
                ':checks'  => static::getLookups(),
                ':hits'    => static::getHits(),
                ':percent' => number_format(static::getEfficiency(), 2) . '%',
            ]), 'debug', 9);
        }
    }
}
