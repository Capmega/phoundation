<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


use Phoundation\Date\DateTime;
use Phoundation\Date\Interfaces\DateTimeInterface;

/**
 * Trait DataEntryStop
 *
 * This trait contains methods for DataEntry objects that require a stop
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryStop
{
    /**
     * Returns the stop for this object
     *
     * @return DateTimeInterface|null
     */
    public function getStop(): ?DateTimeInterface
    {
        return $this->getSourceColumnValue('datetime', 'stop');
    }


    /**
     * Sets the stop for this object
     *
     * @param DateTimeInterface|string|null $stop
     * @return static
     */
    public function setStop(DateTimeInterface|string|null $stop): static
    {
        return $this->setSourceValue('stop', new DateTime($stop, 'system'));
    }
}
