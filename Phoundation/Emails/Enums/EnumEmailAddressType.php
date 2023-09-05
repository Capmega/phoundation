<?php

namespace Phoundation\Emails\Enums;


use Phoundation\Emails\Enums\Interfaces\EnumEmailAddressTypeInterface;

/**
 * enum EnumEmailAddressType
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Emails
 */
enum EnumEmailAddressType: string implements EnumEmailAddressTypeInterface
{
    case to   = 'to';
    case cc   = 'cc';
    case bcc  = 'bcc';
    case from = 'from';
}
