<?php

/**
 * Enum EnumVersionSections
 *
 * Contains the possible sections in a valid version string
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils\Enums;

enum EnumVersionSections: string
{
    case major    = 'major';
    case minor    = 'minor';
    case revision = 'revision';
}
