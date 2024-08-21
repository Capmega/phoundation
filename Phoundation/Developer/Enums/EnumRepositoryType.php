<?php

/**
 * Enum RepositoryType
 *
 * The different types of repositories for plugins
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Enums;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;

enum EnumRepositoryType: string
{
    case core      = 'core';
    case data      = 'data';
    case plugins   = 'plugins';
    case templates = 'templates';
    case project   = 'project';


    /**
     * Returns the correct directory suffix for the specified repository type
     *
     * @return string
     */
    public function getDirectorySuffix(): string
    {
        return match ($this->value) {
            'core'      => 'Phoundation/',
            'data'      => 'data/vendors/',
            'plugins'   => 'Plugins/',
            'templates' => 'Templates/',
            default => throw new OutOfBoundsException(tr('Unsupported EnumRepositoryType ":type" to get a directory suffic', [
                ':type' => $this->value
            ])),
        };
    }
}
