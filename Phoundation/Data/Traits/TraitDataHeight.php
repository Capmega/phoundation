<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Exception\OutOfBoundsException;


/**
 * Trait TraitDataHeight
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
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
     *
     * @return static
     */
    public function setHeight(?int $height): static
    {
        if ($height) {
            if (($height < 0) or ($height > 65535)) {
                throw new OutOfBoundsException(tr('Invalid height ":height" specified', [
                    ':height' => $height,
                ]));
            }
        }

        $this->height = $height;
        return $this;
    }
}
