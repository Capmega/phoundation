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
     * @var PhoDateTimeInterface|null $_date
     */
    protected ?PhoDateTimeInterface $_date = null;


    /**
     * Returns the date
     *
     * @return PhoDateTimeInterface|null
     */
    public function getDateObject(): ?PhoDateTimeInterface
    {
        return $this->_date;
    }


    /**
     * Sets the date
     *
     * @param PhoDateTimeInterface|null $date
     *
     * @return static
     */
    public function setDateObject(?PhoDateTimeInterface $date): static
    {
        $this->_date = $date;
        return $this;
    }
}
