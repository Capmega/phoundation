<?php

/**
 * Class ConnectorNotDefinedException
 *
 * This exception is thrown when an empty connector is used
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */

declare(strict_types=1);

namespace Phoundation\Databases\Connectors\Exception;

class ConnectorNotDefinedException extends ConnectorException
{
}
