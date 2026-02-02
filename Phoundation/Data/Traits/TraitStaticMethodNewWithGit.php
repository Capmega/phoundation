<?php

/**
 * Trait TraitStaticMethodNewWithGit
 *
 * This trait contains just the static new() command with $o_git as an optional parameter
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
     * Returns a new static object that accepts $o_git in the constructor
     *
     * @param GitInterface|null $o_git
     *
     * @return static
     */
    public static function new(GitInterface|null $o_git = null): static
    {
        return new static($o_git);
    }
}
