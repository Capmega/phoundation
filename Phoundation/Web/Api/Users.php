<?php

declare(strict_types=1);

namespace Phoundation\Web\Api;

use Phoundation\Accounts\Users\User;

/**
 * Class Users
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
class Users extends \Phoundation\Accounts\Users\Users
{
    /**
     * Find and authenticate the user by the specified API key
     *
     * @param string $key
     *
     * @return User|null
     */
    public static function getUserFromApiKey(string $key): ?User
    {
        $users_id = sql()->getInteger('SELECT `api_keys`.`users_id` 
                                             FROM   `api_keys`
                                             WHERE  `api_keys`.`key` = :key', [
            ':key' => $key,
        ]);
        if ($users_id) {
            return User::load($users_id, 'id');
        }

        return null;
    }
}