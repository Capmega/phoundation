<?php

/**
 * Trait TraitDataIntInterval
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


trait TraitDataIntInterval
{
    /**
     * The interval for this object
     *
     * @var int|null $interval
     */
    protected ?int $interval = null;


    /**
     * Returns the interval
     *
     * @return int|null
     */
    public function getInterval(): ?int
    {
        return $this->interval;
    }


    /**
     * Sets the interval
     *
     * @param int|null $interval
     *
     * @return static
     */
    public function setInterval(?int $interval): static
    {
        if ($interval) {
            if (($interval < 1)) {
                throw new OutOfBoundsException(tr('Invalid interval ":interval" specified, it must be an positive integer higher than 0', [
                    ':interval' => $interval,
                ]));
            }
        }

        $this->interval = get_null($interval);
        return $this;
    }
}
