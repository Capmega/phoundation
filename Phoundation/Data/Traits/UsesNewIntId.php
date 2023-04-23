<?php

namespace Phoundation\Data\Traits;

/**
 * Trait UsesNewId
 *
 * This trait contains just the static new() command with an optional int ID parameter
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Data
 */
trait UsesNewIntId
{
    /**
     * Object id
     *
     * @var int|null $id
     */
    protected ?int $id = null;

    /**
     * UsesNewIntId class constructor
     *
     * @param int|null $id
     */
    public function __construct(?int $id = null)
    {

    }

    /**
     * Returns a new static object
     *
     * @param int|null $id
     * @return static
     */
    public static function new(?int $id = null): static
    {
        return new static($id);
    }
}
