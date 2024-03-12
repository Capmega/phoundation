<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait TraitDataStaticIsExecutedPath
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait TraitDataStaticIsExecutedPath
{
    /**
     * Returns true if the executed path is the specified path
     *
     * @param string $path
     * @return bool
     */
    public static function isExecutedPath(string $path): bool
    {
        return static::getExecutedPath() === $path;
    }
}