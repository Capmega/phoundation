<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use DateTimeZone;
use Phoundation\Date\DateTime;
use Phoundation\Date\Interfaces\DateTimeInterface;

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
     * @param DateTime|DateTimeInterface|string|null $date
     * @param DateTimeZone|string|null               $timezone
     *
     * @return static
     */
    public function setDate(DateTime|DateTimeInterface|string|null $date, DateTimeZone|string|null $timezone = null): static
    {
        if ($date instanceof DateTime) {
            $this->date = new DateTime($date->format('Y-m-d'), $timezone ?? $date->getTimezone());
        } else {
            if ($date === null) {
                $this->date = null;

            } else {
                $this->date = new DateTime((string) $date, $timezone);
            }
        }

        return $this;
    }
}
