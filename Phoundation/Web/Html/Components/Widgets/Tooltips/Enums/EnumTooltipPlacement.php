<?php

/**
 * Enum EnumTooltipPlacement
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Tooltips\Enums;


enum EnumTooltipPlacement: string
{
    case auto   = 'auto';
    case top    = 'top';
    case bottom = 'bottom';
    case left   = 'left';
    case right  = 'right';
}
