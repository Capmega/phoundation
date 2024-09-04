<?php

/**
 * Trait TraitDataMinimumInteger
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
use Phoundation\Web\Html\Components\P;

trait TraitDataMinimumInteger
{
    /**
     * The minimum value for this object
     *
     * @var int|null $minimum
     */
    protected ?int $minimum = null;


    /**
     * Returns the minimum value
     *
     * @return int|null
     */
    public function getMinimum(): ?int
    {
        return $this->minimum;
    }


    /**
     * Sets the minimum value
     *
     * @param int|null $minimum
     *
     * @return static
     */
    public function setMinimum(?int $minimum): static
    {
        if ($minimum > $this->maximum) {
            throw new OutOfBoundsException(tr('Specified minimum value ":minimum" is above the current maximum value ":maximum"', [
                ':minimum' => $minimum,
                ':maximum' => $this->maximum
            ]));
        }

        $this->minimum = $minimum;

        return $this;
    }
}
