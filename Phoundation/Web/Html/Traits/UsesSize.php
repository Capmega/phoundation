<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Enums\DisplaySize;


/**
 * UsesSize trait
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
trait UsesSize
{
    /**
     * Container value for this container
     *
     * @var DisplaySize|null $size
     */
    protected DisplaySize|null $size = DisplaySize::twelve;


    /**
     * Sets the type for this container
     *
     * @param DisplaySize|int|null $size
     * @return static
     */
    public function setSize(DisplaySize|int|null $size): static
    {
        if (is_numeric($size)) {
            if (($size < 1) or ($size > 12)) {
                throw new OutOfBoundsException(tr('Specified size ":size" is invalid, it should have the DisplaySizeInterface interface, or be a numeric int between 1 and 12', [
                    ':size' => $size
                ]));
            }

            $size = DisplaySize::from((string) $size);
        }

        $this->size = $size;
        return $this;
    }


    /**
     * Returns the type for this container
     *
     * @return DisplaySize|int|null
     */
    public function getSize(): DisplaySize|int|null
    {
        return $this->size;
    }
}