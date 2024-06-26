<?php

/**
 * Trait TraitDataRepositoryType
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */

namespace Phoundation\Developer\Traits;

use Phoundation\Developer\Enums\EnumRepositoryType;

trait TraitDataRepositoryType
{
    /**
     * The type of vendor in this Vendors object
     *
     * @var EnumRepositoryType
     */
    protected EnumRepositoryType $type;


    /**
     * Returns the type of vendors in this list
     *
     * @return EnumRepositoryType
     */
    public function getType(): EnumRepositoryType
    {
        return $this->type;
    }
}
