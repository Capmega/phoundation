<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Tooltips\Enums;

use Phoundation\Web\Html\Components\Tooltips\Enums\Interfaces\EnumTooltipPlacementInterface;


/**
 * Enum EnumTooltipPlacement
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
enum EnumTooltipPlacement: string implements EnumTooltipPlacementInterface
{
    case auto   = 'auto';
    case top    = 'top';
    case bottom = 'bottom';
    case left   = 'left';
    case right  = 'right';
}
