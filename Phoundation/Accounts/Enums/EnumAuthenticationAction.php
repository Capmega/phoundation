<?php

/**
 * Enum EnumAuthenticationAction
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Enums;

enum EnumAuthenticationAction: string
{
    case authentication     = 'authentication';
    case signin             = 'signin';
    case signout            = 'signout';
    case startimpersonation = 'startimpersonation';
    case stopimpersonation  = 'stopimpersonation';
    case other              = 'other';
}
