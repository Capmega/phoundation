<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryTimeout
 *
 * This trait contains methods for DataEntry objects that require a timeout
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryTimeout
{
    /**
     * Returns the timeout for this object
     *
     * @return int|null
     */
    public function getTimeout(): ?int
    {
        return $this->getSourceValueTypesafe('int', 'timeout');
    }


    /**
     * Sets the timeout for this object
     *
     * @param int|null $timeout
     * @return static
     */
    public function setTimeout(?int $timeout): static
    {
        return $this->setSourceValue('timeout', $timeout);
    }
}
