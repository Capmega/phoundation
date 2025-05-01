<?php

/**
 * Trait TraitDataEntryServiceDate
 *
 * This trait contains methods for DataEntry objects that require a service date
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

trait TraitDataEntryServiceDate
{
    /**
     * Returns the DateTime object from this Encounter's service date
     *
     * @return string|null
     */
    public function getServiceDate(): string|null
    {
        return $this->getTypesafe('string', 'service_date');
    }


    /**
     * Returns the DateTime object from this Encounter's service date
     *
     * @return PhoDateTimeInterface|null
     */
    public function getServiceDateObject(): PhoDateTimeInterface|null
    {
        return PhoDateTime::newNull($this->getServiceDate());
    }


    /**
     * Sets the service date field for this Encounter
     *
     * @param PhoDateTimeInterface|string|null $date_time
     *
     * @return static
     */
    public function setServiceDate(PhoDateTimeInterface|string|null $date_time): static
    {
        if ($date_time instanceof PhoDateTimeInterface) {
            $date_time = $date_time->format('Y-m-d');
        }

        return $this->set($date_time, 'service_date');
    }
}
