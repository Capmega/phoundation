<?php

/**
 * Class DataEntryInvalidParentException
 *
 * This exception is thrown when a parent object is attached to a DataEntry that has a class that is not allowed to be
 * attached to it
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Exception;

class DataEntryInvalidParentException extends DataEntryException
{
}
