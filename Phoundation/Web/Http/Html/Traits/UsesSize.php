<?php

namespace Phoundation\Web\Http\Html\Traits;

use Phoundation\Web\Http\Html\Enums\DisplaySize;
use Phoundation\Web\Http\Html\Interfaces\InterfaceDisplaySize;

/**
 * UsesSize trait
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
trait UsesSize
{
    /**
     * Container value for this container
     *
     * @var InterfaceDisplaySize $size
     */
    protected InterfaceDisplaySize $size = DisplaySize::xxl;

    /**
     * Sets the type for this container
     *
     * @param InterfaceDisplaySize $size
     * @return static
     */
    public function setSize(InterfaceDisplaySize $size): static
    {
        $this->size = $size;
        return $this;
    }

    /**
     * Returns the type for this container
     *
     * @return InterfaceDisplaySize
     */
    public function getSize(): InterfaceDisplaySize
    {
        return $this->size;
    }
}