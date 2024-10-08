<?php

declare(strict_types=1);

namespace Phoundation\Databases\Sql\Exception;

/**
 * Class SqlNoTimezonesException
 *
 * This exception is thrown by Sql::getColumn() when the specified column does not exist
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */
class SqlNoTimezonesException extends SqlException
{
}
