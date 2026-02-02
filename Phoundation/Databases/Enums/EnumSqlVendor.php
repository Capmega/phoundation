<?php

/**
 * Enum EnumMysqlVendor
 *
 * Used to identify the vendor for the connected MySQL database. Vendor is either oracle, mariadb, or postgres
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Enums;

enum EnumSqlVendor: string
{
    case postgres = 'postgres';
    case oracle   = 'oracle';
    case mariadb  = 'mariadb';
}
