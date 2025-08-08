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
use Phoundation\Date\Enums\EnumDateFormat;
use Phoundation\Date\Interfaces\PhoDateTimeZoneInterface;
use Phoundation\Date\PhoDateTime;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;


trait TraitDataObjectDate
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
    public function getDateObject(): ?PhoDateTimeInterface
    {
        return $this->date;
    }


    /**
     * Sets the date
     *
     * @param PhoDateTimeInterface|string|null     $date
     * @param PhoDateTimeZoneInterface|string|null $timezone
     *
     * @return static
     */
    public function setDateObject(PhoDateTimeInterface|string|null $date, PhoDateTimeZoneInterface|string|null $timezone = null): static
    {
        if ($date instanceof PhoDateTime) {
            $this->date = new PhoDateTime($date->format('Y-m-d'), $timezone ?? $date->getTimezone());

        } else {
            if (empty($date)) {
                $this->date = null;

            } else {
                $this->date = new PhoDateTime((string) $date, $timezone);
            }
        }

        return $this;
    }
}
