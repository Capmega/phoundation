<?php
/**
 * Enum PagingType
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Virtualization
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

enum EnumPagingType: string
{
    case numbers            = 'numbers';
    case simple             = 'simple';
    case simple_numbers     = 'simple_numbers';
    case full               = 'full';
    case full_numbers       = 'full_numbers';
    case first_last_numbers = 'first_last_numbers';
}
