<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Enums;

use Phoundation\Web\Http\Html\Enums\Interfaces\DisplaySizeInterface;


/**
 * Enum DisplaySizeInterface
 *
 * The different display sizes for elements or element blocks
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
enum DisplaySize: string implements DisplaySizeInterface
{
    case one    = '1';
    case two    = '2';
    case three  = '3';
    case four   = '4';
    case five   = '5';
    case six    = '6';
    case seven  = '7';
    case eight  = '8';
    case nine   = '9';
    case ten    = '10';
    case eleven = '11';
    case twelve = '12';
}
