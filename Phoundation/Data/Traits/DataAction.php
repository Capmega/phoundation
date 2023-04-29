<?php

namespace Phoundation\Data\Traits;


/**
 * Trait DataAction
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataAction
{
    protected string $action;


    /**
     * Returns the source
     *
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }


    /**
     * Sets the source
     *
     * @param string $action
     * @return static
     */
    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }
}