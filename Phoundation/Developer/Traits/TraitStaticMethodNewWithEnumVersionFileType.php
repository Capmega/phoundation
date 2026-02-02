<?php

/**
 * Trait TraitStaticMethodNewWithEnumVersionFileType
 *
 * This trait contains just the static new() method with EnumVersionFileType $o_version_file_type as a required
 * parameter
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Traits;

use Phoundation\Developer\Project\Enums\EnumVersionFileType;


trait TraitStaticMethodNewWithEnumVersionFileType
{
    /**
     * Returns a new static object that accepts EnumVersionFileType $o_version_file_type in the constructor
     *
     * @param EnumVersionFileType $o_version_file_type
     *
     * @return static
     */
    public static function new(EnumVersionFileType $o_version_file_type): static
    {
        return new static($o_version_file_type);
    }
}
