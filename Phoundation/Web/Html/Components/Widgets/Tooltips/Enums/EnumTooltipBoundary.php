<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Tooltips\Enums;

use Phoundation\Web\Html\Components\Widgets\Tooltips\Enums\Interfaces\EnumTooltipBoundaryInterface;


/**
 * Enum EnumTooltipBoundary
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
enum EnumTooltipBoundary: string implements EnumTooltipBoundaryInterface
{
    case viewport     = 'viewport';
    case window       = 'window';
    case scrollParent = 'scrollParent';
}
