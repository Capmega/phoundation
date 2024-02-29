<?php

declare(strict_types=1);

namespace Phoundation\Databases\Connectors\Exception;

use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;


/**
 * Class ConnectorNotExistsException
 *
 * This exception is thrown when the requested connector does not exist (or is deleted)
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class ConnectorNotExistsException extends DataEntryNotExistsException
{
}