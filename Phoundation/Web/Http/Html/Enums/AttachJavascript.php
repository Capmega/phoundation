<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Enums;


/**
 * Enum Services
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
enum AttachJavascript: string
{
    case here = 'here';
    case header = 'header';
    case footer = 'footer';
}
