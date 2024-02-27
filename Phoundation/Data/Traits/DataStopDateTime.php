<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use DateTimeInterface;
use DateTimeZone;
use Phoundation\Date\DateTime;


/**
 * Trait DataStopDateTime
 *
 *
 *
 * @author Sven Olaf Oostenbrink <sven@medinet.ca>
 * @license http://openstop_datetime.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Medinet <copyright@medinet.ca>
 * @package Phoundation\Data
 */
trait DataStopDateTime
{
    /**
     * The stop_datetime date time to use
     *
     * @var DateTimeInterface|null $stop_datetime
     */
    protected ?DateTimeInterface $stop_datetime = null;


    /**
     * Returns the stop_datetime date time
     *
     * @return DateTimeInterface|null
     */
    public function getStopDateTime(): ?DateTimeInterface
    {
        return $this->stop_datetime;
    }


    /**
     * Sets the stop_datetime date time
     *
     * @param \DateTime|DateTimeInterface|string|null $stop_datetime
     * @param DateTimeZone|string|null $timezone
     * @return static
     */
    public function setStopDateTime(\DateTime|DateTimeInterface|string|null $stop_datetime, DateTimeZone|string|null $timezone = null): static
    {
        if ($stop_datetime === null) {
            $this->stop_datetime = null;

        } else {
            $this->stop_datetime = DateTime::new($stop_datetime, $timezone);
        }

        return $this;
    }
}
