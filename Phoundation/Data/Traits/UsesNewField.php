<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;


/**
 * Trait UsesNewField
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Data
 */
trait UsesNewField
{
    use DataColumn;


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
     * @return DefinitionInterface
     */
    public static function new(?string $field = null): DefinitionInterface
    {
        return new static($field);
    }
}
