<?php

namespace Phoundation\Data\Traits;


/**
 * Trait DataSelector
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataSelector
{
    protected string $selector;


    /**
     * Returns the source
     *
     * @return string
     */
    public function getSelector(): string
    {
        return $this->selector;
    }


    /**
     * Sets the source
     *
     * @param string $selector
     * @return static
     */
    public function setSelector(string $selector): static
    {
        $this->selector = $selector;
        return $this;
    }
}