<?php

/**
 * Trait TraitDataMixedValue
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


trait TraitDataMixedValue
{
    /**
     * The value for this object
     *
     * @var mixed $value
     */
    protected mixed $value = null;


    /**
     * Returns the value
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }


    /**
     * Sets the value
     *
     * @param mixed $value
     *
     * @return static
     */
    public function setValue(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }
}
