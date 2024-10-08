<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

/**
 * Trait TraitNewObject
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataNew
{
    /**
     * Returns a new object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }
}