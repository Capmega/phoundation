<?php

namespace Phoundation\Databases\Sql\Exception;


/**
 * Class SqlAccessDeniedException
 *
 * This exception is thrown by Sql::getColumn() when the specified column does not exist
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class SqlAccessDeniedException extends SqlException
{
}
