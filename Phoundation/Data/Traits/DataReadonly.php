<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait DataReadonly
 *
 * This adds readonly state registration to objects
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataReadonly
{
    /**
     * Registers if this object is readonly or not
     *
     * @var bool $readonly
     */
    protected bool $readonly = false;


    /**
     * Returns if this object is readonly or not
     *
     * @return bool
     */
    public function getReadonly(): bool
    {
        return $this->readonly;
    }


    /**
     * Sets if this object is readonly or not
     *
     * @param bool $readonly
     * @return static
     */
    public function setReadonly(bool $readonly): static
    {
        $this->readonly = $readonly;
        return $this;
    }
}