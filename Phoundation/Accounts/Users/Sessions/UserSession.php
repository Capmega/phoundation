<?php
/**
 * Session class
 *
 * This class tracks and manages individual sessions
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users\Sessions;

use Phoundation\Accounts\Exception\SessionNotExistsException;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\Sessions\Interfaces\UserSessionInterface;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Sessions\Exception\SessionDuplicateIdentifierException;
use Phoundation\Data\Traits\TraitDataSourceArray;
use Phoundation\Databases\Sql\Exception\SqlContstraintDuplicateEntryException;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Date\PhoDateTime;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Utils\Strings;
use ReturnTypeWillChange;
use Stringable;


class UserSession implements UserSessionInterface
{
    use TraitDataSourceArray{
        get as protected __Get;
        set as protected __Set;
    }


    /**
     * Session class constructor
     *
     * @param string|null $identifier
     * @param bool        $exception
     */
    public function __construct(?string $identifier = null, bool $exception = true)
    {
        if (empty($identifier)) {
            // Don't load any session data at all
            return;
        }

        $data   = static::load($identifier);
        $source = sql()->getRow('SELECT * FROM `accounts_user_sessions` WHERE `identifier` = :identifier', [
            ':identifier' => $identifier
        ]);

        if (empty($data)) {
            if ($exception) {
                throw new SessionNotExistsException(tr('The specified session ":session" does not exist', [
                    ':session' => $identifier,
                ]));
            }

        } else {
            $this->source           = $source;
            $this->source['string'] = $data;
            $this->source['data']   = static::unserialize($data);
        }
    }


    /**
     * Returns a new static object
     *
     * @param string $identifier
     * @param bool   $exception
     *
     * @return static
     */
    public static function new(string $identifier, bool $exception = true): static
    {
        return new static($identifier, $exception);
    }


    /**
     * Returns the databse id for this session record
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->source['id'];
    }


    /**
     * Returns the session identifier
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->source['identifier'];
    }


    /**
     * Returns the domain for this session
     *
     * @return string
     */
    public function getDomain(): string
    {
        return $this->source['domain'];
    }


    /**
     * Returns the session IP
     *
     * @return string
     */
    public function getIp(): string
    {
        return $this->source['ip'];
    }


    /**
     * Returns the session users id
     *
     * @return int
     */
    public function getUsersId(): int
    {
        return $this->source['users_id'];
    }


    /**
     * Returns the session user object
     *
     * @return UserInterface
     */
    public function getUserObject(): UserInterface
    {
        return User::new()->load($this->source['users_id']);
    }


    /**
     * Returns the session start datetime string
     *
     * @return string
     */
    public function getStart(): string
    {
        return $this->source['start'];
    }


    /**
     * Returns the session start datetime object
     *
     * @return PhoDateTimeInterface
     */
    public function getStartObject(): PhoDateTimeInterface
    {
        return new PhoDateTime($this->source['start']);
    }


    /**
     * Returns the session stop datetime string
     *
     * @return string
     */
    public function getStop(): string
    {
        return $this->source['stop'];
    }


    /**
     * Returns the session stop datetime object
     *
     * @return PhoDateTimeInterface
     */
    public function getStopObject(): PhoDateTimeInterface
    {
        return new PhoDateTime($this->source['stop']);
    }


    /**
     *  Returns the value for the specified session user data key
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, bool $exception = true): mixed
    {
        // Does this entry exist?
        if (array_key_exists($key, $this->source['data'])) {
            return $this->source['data'][$key];
        }

        if ($exception) {
            // The key does not exist
            throw new NotExistsException(tr('The key ":key" does not exist in this ":class" object', [
                ':key'   => $key,
                ':class' => $this::class,
            ]));
        }

        return null;
    }


    /**
     * Sets the specified session user data key to the specified value
     *
     * @param mixed                       $value
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $skip_null_values
     *
     * @return UserSession
     */
    public function set(mixed $value, Stringable|string|float|int $key, bool $skip_null_values = true): static
    {
        if ($value === null) {
            if ($skip_null_values) {
                return $this;
            }
        }

        $this->source['data'][$key] = $value;
        return $this;
    }


    /**
     * Delete the specified session
     *
     * @param string $session
     *
     * @return void
     */
    public static function delete(string $session): void
    {
        $handler = ini_get('session.save_handler');

        switch ($handler) {
            case 'memcached':
                // Remove the session from memcached
                mc('sessions')->delete($session);
                break;

            case 'files':
                // Remove the session from files
                PhoFile::new(ini_get('session.save_path'), PhoRestrictions::newWritableObject(dirname(ini_get('session.save_path'))))
                       ->delete();
                break;

            default:
                throw new OutOfBoundsException(tr('Unknown or unsupported session save handler ":handler" encountered', [
                    ':handler' => $handler,
                ]));
        }
    }


    /**
     * Returns true if the specified session exists in the sessions store
     *
     * @param string $identifier
     *
     * @return bool
     */
    public static function exists(string $identifier): bool
    {
        return sql()->exists('accounts_user_sessions', 'identifier', $identifier);
    }


    /**
     * Returns the session data for the specified session, or NULL if it `doesn't exist
     *
     * @param string      $identifier
     * @param string|null $handler
     *
     * @return string|null
     */
    public static function load(string $identifier, ?string $handler = null): ?string
    {
        $handler = $handler ?? ini_get('session.save_handler');

        return match ($handler) {
            'memcached' => mc('sessions')->get($identifier),
            'files'     => PhoFile::new(Strings::slash(ini_get('session.save_path')) . 'sess_' . $identifier, PhoRestrictions::newWritableObject(dirname(ini_get('session.save_path'))))->getContentsAsString(),
            default     => throw new OutOfBoundsException(tr('Unknown or unsupported session save handler ":handler" encountered', [
                ':handler' => $handler,
            ])),
        };
    }


    /**
     * Saves the session data
     *
     * @return static
     */
    public function save(): static
    {
        $data    = static::serialize($this->source['data']);
        $handler = $handler ?? ini_get('session.save_handler');

        match ($handler) {
            'memcached' => mc('sessions')->set($data, $this->getIdentifier()),
            'files'     => PhoFile::new(Strings::slash(ini_get('session.save_path')) . 'sess_' . $this->getIdentifier(), PhoRestrictions::newWritableObject(dirname(ini_get('session.save_path'))))->putContents($data),
            ''          => throw new OutOfBoundsException(tr('No session save handler ":handler" configured')),
            default     => throw new OutOfBoundsException(tr('Unknown or unsupported session save handler ":handler" encountered', [
                ':handler' => $handler,
            ])),
        };

        return $this;
    }


    /**
     * Registers a new started session in the accounts_user_sessions table
     *
     * @param int    $users_id
     * @param string $domain
     * @param string $ip
     * @param string $identifier
     *
     * @return static
     */
    public static function start(int $users_id, string $domain, string $ip, string $identifier): static
    {
        try {
            sql()->insert('accounts_user_sessions', [
                'users_id'   => $users_id,
                'domain'     => $domain,
                'ip'         => $ip,
                'identifier' => $identifier,
            ]);

            return static::new($identifier, false);

        } catch (SqlContstraintDuplicateEntryException $e) {
            throw new SessionDuplicateIdentifierException(tr('Duplicate session identifier ":identifier" encountered', [
                ':identifier' => $identifier,
            ]), $e);
        }
    }


    /**
     * Stops this session
     *
     * @return static
     */
    public function stop(): static
    {
        sql()->update('sessions', ['stop' => PhoDateTime::new()->format('mysql')]);
        return $this;
    }


    /**
     * Serializes the specified PHP session data
     *
     * @param array       $source
     * @param string|null $handler
     *
     * @return mixed
     */
    public static function serialize(array $source, ?string $handler = null): string
    {
        $handler = $handler ?? ini_get('session.serialize_handler');

        return match ($handler) {
            'php_serialize'     => serialize($source),
            'php', 'php_binary' => throw new UnderConstructionException(tr('Serialization of sessions using session save handler ":handler" is not yet supported', [
                ':handler' => $handler,
            ])),
            default             => throw new OutOfBoundsException(tr('Unknown or unsupported session save handler ":handler" encountered', [
                ':handler' => $handler,
            ])),
        };
    }


    /**
     * Unserializes the specified PHP session data
     *
     * @note Taken from PHP manual, thank you to Frits dot vanCampen at moxio dot com
     * @note Update for use in Phoundation by Sven Olaf Oostenbrink
     * @see  https://www.php.net/manual/en/function.session-decode.php#108037
     *
     * @param string      $source
     * @param string|null $handler
     *
     * @return mixed
     */
    public static function unserialize(string $source, ?string $handler = null): array
    {
        $handler = $handler ?? ini_get('session.serialize_handler');

        return match ($handler) {
            'php_serialize' => unserialize($source),
            'php'           => static::unserializePhp($source),
            'php_binary'    => static::unserializePhpBinary($source),
            default         => throw new OutOfBoundsException(tr('Unknown or unsupported session save handler ":handler" encountered', [
                ':handler' => $handler,
            ])),
        };
    }


    /**
     * Unserializes session data using the internal php handler
     *
     * @param $source
     *
     * @return array
     */
    protected static function unserializePhp($source): array
    {
        $return = [];
        $offset = 0;

        if (empty($source)) {
           return [];
        }

        while ($offset < strlen($source)) {
            if (!str_contains(substr($source, $offset), '|')) {
                throw new OutOfBoundsException(tr('Invalid data ":data" remaining', [
                    ':data' => substr($source, $offset)
                ]));
            }

            $pos          = strpos($source, '|', $offset);
            $num          = $pos - $offset;
            $key          = substr($source, $offset, $num);
            $offset      += $num + 1;
            $data         = unserialize(substr($source, $offset));
            $return[$key] = $data;
            $offset      += strlen(serialize($data));
       }

       return $return;
    }


    /**
     * Unserializes session data using the internal php_binary handler
     *
     * @param $source
     *
     * @return array
     */
    protected static function unserializePhpBinary($source): array
    {
        $return = [];
        $offset = 0;

        if (empty($source)) {
            return [];
        }

        while ($offset < strlen($source)) {
                $num          = ord($source[$offset]);
                $offset      += 1;
                $key          = substr($source, $offset, $num);
                $offset      += $num;
                $data         = unserialize(substr($source, $offset));
                $return[$key] = $data;
                $offset      += strlen(serialize($data));
            }

        return $return;
     }


    /**
     * @param string $identifier
     *
     * @return bool
     */
     public static function isActive(string $identifier): bool
     {
        return (bool) mc('sessions')->get($identifier);
     }
}
