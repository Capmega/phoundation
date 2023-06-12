<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\Input;

use Phoundation\Web\Http\Html\Enums\InputType;

/**
 * Class InputNumeric
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputNumeric extends Input
{
    /**
     * InputNumeric class constructor
     */
    public function __construct()
    {
        $this->type = InputType::numeric;
        parent::__construct();
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
     * @param int|null $max
     * @return $this
     */
    public function setMax(?int $max): static
    {
        $this->attributes['max'] = $max;
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
     * @param int|null $min
     * @return $this
     */
    public function setMin(?int $min): static
    {
        $this->attributes['min'] = $min;
        return $this;
    }


    /**
     * Returns the step value for this numeric input
     *
     * @return int|null
     */
    public function getStep(): ?int
    {
        return isset_get($this->attributes['step']);
    }


    /**
     * Sets the step value for this numeric input
     *
     * @param int|null $step
     * @return $this
     */
    public function setStep(?int $step): static
    {
        $this->attributes['step'] = $step;
        return $this;
    }
}