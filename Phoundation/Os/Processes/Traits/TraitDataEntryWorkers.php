<?php

/**
 * Trait TraitDataEntryWorkers
 *
 * This trait contains methods for DataEntry objects that requires a url
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Os\Processes\Traits;

trait TraitDataEntryWorkers
{
    /**
     * Returns the workers for this object
     *
     * @return int|null
     */
    public function getMinimumWorkers(): ?int
    {
        return $this->getValueTypesafe('int', 'maximum_workers');
    }


    /**
     * Sets the workers for this object
     *
     * @param int|null $workers
     *
     * @return static
     */
    public function setMinimumWorkers(?int $workers): static
    {
        return $this->set($workers, 'maximum_workers');
    }


    /**
     * Returns the workers for this object
     *
     * @return int|null
     */
    public function getMaximumWorkers(): ?int
    {
        return $this->getValueTypesafe('int', 'maximum_workers');
    }


    /**
     * Sets the workers for this object
     *
     * @param int|null $workers
     *
     * @return static
     */
    public function setMaximumWorkers(?int $workers): static
    {
        return $this->set($workers, 'maximum_workers');
    }
}
