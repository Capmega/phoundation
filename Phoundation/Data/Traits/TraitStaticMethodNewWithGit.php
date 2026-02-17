<?php

/**
 * Trait TraitStaticMethodNewWithGit
 *
 * This trait contains just the static new() command with $_git as an optional parameter
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;


trait TraitStaticMethodNewWithGit
{
    /**
     * Returns a new static object that accepts $_git in the constructor
     *
     * @param GitInterface|null $_git
     *
     * @return static
     */
    public static function new(GitInterface|null $_git = null): static
    {
        return new static($_git);
    }
}
