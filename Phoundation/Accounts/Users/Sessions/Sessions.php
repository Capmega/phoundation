<?php

/**
 * Class Sessions
 *
 * Manage session data storage.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users\Sessions;

use Phoundation\Accounts\Users\Sessions\Exception\SessionException;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Date\PhoDateTime;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Os\Processes\Commands\Find;


class Sessions
{
    /**
     * Returns the handler for sessions
     *
     * @return string
     */
    public static function getHandler(): string
    {
        static $handler;

        if ($handler) {
            // Cached
            return $handler;
        }

        $handler = config()->getString('web.sessions.handler', 'files');

        switch ($handler) {
            case 'redis':
                // no break

            case 'mongodb':
                // no break

            case 'sql':
                // These will be supported some day, any day now!
                throw new UnderConstructionException(tr('Session handler ":handler" is still under construction and cannot yet be used', [
                    ':handler' => $handler
                ]));

            case 'files':
                // no break;

            case 'memcached':
                return $handler;

            default:
                throw new OutOfBoundsException(tr('Unknown session handler ":handler" configured in configuration path "web.sessions.handler"', [
                    ':handler' => $handler
                ]));
        }
    }


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
            $age_in_minutes = config()->getInteger('web.sessions.clean.age', 1440);
        }

        switch (Sessions::getHandler()) {
            case 'files':
                Log::action(ts('Cleaning session files older than ":age" minutes', [
                    ':age' => $age_in_minutes,
                ]));

                Find::new(PhoDirectory::newTemporaryObject())
                    ->setOlderThan($age_in_minutes)
                    ->setExecute('rf {} -rf')
                    ->executeNoReturn();
                break;

            case 'memcached':
        }
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
     * Stops all sessions for the specified users_id
     *
     * @param int $users_id
     *
     * @return int
     */
    public static function stopUser(int $users_id): int
    {
        $count    = 0;
        $sessions = sql()->listKeyValue('SELECT `session`, `stop` FROM `accounts_user_sessions` WHERE `users_id` = :users_id', [
            'users_id' => $users_id,
        ]);

        if ($sessions) {
            foreach ($sessions as $session => $stop) {
                if ($stop) {
                    // Remove the session
                    $count++;
                    UserSession::delete($session);
                }
            }

            // Register all sessions as closed
            sql()->update('accounts_user_sessions', ['stop' => PhoDateTime::new()->format('mysql')], ['users_id' => $users_id]);
        }

        return $count;
    }


    /**
     * Stops all sessions for the specified IP
     *
     * @param string $ip
     *
     * @return int
     */
    public static function stopIp(string $ip): int
    {
        $count    = 0;
        $sessions = sql()->listKeyValue('SELECT `session`, `stop` FROM `accounts_user_sessions` WHERE `ip` = :ip', [
            'ip' => $ip,
        ]);

        if ($sessions) {
            foreach ($sessions as $session => $stop) {
                if ($stop) {
                    // Remove the session
                    $count++;
                    UserSession::delete($session);
                }
            }

            // Register all sessions as closed
            sql()->update('accounts_user_sessions', ['stop' => PhoDateTime::new()->format('mysql')], ['ip' => $ip]);
        }

        return $count;
    }


    /**
     * Forcibly close all sessions that have expired
     *
     * The last action is stored in the $_SESSION data, so go over all sessions, check if they still exist in memcached
     *
     * If not, update the stop to now
     *
     * If yes, check the last action, and if that passed $max seconds, update the stop time to now as well
     *
     * @param int|null $max_seconds
     *
     * @return int
     */
    public static function stopExpired(?int $max_seconds = null): int
    {
        $count       = 0;
        $sessions    = sql()->query('SELECT `session` FROM `accounts_user_sessions` WHERE `stop` IS NULL');
        $max_seconds = $max_seconds ?? config()->getInteger('web.sessions.cookies.lifetime', 0);

        while ($session = $sessions->fetch()) {
            $o_session = UserSession::new($session, false);

            if (!$o_session->getIdentifier() or !$o_session->get('last_activity') or ((time() - $o_session->get('last_activity')) > $max_seconds)) {
                $count++;
                static::stop($session);
            }
        }

        return $count;
    }


    /**
     * Close the specified session
     *
     * @param string $session
     *
     * @return bool
     */
    public static function stop(string $session): bool
    {
        $session_data = sql()->getRow('SELECT `session`, `stop` FROM `accounts_user_sessions` WHERE `session` = :session', [
            'session' => $session,
        ]);

        if (!$session_data) {
            throw new SessionException(tr('Cannot close session ":session", it does not exist', [
                ':session' => $session,
            ]));
        }

        if ($session_data['stop']) {
            // Remove the session
            UserSession::delete($session);
            return true;
        }

        // The session was already closed
        return false;
    }


    /**
     * Returns an IteratorInterface with all currently active sessions
     *
     * @return IteratorInterface
     */
    public static function getActive(): IteratorInterface
    {
        return Iterator::new(sql()->listKeyValues('SELECT `identifier` AS `unique`, 
                                                          `id`, `domain`, `identifier`, `users_id`, `ip`, `start`, `stop`
                                                   FROM   `accounts_user_sessions` 
                                                   WHERE  `stop` IS NULL'));
    }


    /**
     * Returns an IteratorInterface with all currently active sessions
     *
     * @return IteratorInterface
     */
    public static function getAll(): IteratorInterface
    {
        return Iterator::new(sql()->listKeyValues('SELECT `identifier` AS `unique`, 
                                                          `id`, `domain`, `identifier`, `users_id`, `ip`, `start`, `stop`
                                                   FROM   `accounts_user_sessions`'));
    }


    /**
     * Returns an IteratorInterface with all currently active sessions
     *
     * @param int $users_id
     *
     * @return IteratorInterface
     */
    public static function getAllForUsersId(int $users_id): IteratorInterface
    {
        return Iterator::new(sql()->listKeyValues('SELECT `identifier` AS `unique`, 
                                                          `id`, `domain`, `identifier`, `users_id`, `ip`, `start`, `stop`
                                                   FROM   `accounts_user_sessions` 
                                                   WHERE  `users_id` = :users_id', [
            ':users_id' => $users_id
        ]));
    }


    /**
     * Returns an IteratorInterface with all currently active sessions
     *
     * @param int $users_id
     *
     * @return IteratorInterface
     */
    public static function getActiveForUsersId(int $users_id): IteratorInterface
    {
        return Iterator::new(sql()->listKeyValues('SELECT `identifier` AS `unique`, 
                                                          `id`, `domain`, `identifier`, `users_id`, `ip`, `start`, `stop`
                                                   FROM   `accounts_user_sessions` 
                                                   WHERE  `users_id` = :users_id 
                                                     AND  `stop` IS NULL', [
            ':users_id' => $users_id
        ]));
    }


    /**
     * Returns an IteratorInterface with all currently active sessions
     *
     * @param string $ip
     *
     * @return IteratorInterface
     */
    public static function getAllForIp(string $ip): IteratorInterface
    {
        return Iterator::new(sql()->listKeyValues('SELECT `identifier` AS `unique`, 
                                                          `id`, `domain`, `identifier`, `users_id`, `ip`, `start`, `stop` 
                                                   FROM   `accounts_user_sessions` 
                                                   WHERE  `ip` = :ip', [
            ':ip' => $ip
        ]));
    }


    /**
     * Returns an IteratorInterface with all currently active sessions
     *
     * @param string $ip
     *
     * @return IteratorInterface
     */
    public static function getActiveForIp(string $ip): IteratorInterface
    {
        return Iterator::new(sql()->listKeyValues('SELECT `identifier` AS `unique`, 
                                                          `id`, `domain`, `identifier`, `users_id`, `ip`, `start`, `stop`
                                                   FROM   `accounts_user_sessions` 
                                                   WHERE  `ip` = :ip 
                                                     AND  `stop` IS NULL', [
            ':ip' => $ip
        ]));
    }


    /**
     * Returns the number of currently active sessions
     *
     * @return int
     */
    public static function getActiveCount(): int
    {
        return sql()->getColumn('SELECT COUNT(`id`) AS `count` 
                                 FROM   `accounts_user_sessions` 
                                 WHERE  `stop` IS NULL');
    }


    /**
     * Returns the number of currently active sessions
     *
     * @return int
     */
    public static function getCount(): int
    {
        return sql()->getColumn('SELECT COUNT(`id`) as `count` FROM `accounts_user_sessions`');
    }


    /**
     * Truncates the accounts_user_sessions table
     *
     * @return void
     */
    public static function truncate(): void
    {
        mc('sessions')->clear();
        sql()->truncate('accounts_user_sessions');
    }


    /**
     * Adds data to the specified sessions list
     *
     * @param IteratorInterface $sessions
     *
     * @return IteratorInterface
     */
    public static function addData(IteratorInterface $sessions): IteratorInterface
    {
        foreach ($sessions as $identifier => $session) {
            $sessions->set(Session::addData($session), $identifier);
        }

        return $sessions;
    }
}

