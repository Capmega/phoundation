<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\Input;

use Phoundation\Date\DateTime;
use Phoundation\Web\Http\Html\Enums\InputType;
use Stringable;


/**
 * Class InputDate
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputDate extends Input
{
    /**
     * InputDate class constructor
     */
    public function __construct()
    {
        $this->type = InputType::date;
        parent::__construct();
    }


    /**
     * Sets the value for the input element
     *
     * @param DateTime|Stringable|string|float|int|null $value
     * @param bool $make_safe
     * @return static
     */
    public function setValue(DateTime|Stringable|string|float|int|null $value, bool $make_safe = true): static
    {
        if ($value instanceof DateTime) {
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
        return isset_get($this->attributes['max']);
    }


    /**
     * Sets the maximum numeric value for this numeric input
     *
     * @param DateTime|Stringable|string|null $max
     * @return $this
     */
    public function setMax(DateTime|Stringable|string|null $max): static
    {
        if ($max instanceof DateTime){
            $max = $max->format('Y-m-d');
        }

        $this->attributes['max'] = (string) $max;
        return $this;
    }


    /**
     * Returns the minimum numeric value for this numeric input
     *
     * @return int|null
     */
    public function getMin(): ?int
    {
        return isset_get($this->attributes['min']);
    }


    /**
     * Sets the minimum numeric value for this numeric input
     *
     * @param DateTime|Stringable|string|null $min
     * @return $this
     */
    public function setMin(DateTime|Stringable|string|null $min): static
    {
        if ($min instanceof DateTime){
            $min = $min->format('Y-m-d');
        }

        $this->attributes['min'] = $min;
        return $this;
    }
}