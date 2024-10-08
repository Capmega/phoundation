<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Tooltips\Enums;

use Phoundation\Web\Html\Components\Widgets\Tooltips\Enums\Interfaces\EnumTooltipTriggerInterface;

/**
 * Enum EnumTooltipTrigger
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
enum EnumTooltipTrigger: string implements EnumTooltipTriggerInterface
{
    case click  = 'click';
    case focus  = 'focus';
    case hover  = 'hover';
    case manual = 'manual';
}
