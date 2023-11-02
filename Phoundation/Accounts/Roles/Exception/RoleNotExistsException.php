<?php

namespace Phoundation\Accounts\Roles\Exception;

use Phoundation\Accounts\Roles\Exception\Interfaces\RoleNotExistsExceptionInterface;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;


/**
 * Class RoleNotExistsException
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class RoleNotExistsException extends DataEntryNotExistsException implements RoleNotExistsExceptionInterface
{
}
