<?php

/**
 * Enum EnumStateMismatchHandling
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Enums;


enum EnumStateMismatchHandling: string
{
    case restrict       = 'restrict';
    case allow_override = 'allow_override';
    case ignore         = 'ignore';
}
