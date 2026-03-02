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

use Phoundation\Accounts\Users\Sessions\Exception\SessionException;
use Phoundation\Accounts\Users\Sessions\Interfaces\UserSessionInterface;
use Phoundation\Accounts\Users\User;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Exception\DataEntryNoIdentifierSpecifiedException;
use Phoundation\Data\DataEntries\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryCode;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryStringDomain;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryStringRemoteIp;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryStringRemoteIpReal;
use Phoundation\Data\Enums\EnumLoadParameters;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Date\PhoDateTime;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Utils\Strings;


class UserSession extends DataEntry implements UserSessionInterface
{
    use TraitDataEntryCode;
    use TraitDataEntryStringDomain;
    use TraitDataEntryStringRemoteIp;
    use TraitDataEntryStringRemoteIpReal;


    /**
     * UserSession class constructor
     *
     * @param IdentifierInterface|false|array|int|string|null $identifier
     * @param EnumLoadParameters|null                         $on_null_identifier
     * @param EnumLoadParameters|null                         $on_not_exists
     */
    public function __construct(IdentifierInterface|false|array|int|string|null $identifier = false, ?EnumLoadParameters $on_null_identifier = null, ?EnumLoadParameters $on_not_exists = null) {
        parent::__construct($identifier, $on_null_identifier, $on_not_exists);
        $this->setAllowModify(false);
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
        return 'code';
    }


    /**
     * Returns the session "close" datetime string or NULL if the session is still open
     *
     * @return string|null
     */
    public function getOpened(): ?string
    {
        return $this->getTypesafe('string', 'opened');
    }


    /**
     * Sets the session close datetime string or NULL if the session is still open
     *
     * @param PhoDateTimeInterface|string|null $close The session close date time
     *
     * @return static
     */
    public function setOpened(PhoDateTimeInterface|string|null $close = null): static
    {
        return $this->set(PhoDateTime::newOrNull($close)?->format('mysql'), 'opened');
    }


    /**
     * Returns the session close datetime object
     *
     * @return PhoDateTimeInterface|null
     */
    public function getOpenedObject(): ?PhoDateTimeInterface
    {
        return PhoDateTime::newOrNull($this->getOpened());
    }


    /**
     * Returns the session "close" datetime string or NULL if the session is still open
     *
     * @return string|null
     */
    public function getClosed(): ?string
    {
        return $this->getTypesafe('string', 'closed');
    }


    /**
     * Sets the session close datetime string or NULL if the session is still open
     *
     * @param PhoDateTimeInterface|string|null $close The session close date time
     *
     * @return static
     */
    public function setClosed(PhoDateTimeInterface|string|null $close = null): static
    {
        return $this->set(PhoDateTime::newOrNull($close)?->format('mysql'), 'closed');
    }


    /**
     * Returns the session close datetime object
     *
     * @return PhoDateTimeInterface|null
     */
    public function getClosedObject(): ?PhoDateTimeInterface
    {
        return PhoDateTime::newOrNull($this->getClosed());
    }


    /**
     * Closes this session
     *
     * This method will set the status of the UserSession entry to "closed" and set the "closed" value to "now" datetime
     *
     * @return static
     */
    public function close(): static
    {
        return $this->setClosed(PhoDateTime::new())->save()->setStatus('closed');
    }


