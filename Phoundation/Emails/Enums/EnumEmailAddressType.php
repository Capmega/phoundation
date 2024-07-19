<?php

/**
 * Enum EnumEmailAddressType
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Plugins\Emails
 */

declare(strict_types=1);

namespace Phoundation\Emails\Enums;

enum EnumEmailAddressType: string
{
    case to   = 'to';
    case cc   = 'cc';
    case bcc  = 'bcc';
    case from = 'from';
}
