<?php

namespace Phoundation\Core;

use Phoundation\Accounts\Users\GuestUser;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Exception\ConfigException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Web\WebPage;



/**
 * Class Session
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Session
{
    /**
     * The current user for this session
     *
     * @var User|null $user
     */
    protected static ?User $user = null;

    /**
     * Tracks if the session has startup or not
     *
     * @var bool $startup
     */
    protected static bool $startup = false;

    /**
     * Language for this session
     *
     * @var string|null $language
     */
    protected static ?string $language = null;

    /**
     * Domain for this session
     *
     * @var string|null $domain
     */
    protected static ?string $domain = null;



    /**
     * @return void
     */
    public static function startup(): void
    {
        if (self::$startup) {
            return;
        }

        self::setLanguage();
    }



    /**
     * @return User
     */
    public static function getUser(): User
    {
        if (self::$user === null) {
            // There is no user, this is a guest session
            return new GuestUser();
        }

        return self::$user;
    }



    /**
     * Authenticate a user with the specified password
     *
     * @param string $user
     * @param string $password
     * @return User
     */
    public static function signIn(string $user, string $password): User
    {
        self::$user = User::authenticate($user, $password);
        self::clear();
        self::start();

//        $_SESSION['users_id']
        return self::$user;
    }



    /**
     * Returns the domain for this session
     *
     * @return string
     */
    public static function getDomain(): string
    {
        return self::$domain;
    }



    /**
     * Sets the domain for this session
     *
     * @return string
     */
    protected static function setDomain(): string
    {
        // Check what domains are accepted by the client (in order of importance) and see if we support any of those
        $supported_domains = Config::get('web.domains');

        if (array_key_exists($_SERVER['HTTP_HOST'], $supported_domains)) {
            self::$domain = $_SERVER['HTTP_HOST'];
            return self::$domain;
        }

        // No supported domain found, redirect to the primary domain
        WebPage::redirect(true);
    }



    /**
     * Returns the language for this session
     *
     * @return string
     */
    public static function getLanguage(): string
    {
        return self::$language;
    }



    /**
     * Returns the language for this session
     *
     * @return string
     */
    protected static function setLanguage(): string
    {
        // Check what languages are accepted by the client (in order of importance) and see if we support any of those
        $supported_languages = Arrays::force(Config::get('languages.supported', []));
        $requested_languages = WebPage::acceptsLanguages();

        foreach ($requested_languages as $requested_language) {
            if (in_array($requested_language['language'], $supported_languages)) {
                self::$language = $requested_language['language'];
                return self::$language;
            }
        }

        // No supported language found, set the default language
        return Config::get('languages.default', 'en');
    }



//    /*
//     * Read value for specified key from $_SESSION[cache][$key]
//     *
//     * If $_SESSION[cache][$key] does not exist, then execute the callback and
//     * store the resulting value in $_SESSION[cache][$key]
//     */
//    function session_cache($key, $callback)
//    {
//        try {
//            if (empty($_SESSION)) {
//                return null;
//            }
//
//            if (!isset($_SESSION['cache'])) {
//                $_SESSION['cache'] = array();
//            }
//
//            if (!isset($_SESSION['cache'][$key])) {
//                $_SESSION['cache'][$key] = $callback();
//            }
//
//            return $_SESSION['cache'][$key];
//
//        } catch (Exception $e) {
//            throw new OutOfBoundsException(tr('session_cache(): Failed'), $e);
//        }
//    }



    /**
     * Checks if an extended session is available for this user
     *
     * @return bool
     */
    public static function checkExtended(): bool
    {
        if (empty($_CONFIG['sessions']['extended']['enabled'])) {
            return false;
        }

        if (isset($_COOKIE['extsession']) and !isset($_SESSION['user'])) {
            // Pull  extsession data
            $ext = sql_get('SELECT `users_id` 
                            FROM `extended_sessions` WHERE `session_key` = ":session_key" AND DATE(`addedon`) < DATE(NOW());', array(':session_key' => cfm($_COOKIE['extsession'])));

            if ($ext['users_id']) {
                $user = sql_get('SELECT * FROM `accounts_users` WHERE `accounts_users`.`id` = :id', array(':id' => cfi($ext['users_id'])));

                if ($user['id']) {
                    // Auto sign in user
                    self::$user = Users::signin($user, true);
                    return true;

                } else {
                    // Remove cookie
                    setcookie('extsession', 'stub', 1);
                }

            } else {
                // Remove cookie
                setcookie('extsession', 'stub', 1);
            }
        }

        return false;
    }



    /**
     * Clear the current session
     *
     * @return void
     */
    public static function clear(): void
    {
        $_SESSION = [];
    }



    /**
     * Start a PHP session
     *
     * @return bool
     */
    public static function start(): bool
    {
        if (!Config::get('web.sessions.enabled', true)) {
            return false;
        }

        switch (Config::get('web.sessions.handler', 'files')) {
            case 'files':
                $path = Path::new(Config::get('web.sessions.path', PATH_DATA), Restrictions::new([PATH_DATA . 'sessions/', '/var/lib/php/sessions'], true, 'system/sessions'))->ensure();

                session_save_path($path);
                session_start();
                break;

            case 'memcached':
                // no-break
            case 'redis':
                // no-break
            case 'mongo':
                // no-break
            case 'mysql':
                throw new UnderConstructionException();
                break;

            default:
                throw new ConfigException(tr('Unknown session handler ":handler" specified in configuration path "web.sessions.handler"', [
                    ':handler' => Config::get('web.sessions.handler', 'files')
                ]));
        }

        return true;
    }
}