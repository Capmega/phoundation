<?php

namespace Phoundation\Data\Traits;

/**
 * Trait UsesNewName
 *
 *
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Data
 */
trait UsesNewName
{
    use DataName;

    /**
     * UsesNewName class constructor
     *
     * @param string|null $name
     */
    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }

    /**
     * Returns a new static object
     *
     * @param string|null $name
     * @return static
     */
    public static function new(?string $name = null): static
    {
        return new static($name);
    }
}
