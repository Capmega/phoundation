<?php

/**
 * Trait TraitDataWidth
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


trait TraitDataWidth
{
    /**
     * The width for this object
     *
     * @var int|null $width
     */
    protected ?int $width = null;


    /**
     * Returns the width
     *
     * @return int|null
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }


    /**
     * Sets the width
     *
     * @param int|null $width
     * @param int $min_width
     * @param int $max_width
     * @return static
     */
    public function setWidth(?int $width, int $min_width = 0,int $max_width = 65536): static
    {
        if ($width) {
            if (($width < $min_width) or ($width > $max_width)) {
                throw new OutOfBoundsException(tr('Invalid width ":width" specified, must be between ":min" and ":max"', [
                    ':width' => $width,
                    ':min'    => $min_width,
                    ':max'    => $max_width
                ]));
            }
        }

        $this->width = get_null($width);
        return $this;
    }
}
