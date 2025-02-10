<?php

/**
 * Trait TraitDataEntryMinute
 *
 * This trait contains methods for DataEntry objects that require a minute
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Date\PhoDateTime;

trait TraitDataEntryMinute
{
    /**
     * Returns the minute for this object
     *
     * @return int|null
     */
    public function getMinute(): ?int
    {
        return $this->getTypesafe('int', 'minute');
    }


    /**
     * Sets the minute for this object
     *
     * @param int|null $minute
     *
     * @return static
     */
    public function setMinute(?int $minute): static
    {
        return $this->set(get_null($minute), 'minute');
    }
}
