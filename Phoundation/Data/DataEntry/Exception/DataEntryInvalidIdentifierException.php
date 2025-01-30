<?php

/**
 * Class DataEntryInvalidIdentifierException
 *
 * This exception is thrown when a DataEntry receives an identifier that is invalid.
 *
 * An invalid identifier, for example, may be a negative number for a DataEntry which has DataEntry::configuration_path
 * not set
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Exception;

use Phoundation\Data\DataEntry\Exception\Interfaces\DataEntryNotExistsExceptionInterface;

class DataEntryInvalidIdentifierException extends DataEntryException implements DataEntryNotExistsExceptionInterface
{
}
