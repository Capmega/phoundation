<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Core\Log\Log;
use Phoundation\Date\DateTimeZone;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Geo\Timezones\Timezone;


/**
 * Trait DataEntryTimezone
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
        return $this->getDataValue('int', 'timezones_id');
    }


    /**
     * Sets the timezones_id for this user
     *
     * @param string|int|null $timezones_id
     * @return static
     */
    public function setTimezonesId(string|int|null $timezones_id): static
    {
        if ($timezones_id and !is_natural($timezones_id)) {
            throw new OutOfBoundsException(tr('Specified timezones_id ":id" is not a natural number', [
                ':id' => $timezones_id
            ]));
        }

        return $this->setDataValue('timezones_id', get_null(isset_get_typed('integer', $timezones_id)));
    }


    /**
     * Returns the timezones_id for this user
     *
     * @return Timezone|null
     */
    public function getTimezone(): ?Timezone
    {
        $timezones_id = $this->getDataValue('int', 'timezones_id');

        if ($timezones_id) {
            return new Timezone($timezones_id);
        }

        return null;
    }


    /**
     * Sets the timezones_id for this user
     *
     * @param Timezone|string|int|null $timezone
     * @return static
     */
    public function setTimezone(Timezone|string|int|null $timezone): static
    {
        if ($timezone) {
            if (!is_numeric($timezone)) {
                $timezone = Timezone::get($timezone);
            }

            // Make sure this timezone is compatible with PHP!
            if (!in_array($timezone, DateTimeZone::listAbbreviations())) {
                Log::warning(tr('Specified timezone ":timezone" is not compatible with PHP, falling back to UTC', [
                    ':timezone' => $timezone
                ]));

                $timezone = TimeZone::new('UTC');
            }

            if (is_object($timezone)) {
                $timezone = $timezone->getId();
            }
        }

        return $this->setTimezonesId(get_null($timezone));
    }
}