<?php

/**
 * Trait TraitDataGzip
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Traits;

trait TraitDataGzip
{
    /**
     * Tracks if gzip should be used
     *
     * @var bool $gzip
     */
    protected bool $gzip = true;


    /**
     * Returns if gzip is used
     *
     * @return bool
     */
    public function getGzip(): bool
    {
        return $this->gzip;
    }


    /**
     * Sets if gzip is used
     *
     * @param bool $gzip
     *
     * @return static
     */
    public function setGzip(bool $gzip): static
    {
        $this->gzip = $gzip;

        return $this;
    }
}
