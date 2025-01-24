<?php

/**
 * Trait TraitDataEntryEndMonth
 *
 * This trait contains methods for DataEntry objects that require "end_month"
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Exception\OutOfBoundsException;


trait TraitDataEntryEndMonth
{
    /**
     * Returns the month for this object
     *
     * @return int|null
     */
    public function getEndMonth(): ?int
    {
        return $this->getTypesafe('int', 'end_month');
    }


    /**
     * Sets the month for this object
     *
     * @param string|int|null $month
     *
     * @return static
     */
    public function setEndMonth(string|int|null $month): static
    {
        if ($month) {
            if (!is_numeric($month) or ($month < 1) or ($month > 12)) {
                $month = match(strtolower($month)) {
                    'jan', 'january'   => 1,
                    'feb', 'february'  => 2,
                    'mar', 'march'     => 3,
                    'apr', 'april'     => 4,
                    'may'              => 5,
                    'jun', 'june'      => 6,
                    'jul', 'july'      => 7,
                    'aug', 'august'    => 8,
                    'sep', 'september' => 9,
                    'oct', 'october'   => 10,
                    'nov', 'november'  => 11,
                    'dec', 'december'  => 12,
                    default            => throw new OutOfBoundsException(tr('Invalid month ":month" specified, must be integer 1-12, or the full English name of the month, or the first three letters of the English name of the month', [
                        ':month' => $month,
                    ])),
                };
            }

        } else {
            $month = null;
        }

        return $this->set(get_null($month), 'end_month');
    }
}
