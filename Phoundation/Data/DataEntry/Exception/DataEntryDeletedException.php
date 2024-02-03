<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Exception;

use Phoundation\Data\DataEntry\Exception\Interfaces\DataEntryNotExistsExceptionInterface;


/**
 * Class DataEntryDeletedException
 *
 * This exception is thrown when a data entry is loaded that has been deleted
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class DataEntryDeletedException extends DataEntryException implements DataEntryNotExistsExceptionInterface
{
}
