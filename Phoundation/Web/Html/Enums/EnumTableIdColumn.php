<?php
/**
 * Enum EnumTableIdColumn
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

enum EnumTableIdColumn: string
{
    case hidden   = 'hidden';
    case checkbox = 'checkbox';
    case visible  = 'visible';
}
