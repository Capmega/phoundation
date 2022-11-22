<?php

namespace Phoundation\Accounts\Users\Users;

use Iterator;



/**
 * Class Accounts
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Users implements Iterator
{
    /**
     * Authenticate a user with the specified password
     *
     * @param string $user
     * @param string $password
     * @return User|null
     */
    public static function authenticate(string $user, string $password): ?User
    {

    }



    /**
     * @return mixed
     */
    public function current(): mixed
    {
        // TODO: Implement current() method.
    }

    public function next(): void
    {
        // TODO: Implement next() method.
    }

    public function key(): mixed
    {
        // TODO: Implement key() method.
    }

    public function valid(): bool
    {
        // TODO: Implement valid() method.
    }

    public function rewind(): void
    {
        // TODO: Implement rewind() method.
    }
}