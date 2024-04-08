<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Web\Html\Enums\EnumElementInputType;

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
class InputNumber extends Input
{
    /**
     * InputNumeric class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->input_type = EnumElementInputType::number;
        parent::__construct($content);
    }


    /**
     * Returns the maximum numeric value for this numeric input
     *
     * @return int|null
     */
    public function getMax(): ?int
    {
        return $this->attributes->get('max', false);
    }


    /**
     * Sets the maximum numeric value for this numeric input
     *
     * @param int|null $max
     *
     * @return $this
     */
    public function setMax(?int $max): static
    {
        return $this->setAttribute($max, 'max');
    }


    /**
     * Returns the minimum numeric value for this numeric input
     *
     * @return int|null
     */
    public function getMin(): ?int
    {
        return $this->attributes->get('min', false);
    }


    /**
     * Sets the minimum numeric value for this numeric input
     *
     * @param int|null $min
     *
     * @return $this
     */
    public function setMin(?int $min): static
    {
        return $this->setAttribute($min, 'min');
    }


    /**
     * Returns the step value for this numeric input
     *
     * @return int|null
     */
    public function getStep(): ?int
    {
        return $this->attributes->get('step', false);
    }


    /**
     * Sets the step value for this numeric input
     *
     * @param int|null $step
     *
     * @return $this
     */
    public function setStep(?int $step): static
    {
        return $this->setAttribute($step, 'step');
    }
}