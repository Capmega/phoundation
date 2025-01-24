<?php

/**
 * Trait TraitStaticMethodNewWithEnvironment
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitStaticMethodNewWithEnvironment
{
    /**
     * Returns a new static object
     *
     * @param string|null $environment
     *
     * @return static
     */
    public static function new(?string $environment = null): static
    {
        return new static($environment);
    }
}
