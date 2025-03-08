<?php

/**
 * Trait TraitDataCache
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opencache.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataCache
{
    /**
     * Tracks if cache is enabled for this object or not
     *
     * @var bool $cache
     */
    protected bool $cache = false;


    /**
     * Returns the cache value
     *
     * @return bool
     */
    public function getCache(): bool
    {
        return $this->cache;
    }


    /**
     * Sets the cache value
     *
     * @param bool $cache
     *
     * @return static
     */
    public function setCache(bool $cache): static
    {
        $this->cache = $cache;
        return $this;
    }
}
