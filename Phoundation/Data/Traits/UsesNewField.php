<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait UsesNewField
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Data
 */
trait UsesNewField
{
    use DataField;


    /**
     * UsesNewField class constructor
     *
     * @param string|null $field
     */
    public function __construct(?string $field = null)
    {
        $this->field = $field;
    }


    /**
     * Returns a new static object
     *
     * @param string|null $field
     * @return static
     */
    public static function new(?string $field = null): static
    {
        return new static($field);
    }
}
