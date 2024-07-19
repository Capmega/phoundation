<?php

/**
 * Enum TableRowType
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

enum EnumTableRowType: string
{
    case header = 'header';
    case row    = 'row';
    case footer = 'footer';
}
