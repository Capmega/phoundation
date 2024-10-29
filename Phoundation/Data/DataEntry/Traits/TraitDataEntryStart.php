<?php

/**
 * Trait TraitDataEntryStart
 *
 * This trait contains methods for DataEntry objects that require a start
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Date\PhoDateTime;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;


trait TraitDataEntryStart
{
    /**
     * Returns the start for this object
     *
     * @return PhoDateTimeInterface|null
     */
    public function getStart(): ?PhoDateTimeInterface
    {
        return $this->getTypesafe('datetime', 'start');
    }


    /**
     * Sets the start for this object
     *
     * @param PhoDateTimeInterface|string|null $start
     *
     * @return static
     */
    public function setStart(PhoDateTimeInterface|string|null $start): static
    {
        return $this->set($start ? new PhoDateTime($start, 'system') : null, 'start');
    }


    /**
     * Returns true if start has a value
     *
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->getStart() !== null;
    }
}
