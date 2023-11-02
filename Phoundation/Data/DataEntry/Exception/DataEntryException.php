<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Exception;

use Phoundation\Data\DataEntry\Exception\Interfaces\DataEntryExceptionInterface;
use Phoundation\Data\Exception\DataException;


/**
 * Class DataException
 *
 * This is the standard exception for Data classes
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class DataEntryException extends DataException implements DataEntryExceptionInterface
{
}
