<?php

/**
 * Trait TraitDataStopDate
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openstop_date.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use DateTimeInterface;
use DateTimeZone;
use Phoundation\Date\DateTime;

trait TraitDataStopDate
{
    /**
     * The stop_date date to use
     *
     * @var DateTimeInterface|null $stop_date
     */
    protected ?DateTimeInterface $stop_date = null;


    /**
     * Returns the stop_date
     *
     * @return DateTimeInterface|null
     */
    public function getStopDate(): ?DateTimeInterface
    {
        return $this->stop_date;
    }


    /**
     * Sets the stop_date date
     *
     * @param \DateTime|DateTimeInterface|string|null $stop_date
     * @param DateTimeZone|string|null                $timezone
     *
     * @return static
     */
    public function setStopDate(\DateTime|DateTimeInterface|string|null $stop_date, DateTimeZone|string|null $timezone = null): static
    {
        if ($stop_date === null) {
            $this->stop_date = null;

        } else {
            // Make sure that the stop_date has no time component
            if (!$stop_date or is_string($stop_date)) {
                $stop_date = DateTime::new($stop_date, $timezone);
            }
            $this->stop_date = DateTime::new($stop_date->format('Y-m-d'), $stop_date->getTimezone());
        }

        return $this;
    }
}
