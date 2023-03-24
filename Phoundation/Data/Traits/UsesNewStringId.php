<?php

namespace Phoundation\Data\Traits;


/**
 * Trait UsesNewStringId
 *
 * This trait contains just the static new() command with an optional string ID parameter
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Data
 */
trait UsesNewStringId
{
    use DataStringId;


    /**
     * UsesNewStringId class constructor
     *
     * @param string|null $id
     */
    public function __construct(?string $id = null)
    {
        $this->id = $id;
    }


    /**
     * Returns a new static object
     *
     * @param string|null $id
     * @return static
     */
    public static function new(?string $id = null): static
    {
        return new static($id);
    }
}
