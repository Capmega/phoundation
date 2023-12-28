<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Traits;


/**
 * Trait DataEntryWorkers
 *
 * This trait contains methods for DataEntry objects that requires a url
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryWorkers
{
    /**
     * Returns the workers for this object
     *
     * @return int|null
     */
    public function getWorkers(): ?int
    {
        return $this->getSourceFieldValue('int', 'workers');
    }


    /**
     * Sets the workers for this object
     *
     * @param int|null $workers
     * @return static
     */
    public function setWorkers(?int $workers): static
    {
        return $this->setSourceValue('workers', $workers);
    }
}