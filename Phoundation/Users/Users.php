<?php

/**
 * Class Users
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Users
{

    /*
     *
     */
    function name($user = null, $key_prefix = '', $default = null)
    {
        try {
            if ($user) {
                if ($key_prefix) {
                    $key_prefix = Strings::endsWith($key_prefix, '_');
                }

                if (is_scalar($user)) {
                    if (!is_numeric($user)) {
                        /*
                         * String, assume its a username
                         */
                        return $user;
                    }

                    /*
                     * This is not a user assoc array, but a user ID.
                     * Fetch user data from DB, then treat it as an array
                     */
                    if (!$user = sql_get('SELECT `nickname`, `name`, `username`, `email` FROM `users` WHERE `id` = :id', array(':id' => $user))) {
                        throw new OutOfBoundsException('name(): Specified user id ":id" does not exist', array(':id' => Strings::Log($user)), 'not-exists');
                    }
                }

                if (!is_array($user)) {
                    throw new OutOfBoundsException(tr('name(): Invalid data specified, please specify either user id, name, or an array containing username, email and or id'), 'invalid');
                }

                $user = not_empty(isset_get($user[$key_prefix . 'nickname']), isset_get($user[$key_prefix . 'name']), isset_get($user[$key_prefix . 'username']), isset_get($user[$key_prefix . 'email']));
                $user = trim($user);

                if ($user) {
                    return $user;
                }
            }

            if ($default === null) {
                $default = tr('Guest');
            }

            /*
             * No user data found, assume guest user.
             */
            return $default;

        } catch (Exception $e) {
            throw new OutOfBoundsException(tr('name(): Failed'), $e);
        }
    }




}