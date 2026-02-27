<?php

/**
 * Class UserSession
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

use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\Sessions\Exception\SessionDuplicateIdentifierException;
use Phoundation\Accounts\Users\Sessions\Exception\SessionException;
use Phoundation\Accounts\Users\Sessions\Exception\SessionNotExistsException;
use Phoundation\Accounts\Users\Sessions\Interfaces\UserSessionInterface;
use Phoundation\Accounts\Users\User;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryStringDomain;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryStringIdentifier;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryStringRemoteIp;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryStringRemoteIpReal;
use Phoundation\Data\Enums\EnumLoadParameters;
use Phoundation\Databases\Sql\Exception\SqlContstraintDuplicateEntryException;
use Phoundation\Date\Enums\EnumDateFormat;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Date\PhoDateTime;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;
use ReturnTypeWillChange;
use Stringable;


class UserSession extends DataEntry implements UserSessionInterface
{
    use TraitDataEntryStringIdentifier;
    use TraitDataEntryStringDomain;
    use TraitDataEntryStringRemoteIp;
    use TraitDataEntryStringRemoteIpReal;


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
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
    {
        return tr('User session');
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'identifier';
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
     * @param mixed                       $default
     * @param bool|null                   $exception
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): mixed
    {
        // Does this entry exist?
        if (array_key_exists('data', $this->source)) {
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

        } elseif ($exception) {
            // The session data does not exist
            throw new NotExistsException(tr('The ":class" object does not have session data loaded', [
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
     * Deletes this session
     *
     * @return static
     */
    public function delete(?string $comments = null, bool $auto_save = true): static
    {
        $handler = ini_get('session.save_handler');

        switch ($handler) {
            case 'memcached':
                // Remove the session from memcached
                mc('sessions')->delete($this->getIdentifier());
                break;

            case 'files':
                // Remove the session from files
                PhoFile::new(ini_get('session.save_path') . $this->getIdentifier(), PhoRestrictions::newWritableObject(dirname(ini_get('session.save_path'))))
                       ->delete();
                break;

            default:
                throw new OutOfBoundsException(tr('Unknown or unsupported session save handler ":handler" encountered', [
                    ':handler' => $handler,
                ]));
        }

        return $this;
    }


    /**
     * @inheritDoc
     */
    public function load(IdentifierInterface|int|array|string|null $identifier = null, ?EnumLoadParameters $on_null_identifier = null, ?EnumLoadParameters $on_not_exists = null): ?static
    {
        parent::load($identifier, $on_null_identifier, $on_not_exists);

        $handler = $handler ?? ini_get('session.save_handler');
        $data    = match ($handler) {
            'memcached' => mc('sessions')->get($identifier),
            'files'     => PhoFile::new(Strings::slash(ini_get('session.save_path')) . 'sess_' . $identifier, PhoRestrictions::newWritableObject(dirname(ini_get('session.save_path'))))->getContentsAsString(),
            ''          => throw new SessionException(tr('No session save handler ":handler" configured')),
            default     => throw new SessionException(tr('Unknown or unsupported session save handler ":handler" encountered', [
                ':handler' => $handler,
            ])),
        };

        return $this->addData($data);
    }


    /**
     * Will save the data from this data entry to the database
     *
     * @param bool        $force
     * @param bool        $skip_validation
     * @param string|null $comments
     *
     * @return static
     */
    public function save(bool $force = false, bool $skip_validation = false, ?string $comments = null): static
    {
        $data    = static::serialize($this->source['data']);
        $handler = $handler ?? ini_get('session.save_handler');

        match ($handler) {
            'memcached' => mc('sessions')->set($data, $this->getIdentifier()),
            'files'     => PhoFile::new(Strings::slash(ini_get('session.save_path')) . 'sess_' . $this->getIdentifier(), PhoRestrictions::newWritableObject(dirname(ini_get('session.save_path'))))->putContents($data),
            ''          => throw new SessionException(tr('No session save handler ":handler" configured')),
            default     => throw new SessionException(tr('Unknown or unsupported session save handler ":handler" encountered', [
                ':handler' => $handler,
            ])),
        };

        return parent::save($force, $skip_validation, $comments);
    }


    /**
     * Registers a new started session in the accounts_user_sessions table and returns a UserSession object for it
     *
     * @param int|null $users_id
     * @param string   $domain
     * @param string   $ip
     * @param string   $identifier
     *
     * @return static
     */
    public static function start(?int $users_id, string $domain, string $ip, string $identifier): static
    {
        $retry = 0;

        while ($retry++ < 5) {
            try {
                sql()->insert('accounts_user_sessions', [
                    'id'         => Numbers::getRandomInt(),
                    'ip'         => $ip,
                    'domain'     => $domain,
                    'users_id'   => $users_id,
                    'identifier' => $identifier,
                ]);

                return static::new($identifier, false);

            } catch (SqlContstraintDuplicateEntryException $e) {
                // TODO This blindly assumes duplicate identifier while it can also be a duplicate ID. For a duplicate session identifier, it will retry 5 times with the same value. Chances of this happening are VERY TIY, but FIX THIS ANYWAY
                // Duplicate ID or identifier, try again
            }
        }

        // TODO We COULD have encountered 5x an existing accounts_user_sessions.id, even though its a 1 in (5 x PHP_INT_MAX) chance
        throw new SessionDuplicateIdentifierException(tr('Duplicate session identifier ":identifier" encountered', [
            ':identifier' => $identifier,
        ]), $e);
    }


    /**
     * Stops this session
     *
     * @return void
     */
    public function stop(): void
    {
        sql()->update('accounts_user_sessions', [
            'stop' => PhoDateTime::new()->format(EnumDateFormat::mysql_datetime)
        ], [
            'identifier' => $this->getIdentifier(),
        ]);
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
     * Returns if the specified identifier is an active session.
     *
     * @return bool
     */
     public function isActive(): bool
     {
         $session = mc('sessions')->get($this->getIdentifier());

         if ($session) {
            if (array_get_safe($session, 'stop')) {
                return false;
            }

            return true;
         }

         return false;
     }


    /**
     * Returns if the specified identifier is an active session.
     *
     * @note If called as a static method, an identifier MUST be specified. If called as an object method, no identifier may be specified
     *
     * @param string $identifier The session identifier string to test
     *
     * @return bool
     */
     public static function isActiveSession(string $identifier): bool
     {
         if (empty($identifier)) {
             throw new OutOfBoundsException(ts('Cannot check if session identifier is active, no (or empty) identifier specified'));
         }

         $session = mc('sessions')->get($identifier);

         if ($session) {
            if (array_get_safe($session, 'stop')) {
                return false;
            }

            return true;
         }

         return false;
     }


    /**
     * Adds data to the specified sessions list
     *
     * @param array $session
     *
     * @return static
     */
    public function addData(array $session): static
    {
        // Ensure the session exists!
        $this->source['data']          = UserSession::new($session['identifier'], false)->getSource();
        $this->source['user']          = User::new()->loadNull(array_get_safe(array_get_safe($session['data'], 'user'), 'id'));
        $this->source['last_activity'] = array_get_safe($session['data'], 'last_activity') ?? array_get_safe($session, 'stop') ?? array_get_safe($session, 'start');
        $this->source['last_activity'] = PhoDateTime::new($this->source['last_activity']);
        $this->source['start']         = PhoDateTime::new($session['start']);
        $this->source['stop']          = PhoDateTime::new($session['stop']);

        return $this;
    }


    /**
     * Copies the data for this session to a session with the specified identifier
     *
     * @param string $identifier
     *
     * @return $this
     */
    public function copyTo(string $identifier): static
    {
throw new UnderConstructionException();
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $_definitions
     *
     * @return static
     */
    protected function setDefinitionsObject(DefinitionsInterface $_definitions): static
    {
        $_definitions->removeKeys('meta_divider')

                     ->add(DefinitionFactory::newCreatedBy()
                                            ->setOptional(true)
                                            ->setRender(true))

                     ->add(DefinitionFactory::newDivider('meta_divider'))

                     ->add(DefinitionFactory::newCode('identifier')
                                            ->setLabel(tr('Identifier'))
                                            ->setDisabled(true)
                                            ->setReadonly(true)
                                            ->setSize(6))

                     ->add(DefinitionFactory::newDomain()
                                            ->setDisabled(true)
                                            ->setReadonly(true)
                                            ->setSize(6))

                    ->add(DefinitionFactory::newUsersId())

                    ->add(DefinitionFactory::newIpAddress('remote_ip')
                                           ->setLabel(tr('Remote IP Address'))
                                           ->setReadonly(true)
                                           ->setOptional(true)
                                           ->setSize(4))

                    ->add(DefinitionFactory::newIpAddress('remote_ip_real')
                                           ->setLabel(tr('Real Remote IP Address'))
                                           ->setReadonly(true)
                                           ->setOptional(true)
                                           ->setSize(4))

                    ->add(DefinitionFactory::newDateTime('closed')
                                           ->setReadonly(true)
                                           ->setOptional(true)
                                           ->setMaxLength(4)
                                           ->setHelpText(tr('Global ID'))
                                           ->setSize(3));

        return $this;
    }
}
