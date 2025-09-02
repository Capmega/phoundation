<?php

/**
 * Enum EnumAnchorRenderEmpty
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Enums;

enum EnumAnchorRenderEmpty: string
{
    case not   = 'not';
    case empty = 'empty';
    case url   = 'url';
}
