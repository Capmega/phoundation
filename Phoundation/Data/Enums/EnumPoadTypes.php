<?php

/**
 * Enum EnumPoadTypes
 *
 * Used to identify what POAD types are supported
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Enums;

enum EnumPoadTypes: string
{
    case object   = 'object';
    case compound = 'compound';
}
