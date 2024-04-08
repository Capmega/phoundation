<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use DateTimeInterface;
use DateTimeZone;
use Phoundation\Date\DateTime;

/**
 * Trait TraitDataStartDateTime
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openstart.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataStartDateTime
{
    /**
     * The start date time to use
     *
     * @var \Phoundation\Date\Interfaces\DateTimeInterface|null $start_datetime
     */
    protected ?\Phoundation\Date\Interfaces\DateTimeInterface $start_datetime = null;


    /**
     * Returns the start date time
     *
     * @return \Phoundation\Date\Interfaces\DateTimeInterface|null
     */
    public function getStartDateTime(): ?\Phoundation\Date\Interfaces\DateTimeInterface
    {
        return $this->start_datetime;
    }


    /**
     * Sets the start datetime
     *
     * @param \DateTime|DateTimeInterface|string|null $start_datetime
     * @param DateTimeZone|string|null                $timezone
     *
     * @return static
     */
    public function setStartDateTime(\DateTime|DateTimeInterface|string|null $start_datetime, DateTimeZone|string|null $timezone = null): static
    {
        if ($start_datetime === null) {
            $this->start_datetime = null;

        } else {
            $this->start_datetime = DateTime::new($start_datetime, $timezone);
        }

        return $this;
    }
}
