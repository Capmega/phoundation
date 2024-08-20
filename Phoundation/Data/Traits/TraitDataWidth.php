<?php

/**
 * Trait TraitDataWidth
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
     *
     * @return static
     */
    public function setWidth(?int $width): static
    {
        if ($width) {
            if (($width < 0) or ($width > 65535)) {
                throw new OutOfBoundsException(tr('Invalid width ":width" specified', [
                    ':width' => $width,
                ]));
            }
        }
        $this->width = $width;

        return $this;
    }
}
