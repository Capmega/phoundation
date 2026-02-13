<?php

/**
 * Trait TraitStaticMethodNewWithRepository
 *
 * This trait contains just the static new() command with $_repository as an optional parameter
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Developer\Versioning\Repositories\Interfaces\RepositoryInterface;


trait TraitStaticMethodNewWithRepository
{
    /**
     * Returns a new static object that accepts $_repository in the constructor
     *
     * @param RepositoryInterface|null $_repository
     *
     * @return static
     */
    public static function new(RepositoryInterface|null $_repository = null): static
    {
        return new static($_repository);
    }
}
