<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Enum;

use Phoundation\Os\Processes\Enum\Interfaces\EnumIoNiceClassInterface;

/**
 * Enum EnumIoNice
 *
 * This enum defines ionice classes
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Processes
 */
enum EnumIoNiceClass: int implements EnumIoNiceClassInterface
{
    case none        = 0;
    case realtime    = 1;
    case best_effort = 2;
    case idle        = 3;
}
