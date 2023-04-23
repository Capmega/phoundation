<?php

namespace Phoundation\Databases\Sql\Exception;


/**
 * Class SqlMultipleResultsException
 *
 * This exception is thrown by Sql::get() when the specified query caused multiple results instead of only one
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class SqlMultipleResultsException extends SqlException
{
}
