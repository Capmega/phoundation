<?php

/**
 * Class SqlUnknownDatabaseException
 *
 * This exception is thrown when trying to access a database that not (or no longer) exists.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Sql\Exception;


class SqlUnknownDatabaseException extends SqlException
{
}
