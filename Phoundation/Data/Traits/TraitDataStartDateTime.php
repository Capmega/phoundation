<?php

/**
 * Trait TraitDataStartDateTime
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openstart.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use DateTimeInterface;
use DateTimeZone;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Date\PhoDateTime;


trait TraitDataStartDateTime
{
    /**
     * The start date time to use
     *
     * @var PhoDateTimeInterface|null $start_datetime
     */
    protected ?PhoDateTimeInterface $start_datetime = null;


    /**
     * Returns the start date time
     *
     * @return PhoDateTimeInterface|null
     */
    public function getStartDateTime(): ?PhoDateTimeInterface
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
        if (empty($start_datetime)) {
            $this->start_datetime = null;

        } else {
            $this->start_datetime = PhoDateTime::new($start_datetime, $timezone);
        }

        return $this;
    }
}
