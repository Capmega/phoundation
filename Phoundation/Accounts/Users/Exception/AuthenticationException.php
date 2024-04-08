<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users\Exception;

use Phoundation\Accounts\Users\Exception\Interfaces\AuthenticationExceptionInterface;
use Phoundation\Data\Traits\TraitDataNewTarget;
use Throwable;

/**
 * Class AuthenticationException
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */
class AuthenticationException extends UsersException implements AuthenticationExceptionInterface
{
    use TraitDataNewTarget;

    /**
     * AuthenticationException class constructor
     *
     * @param Throwable|array|string|null $messages
     * @param Throwable|null              $previous
     */
    public function __construct(Throwable|array|string|null $messages, ?Throwable $previous = null)
    {
        parent::__construct($messages, $previous);
        $this->makeWarning();
    }
}
