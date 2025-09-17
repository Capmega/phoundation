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

use Phoundation\Cache\Cache;
use Phoundation\Data\Interfaces\CacheableObjectInterface;
use Phoundation\Data\Interfaces\ContentObjectInterface;


trait TraitDataCache
{
    /**
     * Tracks if cache is enabled for this object or not
     *
     * @var bool $use_cache
     */
    protected bool $use_cache = true;


    /**
     * Returns if caching should be used, or not
     *
     * @note If global caching has been disabled, this method will always return false, even if the use_cache was set to true using object::setUseCache(true)
     *
     * @return bool
     */
    public function getUseCache(): bool
    {
        if (Cache::getEnabled()) {
            return $this->use_cache;
        }

        return false;
    }


    /**
     * Returns if caching should be used, or not
     *
     * @note If global caching has been disabled, this method will always return false, even if the use_cache was set to true using object::setUseCache(true)
     *
     * @return bool
     */
    public function getUseLocalCache(): bool
    {
        return $this->getUseCache() and config()->getBoolean('cache.local.enabled', true);
    }


    /**
     * Returns if caching should be used, or not
     *
     * @note If global caching has been disabled, this method will always return false, even if the use_cache was set to true using object::setUseCache(true)
     *
     * @return bool
     */
    public function getUseGlobalCache(): bool
    {
        return $this->getUseCache() and config()->getBoolean('cache.global.enabled', true);
    }


    /**
     * Sets if caching should be used, or not
     *
     * @param bool $use_cache
     *
     * @return static
     */
    public function setUseCache(bool $use_cache): static
    {
        $this->use_cache = $use_cache;

        // Pass
        if ($this instanceof ContentObjectInterface) {
            $o_content = $this->getContent();

            if ($o_content instanceof CacheableObjectInterface) {
                $o_content->setUseCache($use_cache);
            }
        }

        return $this;
    }
}
