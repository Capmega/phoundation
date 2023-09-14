<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users\Exception;

use Phoundation\Accounts\Users\Exception\Interfaces\AuthenticationExceptionInterface;
use Throwable;


/**
 * Class AuthenticationException
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class AuthenticationException extends UsersException implements AuthenticationExceptionInterface
{
    /**
     * The new target that should be executed because of this access denied
     *
     * @var string|int|null
     */
    protected string|int|null $new_target;


    /**
     * AuthenticationException class constructor
     *
     * @param Throwable|array|string|null $messages
     * @param Throwable|null $previous
     */
    public function __construct(Throwable|array|string|null $messages, ?Throwable $previous = null)
    {
        parent::__construct($messages, $previous);
        $this->makeWarning();
    }


    /**
     * Returns the new target
     *
     * @return string|int|null
     */
    public function getNewTarget(): string|int|null
    {
        return $this->new_target;
    }


    /**
     * Sets the new target
     *
     * @param string|int|null $new_target
     * @return static
     */
    public function setNewTarget(string|int|null $new_target): static
    {
        $this->new_target = $new_target;
        return $this;
    }
}
