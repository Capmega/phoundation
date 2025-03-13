<?php

/**
 * Trait TraitDataCacheKey
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


use Phoundation\Core\Log\Log;

trait TraitDataCacheKey
{
    use TraitDataCache;


    /**
     * Tracks what cache key will be used for this object
     *
     * @var string|null $cache_key
     */
    protected ?string $cache_key = null;


    /**
     * Returns what cache key will be used for this object
     *
     * @param string|null $append
     *
     * @return string|null
     */
    public function getCacheKey(?string $append = null): ?string
    {
        if ($this->cache) {
            if (empty($this->cache_key)) {
                $this->cache_key = PROJECT . '-' . $this->getCacheKeySeed();
            }

            return $this->cache_key . ($append ? '-' . $append : null);
        }

        return null;
    }


    /**
     * Sets what cache key will be used for this object
     *
     * @param string|null $cache_key
     *
     * @return static
     */
    public function setCacheKey(?string $cache_key): static
    {
        $this->cache_key = $cache_key;
        return $this;
    }
}
