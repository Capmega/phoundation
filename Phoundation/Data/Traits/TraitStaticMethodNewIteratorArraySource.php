<?php

/**
 * Trait TraitStaticMethodNewArrayContent
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

use Phoundation\Data\Interfaces\IteratorInterface;


trait TraitStaticMethodNewIteratorArraySource
{
    /**
     * Returns a new class
     *
     * @param IteratorInterface|array|null $source
     *
     * @return static
     */
    public static function new(IteratorInterface|array|null $source = null): static
    {
        return new static($source);
    }
}
