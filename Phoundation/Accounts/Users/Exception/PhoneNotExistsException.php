<?php

/**
 * Class PhoneNotExistsException
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */

declare(strict_types=1);

namespace Phoundation\Accounts\Users\Exception;

use Phoundation\Accounts\Users\Exception\Interfaces\PhoneNotExistsExceptionInterface;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;

class PhoneNotExistsException extends DataEntryNotExistsException implements PhoneNotExistsExceptionInterface
{
}
