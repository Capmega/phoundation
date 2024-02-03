<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Traits;


/**
 * Trait DataIsRelative
 *
 * This trait tracks the "is_relative" switch
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
trait DataIsRelative
{
    /**
     * Tracks if the value is relative
     *
     * @var bool $is_relative
     */
    protected bool $is_relative = false;


    /**
     * Returns if the value is relative
     *
     * @return bool
     */
    public function getIsRelative(): bool
    {
        return $this->is_relative;
    }
}
