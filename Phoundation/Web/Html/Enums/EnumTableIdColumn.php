<?php

/**
 * Enum TableIdColumn
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

use Phoundation\Web\Html\Enums\Interfaces\EnumTableIdColumnInterface;

enum EnumTableIdColumn: string implements EnumTableIdColumnInterface
{
    case hidden   = 'hidden';
    case checkbox = 'checkbox';
    case visible  = 'visible';
}
