<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Enums;

use Phoundation\Accounts\Enums\Interfaces\EnumAccountTypeInterface;


/**
 * Enum EnumAccountType
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
enum EnumAccountType: string implements EnumAccountTypeInterface
{
    case business = 'restrict';
    case personal = 'allow_override';
    case other    = 'other';
}