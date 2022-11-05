<?php
/*
 * Chat library
 *
 * This is a library to interface with the boom chat plugin
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package chat
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package chat
 * @version 2.5.70: Added function and documentation
 *
 * @return void
 */
function chat_library_init() {
    try {
        load_config('chat');

    }catch(Exception $e) {
        throw new CoreException('chat_library_init(): Failed', $e);
    }
}




/*
 * Insert the specified user to the boom chat database
 *
 * Boom chat database structure:
 * | user_id          | int(11)      | NO   | PRI | NULL                    | auto_increment |
 * | user_name        | varchar(16)  | NO   |     | NULL                    |                |
 * | user_password    | varchar(60)  | NO   |     | NULL                    |                |
 * | user_email       | varchar(80)  | NO   |     | NULL                    |                |
 * | user_ip          | varchar(30)  | NO   |     | NULL                    |                |
 * | user_join        | int(12)      | NO   |     | NULL                    |                |
 * | last_action      | int(11)      | NO   |     | NULL                    |                |
 * | last_message     | varchar(500) | NO   |     | NULL                    |                |
 * | user_status      | int(1)       | NO   |     | 1                       |                |
 * | user_action      | int(1)       | NO   |     | 1                       |                |
 * | user_color       | varchar(10)  | NO   |     | user                    |                |
 * | user_rank        | int(1)       | NO   |     | 1                       |                |
 * | user_access      | int(1)       | NO   |     | 4                       |                |
 * | user_roomid      | int(6)       | NO   |     | 1                       |                |
 * | user_kick        | text         | NO   |     | NULL                    |                |
 * | user_mute        | varchar(16)  | NO   |     | NULL                    |                |
 * | mute_time        | int(12)      | NO   |     | NULL                    |                |
 * | user_flood       | int(1)       | NO   |     | NULL                    |                |
 * | user_theme       | varchar(16)  | NO   |     | Default                 |                |
 * | user_sex         | int(1)       | NO   |     | 0                       |                |
 * | user_age         | int(2)       | NO   |     | 0                       |                |
 * | user_description | text         | NO   |     | NULL                    |                |
 * | user_avatar      | varchar(50)  | NO   |     | default_avatar.png      |                |
 * | alt_name         | varchar(100) | NO   |     | NULL                    |                |
 * | upload_count     | int(11)      | NO   |     | 0                       |                |
 * | upload_access    | int(11)      | NO   |     | 1                       |                |
 * | user_sound       | int(1)       | NO   |     | 1                       |                |
 * | temp_pass        | varchar(40)  | NO   |     | 0                       |                |
 * | temp_time        | int(11)      | NO   |     | 0                       |                |
 * | user_tumb        | varchar(100) | NO   |     | default_avatar_tumb.png |                |
 * | guest            | int(1)       | NO   |     | 0                       |                |
 * | verified         | int(1)       | NO   |     | 1                       |                |
 * | valid_key        | varchar(64)  | NO   |     | NULL                    |                |
 * | user_ignore      | text         | NO   |     | NULL                    |                |
 * | first_check      | int(11)      | NO   |     | 0                       |                |
 * | join_chat        | int(11)      | NO   |     | 0                       |                |
 * | email_count      | int(1)       | NO   |     | 0                       |                |
 * | user_friends     | text         | NO   |     | NULL                    |                |
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package templates
 * @see chat_validate_user()
 * @see chat_update_user()
 * @table: `template`
 * @note: This is a note
 * @version 2.5.38: Added function and documentation
 * @example
 * code
 * $result = templates_insert(array('foo' => 'bar',
 *                                 'foo' => 'bar',
 *                                 'foo' => 'bar'));
 * showdie($result);
 * /code
 *
 * @param params $template The template to be inserted
 * @param string $template[foo]
 * @param string $template[bar]
 * @return params The specified template, validated and sanitized
 */
function chat_add_user($user) {
    try {
        $user = chat_validate_user($user);

        sql_query('INSERT INTO `users` (`user_id`, `user_name`, `user_email`, `user_password`, `alt_name`, `user_ip`, `user_join`)
                   VALUES              (:user_id , :user_name , :user_email , :user_password , :alt_name , :user_ip , NOW()      )',

                   array(':user_id'       => $user['id'],
                         ':user_name'     => $user['user_name'],
                         ':alt_name'      => $user['alt_name'],
                         ':user_email'    => $user['user_email'],
                         'user_ip'        => isset_get($_SERVER['REMOTE_ADDR'], '127.0.0.1'),
                         ':user_password' => unique_code()),

                   'chat');

        return sql_insert_id('chat');

    }catch(Exception $e) {
        throw new CoreException(tr('chat_add_user(): Failed'), $e);
    }
}



