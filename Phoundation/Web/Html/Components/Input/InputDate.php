<?php

/**
 * Class InputDate
 *
 * @see       https://www.daterangepicker.com/
 * @see       https://getdatepicker.com/
 * @see       https://flatpickr.js.org/
 * @see       https://github.com/eureka2/ab-datepicker
 * @see       https://datebox.jtsage.dev/
 * @see       https://preview.keenthemes.com/html/metronic/docs/forms/daterangepicker
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Date\PhoDateTime;
use Phoundation\Web\Html\Enums\EnumInputType;
use Stringable;


class InputDate extends InputText
{
    /**
     * InputDate class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->input_type = EnumInputType::date;
        parent::__construct($content);
    }


    /**
     * Sets the value for the input element
     *
     * @param PhoDateTimeInterface|Stringable|string|float|int|null $value
     * @param bool                                         $make_safe
     *
     * @return static
     */
    public function setValue(PhoDateTimeInterface|Stringable|string|float|int|null $value, bool $make_safe = true): static
    {
        if ($value instanceof PhoDateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        return parent::setValue($value, $make_safe);
    }


    /**
     * Returns the maximum numeric value for this numeric input
     *
     * @return int|null
     */
    public function getMax(): ?int
    {
        return $this->o_attributes->get('max', false);
    }


    /**
     * Sets the maximum numeric value for this numeric input
     *
     * @param PhoDateTimeInterface|Stringable|string|null $max
     *
     * @return static
     */
    public function setMax(PhoDateTimeInterface|Stringable|string|null $max): static
    {
        if ($max instanceof PhoDateTimeInterface) {
            $max = $max->format('Y-m-d');
        }

        return $this->setAttribute(get_null((string) $max), 'max');
    }


    /**
     * Returns the minimum numeric value for this numeric input
     *
     * @return int|null
     */
    public function getMin(): ?int
    {
        return $this->o_attributes->get('min', false);
    }


    /**
     * Sets the minimum numeric value for this numeric input
     *
     * @param PhoDateTimeInterface|Stringable|string|null $min
     *
     * @return static
     */
    public function setMin(PhoDateTimeInterface|Stringable|string|null $min): static
    {
        if ($min instanceof PhoDateTimeInterface) {
            $min = $min->format('Y-m-d');
        }

        return $this->setAttribute(get_null((string) $min), 'min');
    }
}
