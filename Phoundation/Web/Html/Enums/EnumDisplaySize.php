<?php
/**
 * Enum DisplaySizeInterface
 *
 * The different display sizes for elements or element blocks
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

enum EnumDisplaySize: string
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
