<?php

namespace Phoundation\Data\Traits;

use Phoundation\Core\Strings;


/**
 * Trait DataValue
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataValue
{
    /**
     * The value for this object
     *
     * @var string $value
     */
    protected string $value;



    /**
     * Returns the value
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }



    /**
     * Sets the value
     *
     * @param string $value
     * @return static
     */
    public function setValue(string $value): static
    {
        $this->value = Strings::slash($value);
        return $this;
    }
}