<?php

/**
 * Trait TraitDataDate
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use DateTimeZone;
use Phoundation\Date\PhoDateTime;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;


trait TraitDataDate
{
    /**
     * The date to use
     *
     * @var PhoDateTimeInterface|null $date
     */
    protected ?PhoDateTimeInterface $date = null;


    /**
     * Returns the date
     *
     * @return PhoDateTimeInterface|null
     */
    public function getDate(): ?PhoDateTimeInterface
    {
        return $this->date;
    }


    /**
     * Sets the date
     *
     * @param PhoDateTimeInterface|string|null $date
     * @param DateTimeZone|string|null         $timezone
     *
     * @return static
     */
    public function setDate(PhoDateTimeInterface|string|null $date, DateTimeZone|string|null $timezone = null): static
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
