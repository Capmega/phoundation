<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Exception;

/**
 * Class DataEntryStateException
 *
 * This exception is thrown when a DataEntry has a state that is conflicting with an action that is being executed
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
class DataEntryStateException extends DataEntryException
{
}
