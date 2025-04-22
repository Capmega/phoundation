<?php

/**
 * Enum EnumLoadParameters
 *
 * Used to identify parameters for NULL and not exist actions with DataEntry::load()
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Enums;

enum EnumLoadParameters: string
{
    case null      = 'null';
    case this      = 'this';
    case exception = 'exception';
}
