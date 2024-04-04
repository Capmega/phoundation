<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait TraitUsesNewField
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Data
 */
trait TraitUsesNewColumn
{
    use TraitDataColumn;


    /**
     * TraitUsesNewField class constructor
     *
     * @param string|null $column
     */
    public function __construct(?string $column = null)
    {
        $this->column = $column;
    }


    /**
     * Returns a new static object
     *
     * @param string|null $column
     *
     * @return static
     */
    public static function new(?string $column = null): static
    {
        return new static($column);
    }
}
