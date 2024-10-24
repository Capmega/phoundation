<?php

/**
 * Trait TraitDataDate
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use DateTimeZone;
use Phoundation\Date\PhoDateTime;
use Phoundation\Date\Interfaces\DateTimeInterface;


trait TraitDataDate
{
    /**
     * The date to use
     *
     * @var DateTimeInterface|null $date
     */
    protected ?DateTimeInterface $date = null;


    /**
     * Returns the date
     *
     * @return DateTimeInterface|null
     */
    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }


    /**
     * Sets the date
     *
     * @param PhoDateTime|DateTimeInterface|string|null $date
     * @param DateTimeZone|string|null                  $timezone
     *
     * @return static
     */
    public function setDate(PhoDateTime|DateTimeInterface|string|null $date, DateTimeZone|string|null $timezone = null): static
    {
        if ($date instanceof PhoDateTime) {
            $this->date = new PhoDateTime($date->format('Y-m-d'), $timezone ?? $date->getTimezone());
        } else {
            if ($date === null) {
                $this->date = null;

            } else {
                $this->date = new PhoDateTime((string) $date, $timezone);
            }
        }

        return $this;
    }
}
