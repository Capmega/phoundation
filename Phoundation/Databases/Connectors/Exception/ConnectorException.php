<?php

/**
 * Class ConnectorException
 *
 * This is the default exception for database connector issues
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */

declare(strict_types=1);

namespace Phoundation\Databases\Connectors\Exception;

use Phoundation\Databases\Sql\Exception\SqlException;

class ConnectorException extends SqlException
{
}
