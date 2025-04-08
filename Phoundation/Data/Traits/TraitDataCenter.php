<?php

/**
 * Trait TraitDataCenter
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opencenter.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataCenter
{
    /**
     * Tracks the center flag
     *
     * @var bool $center
     */
    protected bool $center = false;


    /**
     * Returns the center flag
     *
     * @return bool
     */
    public function getCenter(): bool
    {
        return $this->center;
    }


    /**
     * Sets the center flag
     *
     * @param bool $center
     *
     * @return static
     */
    public function setCenter(bool $center): static
    {
        $this->center = $center;
        return $this;
    }
}
