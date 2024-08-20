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

use Phoundation\Date\DateTime;
use Phoundation\Date\Interfaces\DateTimeInterface;

trait TraitDataEntryStart
{
    /**
     * Returns the start for this object
     *
     * @return DateTimeInterface|null
     */
    public function getStart(): ?DateTimeInterface
    {
        return $this->getTypesafe('datetime', 'start');
    }


    /**
     * Sets the start for this object
     *
     * @param DateTimeInterface|string|null $start
     *
     * @return static
     */
    public function setStart(DateTimeInterface|string|null $start): static
    {
        return $this->set($start ? new DateTime($start, 'system') : null, 'start');
    }
}
