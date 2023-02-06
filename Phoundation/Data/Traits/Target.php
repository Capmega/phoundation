<?php

namespace Phoundation\Data\Traits;



/**
 * Trait Source
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
trait Target
{
    protected string $target;



    /**
     * Returns the source
     *
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }



    /**
     * Sets the source
     *
     * @param string $target
     * @return static
     */
    public function setTarget(string $target): static
    {
        $this->target = $target;
        return $this;
    }
}