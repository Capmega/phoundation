<?php

/**
 * Enum EnumPhoundationType
 *
 * Contains the possible Phoundation repository types
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Phoundation\Enums;

enum EnumPhoundationType: string
{
    case system    = 'system';
    case plugins   = 'plugins';
    case templates = 'templates';
    case project   = 'project';
    case data      = 'data';
    case cdn       = 'cdn';
}
