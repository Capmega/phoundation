<?php

/**
 * Class SqlServerNotAvailableException
 *
 * This exception is thrown when trying to connect to an SQL server that refuses connection
 *
 * This may be caused by firewall configuration, incorrect server hostname or ip, incorrect port, or the SQL service
 * being down
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */

declare(strict_types=1);

namespace Phoundation\Databases\Sql\Exception;

class SqlServerNotAvailableException extends SqlException
{
}
