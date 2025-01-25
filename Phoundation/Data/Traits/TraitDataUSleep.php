<?php

/**
 * Trait TraitDataUSleep
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataUSleep
{
    /**
     * Tracks sleep times for this object
     *
     * @var int|null $usleep
     */
    protected ?int $usleep = null;


    /**
     * Returns sleep times for this object
     *
     * @return int|null
     */
    public function getUSleep(): ?int
    {
        return $this->usleep;
    }


    /**
     * Sets sleep times for this object
     *
     * @param int|null $usleep
     *
     * @return static
     */
    public function setUSleep(?int $usleep): static
    {
        $this->usleep = $usleep;

        return $this;
    }
}
