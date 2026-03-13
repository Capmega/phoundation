<?php

/**
 * Trait TraitDataEntryFrom
 *
 * This trait contains methods for DataEntry objects that require a from date
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


trait TraitDataEntryFrom
{
    /**
     * Returns the form datetime for this object
     *
     * @return string|null
     */
    public function getFrom(): string|null
    {
        return $this->getTypesafe('string', 'from');
    }


    /**
     * Returns the form PhoDateTime object for this object
     *
     * @return PhoDateTimeInterface|null
     */
    public function getFromObject(): PhoDateTimeInterface|null
    {
        return PhoDateTime::newOrNull($this->getFrom());
    }


    /**
     * Sets the from field for this object
     *
     * @param PhoDateTimeInterface|string|null $date_time
     *
     * @return static
     */
    public function setFrom(PhoDateTimeInterface|string|null $date_time): static
    {
        if ($date_time instanceof PhoDateTimeInterface) {
            $date_time = $date_time->format('Y-m-d');
        }

        return $this->set($date_time, 'from');
    }
}
