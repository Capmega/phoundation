<?php

declare(strict_types=1);

namespace Phoundation\Date;

use Phoundation\Data\Iterator;
use Phoundation\Date\Interfaces\DateRangePickerRangesInterface;
use Phoundation\Exception\OutOfBoundsException;
use Stringable;


/**
 * Class DateRangePickerRanges
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Date
 */
class DateRangePickerRanges extends Iterator implements DateRangePickerRangesInterface
{
    /**
     * @inheritDoc
     */
    public function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null = true, bool $exception = true): static
    {
        if (!is_string($value)) {
            throw new OutOfBoundsException(tr('Specified value ":value" must be string', [
                ':value' => $value
            ]));
        }

        if (!preg_match('/^\[\s*moment\s*\(.*?\)(?:.[a-z0-9-_]+\(.*?\))?\s*,\s+moment\s*\(.*?\)(?:.[a-z0-9-_]+\(.*?\)\s*)?\]$/i', $value)) {
            throw new OutOfBoundsException(tr('Specified value ":value" for key ":key" must be a valid daterangepicker range string like "[moment().subtract(6, "days"), moment()]"', [
                ':key'   => $key,
                ':value' => $value
            ]));
        }

        return parent::add($value, $key, $skip_null, $exception);
    }


    /**
     * Set the default daterangepicker ranges
     *
     * @return $this
     */
    public function useDefault(): static
    {
        return $this
            ->clear()
            ->add('[moment(), moment()]'                                                                          , tr('Today'))
            ->add('[moment().subtract(1, "days"), moment().subtract(1, "days")]'                                  , tr('Yesterday'))
            ->add('[moment().subtract(6, "days"), moment()]'                                                       , tr('Last 7 Days'))
            ->add('[moment().subtract(29, "days"), moment()]'                                                     , tr('Last 30 Days'))
            ->add('[moment().startOf("month"), moment().endOf("month")]'                                          , tr('This Month'))
            ->add('[moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")]', tr('Last Month'))
            ->add('[moment().startOf("year"), moment().endOf("year")]'                                            , tr('This Year'))
            ->add('[moment().subtract(1, "year").startOf("year"), moment().subtract(1, "year").endOf("year")]'    , tr('Last Year'));
    }
}
