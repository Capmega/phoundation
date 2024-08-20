<?php

/**
 * Trait TraitDataBegin
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openstart_datetime.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use DateTimeInterface;
use DateTimeZone;
use Phoundation\Date\DateTime;

trait TraitDataStartDate
{
    /**
     * The start_datetime date to use
     *
     * @var DateTimeInterface|null $start_date
     */
    protected ?DateTimeInterface $start_date = null;


    /**
     * Returns the start_datetime
     *
     * @return DateTimeInterface|null
     */
    public function getStartDateTime(): ?DateTimeInterface
    {
        return $this->start_date;
    }


    /**
     * Sets the start_date date
     *
     * @param \DateTime|DateTimeInterface|string|null $start_date
     * @param DateTimeZone|string|null                $timezone
     *
     * @return static
     */
    public function setStartDate(\DateTime|DateTimeInterface|string|null $start_date, DateTimeZone|string|null $timezone = null): static
    {
        if ($start_date === null) {
            $this->start_date = null;

        } else {
            // Make sure that the start_datetime has no time component
            if (!$start_date or is_string($start_date)) {
                $start_date = DateTime::new($start_date, $timezone);
            }
            $this->start_date = DateTime::new($start_date->format('Y-m-d'), $start_date->getTimezone());
        }

        return $this;
    }
}
