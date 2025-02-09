<?php

/**
 * Trait TraitDataHeight
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


trait TraitDataHeight
{
    /**
     * The height for this object
     *
     * @var int|null $height
     */
    protected ?int $height = null;


    /**
     * Returns the height
     *
     * @return int|null
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }


    /**
     * Sets the height
     *
     * @param int|null $height
     * @param int $min_height
     * @param int $max_height
     * @return static
     */
    public function setHeight(?int $height, int $min_height = 0,int $max_height = 65536): static
    {
        if ($height) {
            if (($height < $min_height) or ($height > $max_height)) {
                throw new OutOfBoundsException(tr('Invalid height ":height" specified, must be between ":min" and ":max"', [
                    ':height' => $height,
                    ':min'    => $min_height,
                    ':max'    => $max_height
                ]));
            }
        }

        $this->height = get_null($height);
        return $this;
    }
}
