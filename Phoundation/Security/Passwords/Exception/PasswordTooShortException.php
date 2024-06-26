<?php

/**
 * Class PasswordTooShortException
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
 */

declare(strict_types=1);

namespace Phoundation\Security\Passwords\Exception;

use Phoundation\Accounts\Users\Exception\UsersException;
use Throwable;

class PasswordTooShortException extends UsersException
{
    public function __construct(Throwable|array|string|null $messages, ?Throwable $previous = null)
    {
        parent::__construct($messages, $previous);
        $this->makeWarning();
    }
}
