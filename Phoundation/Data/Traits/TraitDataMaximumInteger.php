<?php

/**
 * Trait TraitDataMaximumInteger
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

use Phoundation\Exception\OutOfBoundsException;


trait TraitDataMaximumInteger
{
    /**
     * The maximum value for this object
     *
     * @var int|null $maximum
     */
    protected ?int $maximum = null;


    /**
     * Returns the maximum value
     *
     * @return int|null
     */
    public function getMaximum(): int|null
    {
        return $this->maximum;
    }


    /**
     * Sets the maximum value
     *
     * @param int|null $maximum
     *
     * @return static
     */
    public function setMaximum(int|null $maximum): static
    {
        if ($maximum < $this->minimum) {
            throw new OutOfBoundsException(tr('Specified maximum value ":maximum" is below the current minimum value ":minimum"', [
                ':maximum' => $maximum,
                ':minimum' => $this->minimum
            ]));
        }

        $this->maximum = get_null($maximum);
        return $this;
    }
}
