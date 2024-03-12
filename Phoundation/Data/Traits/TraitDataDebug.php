<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait TraitDataDebug
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opendebug.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait TraitDataDebug
{
    /**
     *
     *
     * @var bool $debug
     */
    protected bool $debug = false;


    /**
     * Returns the debug value
     *
     * @return bool
     */
    public function getDebug(): bool
    {
        return $this->debug;
    }


    /**
     * Sets the debug value
     *
     * @param bool $debug
     * @return static
     */
    public function setDebug(bool $debug): static
    {
        $this->debug = $debug;
        return $this;
    }
}