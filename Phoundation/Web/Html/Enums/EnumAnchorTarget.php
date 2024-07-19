<?php

/**
 * Enum EnumAnchorTarget
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

namespace Phoundation\Web\Html\Enums;

enum EnumAnchorTarget: string
{
    case self   = '_self';
    case blank  = '_blank';
    case parent = '_parent';
    case top    = '_top';
}
