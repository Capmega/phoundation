<?php

/**
 * Trait TraitStaticMethodNewWithName
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


trait TraitStaticMethodNewWithName
{
    use TraitDataName;


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
     *
     * @return static
     */
    public static function new(?string $name = null): static
    {
        return new static($name);
    }
}
