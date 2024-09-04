<?php

/**
 * Trait TraitDataCurrentFloat
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


use Phoundation\Exception\OutOfBoundsException;

trait TraitDataCurrentFloat
{
    /**
     * The current value for this object
     *
     * @var float|null $current
     */
    protected ?float $current = null;


    /**
     * Returns the current value
     *
     * @return float|null
     */
    public function getCurrent(): ?float
    {
        return $this->current;
    }


    /**
     * Sets the current value
     *
     * @param float|null $current
     *
     * @return static
     */
    public function setCurrent(?float $current): static
    {
        if ($current < $this->minimum) {
            throw new OutOfBoundsException(tr('Specified current value ":current" is below the current minimum value ":minimum"', [
                ':current' => $current,
                ':minimum' => $this->minimum

            ]));
        }

        if ($current > $this->maximum) {
            throw new OutOfBoundsException(tr('Specified current value ":current" is above the current maximum value ":maximum"', [
                ':current' => $current,
                ':maximum' => $this->maximum

            ]));
        }

        $this->current = $current;

        return $this;
    }
}
