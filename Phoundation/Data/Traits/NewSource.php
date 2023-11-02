<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait NewSource
 *
 * This trait contains just the static new() command without any parameters
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Data
 */
trait NewSource
{
    /**
     * Class constructor
     *
     * @param array|null $source
     */
    public function __construct(?array $source = null)
    {
    }


    /**
     * Returns a new static object
     *
     * @param array|null $source
     * @return static
     */
    public static function new(?array $source = null): static
    {
        return new static($source);
    }
}
