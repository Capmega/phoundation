<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Roles\Exception;

use Phoundation\Accounts\Roles\Exception\Interfaces\RightNotExistsExceptionInterface;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;

/**
 * Class RightNotExistsException
 *
 *
 *
 * @see       \Phoundation\Data\DataEntry\DataList
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */
class RightNotExistsException extends DataEntryNotExistsException implements RightNotExistsExceptionInterface
{
}
