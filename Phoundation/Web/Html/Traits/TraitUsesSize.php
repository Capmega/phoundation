<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Enums\EnumDisplaySize;

/**
 * Trait TraitUsesSize
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
trait TraitUsesSize
{
    /**
     * Container value for this container
     *
     * @var EnumDisplaySize|null $size
     */
    protected EnumDisplaySize|null $size = EnumDisplaySize::twelve;


    /**
     * Returns the type for this container
     *
     * @return EnumDisplaySize|int|null
     */
    public function getSize(): EnumDisplaySize|int|null
    {
        return $this->size;
    }


    /**
     * Sets the type for this container
     *
     * @param EnumDisplaySize|int|null $size
     *
     * @return static
     */
    public function setSize(EnumDisplaySize|int|null $size): static
    {
        if (is_numeric($size)) {
            if (($size < 1) or ($size > 12)) {
                throw new OutOfBoundsException(tr('Specified size ":size" is invalid, it should have the DisplaySizeInterface interface, or be a numeric int between 1 and 12', [
                    ':size' => $size,
                ]));
            }
            $size = EnumDisplaySize::from((string) $size);
        }
        $this->size = $size;

        return $this;
    }
}