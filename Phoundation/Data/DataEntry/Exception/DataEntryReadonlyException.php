<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Exception;


/**
 * Class DataEntryReadonlyException
 *
 * This exception is thrown when a data entry is trying to save its data while being in readonly state
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class DataEntryReadonlyException extends DataEntryException
{
}