/*
 * Update the specified user in the boom chat database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package templates
 * @see chat_validate_user()
 * @see chat_add_user()
 * @table: `template`
 * @note: This is a note
 * @version 2.5.38: Added function and documentation
 * @example Update a template in the database
 * code
 * $result = templates_update(array('id'  => 42,
 *                                  'foo' => 'bar',
 *                                  'foo' => 'bar',
 *                                  'foo' => 'bar'));
 * showdie($result);
 * /code
 *
 * @param params $params The template to be updated
 * @param string $params[foo]
 * @param string $params[bar]
 * @return boolean True if the user was updated, false if not. If not updated, this might be because no data has changed
 */
function chat_update_user($user) {
    static $fail = false;

    try {
        $user = chat_validate_user($user);

        $r    = sql_query('UPDATE `users`

                           SET    `user_name`  = :user_name,
                                  `user_email` = :user_email,
                                  `alt_name`   = :alt_name,
                                 `user_rank`  = :user_rank

                           WHERE  `user_id`    = :user_id',

                           array(':user_id'    => $user['id'],
                                 ':user_name'  => $user['user_name'],
                                 ':alt_name'   => $user['alt_name'],
                                 ':user_email' => $user['user_email'],
                                 ':user_rank'  => $user['rank']),

                            'chat');

        if (!$r->rowCount()) {
            /*
             * This means either no data has been changed, or the specified ID doesn't exist.
             * The former is okay, the latter should never happen.
             */
            if (!sql_get('SELECT `user_id` FROM `users` WHERE `user_id` = :user_id', 'user_id', array(':user_id' => $user['id']), 'chat')) {
                if ($fail) {
                    /*
                     * > first failure, notify of failure
                     */
                    throw new CoreException(tr('chat_update_user(): Specified user ":user" does not exist', array(':user' => name($user))), 'not-exists');

                } else {
                    /*
                     * First failure, user doesnt exist. Try adding it and try
                     * update again
                     */
                    $fail = true;
                    chat_add_user($user);
                    return chat_update_user($user);
                }
            }
        }

    }catch(Exception $e) {
        throw new CoreException(tr('chat_update_user(): Failed'), $e);
    }
}



/*
 * Validate the specified user
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @user Function reference
 * @package users
 *
 * @param array $user The user to validate
 * @return array The validated and cleaned $user array
 */
function chat_validate_user($user) {
    try {
        load_libs('validate,seo');

        $v = new ValidateForm($user, 'name,email,nickname');

        /*
         * Set boom chat data
         */
        $user['user_name']  = name($user['name']);
        $user['user_email'] = $user['email'];
        $user['alt_name']   = $user['nickname'];

        /*
         * Validate alt_name
         */
        if (empty($user['alt_name'])) {
            $user['alt_name'] = '';

        } else {
            $v->hasMinChars($user['alt_name'], 2, tr('Please ensure the alt name has at least 2 characters'));
            $v->hasMaxChars($user['alt_name'], 128, tr('Please ensure the alt name has less than 128 characters'));

            $user['alt_name'] = str_clean($user['alt_name']);
        }

        /*
         * Validate user_name
         */
        if (empty($user['user_name'])) {
            $user['user_name'] = '';

        } else {
            $v->hasMinChars($user['user_name'], 2, tr('Please ensure the user user_name has at least 2 characters'));
            $v->hasMaxChars($user['user_name'], 128, tr('Please ensure the user user_name has less than 128 characters'));

            $user['user_name'] = str_clean($user['user_name']);
        }

        /*
         * Validate user_email
         */
        if (empty($user['user_email'])) {
            $user['user_email'] = '';

        } else {
            $v->isEmail($user['user_email'], tr('Please specify a valid email'));
        }

        /*
         * All valid?
         */
        $v->isValid();

        /*
         * Set rank
         */
        if (has_rights('admin', $user)) {
            $user['rank'] = 5;

        } elseif (has_rights('moderator', $user)) {
            $user['rank'] = 3;

        } else {
            $user['rank'] = 1;
        }

        return $user;

    }catch(Exception $e) {
        throw new CoreException('chat_validate_user(): Failed', $e);
    }
}



