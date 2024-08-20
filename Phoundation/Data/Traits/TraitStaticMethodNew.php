<?php

/**
 * Trait TraitStaticMethodNew
 *
 * This trait contains just the static new() command without any parameters
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitStaticMethodNew
{
    /**
     * Returns a new static object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }
}