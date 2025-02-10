<?php

/**
 * Class DataEntryDeletedException
 *
 * This exception is thrown when a data entry is loaded that has been deleted
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Exception;


class DataEntryDeletedException extends DataEntryNotExistsException
{
}