    /**
     * Deletes this session
     *
     * @param string|null $comments
     * @param bool        $auto_save
     *
     * @return static
     */
    public function delete(?string $comments = null, bool $auto_save = true): static
    {
        $handler = ini_get('session.save_handler');

        switch ($handler) {
            case 'memcached':
                // Remove the session from memcached
                mc('sessions')->delete($this->getCode());
                break;

            case 'files':
                // Remove the session from files
                PhoFile::new(ini_get('session.save_path') . $this->getCode(), PhoRestrictions::newWritableObject(dirname(ini_get('session.save_path'))))
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
     * Loads the data for this DataEntry object matching the specified identifier that MUST exist in the database
     *
     * This method also accepts DataEntry objects of the same class, in which case it will simply return the specified object, as long as it exists in the
     * database.
     *
     * If the DataEntry does not exist in the database, then this method will check if perhaps it exists as a configuration entry. This requires
     * DataEntry::$config_path to be set. DataEntries from configuration will be in readonly mode automatically as they cannot be stored in the database.
     *
     * DataEntries from the database will also have their status checked. If the status is "deleted", then a DataEntryDeletedException will be thrown
     *
     * @note The test to see if a DataEntry object exists in the database can be either DataEntry::isNew() or DataEntry::getId(), which should return a valid
     *       database id
     *
     * @param IdentifierInterface|array|string|int|null $identifier              Identifier for the DataEntry object to load. Can be specified with a
     *                                                                           [column => value] array, though also accepts an integer value which will
     *                                                                           convert to [id_column => integer_value] or a string value which will convert to
     *                                                                           [unique_column => string_value]]
     *
     * @param EnumLoadParameters|null $on_null_identifier                        Specifies how this load method will handle the specified identifier being NULL.
     *                                                                           Options are: EnumLoadParameters::exception: Throws a
     *                                                                           DataEntryNoIdentifierSpecifiedException EnumLoadParameters::null: Will return
     *                                                                           NULL EnumLoadParameters::this: Will return the object as-is, without loading
     *                                                                           anything).
     *
     *                                                                           Defaults to EnumLoadParameters::exception
     *
     * @param EnumLoadParameters|null $on_not_exists                             Specifies how this load method will handle the specified identifier not
     *                                                                           existing in the database. Options are: EnumLoadParameters::exception: Throws a
     *                                                                           DataEntryNotExistsException. EnumLoadParameters::null: Returns NULL
     *                                                                           EnumLoadParameters::this Returns this, the object as-is, without loading
     *                                                                           anything.
     *
     *                                                                           Defaults to EnumLoadParameters::exception
     *
     * @return static|null
     *
     * @throws DataEntryNoIdentifierSpecifiedException Thrown when the specified identifier is empty and $on_null_identifier is set to
     *                                                 EnumLoadParameters::exception
     * @throws DataEntryNotExistsException             Thrown when the specified identifier does not exist and $on_not_exists is set to
     *                                                 EnumLoadParameters::exception
     */
    public function load(IdentifierInterface|int|array|string|null $identifier = null, ?EnumLoadParameters $on_null_identifier = null, ?EnumLoadParameters $on_not_exists = null): ?static
    {
        parent::load($identifier, $on_null_identifier, $on_not_exists);

        $handler = $handler ?? ini_get('session.save_handler');
        $data    = match ($handler) {
            'memcached' => mc('sessions')->get($this->getCode()),
            'files'     => PhoFile::new(Strings::slash(ini_get('session.save_path')) . 'sess_' . $this->getCode(), PhoRestrictions::newWritableObject(dirname(ini_get('session.save_path'))))->getContentsAsString(),
            ''          => throw new SessionException(tr('No session save handler configured')),
            default     => throw new SessionException(tr('Unknown or unsupported session save handler ":handler" encountered', [
                ':handler' => $handler,
            ])),
        };

        return $this->addData($data);
    }


    /**
     * Will save the data from this data entry to the database
     *
     * @param bool        $force           [false] If true, will force saving, even if the DataEntry object has not been modified
     * @param bool        $skip_validation [false] If true, will skip validation even if it is required
     * @param string|null $comments        [null]  If specified, will add these comments to the meta history
     *
     * @return static
     */
    public function save(bool $force = false, bool $skip_validation = false, ?string $comments = null): static
    {
        $data    = static::serialize(array_get_safe($this->source, 'data', []));
        $handler = $handler ?? ini_get('session.save_handler');

        match ($handler) {
            'memcached' => mc('sessions')->set($data, $this->getCode()),
            'files'     => PhoFile::new(Strings::slash(ini_get('session.save_path')) . 'sess_' . $this->getCode(), PhoRestrictions::newWritableObject(dirname(ini_get('session.save_path'))))->putContents($data),
            ''          => throw new SessionException(tr('No session save handler configured')),
            default     => throw new SessionException(tr('Unknown or unsupported session save handler ":handler" encountered', [
                ':handler' => $handler,
            ])),
        };

        return parent::save($force, $skip_validation, $comments);
    }


