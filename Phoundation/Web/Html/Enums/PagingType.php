<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

use Phoundation\Web\Html\Enums\Interfaces\PagingTypeInterface;


/**
 * Enum PagingType
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
enum PagingType: string implements PagingTypeInterface
{
    case numbers            = 'numbers';
    case simple             = 'simple';
    case simple_numbers     = 'simple_numbers';
    case full               = 'full';
    case full_numbers       = 'full_numbers';
    case first_last_numbers = 'first_last_numbers';
}
