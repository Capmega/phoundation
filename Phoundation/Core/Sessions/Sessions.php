<?php

declare(strict_types=1);

namespace Phoundation\Core\Sessions;

use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Interfaces\SessionInterface;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Utils\Config;

/**
 * Class Sessions
 *
 * Manage session data storage.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */
class Sessions
{
    /**
     * Sessions class constructor
     */
    public function __construct() {}


    /**
     * Clean up old sessions
     *
     * @param int|null $age_in_minutes
     *
     * @return void
     */
    public static function clean(?int $age_in_minutes): void
    {
        if (!$age_in_minutes) {
            $age_in_minutes = Config::getInteger('tmp.clean.age', 1440);
        }

        Log::action(tr('Cleaning session files older than ":age" minutes', [
            ':age' => $age_in_minutes,
        ]));

        Find::new(FsDirectory::getTemporary())
            ->setOlderThan($age_in_minutes)
            ->setExecute('rf {} -rf')
            ->executeNoReturn();
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
     *
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
     *
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
