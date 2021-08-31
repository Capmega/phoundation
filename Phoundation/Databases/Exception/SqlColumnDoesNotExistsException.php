<?php

namespace Phoundation\Databases\Exception;

/**
 * Class SqlColumnDoesNotExistsException
 *
 * This exception is thrown by Sql::getColumn() when the specified column does not exist
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2021 <copyright@capmega.com>
 * @package Phoundation\Databases
 */
class SqlColumnDoesNotExistsException extends SqlException
{
}
