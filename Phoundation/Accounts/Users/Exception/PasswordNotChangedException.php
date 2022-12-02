<?php

namespace Phoundation\Accounts\Users\Exception;

use Throwable;



/**
 * Class PasswordNotChangedException
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class PasswordNotChangedException extends UsersException
{
    public function __construct(array|string $messages, mixed $data = null, ?string $code = null, ?Throwable $previous = null)
    {
        parent::__construct($messages, $data, $code, $previous);
        $this->makeWarning();
    }
}
