<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait UsesNewTable
 *
 * This trait contains just the static new() command without any parameters
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Data
 */
trait UsesNewTable
{
    use DataTable;


    /**
     * UsesNewTable class constructor
     *
     * @param string|null $table
     */
    public function __construct(?string $table = null)
    {
        self::$table = $table;
    }


    /**
     * Returns a new static object
     *
     * @param string|null $table
     * @return static
     */
    public static function new(?string $table = null): static
    {
        return new static($table);
    }
}
