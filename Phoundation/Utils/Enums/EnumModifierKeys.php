<?php

/**
 * Enum EnumModifierKeys
 *
 * Contains the possible modifier keys available on typical IBM compatible keyboards
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils\Enums;

enum EnumModifierKeys: string
{
    case ctrl  = 'ctrl';
    case alt   = 'alt';
    case shift = 'shift';
}
