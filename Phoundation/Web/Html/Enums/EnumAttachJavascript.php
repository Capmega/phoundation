<?php

/**
 * Enum EnumAttachJavascript
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

enum EnumAttachJavascript: string
{
    case here   = 'here';
    case header = 'header';
    case footer = 'footer';
}
