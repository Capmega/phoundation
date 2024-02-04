<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryReason
 *
 * This trait contains methods for DataEntry objects that require a name and reason
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryReason
{
    /**
     * Returns the reason for this object
     *
     * @return string|null
     */
    public function getReason(): ?string
    {
        return $this->getSourceValueTypesafe('string', 'reason');
    }


    /**
     * Sets the reason for this object
     *
     * @param string|null $reason
     * @return static
     */
    public function setReason(?string $reason): static
    {
        return $this->setSourceValue('reason', $reason);
    }
}
