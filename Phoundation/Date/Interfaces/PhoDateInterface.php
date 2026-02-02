<?php

namespace Phoundation\Date\Interfaces;

use Stringable;

interface PhoDateInterface extends PhoDateTimeInterface
{
    /**
     * Returns the source of this PhoDateTime object
     *
     * @return string
     */
    public function getSource(): string;

    /**
     * Converts the given month name (Nov, November, 11) to the month number (11)
     *
     * @param Stringable|string|int|null $month               The month value to convert. Must be a valid full month name, a valid three-letter month code, or a
     *                                                        month number [1-12]
     * @param bool                       $permit_null [false] If true, allows (and returns) NULL values for dates
     *
     * @return int|null
     */
    public static function convertMonthNameToNumber(Stringable|string|int|null $month, bool $permit_null = false): ?int;
}
