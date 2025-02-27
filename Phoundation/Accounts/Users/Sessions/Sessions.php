<?php
/**
 * Sessions class
 *
 * This class tracks and manages user sessions
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users\Sessions;

use Phoundation\Core\Exception\SessionException;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Date\PhoDateTime;


class Sessions
{
    /**
     * Add the specified session to the sessions tracking table
     *
     * @param int    $users_id
     * @param string $domain
     * @param string $ip
     * @param string $session
     *
     * @return void
     */
    public static function start(int $users_id, string $domain, string $ip, string $session): void
    {
        $data = [
            'users_id' => $users_id,
            'domain'   => $domain,
            'ip'       => $ip,
            'session'  => $session,
        ];

        sql()->insert('accounts_sessions', $data);
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
        $sessions = sql()->listKeyValue('SELECT `session`, `stop` FROM `accounts_sessions` WHERE `users_id` = :users_id', [
            'users_id' => $users_id,
        ]);

        if ($sessions) {
            foreach ($sessions as $session => $stop) {
                if ($stop) {
                    // Remove the session
                    $count++;
                    Session::delete($session);
                }
            }

            // Register all sessions as closed
            sql()->update('accounts_sessions', ['stop' => PhoDateTime::new()->format('mysql')], ['users_id' => $users_id]);
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
        $sessions = sql()->listKeyValue('SELECT `session`, `stop` FROM `accounts_sessions` WHERE `ip` = :ip', [
            'ip' => $ip,
        ]);

        if ($sessions) {
            foreach ($sessions as $session => $stop) {
                if ($stop) {
                    // Remove the session
                    $count++;
                    Session::delete($session);
                }
            }

            // Register all sessions as closed
            sql()->update('accounts_sessions', ['stop' => PhoDateTime::new()->format('mysql')], ['ip' => $ip]);
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
        $sessions    = sql()->query('SELECT `session` FROM `accounts_sessions` WHERE `stop` IS NULL');
        $max_seconds = $max_seconds ?? config()->getInteger('web.sessions.cookies.lifetime', 0);

        while ($session = $sessions->fetch()) {
            $o_session = Session::new($session, false);

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
        $session_data = sql()->get('SELECT `session`, `stop` FROM `accounts_sessions` WHERE `session` = :session', [
            'session' => $session,
        ]);

        if (!$session_data) {
            throw new SessionException(tr('Cannot close session ":session", it does not exist', [
                ':session' => $session,
            ]));
        }

        if ($session_data['stop']) {
            // Remove the session
            Session::delete($session);
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
        return Iterator::new(sql()->listKeyValues('SELECT COUNT(`id`) AS `count` 
                                                   FROM   `accounts_sessions` 
                                                   WHERE  `stop` IS NULL'));
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
        return Iterator::new(sql()->listKeyValues('SELECT * 
                                                   FROM   `accounts_sessions` 
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
        return Iterator::new(sql()->listKeyValues('SELECT * 
                                                   FROM   `accounts_sessions` 
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
        return Iterator::new(sql()->listKeyValues('SELECT * 
                                                   FROM   `accounts_sessions` 
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
        return Iterator::new(sql()->listKeyValues('SELECT * 
                                                   FROM   `accounts_sessions` 
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
                                 FROM   `accounts_sessions` 
                                 WHERE  `stop` IS NULL');
    }


    /**
     * Returns the number of currently active sessions
     *
     * @return int
     */
    public static function getCount(): int
    {
        return sql()->getColumn('SELECT COUNT(`id`) as `count` FROM `accounts_sessions`');
    }


    /**
     * Returns an Iterator object containing all active sessions
     *
     * @return IteratorInterface
     */
    public static function list(): IteratorInterface
    {
        return Iterator::new(sql()->listKeyValue('SELECT `session` FROM `accounts_sessions` WHERE `stop` IS NULL'));
    }


    /**
     * Truncates the accounts_sessions table
     *
     * @return void
     */
    public static function truncate(): void
    {
        mc('sessions')->flush();
        sql()->truncate('accounts_sessions');
    }
}
