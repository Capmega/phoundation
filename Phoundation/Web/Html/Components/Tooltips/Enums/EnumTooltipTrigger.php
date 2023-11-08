<?php

namespace Phoundation\Web\Html\Components\Tooltips\Enums;

use Phoundation\Web\Html\Components\Tooltips\Enums\Interfaces\EnumTooltipTriggerInterface;


/**
 * Enum EnumTooltipTrigger
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
enum EnumTooltipTrigger: string implements EnumTooltipTriggerInterface
{
    case click  = 'click';
    case focus  = 'focus';
    case hover  = 'hover';
    case manual = 'manual';
}
