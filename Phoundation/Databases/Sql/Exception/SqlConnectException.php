<?php

declare(strict_types=1);

namespace Phoundation\Databases\Sql\Exception;


/**
 * Class SqlConnectException
 *
 * This exception is thrown when trying to use a database that does not exist
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class SqlConnectException extends SqlException
{
}