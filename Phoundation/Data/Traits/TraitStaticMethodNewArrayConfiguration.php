<?php

/**
 * Trait TraitStaticMethodNewArrayConfiguration
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


trait TraitStaticMethodNewArrayConfiguration
{
    /**
     * Returns a new static object
     *
     * @param array|null $configuration
     *
     * @return static
     */
    public static function new(?array $configuration = null): static
    {
        return new static($configuration);
    }
}
