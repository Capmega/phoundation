<?php

/**
 * Trait TraitDataSleep
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


trait TraitDataSleep
{
    /**
     * Tracks sleep times for this object
     *
     * @var int|null $sleep
     */
    protected ?int $sleep = null;


    /**
     * Returns sleep times for this object
     *
     * @return int|null
     */
    public function getSleep(): ?int
    {
        return $this->sleep;
    }


    /**
     * Sets sleep times for this object
     *
     * @param int|null $sleep
     *
     * @return static
     */
    public function setSleep(?int $sleep): static
    {
        $this->sleep = $sleep;

        return $this;
    }
}
