<?php

namespace Phoundation\Core;

use Phoundation\Users\User;

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
    protected ?User $user = null;



    /**
     * @return User
     */
    public static function currentUser(): User
    {
        if ($this->user === null) {
            // There is no user, this is a guest session
            return new User();
        }

        return $this->user;
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
}