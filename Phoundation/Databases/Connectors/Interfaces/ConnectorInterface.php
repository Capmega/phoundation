<?php

declare(strict_types=1);

namespace Phoundation\Databases\Connectors\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;


/**
 * Interface SqlConnectorInterface
 *
 * This interface represents a single SQL connector coming either from configuration or DB storage
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
interface ConnectorInterface extends DataEntryInterface
{
}