    /**
     * Registers a new opened session in the accounts_user_sessions table and returns a UserSession object for it
     *
     * @param string $code           The unique session code
     * @param int    $users_id       The database id for the user that owns the session
     * @param string $domain         The domain to which the session is locked (probably, later we may have multi domain sessions?)
     * @param string $remote_ip      The remote IP address where the request came from
     * @param string $remote_ip_real The "real" remote IP address where the request came from (if detected so)
     *
     * @return static
     * @todo Check multi domain sessions support
     */
    public static function newOpen(string $code, int $users_id, string $domain, string $remote_ip, string $remote_ip_real): static
    {
        return (new static())->setCode($code)
                             ->setCreatedBy($users_id)
                             ->setDomain($domain)
                             ->setRemoteIp($remote_ip)
                             ->setRemoteIpReal($remote_ip_real)
                             ->save();
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
     * @param string $source The source string that must be unserialized
     *
     * @return array
     */
    protected static function unserializePhp(string $source): array
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
     * @param string $source The source string that must be unserialized
     *
     * @return array
     */
    protected static function unserializePhpBinary(string $source): array
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
     * Returns if this UserSession is an active session.
     *
     * @return bool
     */
     public function isActive(): bool
     {
         $session = mc('sessions')->get($this->getIdentifier());

         if ($session) {
            if (array_get_safe($session, 'closed')) {
                return false;
            }

            return true;
         }

         return false;
     }


    /**
     * Returns if the specified code is an active session.
     *
     * @param string $code The session identifier string to test
     *
     * @return bool
     */
     public static function isActiveSession(string $code): bool
     {
         if (empty($code)) {
             throw new OutOfBoundsException(ts('Cannot check if session identifier is active, no (or empty) identifier specified'));
         }

         $session = mc('sessions')->get($code);

         if ($session) {
            if (array_get_safe($session, 'closed')) {
                return false;
            }

            return true;
         }

         return false;
     }


    /**
     * Adds data to the specified sessions list
     *
     * @param array|string|null $session
     *
     * @return static
     */
    public function addData(array|string|null $session): static
    {
        if ($session) {
            if (is_string($session)) {
                // Decode the  session first
                $session = $this->unserialize($session);
            }
showdie($session);
            // Ensure the session exists!
            $this->source['data']          = UserSession::new($session['code'], false)->getSource();
            $this->source['user']          = User::new()->loadNull(array_get_safe(array_get_safe($session['data'], 'user'), 'id'));
            $this->source['last_activity'] = array_get_safe($session['data'], 'last_activity') ?? array_get_safe($session, 'closed') ?? array_get_safe($session, 'opened');
            $this->source['last_activity'] = PhoDateTime::new($this->source['last_activity']);
            $this->source['opened']        = PhoDateTime::new($session['opened']);
            $this->source['closed']        = PhoDateTime::new($session['closed']);
        }

        return $this;
    }


    /**
     * Copies the data for this session to a session with the specified code
     *
     * @param string $code
     *
     * @return $this
     */
    public function copyTo(string $code): static
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
        $_definitions->add(DefinitionFactory::newCode()
                                            ->setLabel(tr('Code'))
                                            ->setDisabled(true)
                                            ->setReadonly(true)
                                            ->setSize(6))

                     ->add(DefinitionFactory::newDateTime('opened')
                                            ->setReadonly(true)
                                            ->setOptional(true)
                                            ->setMaxLength(4)
                                            ->setLabel(tr('opened'))
                                            ->setSize(3))

                     ->add(DefinitionFactory::newDateTime('closed')
                                            ->setReadonly(true)
                                             ->setOptional(true)
                                            ->setMaxLength(4)
                                            ->setLabel(tr('Closed'))
                                            ->setSize(3))

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
                                            ->setSize(4));

        return $this;
    }
}