/*
 *
 */
function chat_get_user($user) {
    try {
        return sql_get('SELECT `user_name`, `user_password` FROM `users` WHERE `user_id` = :user_id', array(':user_id' => $user['id']), null, 'chat');

    }catch(Exception $e) {
        throw new CoreException(tr('chat_get_user(): Failed'), $e);
    }
}



/*
 *
 */
function chat_start($user) {
    global $_CONFIG;

    try {
        if (!$user) {
            /*
             * This user doesnt exist yet
             */
            throw new CoreException(tr('chat_start(): Specified user ":user" doesn\'t exist in the chat database', array(':user' => $user['id'])), 'not-exists');
        }

        setcookie('username', $user['user_name']    , time() + 86400, '/', ''.Strings::startsWith($_SESSION['domain'], '.'));
        setcookie('password', $user['user_password'], time() + 86400, '/', ''.Strings::startsWith($_SESSION['domain'], '.'));

        return '<iframe src="'.PROTOCOL.'chat.'.$_CONFIG['domain'].'" frameborder="0" class="chat"></iframe>';

    }catch(Exception $e) {
        throw new CoreException(tr('chat_start(): Failed'), $e);
    }
}



/*
 *
 */
function chat_end($userid) {
    try {
        sql_query('UPDATE `users`

                   SET    `user_status` = :user_status

                   WHERE  `user_id`     = :userid',

                   array(':user_status' => 3,
                         ':userid'      => $userid),

                   null, 'chat');

    }catch(Exception $e) {
        throw new CoreException(tr('chat_end(): Failed'), $e);
    }
}



/*
 *
 */
function chat_update_rank($user) {
    try {
        if (has_rights('god', $user)) {
            $rank = 5;

        } elseif (has_rights('moderator', $user)) {
            $rank = 3;

        } else {
            $rank = 1;
        }

        $r = sql_query('UPDATE `users`

                        SET    `user_rank` = :user_rank

                        WHERE  `user_id`   = :user_id',

                        array(':user_id'   => $user['id'],
                              ':user_rank' => $rank), null, 'chat');

        if (!$r->rowCount()) {
            /*
             * This means either no data has been changed, or the specified ID doesn't exist.
             * The former is okay, the latter should never happen.
             */
            if (!sql_get('SELECT `user_id` FROM `users` WHERE `user_id` = :user_id', 'user_id', array(':user_id' => $user['id']))) {
                load_libs('user');
                throw new CoreException(tr('chat_update_rank(): Specified user ":user" does not exist', array(':user' => name($user))), 'not-exists');
            }
        }

    }catch(Exception $e) {
        throw new CoreException(tr('chat_update_rank(): Failed'), $e);
    }
}



/*
 * Ensure that all users in the igotit database exist in the chat database.
 * Those that exist in the chat database and do not exist in the igotit database should be removed
 * If users in igotit have status, then ensure that this status is reflected in chat as well
 */
function chat_sync_users($user, $log_console = false) {
    try {
        /*
         * List all users form igotit site, and ensure they are in the chat
         */
        $r = sql_query('SELECT `id`, `name`, `user_name`, `email`, `status` FROM `users`');

        $s = sql_prepare('SELECT `user_id`, `user_name`, `user_email`, `user_status` FROM `users` WHERE `user_id` = :user_id');

        while ($user = sql_fetch($r)) {
            try {
                if (!$chat_user = $s->execute(array(':user_id' => $user['id']))) {
                    chat_add_user($user);

                } else {
                    chat_update_user($user);
                }

            }catch(Exception $e) {
                throw new CoreException(tr('chat_sync_users(): Failed to process user ":user"', array(':user' => name($user))), $e);
            }
        }

    }catch(Exception $e) {
        throw new CoreException(tr('chat_sync_users(): Failed'), $e);
    }
}



/*
 * Update user avatar
 */
function chat_update_avatar($user, $avatar) {
    try {
        $r = sql_query('UPDATE `users`

                        SET    `user_avatar` = :user_avatar,
                               `avatar`      = :avatar

                        WHERE  `user_id`     = :user_id',

                        array(':user_id'     => $user['id'],
                              ':avatar'      => $avatar,
                              ':user_avatar' => $avatar), 'chat');

    }catch(Exception $e) {
        throw new CoreException(tr('chat_update_avatar(): Failed'), $e);
    }
}
?>
