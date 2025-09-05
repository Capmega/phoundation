<?php

/**
 * Class SqlColumnCannotBeNullException
 *
 * This exception is thrown when trying to update an SQL table column to NULL while that column is defined as NOT NULL
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Sql\Exception;


class SqlColumnCannotBeNullException extends SqlException
{
}
