<?php

/**
 * Trait TraitDataEntryStop
 *
 * This trait contains methods for DataEntry objects that require a stop
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Date\PhoDateTime;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;


trait TraitDataEntryStop
{
    /**
     * Returns the stop for this object
     *
     * @return PhoDateTimeInterface|null
     */
    public function getStop(): ?PhoDateTimeInterface
    {
        return $this->getTypesafe('datetime', 'stop');
    }


    /**
     * Sets the stop for this object
     *
     * @param PhoDateTimeInterface|string|null $stop
     *
     * @return static
     */
    public function setStop(PhoDateTimeInterface|string|null $stop): static
    {
        return $this->set($stop ? new PhoDateTime($stop, 'system') : null, 'stop');
    }


    /**
     * Returns true if stop has a value
     *
     * @return bool
     */
    public function isStopped(): bool
    {
        return $this->getStop() !== null;
    }
}
