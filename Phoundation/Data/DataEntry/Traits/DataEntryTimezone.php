<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Core\Log\Log;
use Phoundation\Date\DateTimeZone;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Geo\Timezones\Interfaces\TimezoneInterface;
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
        return $this->getSourceColumnValue('int', 'timezones_id');
    }


    /**
     * Sets the timezones_id for this user
     *
     * @param int|null $timezones_id
     * @return static
     */
    public function setTimezonesId(?int $timezones_id): static
    {
        return $this->setSourceValue('timezones_id', $timezones_id);
    }


    /**
     * Returns the timezone for this user
     *
     * @return TimezoneInterface|null
     */
    public function getTimezone(): ?TimezoneInterface
    {
        $timezones_id = $this->getSourceColumnValue('int', 'timezones_id');

        if ($timezones_id) {
            return new Timezone($timezones_id);
        }

        return null;
    }


    /**
     * Returns the timezones_name for this user
     *
     * @return string|null
     */
    public function getTimezonesName(): ?string
    {
        return $this->getSourceColumnValue('string', 'timezones_name');
    }


    /**
     * Sets the timezones_name for this user
     *
     * @param string|null $timezones_name
     * @return static
     */
    public function setTimezonesName(?string $timezones_name): static
    {
        return $this->setSourceValue('timezones_name', $timezones_name);
    }
}
