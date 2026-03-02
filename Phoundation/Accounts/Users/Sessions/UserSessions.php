<?php

/**
 * Class UserSessions
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users\Sessions;

use PDOStatement;
use Phoundation\Accounts\Users\Sessions\Exception\SessionException;
use Phoundation\Accounts\Users\Sessions\Interfaces\UserSessionInterface;
use Phoundation\Accounts\Users\Sessions\Interfaces\UserSessionsInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\DataIterator;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Date\Enums\EnumDateFormat;
use Phoundation\Date\PhoDateTime;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Os\Processes\Commands\Find;
use ReturnTypeWillChange;
use Stringable;


class UserSessions extends DataIterator implements UserSessionsInterface
{
    /**
     * UserSessions constructor
     *
     * @param IteratorInterface|array|string|PDOStatement|null $source
     */
    public function __construct(IteratorInterface|array|string|PDOStatement|null $source = null)
    {
        parent::__construct($source);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'accounts_user_sessions';
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'code';
    }


    /**
     * Returns the class for a single DataEntry in this Iterator object
     *
     * @return string|null
     */
    public static function getDefaultContentDataType(): ?string
    {
        return UserSession::class;
    }


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
                    ->setAtime('-' . $age_in_minutes)
                    ->setExec('rf {} -rf')
                    ->executeNoReturn();
                break;

            case 'memcached':
        }
    }


    /**
     * Stops all sessions for the specified users_id
     *
     * @param int $users_id
     *
     * @return int
     */
    public static function closeUser(int $users_id): int
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
            sql()->update('accounts_user_sessions', ['stop' => PhoDateTime::new()->format(EnumDateFormat::mysql_datetime)], ['users_id' => $users_id]);
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
    public static function closeIp(string $ip): int
    {
        $count    = 0;
        $sessions = sql()->listKeyValue('SELECT `session`, `closed` FROM `accounts_user_sessions` WHERE `ip` = :ip', [
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
            sql()->update('accounts_user_sessions', ['stop' => PhoDateTime::new()->format(EnumDateFormat::mysql_datetime)], ['ip' => $ip]);
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
            $_session = UserSession::new($session, false);

            if (!$_session->getCode() or !$_session->get('last_activity') or ((time() - $_session->get('last_activity')) > $max_seconds)) {
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
     * Loads all active sessions into this object
     *
     * @return static
     */
    public function loadActive(): static
    {
        $this->source = sql()->listKeyValues('SELECT `code` AS `unique`, 
                                                     `id`, `domain`, `code`, `users_id`, `ip`, `start`, `stop`
                                              FROM   `accounts_user_sessions` 
                                              WHERE  `stop` IS NULL');

        return $this;
    }


    /**
     * Loads all sessions into this object
     *
     * @return static
     */
    public function loadAll(): static
    {
        $this->source = sql()->listKeyValues('SELECT `code` AS `unique`, 
                                                     `id`, `domain`, `code`, `users_id`, `ip`, `start`, `stop`
                                              FROM   `accounts_user_sessions`');

        return $this;
    }


    /**
     * Loads all sessions for the specified users_id into this object
     *
     * @param int $users_id
     *
     * @return static
     */
    public function loadAllForUsersId(int $users_id): static
    {
        $this->source = sql()->listKeyValues('SELECT `code` AS `unique`, 
                                                     `id`, `domain`, `code`, `users_id`, `ip`, `start`, `stop`
                                              FROM   `accounts_user_sessions` 
                                              WHERE  `users_id` = :users_id', [
                                                  ':users_id' => $users_id
        ]);

        return $this;
    }


    /**
     * Loads all active sessions for the specified users_id into this object
     *
     * @param int $users_id
     *
     * @return static
     */
    public function loadActiveForUsersId(int $users_id): static
    {
        $this->source = sql()->setDebug(true)->listKeyValues('SELECT `code` AS `unique`, 
                                                                     `id`, `domain`, `code`, `users_id`, `ip`, `start`, `stop`
                                                              FROM   `accounts_user_sessions` 
                                                              WHERE  `users_id` = :users_id 
                                                              AND    `stop` IS NULL', [
                                                                  ':users_id' => $users_id
        ]);

        return $this;
    }


    /**
     * Loads all sessions from the specified IP address into this object
     *
     * @param string $ip
     *
     * @return static
     */
    public function loadAllForIp(string $ip): static
    {
        $this->source = sql()->listKeyValues('SELECT `code` AS `unique`, 
                                                     `id`, `domain`, `code`, `users_id`, `ip`, `start`, `stop` 
                                              FROM   `accounts_user_sessions` 
                                              WHERE  `ip` = :ip', [
                                                  ':ip' => $ip
        ]);

        return $this;
    }


    /**
     * Loads all active sessions from the specified IP address into this object
     *
     * @param string $ip
     *
     * @return static
     */
    public function loadActiveForIp(string $ip): static
    {
        $this->source = sql()->listKeyValues('SELECT `code` AS `unique`, 
                                                     `id`, `domain`, `code`, `users_id`, `ip`, `start`, `stop`
                                              FROM   `accounts_user_sessions` 
                                              WHERE  `ip` = :ip 
                                              AND    `stop` IS NULL', [
                                                  ':ip' => $ip
        ]);

        return $this;
    }


    /**
     * Returns the number of currently active sessions
     *
     * @return int
     */
    public function getActiveCount(): int
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
    public static function getAllCount(): int
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
     * @param array $sessions_data The data for multiple sessions to add to these sessions
     *
     * @return static
     */
    public function addData(array $sessions_data): static
    {
        foreach ($this as $code => $_session) {
            $this->get($code)->addData(array_get_safe($sessions_data, $code));
        }

        return $this;
    }


    /**
     * Returns the specified UserSessions object
     *
     * @param Stringable|string|float|int $key
     * @param mixed|null                  $default
     * @param bool|null                   $exception
     * @return static|null
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): ?UserSessionInterface
    {
        return parent::get($key, $default, $exception);
    }


    /**
     * Returns a random UserSessions object
     *
     * @return static|null
     */
    #[ReturnTypeWillChange] public function getRandom(): ?UserSessionInterface
    {
        return parent::getRandom();
    }


    /**
     * Returns the current UserSessions object
     *
     * @note overrides the IteratorCore::current() method which returns mixed
     *
     * @return static|null
     */
    #[ReturnTypeWillChange] public function current(): ?UserSessionInterface
    {
        return parent::current();
    }
}
