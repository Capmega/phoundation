<?php

/**
 * Class InputNumber
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Web\Html\Enums\EnumInputType;


class InputNumber extends Input
{
    /**
     * InputNumeric class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->input_type = EnumInputType::number;
        parent::__construct($content);
    }


    /**
     * Returns the maximum numeric value for this numeric input
     *
     * @return float|int|null
     */
    public function getMax(): float|int|null
    {
        return $this->attributes->get('max', false);
    }


    /**
     * Sets the maximum numeric value for this numeric input
     *
     * @param float|int|null $max
     *
     * @return static
     */
    public function setMax(float|int|null $max): static
    {
        return $this->setAttribute($max, 'max');
    }


    /**
     * Returns the minimum numeric value for this numeric input
     *
     * @return float|int|null
     */
    public function getMin(): float|int|null
    {
        return $this->attributes->get('min', false);
    }


    /**
     * Sets the minimum numeric value for this numeric input
     *
     * @param float|int|null $min
     *
     * @return static
     */
    public function setMin(float|int|null $min): static
    {
        return $this->setAttribute($min, 'min');
    }


    /**
     * Returns the step value for this numeric input
     *
     * @return float|int|null
     */
    public function getStep(): float|int|null
    {
        return $this->attributes->get('step', false);
    }


    /**
     * Sets the step value for this numeric input
     *
     * @param float|int|null $step
     *
     * @return static
     */
    public function setStep(float|int|null $step): static
    {
        return $this->setAttribute($step, 'step');
    }
}
