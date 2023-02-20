<?php

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Geo\Timezones\Timezone;



/**
 * Trait DataEntryTimezone
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryTimezone
{
    /**
     * Returns the timezones_id for this user
     *
     * @return int|null
     */
    public function getTimezonesId(): ?int
    {
        return $this->getDataValue('timezones_id');
    }



    /**
     * Sets the timezones_id for this user
     *
     * @param int|null $timezones_id
     * @return static
     */
    public function setTimezonesId(?int $timezones_id): static
    {
        return $this->setDataValue('timezones_id', $timezones_id);
    }



    /**
     * Returns the timezones_id for this user
     *
     * @return Timezone|null
     */
    public function getTimezone(): ?Timezone
    {
        $timezones_id = $this->getDataValue('timezones_id');

        if ($timezones_id) {
            return new Timezone($timezones_id);
        }

        return null;
    }



    /**
     * Sets the timezones_id for this user
     *
     * @param Timezone|string|int|null $timezones_id
     * @return static
     */
    public function setTimezone(Timezone|string|int|null $timezones_id): static
    {
        if (!is_numeric($timezones_id)) {
            $timezones_id = Timezone::get($timezones_id);
        }

        if (is_object($timezones_id)) {
            $timezones_id = $timezones_id->getId();
        }

        return $this->setDataValue('timezones_id', $timezones_id);
    }
}