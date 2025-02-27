<?php

/**
 * Class InvalidConnectorTypeException
 *
 * This exception is thrown when the requested connector does not apply to the specified database type
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Connectors\Exception;


class InvalidConnectorTypeException extends ConnectorException
{
}
