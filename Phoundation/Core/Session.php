<?php

namespace Phoundation\Core;

use Phoundation\Users\User;
use Phoundation\Users\Users;

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
     * @return User
     */
    public static function currentUser(): User
    {
        if (self::$user === null) {
            // There is no user, this is a guest session
            return new User();
        }

        return self::$user;
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
     * Authenticate a user with the specified password
     *
     * @param string $user
     * @param string $password
     * @return User
     */
    public static function signIn(string $user, string $password): User
    {
        self::$user = Users::authenticate($user, $password);
    }



    /**
     * Checks if an extended session is available for this user
     *
     * @return bool
     */
    public function checkExtended(): bool
    {
        if (empty($_CONFIG['sessions']['extended']['enabled'])) {
            return false;
        }

        if (isset($_COOKIE['extsession']) and !isset($_SESSION['user'])) {
            // Pull  extsession data
            $ext = sql_get('SELECT `users_id` 
                            FROM `extended_sessions` WHERE `session_key` = ":session_key" AND DATE(`addedon`) < DATE(NOW());', array(':session_key' => cfm($_COOKIE['extsession'])));

            if ($ext['users_id']) {
                $user = sql_get('SELECT * FROM `users` WHERE `users`.`id` = :id', array(':id' => cfi($ext['users_id'])));

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
}