<?php

/**
 * Trait TraitDataEntryTimezone
 *
 * This trait contains methods for DataEntry objects that require a timezone
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Geo\Timezones\Timezone;
use Phoundation\Geo\Timezones\Interfaces\TimezoneInterface;



trait TraitDataEntryTimezone
{
    /**
     * Setup virtual configuration for Timezones
     *
     * @return static
     */
    protected function addVirtualConfigurationTimezones(): static
    {
        return $this->addVirtualConfiguration('timezones', Timezone::class, [
            'id',
            'code',
            'name'
        ]);
    }


    /**
     * Returns the timezones_id column
     *
     * @return int|null
     */
    public function getTimezonesId(): ?int
    {
        return $this->getVirtualData('timezones', 'int', 'id');
    }


    /**
     * Sets the timezones_id column
     *
     * @param int|null $id
     * @return static
     */
    public function setTimezonesId(?int $id): static
    {
        return $this->setVirtualData('timezones', $id, 'id');
    }


    /**
     * Returns the timezones_code column
     *
     * @return string|null
     */
    public function getTimezonesCode(): ?string
    {
        return $this->getVirtualData('timezones', 'string', 'code');
    }


    /**
     * Sets the timezones_code column
     *
     * @param string|null $code
     * @return static
     */
    public function setTimezonesCode(?string $code): static
    {
        return $this->setVirtualData('timezones', $code, 'code');
    }


    /**
     * Returns the timezones_name column
     *
     * @return string|null
     */
    public function getTimezonesName(): ?string
    {
        return $this->getVirtualData('timezones', 'string', 'name');
    }


    /**
     * Sets the timezones_name column
     *
     * @param string|null $name
     * @return static
     */
    public function setTimezonesName(?string $name): static
    {
        return $this->setVirtualData('timezones', $name, 'name');
    }


    /**
     * Returns the Timezone Object
     *
     * @return TimezoneInterface|null
     */
    public function getTimezoneObject(): ?TimezoneInterface
    {
        return $this->getVirtualObject('timezones');
    }


    /**
     * Returns the timezones_id for this user
     *
     * @param TimezoneInterface|null $o_object
     *
     * @return static
     */
    public function setTimezoneObject(?TimezoneInterface $o_object): static
    {
        return $this->setVirtualObject('timezones', $o_object);
    }
}
