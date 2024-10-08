<?php
/**
 * Enum EnumLibraryType
 *
 * This enum contains the three different type of libraries that can exist: System, Plugin, Template
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */

declare(strict_types=1);

namespace Phoundation\Core\Enums;

enum EnumLibraryType: string
{
    case system   = 'Phoundation/';
    case plugin   = 'Plugins/';
    case template = 'Templates/';
}
