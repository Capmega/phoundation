<?php

namespace Phoundation\Core\Sessions;

use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Core\Sessions\Interfaces\SessionInterface;
use Phoundation\Exception\UnderConstructionException;


/**
 * Class Sessions
 *
 * Manage session data storage.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Sessions
{
    /**
     * Sessions class constructor
     */
    public function __construct()
    {
    }


    /**
     * Returns a new sessions object
     *
     * @return static
     */
    public static function new(): static
    {
        return new Sessions();
    }


    /**
     * Close the specified session
     *
     * @param SessionInterface|int $session
     * @return void
     */
    public function close(SessionInterface|int $session): void
    {
throw new UnderConstructionException();
    }


    /**
     * Drop all sessions for the specified user, this user will be signed out on every device
     *
     * @param UserInterface|int $user
     * @return void
     */
    public function drop(UserInterface|int $user): void
    {
        //throw new UnderConstructionException();

    }


    /**
     * Drop all sessions, everyone will be signed out
     *
     * @return void
     */
    public function dropAll(): void
    {
        throw new UnderConstructionException();
    }
}
