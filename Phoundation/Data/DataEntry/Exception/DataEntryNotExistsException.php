<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Exception;

use Phoundation\Data\DataEntry\Exception\Interfaces\DataEntryNotExistsExceptionInterface;


/**
 * Class DataEntryAlreadyExistsException
 *
 * This exception is thrown when a data entry does not exist
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
class DataEntryNotExistsException extends DataEntryException implements DataEntryNotExistsExceptionInterface
{
}
