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
use Phoundation\Data\Traits\TraitDataSourceArray;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Utils\Strings;

class Session
{
    use TraitDataSourceArray;

    /**
     * Tracks the session identifier
     *
     * @var string|null $identifier
     */
    protected ?string $identifier;

    /**
     * Contains the session data string
     *
     * @var string|null $string
     */
    protected ?string $string;


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

        $string = static::load($identifier);

        if (empty($string)) {
            if ($exception) {
                throw new SessionNotExistsException(tr('The specified session ":session" does not exist', [
                    ':session' => $identifier
                ]));
            }

        } else {
            $this->identifier = $identifier;
            $this->string     = $string;
            $this->source     = static::unserialize($string);
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
     * Returns the session identifier
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
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
     * @param string $session
     *
     * @return bool
     */
    public static function exists(string $session): bool
    {
        return (bool) static::load($session);
    }


    /**
     * Returns the session data for the specified session, or NULL if it does not exist
     *
     * @param string      $session
     * @param string|null $handler
     *
     * @return string|null
     */
    public static function load(string $session, ?string $handler = null): ?string
    {
        $handler = $handler ?? ini_get('session.save_handler');

        return match ($handler) {
            'memcached' => mc('sessions')->get($session),
            'files'     => PhoFile::new(Strings::slash(ini_get('session.save_path')) . 'sess_' . $session, PhoRestrictions::newWritableObject(dirname(ini_get('session.save_path'))))->getContentsAsString(),
            default     => throw new OutOfBoundsException(tr('Unknown or unsupported session save handler ":handler" encountered', [
                ':handler' => $handler,
            ])),
        };
    }


    /**
     * Unserializes the specified PHP session dta
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
}
