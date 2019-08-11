<?php
/*
 * Users library
 *
 * This library contains user functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the ssh library configuration
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package user
 *
 * @return void
 */
function user_library_init(){
    try{
        load_config('user');

    }catch(Exception $e){
        throw new BException('user_library_init(): Failed', $e);
    }
}



 /*
  * Get user data with MC write through cache
  */
function user_data($id) {
    global $_CONFIG;

    try{
        load_libs('memcached');

        $key  = 'USER-'.$id;
        $user = memcached_get($key);

        if(empty($user)) {
            $user = sql_get('SELECT * FROM users WHERE id = :id', array(':id' => cfi($id)));
            memcached_put($key, $user);

        } else {
            return $user;
        }

    }catch(Exception $e){
        throw new BException('user_data(): Failed', $e);
    }
}



/*
 * Make sure some avatar is being displayed
 */
function user_avatar($avatar, $type = null) {
    global $_CONFIG;

    try{
        if(!$type){
            $type = '';
        }

        if(empty($avatar)) {
            return 'img/default-user.png';
        }

        return $avatar.'-'.$type.'.jpg';

    }catch(Exception $e){
        throw new BException('user_avatar(): Failed', $e);
    }
}



/*
 * Update the user avatar in the database
 */
function user_update_avatar($user, $avatar) {
    global $_CONFIG;

    try{
        if(!is_numeric($user)){
            if(!is_array($user) or empty($user['id'])){
                throw new BException('user_update_avatar(): Invalid user specified');
            }

            $user = $user['id'];
        }

        sql_query('UPDATE `users` SET `avatar` = :avatar WHERE `id` = :id', array(':avatar' => cfm($avatar), ':id' => $user));

        return $avatar;

    }catch(Exception $e){
        throw new BException('user_update_avatar(): Failed', $e);
    }
}



/*
 * Find an avatar for this user
 */
function user_find_avatar($user) {
    global $_CONFIG;

    try{
        if(!is_array($user)){
            if(!is_numeric($user)){
                throw new BException('user_find_avatar(): Invalid user specified');
            }

            $user = sql_get('SELECT * FROM `users` WHERE id = :id', array(':id' => cfi($user)));
        }

        /*
         * Try getting an avatar from Facebook, Google, Microsoft (or Gravatar maybe?)
         */
        if(!empty($user['fb_id'])){
            load_libs('facebook');
            return facebook_get_avatar($user);

        }elseif(!empty($user['gp_id'])){
            load_libs('google');
            return google_get_avatar($user);

        }elseif(!empty($user['ms_id'])){
            load_libs('microsoft');
            return microsoft_get_avatar($user);

// :TODO: Implement one day in the future
//        }elseif($_CONFIG['gravatar']){
//            load_libs('gravatar');
//            return gravatar_get_avatar($user);

        }else{
            return '';
        }


    }catch(Exception $e){
        throw new BException('user_find_avatar(): Failed', $e);
    }
}



/*
 * Remove the user from the specified groups
 */
function user_update_groups($user, $groups, $validate = false){
    try{
        if(!$validate){
            $users_id = $user;

        }else{
            /*
             * Validate user
             */
            if(!$user){
                throw new BException(tr('user_add_to_group(): No user specified'), 'not-specified');
            }

            if(is_numeric($user)){
                $users_id = sql_get('SELECT `id` FROM `users` WHERE `id` = :id', true, array(':id' => $user));

            }else{
                $users_id = sql_get('SELECT `id` FROM `users` WHERE (`username` = :username OR `email` = :email)', 'id', array(':username' => $user, ':email' => $user));

                if(!$users_id){
                    throw new BException(tr('user_add_to_group(): Specified user ":user" does not exist', array(':user' => $user)), 'not-exists');
                }
            }
        }

        sql_query('DELETE FROM `users_groups` WHERE `users_id` = :users_id', array(':users_id' => $users_id));

        if($groups){
            return user_add_to_group($user, $groups, false);
        }

        return 0;

    }catch(Exception $e){
        throw new BException('user_update_groups(): Failed', $e);
    }
}



/*
 * Load the groups for the specified user
 * Groups will always be returned
 * If no user is specified, current $_SESSION['user'] user will be assumed and groups will be loaded there
 */
function user_load_groups($user = null){
    try{
        if(!$user){
            if(empty($_SESSION['user']['id'])){
                return null;
            }

            if(empty($_SESSION['user']['groups'])){
                $_SESSION['user']['groups'] = sql_list('SELECT    `groups`.`seoname`,
                                                                  `groups`.`name`

                                                        FROM      `users_groups`

                                                        LEFT JOIN `groups`
                                                        ON        `groups`.`id` = `users_groups`.`groups_id`

                                                        WHERE     `users_groups`.`users_id` = :users_id

                                                        ORDER BY  `groups`.`name`', array(':users_id' => $_SESSION['user']['id']));
            }

            return $_SESSION['user']['groups'];
        }

        if(is_array($user)){
            if(empty($user['id'])){
                throw new BException(tr('user_load_groups(): Specified user array does not contain required "id" field'), 'invalid');
            }

            $user = $user['id'];
        }

        $groups = sql_list('SELECT    `groups`.`seoname`,
                                      `groups`.`name`

                            FROM      `users_groups`

                            LEFT JOIN `groups`
                            ON        `groups`.`id` = `users_groups`.`groups_id`

                            WHERE     `users_groups`.`users_id` = :users_id

                            ORDER BY  `groups`.`name`', array(':users_id' => $user));

        return $groups;

    }catch(Exception $e){
        throw new BException('user_load_groups(): Failed', $e);
    }
}



/*
 *
 */
function user_is_group_member($group_list, $user = null){
    try{
        if(empty($user['groups'])){
            $groups = user_load_groups($user);

        }else{
            $groups = &$user['groups'];
        }

        foreach(array_force($group_list) as $group){
            if(!in_array($group, $groups)){
                return false;
            }
        }

        return true;

    }catch(Exception $e){
        throw new BException('user_is_group_member(): Failed', $e);
    }
}



/*
 * Add the user to the specified groups
 */
function user_add_to_group($user, $groups, $validate = true){
    try{
        if(!$validate){
            $users_id = $user;

        }else{
            /*
             * Validate user
             */
            if(!$user){
                throw new BException(tr('user_add_to_group(): No user specified'), 'not-specified');
            }

            if(is_numeric($user)){
                $users_id = sql_get('SELECT `id` FROM `users` WHERE `id` = :id', 'id', array(':id' => $user));

            }else{
                $users_id = sql_get('SELECT `id` FROM `users` WHERE (`username` = :username OR `email` = :email)', 'id', array(':username' => $user, ':email' => $user));

                if(!$users_id){
                    throw new BException(tr('user_add_to_group(): Specified user ":user" does not exist', array(':user' => $user)), 'not-exists');
                }
            }
        }

        /*
         * Validate group
         */
        if(!$groups){
            throw new BException(tr('user_add_to_group(): No groups specified'), 'not-specified');
        }

        if(is_numeric($groups)){
            $groups_id = sql_get('SELECT `id` FROM `groups` WHERE `id` = :id', 'id', array(':id' => $groups));

        }else{
            if(is_string($groups) and strstr($groups, ',')){
                /*
                 * Groups specified as CSV list
                 */
                $groups = array_force($groups);
            }

            /*
             * Add user to multiple groups?
             */
            if(is_array($groups)){
                $count = 0;

                foreach($groups as $group){
                    if(!$group) continue;

                    if(user_add_to_group($user, $group, false)){
                        $count++;
                    }
                }

                return $count;
            }

            $groups_id = sql_get('SELECT `id` FROM `groups` WHERE `seoname` = :seoname', 'id', array(':seoname' => $groups));

            if(!$groups_id){
                throw new BException(tr('user_add_to_group(): Specified group ":group" does not exist', array(':group' => $groups)), 'not-exists');
            }
        }

        /*
         * User already member of specified group?
         */
        $exists = sql_get('SELECT `users_id` FROM `users_groups` WHERE `users_id` = :users_id AND `groups_id` = :groups_id', 'users_id', array(':users_id'  => $users_id, ':groups_id' => $groups_id));

        if($exists){
            return 0;
        }

        /*
         * Add user to the specified group
         */
        sql_query('INSERT INTO `users_groups` (`users_id`, `groups_id`)
                   VALUES                     (:users_id , :groups_id )',

                   array(':users_id'  => $users_id,
                         ':groups_id' => $groups_id));

        return 1;

    }catch(Exception $e){
        throw new BException('user_add_to_group(): Failed', $e);
    }
}



/*
 * Remove the user from the specified groups
 */
function user_remove_from_group($user, $groups, $validate = true){
    try{
        if(!$validate){
            $users_id = $user;

        }else{
            /*
             * Validate user
             */
            if(!$user){
                throw new BException(tr('user_remove_from_group(): No user specified'), 'not-specified');
            }

            if(is_numeric($user)){
                $users_id = sql_get('SELECT `id` FROM `users` WHERE `id` = :id', 'id', array(':id' => $user));

            }else{
                $users_id = sql_get('SELECT `id` FROM `users` WHERE (`username` = :username OR `email` = :email)', 'id', array(':username' => $user, ':email' => $user));

                if(!$users_id){
                    throw new BException(tr('user_remove_from_group(): Specified user ":user" does not exist', array(':user' => $user)), 'not-exists');
                }
            }
        }

        /*
         * Validate group
         */
        if(!$groups){
            throw new BException(tr('user_remove_from_group(): No groups specified'), 'not-specified');
        }

        if(is_numeric($groups)){
            $groups_id = sql_get('SELECT `id` FROM `groups` WHERE `id` = :id', 'id', array(':id' => $groups));

        }else{
            if(is_string($groups) and strstr($groups, ',')){
                /*
                 * Groups specified as CSV list
                 */
                $groups = array_force($groups);
            }

            /*
             * Add user to multiple groups?
             */
            if(is_array($groups)){
                $count = 0;

                foreach($groups as $group){
                    if(user_remove_from_group($user, $group, false)){
                        $count++;
                    }
                }

                return $count;
            }

            $groups_id = sql_get('SELECT `id` FROM `groups` WHERE `seoname` = :seoname', 'id', array(':seoname' => $groups));

            if(!$groups_id){
                throw new BException(tr('user_remove_from_group(): Specified group ":group" does not exist', array(':group' => $groups)), 'not-exists');
            }
        }

        /*
         * Delete user from specified group
         */
        $r = sql_query('DELETE FROM `users_groups` WHERE `users_id` = :users_id AND `groups_id` = :groups_id', 'users_id', array(':users_id'  => $users_id, ':groups_id' => $groups_id));

        return $r->rowCount();

    }catch(Exception $e){
        throw new BException('user_remove_from_group(): Failed', $e);
    }
}



/*
 * Authenticate the specified user with the specified password
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package user
 *
 * @param string $username
 * @param string $password
 * @param string $captcha
 * @param string $status
 * @return
 */
function user_authenticate($username, $password, $captcha = null, $status = null){
    global $_CONFIG;

    try{
        /*
         * Data validation and get user data
         */
        if(!is_scalar($username)){
            throw new BException('user_authenticate(): Specified username is not valid', 'invalid');
        }

        if($status){
            /*
             * If specified, status will be treated as a list or array of
             * allowed statuses. If NULL is still authorized, then it can be
             * specified either as null (in an array), or "null" (in a string
             * list, or array)
             */
            $in   = sql_in($status);
            $null = false;


            foreach($in as $key => $status_value){
                if(($status_value === 'null') or ($status_value === null)){
                    $null = true;
                    unset($in[$key]);
                    break;
                }
            }

            $where   = ' WHERE (`status`   IN ('.sql_in_columns($in).') '.($null ? ' OR `status` IS NULL' : '').')
                         AND   (`email`    = :email
                         OR     `username` = :username)';

            $execute[':email']    = $username;
            $execute[':username'] = $username;

        }elseif($status === null){
            $where = ' WHERE  `status`   IS NULL
                       AND   (`email`    = :email
                       OR     `username` = :username)';

            $execute = array(':email'    => $username,
                             ':username' => $username);

        }elseif($status === false){
            $where = ' WHERE (`email`    = :email
                       OR     `username` = :username)';

            $execute = array(':email'    => $username,
                             ':username' => $username);

        }else{
            throw new BException(tr('user_authenticate(): Unknown status ":status" specified', array(':status' => $status)), 'unknown');
        }

        $execute = array_merge($execute, isset_get($in, array()));

        $user = sql_get('SELECT *, `locked_until` - UTC_TIMESTAMP() AS `locked_left`

                         FROM   `users` '.$where, $execute);

        if(!$user){
            throw new BException(tr('user_authenticate(): Specified user account ":username" with status ":status" not found', array(':username' => $username, ':status' => $status)), 'not-exists');
        }



        /*
         * Check authentication failures and account locking
         */
        if($user['locked_left'] > 0){
            /*
             * Only lock if configured to do so
             */
            if($_CONFIG['security']['authentication']['auto_lock_fails'] and $_CONFIG['security']['authentication']['auto_lock_time']){
                throw new BException(tr('user_authenticate(): Specified user account is locked'), 'warning/locked', array('locked' => $user['locked_left']));
            }
        }

        /*
         * If we have too many auth_fails then lock the account temporarily
         */
        if(!$user['locked_until'] and ($user['auth_fails'] >= $_CONFIG['security']['authentication']['auto_lock_fails'])){
            /*
             * Only lock if configured to do so
             */
            if($_CONFIG['security']['authentication']['auto_lock_fails'] and $_CONFIG['security']['authentication']['auto_lock_time']){
                $user['locked_left'] = $_CONFIG['security']['authentication']['auto_lock_time'];

                sql_query('UPDATE `users`

                           SET    `locked_until` = UTC_TIMESTAMP() + INTERVAL '.$_CONFIG['security']['authentication']['auto_lock_time'].' SECOND

                           WHERE  `id`           = :id',

                           array(':id' => $user['id']));

                throw new BException(tr('user_authenticate(): Specified user account is locked'), 'warning/locked', array('locked' => $user['locked_left']));
            }
        }

        if($user['locked_until']){
            /*
             * This account was locked but the timout expired. Set the
             * locked_until date back to NULL. We haven't authenticated yet, but
             * that is okay, we're only doing this to auto update the user
             * administration
             */
            sql_query('UPDATE `users`

                       SET    `locked_until` = NULL

                       WHERE  `id`           = :id',

                       array(':id'           => $user['id']));
        }



        /*
         * User with "type" not null are special users that are not allowed to sign in
         */
        if(!empty($user['type'])){
            /*
             * This check will only do anything if the users table contains the "type" column. If it doesn't, nothing will ever happen here, really
             */
            log_database(tr('user_authenticate(): Specified user account ":username" has status ":type" and cannot be authenticated', array(':username' => str_log($username), ':type' => str_log($user['type']))), 'authentication/not-exists');
            throw new BException(tr('user_authenticate(): Specified user account has status ":type" and cannot be authenticated', array(':type' => $user['type'])), 'type');
        }



        /*
         * Check captcha
         */
        $failures = $_CONFIG['security']['authentication']['captcha_failures'] - 1;

        if($failures < 0){
            $failures = 0;
        }

        $captcha_required = false;
        // $captcha_required = ($captcha or user_authentication_requires_captcha($failures));

        if($captcha_required){
// :TODO: There might be a configuration issue where $_CONFIG['captcha']['type'] is disabled, but $captcha_required does require captcha..
            load_libs('captcha');

            try{
                captcha_verify_response($captcha);

            }catch(Exception $e){
                throw new BException(tr('user_authenticate(): CAPTCHA test failed for ":user"', array(':user' => $user['id'])), 'warning/captcha');
            }
        }



        /*
         * Compare user password
         */
        if(substr($user['password'], 0, 1) != '*'){
            /*
             * No encryption method specified, assume SHA1
             */
            $algorithm = 'sha256';

        }else{
            $algorithm = str_cut($user['password'], '*', '*');
        }

        if(strlen($password) > 256){
            throw new BException(tr('user_authenticate(): Specified password too long, should be less than 256 characters'), 'invalid');
        }

        try{
            $password = get_hash($password, $algorithm, false);

        }catch(Exception $e){
            switch($e->getCode()){
                case 'unknown-algorithm':
                    throw new BException(tr('user_authenticate(): User account ":name" has an unknown algorithm ":algorithm" specified', array(':user' => name($user), ':algorithm' => $algorithm)), $e);

                default:
                    throw new BException(tr('user_authenticate(): Password hashing failed for user account ":name"', array(':user' => name($user))), $e);
            }
        }

        if($password != str_rfrom($user['password'], '*')){
            log_database(tr('user_authenticate(): Specified password does not match stored password for user ":username"', array(':username' => $username)), 'authentication/failed');
            throw new BException(tr('user_authenticate(): Specified password does not match stored password'), 'warning/password');
        }



        /*
         * Apply IP locking system
         */
        if($_CONFIG['security']['signin']['ip_lock'] and (PLATFORM_HTTP)){
            include(__DIR__.'/handlers/user-ip-lock.php');
        }



        /*
         * Check if authentication for this user is limited to a specific domain
         */
        if(($_CONFIG['whitelabels']) and $user['domain']){
            if($user['domain'] !== $_SERVER['HTTP_HOST']){
                throw new BException(tr('user_autohenticate(): User account ":name" is limited to authenticate only in domain ":domain"', array(':name' => name($user), ':domain' => $user['domain'])), 'domain-limit');
            }
        }



        /*
         * Use two factor authentication, the user has to authenticate by SMS as well
         */
        if($_CONFIG['security']['signin']['two_factor']){
            if(empty($user['phone'])){
                throw new BException('user_autohenticate(): Two factor authentication impossible for user account "'.$user['id'].' / '.$user['name'].'" because no phone is registered', 'two-factor-no-phone');
            }

            $user['authenticated'] = 'two_factor';
            $user['two_factor']    = uniqid();

            load_libs('twilio');
            $twilio = twilio_load();
            $twilio->account->messages->sendMessage($_CONFIG['security']['signin']['twofactor'], $user['phone'], 'The "'.$_CONFIG['name'].'"authentication code is "'.$user['two_factor'].'"');

            return $user;
        }



        /*
         * Wait a random little bit so the authentication failure cannot be
         * timed (timing attacks will be harder), and library attacks will be
         * harder because authentication will be a relatively slow process
         */
        usleep(mt_rand(1000, 500000));
        sql_query('UPDATE `users` SET `auth_fails` = 0 WHERE `id` = :id', array(':id' => $user['id']));

        user_log_authentication($username, $user['id'], $captcha_required);

        /*
         * Set users timezone
         */
        if(empty($user['timezone'])){
            $user['timezone'] = $_CONFIG['timezone']['display'];
        }

        $user['authenticated'] = true;
        log_file(tr('Authenticated user ":user"', array(':user' => $user['id'].' / '.$user['email'])), 'user-authenticate', 'green');

        return $user;

    }catch(Exception $e){
        log_file(tr('Failed to authenticate user ":user" from ":username" because ":e"', array(':username' => $username, ':user' => isset_get($user['id']).' / '.isset_get($user['email']), ':e' => $e)), 'user-authenticate', 'red');

        /*
         * If a certain account is being attacked, then lock it temporarily
         */
        if(!empty($user['id'])){
            if(!$user['locked_left'] and (($user['auth_fails'] + 1) >= $_CONFIG['security']['authentication']['auto_lock_fails'])){
                sql_query('UPDATE `users`

                           SET    `locked_until` = UTC_TIMESTAMP() + INTERVAL '.$_CONFIG['security']['authentication']['auto_lock_time'].' SECOND,
                                  `auth_fails`   = `auth_fails` + 1

                           WHERE  `id` = :id',

                           array(':id' => $user['id']));

            }else{
                /*
                 * Update only the authentication failure count
                 */
                sql_query('UPDATE `users`

                           SET    `auth_fails` = `auth_fails` + 1

                           WHERE  `id`         = :id',

                           array(':id' => $user['id']));
            }
        }



        /*
         * Wait a little bit so the authentication failure cannot be timed, and
         * library attacks will be harder
         */
        user_log_authentication($username, isset_get($user['id']), isset_get($captcha_required), $e);
        usleep(mt_rand(1000, 2000000));

        if($e->getCode() == 'password'){
            /*
             * Password match failed. Check old passwords table to see if
             * perhaps the user used an old password
             */
            if($date = sql_get('SELECT `createdon` FROM `passwords` WHERE `users_id` = :users_id AND `password` = :password', 'id', array(':users_id' => isset_get($user['id']), ':password' => isset_get($password)))){
                $date = new DateTime($date);
                throw new BException('user_authenticate(): Your password was updated on "'.str_log($date->format($_CONFIG['formats']['human_date'])).'"', 'oldpassword');
            }
        }

        throw new BException('user_authenticate(): Failed', $e);
    }
}



/*
 *
 */
function user_log_authentication($username, $users_id, $captcha_required, $e = null){
    try{
        if($e){
            $failed_reason = $e->getMessage();
            $status        = $e->getCode();

        }else{
            $status = null;
        }

        sql_query('INSERT INTO `authentications` (`createdby`, `status`, `captcha_required`, `failed_reason`, `users_id`, `username`, `ip`)
                   VALUES                        (:createdby , :status , :captcha_required , :failed_reason , :users_id , :username , :ip )',

                   array(':status'           => $status,
                         ':createdby'        => isset_get($_SESSION['user']['id']),
                         ':users_id'         => $users_id,
                         ':username'         => $username,
                         ':failed_reason'    => str_truncate(trim(str_from(isset_get($failed_reason), '():')), 127),
                         ':captcha_required' => (boolean) $captcha_required,
                         ':ip'               => isset_get($_SERVER['REMOTE_ADDR'])));

    }catch(Exception $e){
        throw new BException('user_log_authentication(): Failed', $e);
    }
}



/*
 *
 */
function user_authentication_requires_captcha($failures = null){
    global $_CONFIG;
    static $result = null;

    try{
        if(is_bool($result)){
            return $result;
        }

        if(!$failures){
            $failures = $_CONFIG['security']['authentication']['captcha_failures'];
        }

        if(!$failures){
            if($failures === false){
                /*
                 * Never use CAPTCHA!
                 */
                return false;
            }

            /*
             * Always use CAPTCHA!
             */
            return true;
        }

        /*
         * Get the last N logins. If they are all failed, then captcha is
         * required
         */
        $list = sql_query('SELECT   `status` IS NOT NULL AS `fail`

                           FROM     `authentications`

                           WHERE    `ip` = :ip

                           ORDER BY `id` DESC

                           LIMIT    '.$failures,

                           array(':ip' => isset_get($_SERVER['REMOTE_ADDR'])));

        if($list->rowCount() < $failures){
            return false;
        }

        while(($fail = sql_fetch($list, true)) !== null){
            if(!$fail){
                /*
                 * We had a non failure in between, so we're okay
                 */
                $result = false;
                return $result;
            }
        }

        $result = true;
        return $result;

    }catch(Exception $e){
        throw new BException('user_authentication_requires_captcha(): Failed', $e);
    }
}



/*
 * Do a user signin
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package user
 *
 * @param array $user
 * @param boolean $extended
 * @param boolean $redirect
 * @param boolean $html_flash
 * @return params The specified users parameters array
 */
function user_signin($user, $extended = false, $redirect = null, $html_flash = null, $coupon = null) {
    global $_CONFIG;

    try{
        if($redirect === null){
            if(isset_get($_GET['redirect'])){
                $redirect = $_GET['redirect'];

            }elseif(isset_get($_GET['redirect'])){
                $redirect = $_GET['redirect'];
            }
        }

        if(!is_array($user)){
            throw new BException('user_signin(): Specified user variable is not an array', 'invalid');
        }

        /*
         * HTTP signin requires cookie support and an already active session!
         * Shell signin requires neither
         */
        if((PLATFORM_HTTP) and (empty($_COOKIE) or !session_id())){
            throw new BException('user_signin(): This user has no active session or no session id, so probably has no cookies', 'cookies-required');
        }

        if(session_status() == PHP_SESSION_ACTIVE){
            /*
             * Reset session data
             */
            if($_CONFIG['security']['signin']['destroy_session']){
                session_destroy();
                session_start();
                session_regenerate_id();
            }
        }

        /*
         * Store last login
         */
        sql_query('UPDATE `users` SET `last_signin` = UTC_TIMESTAMP(), `signin_count` = `signin_count` + 1 WHERE `id` = :id', array(':id' => cfi($user['id'])));

        if($extended){
            user_create_extended_session($user['id']);
        }

        if(empty($user['avatar'])){
            try{
                $user['avatar'] = user_find_avatar($user);

            }catch(Exception $e){
// :TODO: Add notifications somewhere?
                log_console($e);
            }
        }

        /*
         * Load employee data
         */
        load_libs('companies');
        $user['employee'] = companies_get_employee(array('filters' => array('users_id' => $user['id'],
                                                                            'status'   => null)));

        if($user['employee']){
            if($user['employee']['customers_id']){
                /*
                 * Load customers data
                 */
                load_libs('customers');
                $user['customer'] = customers_get(array('id'      => $user['employee']['customers_id'],
                                                        'columns' => 'name,seoname'));
            }

            if($user['employee']['providers_id']){
                /*
                 * Load providers data
                 */
                load_libs('providers');
                $user['provider'] = providers_get(array('id'      => $user['employee']['providers_id'],
                                                        'columns' => 'name,seoname'));
            }
        }

        $_SESSION['user'] = $user;

        if($coupon){
            load_libs('coupons');
            coupons_add_coupon($coupon);
        }

        if(empty($_SESSION['user']['roles_id'])){
            $_SESSION['user']['role'] = null;

        }else{
            $_SESSION['user']['role'] = sql_get('SELECT `roles`.`name` FROM `roles` WHERE `id` = :id', 'name', array(':id' => $_SESSION['user']['roles_id']));
        }

        if($html_flash){
            html_flash_set(isset_get($html_flash['text']), isset_get($html_flash['type']), isset_get($html_flash['class']));
        }

        if(empty($_SESSION['user']['language'])){
            $_SESSION['user']['language'] = $_CONFIG['language']['default'];
        }

        if($redirect and (PLATFORM_HTTP)){
            /*
             * Do not redirect to signin page
             */
            if($redirect == $_CONFIG['redirects']['signin']){
                $redirect = $_CONFIG['redirects']['index'];
            }

            session_redirect('http', $redirect);
        }

        log_database(tr('user_signin(): Signed in user ":user"', array(':user' => name($user))), 'signin/success');

        return $_SESSION['user'];

    }catch(Exception $e){
        log_database(tr('user_signin(): User sign in failed for user ":user" because ":message"', array(':user' => name($user), ':message' => $e->getMessage())), 'signin/failed');
        throw new BException('user_signin(): Failed', $e);
    }
}



/*
 * Do a user signout
 */
function user_signout() {
    global $_CONFIG;

    try{
        $cookie = isset_get($_COOKIE['base']);

        if(isset($_COOKIE['extsession'])) {
            /*
             * Remove cookie
             */
            setcookie('extsession', 'stub', 1);

            if(isset($_SESSION['user'])){
                sql_query('DELETE FROM `extended_sessions` WHERE `users_id` = :users_id', array('users_id' => cfi($_SESSION['user']['id'])));
            }
        }

        /*
         * Remove session info
         */
        unset($_SESSION['user']);

        session_destroy();
        setcookie('base', 'stub', 1, '/');

        if($cookie){
            file_delete(ROOT.'data/cookies/sess_'.$cookie, ROOT.'data/cookies');
        }

    }catch(Exception $e){
        throw new BException('user_signout(): Failed', $e);
    }
}



/*
 * Create an extended login that can survive beyond the standard short lived PHP sessions
 */
function user_create_extended_session($users_id) {
    global $_CONFIG;

    try{
        if(!$_CONFIG['sessions']['extended']) {
            return false;
        }

        /*
         * Create new code
         */
        $code = sha1($users_id.'-'.uniqid($_SESSION['domain'], true).'-'.time());

        //remove old entries
        if($_CONFIG['sessions']['extended']['clear'] != false) {
            sql_query('DELETE FROM `extended_sessions` WHERE `users_id` = :users_id', array('users_id' => cfi($users_id)));
        }

        /*
         * Add to db
         */
        sql_query('INSERT INTO `extended_sessions` (`users_id`, `session_key`, `ip`)
                   VALUES                          (:users_id , :session_key , :ip)',

                   array(':users_id'    => cfi($users_id),
                         ':session_key' => $code,
                         ':ip'          => ip2long($_SERVER['REMOTE_ADDR'])));

        setcookie('extsession', $code, (time() + $_CONFIG['sessions']['extended']['age']));
        return $code;

    }catch(Exception $e){
        throw new BException('user_create_extended_session(): Failed', $e);
    }
}



/*
 * Set a users verification code
 */
function user_set_verify_code($user, $email_type = false, $email = null){
	global $_CONFIG;

    try{
        load_libs('email');

        if(!is_array($user)){
            throw new BException('user_set_verify_code(): Invalid user specified', 'invalid');
        }

        $code = sql_get('SELECT `verify_code` FROM `users` WHERE `id` = :id', true, array(':id' => cfi($user['id'])));

        if(!$code){
            /*
             * Create a unique code.
             */
            $code = unique_code();

            /*
             * Update user validation with that code
             */
            $r = sql_query('UPDATE `users`

                            SET    `verify_code` = :verify_code,
                                   `verifiedon`  = NULL

                            WHERE  `id`          = :id',

                            array(':id'          => cfi($user['id']),
                                  ':verify_code' => cfm($code)));

            if(!$r->rowCount()){
                throw new BException(tr('user_set_verify_code(): Specified user ":user" does not exist', array(':user' => $user['id'])), 'not-exists');
            }
        }
        switch($email_type){
            case '':
                break;

            case 'signup':

                email_send(array('format'   => 'html',
                                 'delayed'  => false,
                                 'to'       => $user['email'],
                                 'from'     => ($email ? $email : 'noreply@'.$_CONFIG['domain']),
                                 'template' => 'validate-email',
                                 'keywords' => array(':title' => tr('Action Requiered: Confirm your account'),
                                                     ':name'  => name($user),
                                                     ':url'   => domain('/verify/'.$code))));
                break;

            case 'update':
                email_send(array('format'   => 'html',
                                 'delayed'  => false,
                                 'to'       => $user['email'],
                                 'from'     => ($email ? $email : 'noreply@'.$_CONFIG['domain']),
                                 'template' => 'update-email',
                                 'keywords' => array(':title' => tr('Action Requiered: Confirm your new email'),
                                                     ':name'  => name($user),
                                                     ':url'   => domain('/verify/'.$code))));
                break;

            default:
                throw new BException(tr('user_set_verify_code(): Specified email type ":type" does not exist', array(':type' => $email_type)), 'not-exists');
        }

        return $code;

    }catch(Exception $e){
        throw new BException('user_set_verify_code(): Failed', $e);
    }
}



/*
 * Set a users verification code
 */
function user_verify($code){
    try{
        $user = sql_get('SELECT * FROM `users` WHERE `verify_code` = :verify_code', array(':verify_code' => cfm($code)));

        if(!$user){
            throw new BException(tr('user_verify(): The specified verify code ":code" does not exist', array(':code' => $code)), 'not-exists');
        }

        /*
         * This code exists, yay! Register the user as verified
         */
        sql_query('UPDATE `users`

                   SET    `verify_code` = NULL,
                          `verifiedon`  = NOW()

                   WHERE  `id`          = :id', array(':id' => cfi($user['id'])));

        if(isset_get($_SESSION['user']['id']) == $user['id']){
            /*
             * Hey, the currently logged in user is the user being verified!
             */
            $_SESSION['user']['verifiedon']  = date_convert(null, 'mysql');
            $_SESSION['user']['verify_code'] = null;
        }

        return $user;

    }catch(Exception $e){
        throw new BException('user_verify(): Failed', $e);
    }
}



/*
 * Returns if some of the userdata is blacklisted or not
 */
function user_check_blacklisted($name){
    try{
//:TODO: Implement. THROW EXCEPTION IF BLACKLISTED!

    }catch(Exception $e){
        throw new BException('user_blacklisted(): Failed', $e);
    }
}



/*
 * Wrapper for user_signup
 */
function user_create($user, $options){
    try{
        return user_signup($user, $options);

    }catch(Exception $e){
        throw new BException('user_create(): Failed', $e);
    }
}



/*
 * Add a new user
 */
function user_signup($user, $options = null){
    global $_CONFIG;

    try{
        array_ensure($options, 'no_password,role');

        if($options['role']){
            /*
             * This option forces the user to be the specified role
             */
            $user['role'] = $options['role'];
        }

        if(empty($user['password']) and (isset_get($user['status']) !== '_new') and !$options['no_password']){
            throw new BException(tr('user_signup(): Please specify a password'), 'not-specified');
        }

        $user       = user_validate($user, $options);
        $user['id'] = sql_random_id('users');

        sql_query('INSERT INTO `users` (`id`, `meta_id`, `status`, `createdby`, `username`, `password`, `name`, `email`, `roles_id`, `role`, `timezone`)
                   VALUES              (:id , :meta_id , :status , :createdby , :username , :password , :name , :email , :roles_id , :role , :timezone )',

                   array(':id'        => $user['id'],
                         ':createdby' => isset_get($_SESSION['user']['id']),
                         ':meta_id'   => meta_action(),
                         ':username'  => $user['username'],
                         ':status'    => $user['status'],
                         ':name'      => $user['name'],
                         ':password'  => (empty($user['password']) ? '' : (($user['status'] === '_new') ? '' : get_hash($user['password'], $_CONFIG['security']['passwords']['hash']))),
                         ':email'     => $user['email'],
                         ':role'      => $user['role'],
                         ':roles_id'  => $user['roles_id'],
                         ':timezone'  => $user['timezone']));

        /*
         * Return data from database with the given $user merged over it.
         *
         * NOTE: This is done on purpose! The `users` table may contain extra
         * columns that are not yet filled in by user_signup(), and would be
         * lost with the data we return if we don't copy the given $user over
         * the database data.
         */
        return array_merge(user_get(sql_insert_id(), isset_get($user['status'])), $user);

    }catch(Exception $e){
        throw new BException('user_signup(): Failed', $e);
    }
}



/*
 * Update the specified user in the database
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package user
 * @version 2.5.21: Added function and documentation
 * @note This function will NOT update the users password
 * @note This function will update the users right list according to its roles_id, and specified rights
 * @note This function will update the users groups list
 *
 * @param params $user The user to be updated
 * @param string $user[username]
 * @param string $user[nickname]
 * @param string $user[name]
 * @param string $user[email]
 * @param string $user[role]
 * @param string $user[roles_id]
 * @param string $user[language]
 * @param string $user[gender]
 * @param string $user[latitude]
 * @param string $user[longitude]
 * @param string $user[phones]
 * @param string $user[keywords]
 * @param string $user[country]
 * @param string $user[commentary]
 * @param string $user[description]
 * @param string $user[avatar]
 * @param string $user[timezone]
 * @param string $user[redirect]
 * @return boolean True if the user was updated, false otherwise
 */
function user_update($user){
    try{
        $user = user_validate($user, array('password'            => false,
                                           'validation_password' => false));

        meta_action($user['meta_id'], 'update');

        $update = sql_query('UPDATE `users`

                             SET    `modifiedby`  = :modifiedby,
                                    `modifiedon`  = NOW(),
                                    `status`      = :status,
                                    `username`    = :username,
                                    `nickname`    = :nickname,
                                    `name`        = :name,
                                    `email`       = :email,
                                    `roles_id`    = :roles_id,
                                    `role`        = :role,
                                    `language`    = :language,
                                    `gender`      = :gender,
                                    `latitude`    = :latitude,
                                    `longitude`   = :longitude,
                                    `phones`      = :phones,
                                    `keywords`    = :keywords,
                                    `country`     = :country,
                                    `commentary`  = :commentary,
                                    `description` = :description,
                                    `avatar`      = :avatar,
                                    `timezone`    = :timezone,
                                    `redirect`    = :redirect

                             WHERE  `id`          = :id',

                             array(':modifiedby'  =>  isset_get($_SESSION['user']['id']),
                                   ':id'          =>  $user['id'],
                                   ':username'    =>  get_null($user['username']),
                                   ':nickname'    =>  get_null($user['nickname']),
                                   ':email'       =>  get_null($user['email']),
                                   ':name'        =>  $user['name'],
                                   ':language'    =>  $user['language'],
                                   ':gender'      =>  $user['gender'],
                                   ':latitude'    =>  $user['latitude'],
                                   ':longitude'   =>  $user['longitude'],
                                   ':roles_id'    =>  $user['roles_id'],
                                   ':role'        =>  $user['role'],
                                   ':keywords'    =>  $user['keywords'],
                                   ':phones'      =>  $user['phones'],
                                   ':status'      =>  $user['status'],
                                   ':avatar'      =>  $user['avatar'],
                                   ':timezone'    =>  $user['timezone'],
                                   ':redirect'    =>  $user['redirect'],
                                   ':commentary'  =>  $user['commentary'],
                                   ':description' =>  $user['description'],
                                   ':country'     =>  $user['country']));

        $user['_updated'] = (boolean) $update->rowCount();

        user_update_rights($user);
        $user['_updated'] &= (boolean) $update->rowCount();

        user_update_groups($user['id'], $user['groups']);
        $user['_updated'] &= (boolean) $update->rowCount();

        return $user;

    }catch(Exception $e){
        throw new BException('user_update(): Failed', $e);
    }
}



/*
 * Update user password. This can be used either by the current user, or by an
 * admin user updating the users password
 */
function user_update_password($params, $current = true){
    global $_CONFIG;

    try{
        array_ensure($params);
        array_ensure($params, 'id,password,password2,cpassword');

        array_default($params, 'validated'             , false);
        array_default($params, 'check_banned_passwords', true);

        if(!is_array($params)){
            throw new BException(tr('user_update_password(): Invalid params specified'), 'invalid');
        }

        if(empty($params['id'])){
            throw new BException(tr('user_update_password(): No users id specified'), 'not-specified');
        }

        if(empty($params['password'])){
            throw new BException(tr('user_update_password(): Please specify a password'), 'warning/not-specified');
        }

        if(empty($params['password2'])){
            throw new BException(tr('user_update_password(): No validation password specified'), 'not-specified');
        }

        /*
         * Check if password is equal to password2
         */
        if($params['password'] != $params['password2']){
            throw new BException(tr('user_update_password(): Specified password does not match the validation password'), 'warning/mismatch');
        }

        /*
         * Check if password is NOT equal to cpassword
         */
        if($current and ($params['password'] == $params['cpassword'])){
            throw new BException(tr('user_update_password(): Specified new password is the same as the current password'), 'warning/same-as-current');
        }

        if($current){
            if(empty($params['cpassword'])){
                throw new BException(tr('user_update_password(): Please specify the current password'), 'warning/not-specified');
            }

            user_authenticate($_SESSION['user']['email'], $params['cpassword']);
        }

        if(!$params['validated']){
            /*
             * Check password strength
             */
            $strength = user_password_strength($params['password'], $params['check_banned_passwords']);
        }

        /*
         * Prepare new password
         */
        $password = get_hash($params['password'], $_CONFIG['security']['passwords']['hash']);

        /*
         * Ensure that this new password is not the same as one of the N
         * previous passwords in N previous days
         */
        if($_CONFIG['security']['passwords']['unique_days']){
            $list = sql_query('SELECT   `password`

                               FROM     `passwords`

                               WHERE    `users_id`  = :users_id
                               AND      `createdon` > DATE_SUB(UTC_TIMESTAMP(), INTERVAL :day DAY)

                               ORDER BY `id`

                               LIMIT    '.$_CONFIG['security']['passwords']['unique_updates'],

                               array(':users_id' => $params['id'],
                                     ':day'      => $_CONFIG['security']['passwords']['unique_days']));

            while($previous = sql_fetch($list)){
                if($previous == $password){
                    /*
                     * This password has been used before
                     */
                    throw new BException(tr('user_update_password(): The specified password has already been used before'), 'used-before');
                }
            }
        }

        /*
         * Update the password
         */
        $r = sql_query('UPDATE `users`

                        SET    `modifiedon` = NOW(),
                               `modifiedby` = :modifiedby,
                               `password`   = :password

                        WHERE  `id`         = :id',

                        array(':id'         => $params['id'],
                              ':modifiedby' => isset_get($_SESSION['user']['id']),
                              ':password'   => $password));

        if(!$r->rowCount()){
            /*
             * Nothing was updated. This may be because the password remained the same, OR
             * because the user does not exist. check for this!
             */
            if(!sql_get('SELECT `id` FROM `users` WHERE `id` = :id', 'id', array(':id' => $params['id']))){
                throw new BException(tr('user_update_password(): The specified users_id "'.str_log($params['id']).'" does not exist'), 'not-exists');
            }

            /*
             * Password remains the same, no problem
             */
        }

        /*
         * Add the new password to the password storage
         */
        sql_query('INSERT INTO `passwords` (`createdby`, `users_id`, `password`)
                   VALUES                  (:createdby , :users_id , :password )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':users_id'  => $params['id'],
                         ':password'  => $password));

        return $r->rowCount();

    }catch(Exception $e){
        throw new BException('user_update_password(): Failed', $e);
    }
}



/*
 * Return requested data for specified user
 */
function user_get($user = null, $status = null){
    global $_CONFIG;

    try{
        if($user){
            if(!is_scalar($user)){
                if(!is_array($user)){
                    throw new BException(tr('user_get(): Specified user data ":data" is not scalar or array', array(':data' => $user)), 'invalid');
                }

                $user = $user['id'];
            }

            if(is_numeric($user)){
                $retval = sql_get('SELECT    `users`.*,
                                             `users`.`password`      AS `password2`,

                                             `createdby`.`name`      AS `createdby_name`,
                                             `createdby`.`email`     AS `createdby_email`,
                                             `createdby`.`username`  AS `createdby_username`,

                                             `modifiedby`.`name`     AS `modifiedby_name`,
                                             `modifiedby`.`email`    AS `modifiedby_email`,
                                             `modifiedby`.`username` AS `modifiedby_username`

                                   FROM      `users`

                                   LEFT JOIN `users` AS `createdby`
                                   ON        `createdby`.`id`  = `users`.`createdby`

                                   LEFT JOIN `users` AS `modifiedby`
                                   ON        `modifiedby`.`id` = `users`.`modifiedby`

                                   WHERE     `users`.`id`      = :id
                                   AND       (`users`.`status` '.sql_where_null($status).' OR `users`.`status` = "locked")',

                                   array(':id' => $user));

            }else{
                $retval = sql_get('SELECT    `users`.*,
                                             `users`.`password`      AS `password2`,

                                             `createdby`.`name`      AS `createdby_name`,
                                             `createdby`.`email`     AS `createdby_email`,
                                             `createdby`.`username`  AS `createdby_username`,

                                             `modifiedby`.`name`     AS `modifiedby_name`,
                                             `modifiedby`.`email`    AS `modifiedby_email`,
                                             `modifiedby`.`username` AS `modifiedby_username`

                                   FROM      `users`

                                   LEFT JOIN `users` AS `createdby`
                                   ON        `createdby`.`id` = `users`.`createdby`

                                   LEFT JOIN `users` AS `modifiedby`
                                   ON        `modifiedby`.`id` = `users`.`modifiedby`

                                   WHERE     `users`.`email`    = :email
                                   OR        `users`.`username` = :username
                                   AND       (`users`.`status` '.sql_where_null($status).' OR `users`.`status` = "locked")',

                                   array(':email'    => $user,
                                         ':username' => $user));
            }

        }else{
            /*
             * Pre-create a new user
             */
            $retval = sql_get('SELECT    `users`.*,
                                         `users`.`password`      AS `password2`,

                                         `createdby`.`name`      AS `createdby_name`,
                                         `createdby`.`email`     AS `createdby_email`,
                                         `createdby`.`username`  AS `createdby_username`,

                                         `modifiedby`.`name`     AS `modifiedby_name`,
                                         `modifiedby`.`email`    AS `modifiedby_email`,
                                         `modifiedby`.`username` AS `modifiedby_username`

                               FROM      `users`

                               LEFT JOIN `users` AS `createdby`
                               ON        `createdby`.`id` = `users`.`createdby`

                               LEFT JOIN `users` AS `modifiedby`
                               ON        `modifiedby`.`id` = `users`.`modifiedby`

                               WHERE     `users`.`createdby` = :createdby
                               AND       `users`.`status`    = "_new"',

                               array(':createdby' => $_SESSION['user']['id']));

            if(!$retval){
                $id = user_signup(array('status' => '_new'), array('no_validation' => true));
                return user_get(null);
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new BException('user_get(): Failed', $e);
    }
}



/*
 * Load the rights for the specified user
 */
function user_load_rights($user){
    try{
        if(!is_numeric($user)){
            if(!is_array($user)){
                throw new BException('user_load_rights(): Invalid user, please specify either users_id or user array with id', 'invalid');
            }

            $user = isset_get($user['id']);
        }

        return sql_list('SELECT `name`,
                                `name` AS `right`

                         FROM   `users_rights`

                         WHERE  `users_id` = :users_id',

                         array(':users_id' => $user));

    }catch(Exception $e){
        throw new BException('user_load_rights(): Failed', $e);
    }
}



/*
 * Make the current session the specified user
 * NOTE: Since this function is rarely used, it it implemented by a handler
 */
function user_switch($users_id, $redirect = '/'){
    include(__DIR__.'/handlers/user-switch.php');
}



///*
// * Find the password field when browser password saving has been disabled
// */
//function user_process_signin_fields($post){
//    global $_CONFIG;
//
//    try{
//        if(empty($_CONFIG['security']['signin']['save_password'])){
//            /*
//             * Clear username and password fields, to ensure they are not being used
//             */
//            unset($post['username']);
//            unset($post['password']);
//
//            /*
//             * Password field is password********
//             */
//            foreach(array_max($post) as $key => $value){
//                if((substr($key, 0, 8) == 'password') and (strlen($key) == 16)){
//                    /*
//                     * This is the password field, set it.
//                     */
//                    $post['password'] = $post[$key];
//                    unset($post[$key]);
//
//                }elseif((substr($key, 0, 8) == 'username') and (strlen($key) == 16)){
//                    /*
//                     * This is the username field, set it.
//                     */
//                    $post['username'] = $post[$key];
//                    unset($post[$key]);
//                    continue;
//                }
//
//                if(isset($post['username']) and isset($post['password'])){
//                    break;
//                }
//            }
//        }
//
//        return $post;
//
//    }catch(Exception $e){
//        throw new BException('user_process_signin_fields(): Failed', $e);
//    }
//}



/*
 * Update the rights for this user.
 * Requires a user array with $user['id'], and $user['roles_id']
 */
function user_update_rights($user){
    try{
        if(empty($user['id'])){
            throw new BException('user_update_rights(): Cannot update rights, no user specified', 'not-specified');
        }

        if(empty($user['roles_id'])){
            throw new BException('user_update_rights(): Cannot update rights, no role specified', 'not-specified');
        }

        /*
         * Get new rights, delete all old rights, and prepare the query to insert these new rights
         */
        sql_query('DELETE FROM `users_rights` WHERE `users_id` = :users_id', array(':users_id' => $user['id']));

        $rights  = sql_list('SELECT    `rights`.`id`,
                                       `rights`.`name`

                             FROM      `roles_rights`

                             LEFT JOIN `rights`
                             ON        `rights`.`id` = `roles_rights`.`rights_id`

                             WHERE     `roles_id` = :roles_id',

                             array(':roles_id' => $user['roles_id']));

        $p       = sql_prepare('INSERT INTO `users_rights` (`users_id`, `rights_id`, `name`)
                                VALUES                     (:users_id , :rights_id , :name )');

        $execute = array(':users_id' => $user['id']);

        foreach($rights as $id => $name){
            $execute[':rights_id'] = $id;
            $execute[':name']      = $name;

            $p->execute($execute);
        }

    }catch(Exception $e){
        throw new BException('user_update_rights(): Failed', $e);
    }
}



/*
 * Simple function to test password strength
 * Found on http://www.phpro.org/examples/Password-Strength-Tester.html
 *
 * Rewritten for use in BASE project by Sven Oostenbrink
 */
// :TODO: Improve. This function uses some bad algorithms that could cause false high ranking passwords
function user_password_strength($password, $check_banned = true, $exception = true){
    global $_CONFIG;

    try{
        /*
         * Get the length of the password
         */
        $strength = 10;
        $length   = strlen($password);

        if($length < 8){
            if(!$length){
                throw new BException(tr('user_password_strength(): No password specified'), 'not-specified');
            }

            throw new BException(tr('user_password_strength(): Specified password is too short'), 'validation');
        }

        /*
         * Check for banned passwords
         */
        if($check_banned){
            user_password_banned($password);
        }

        /*
         * Check if password is not all lower case
         */
        if(strtolower($password) != $password){
            $strength += 5;
        }

        /*
         * Check if password is not all upper case
         */
        if(strtoupper($password) != $password){
            $strength += 5;
        }

        /*
         * Bonus for long passwords
         */
        $strength += ($length - 8);

        /*
         * Get the upper case letters in the password
         */
        preg_match_all('/[A-Z]/', $password, $matches);
        $strength += (count($matches[0]) / 2);

        /*
         * Get the lower case letters in the password
         */
        preg_match_all('/[a-z]/', $password, $matches);
        $strength += (count($matches[0]) / 2);

        /*
         * Get the numbers in the password
         */
        preg_match_all('/[0-9]/', $password, $matches);
        $strength += (count($matches[0]) * 2);

        /*
         * Check for special chars
         */
        preg_match_all('/[|!@#$%&*\/=?,;.:\-_+~^\\\]/', $password, $matches);
        $strength += (count($matches[0]) * 2);

        /*
         * Get the number of unique chars
         */
        $chars            = str_split($password);
        $num_unique_chars = count(array_unique($chars));

        $strength += $num_unique_chars * 2;

        /*
         * Test for same character repeats
         */
        $counts = array();

        for($i = 0; $i < $length; $i++){
            if(empty($counts[$password[$i]])){
                $counts[$password[$i]] = substr_count($password, $password[$i]);
            }
        }

        sort($counts);

        $count = (array_pop($counts) + array_pop($counts) + array_pop($counts));

        if(($count / ($length + 3) * 10) >= 3){
            $strength = $strength - ($strength * ($count / $length));

        }else{
            $strength = $strength + ($strength * ($count / $length));
        }

        /*
         * Strength is a number 1-10;
         */
        $strength = (($strength > 99) ? 99 : $strength);
        $strength = floor(($strength / 10) + 1);

        log_console(tr('Password strength is ":strength"', array(':strength' => number_format($strength, 2))), 'VERBOSE');

        if($_CONFIG['users']['password_minumum_strength'] and ($strength < $_CONFIG['users']['password_minumum_strength'])){
            if($exception){
                throw new BException(tr('user_password_strength(): The specified password is too weak, please use a better password. Use more characters, add numbers, special characters, caps characters, etc. On a scale of 1-10, current strength is ":strength"', array(':strength' => $strength)), 'validation');
            }

            return false;
        }

        return $strength;

    }catch(Exception $e){
        throw new BException('user_password_strength(): Failed', $e);
    }
}



/*
 *
 */
function user_password_banned($password){
    global $_CONFIG;

    try{
        if(($password == $_CONFIG['domain']) or ($password == str_until($_CONFIG['domain'], '.'))){
            throw new BException(tr('user_password_banned(): The default password is not allowed to be used'), 'banned');
        }

// :TODO: Add more checks

    }catch(Exception $e){
        throw new BException('user_password_banned(): Failed', $e);
    }
}



/*
 * Validate the specified user. Validations is done in sections, and sections
 * can be disabled if needed
 */
function user_validate($user, $options = array()){
    global $_CONFIG;

    try{
        array_default($options, 'password'           , true);
        array_default($options, 'validation_password', true);
        array_default($options, 'role'               , true);
        array_default($options, 'no_validation'      , false);

        load_libs('validate');
        $v = new ValidateForm($user, 'name,username,nickname,email,password,password2,redirect,description,role,roles_id,commentary,gender,latitude,longitude,language,country,fb_id,fb_token,gp_id,gp_token,ms_id,ms_token_authentication,ms_token_access,tw_id,tw_token,yh_id,yh_token,status,validated,avatar,phones,type,domain,title,priority,reference_codes,timezone,groups,keywords');

        $user['email2'] = $user['email'];
        $user['terms']  = true;

        if($options['no_validation']){
            return $user;
        }

        /*
         * Validate domain
         */
        if($user['domain']){
            $user['domain'] = trim(strtolower($user['domain']));
            if($v->isRegex($user['domain'], '/[a-z.]/', tr('Please provide a valid domain name')));

            /*
             * Does the domain exist in the whitelabel system?
             */
            $exist = sql_get('SELECT `domain` FROM `whitelabels` WHERE `domain` = :domain', array(':domain' => $user['domain']));

            if(!$exist){
                $v->setError(tr('The specified domain ":domain" does not exist', array(':domain' => $user['domain'])));
            }
        }

        /*
         * Validate username
         */
        if($user['username']){
            $v->isAlphaNumeric($user['username'], tr('Please provide a valid username, it can only contain letters and numbers'), VALIDATE_IGNORE_DOT|VALIDATE_IGNORE_DASH);

            if($v->isNumeric($user['username'])){
                $v->setError(tr('Please provide a non numeric username'));
            }

            $username = substr($user['username'], 0, 1);

            if($v->isNumeric($username)){
                $v->setError(tr('Please provide a username that does not start with a number'));
            }

            $exists = sql_query('SELECT `id` FROM `users` WHERE `username` = :username AND `id` != :id', array(':id' => isset_get($user['id']), ':username' => $user['username']));

            if($exists->rowCount()){
                $v->setError(tr('The username ":username" is already taken', array(':username' => $user['username'])));
            }

        }else{
            $user['username'] = null;

            if(!$user['email']){
                $v->setError(tr('Please provide at least an email or username'));
            }
        }

        /*
         * Validate email address
         */
        if($user['email']){
            $v->isEmail($user['email'], tr('Please ensure that the specified email is valid'));

            /*
             * Double emails are NOT allowed
             */
            $exists = sql_get('SELECT `id` FROM `users` WHERE `email` = :email AND `id` != :id', true, array(':email' => $user['email'], ':id' => isset_get($user['id'], 0)));

            if($exists){
                $v->setError(tr('The email address ":email" is already taken', array(':email' => $user['email'])));
            }
        }else{
            $user['email'] = null;
        }

        /*
         * Validate nickname
         */
        if($user['nickname']){
            $v->hasMinChars($user['nickname'], 2, tr('Please ensure that the users nick name has a minimum of 2 characters'));

            if($_CONFIG['users']['unique_nicknames']){
                /*
                 * Double nicknames are NOT allowed
                 */
                $exists = sql_get('SELECT `id` FROM `users` WHERE `nickname` = :nickname AND `id` != :id', true, array(':id' => $user['id'], ':nickname' => $user['nickname']));

                if($exists){
                    $v->setError(tr('The nickname ":nickname" is already taken', array(':nickname' => $user['nickname'])));
                }
            }
        }

        if($user['name']){
            $v->hasMinChars($user['name'], 2, tr('Please ensure that the users name has a minimum of 2 characters'));
        }

        if($user['title']){
            $v->hasMinChars($user['title'],  2, tr('Please ensure that the users title has a minimum of 2 characters'));
            $v->hasMaxChars($user['title'], 24, tr('Please ensure that the users title has a minimum of 24 characters'));
        }

        if($user['priority']){
            $v->isNumeric($user['priority'],  tr('Please ensure that the users priority is numeric'));
        }

        if(!empty($user['reference_codes'])){
            if(!is_scalar($user['reference_codes'])){
                $v->setError(tr('Please ensure that the reference number is a scalar value'));
            }
        }

        if(!$user['timezone']){
            $user['timezone'] = $_CONFIG['timezone']['display'];
        }

        $v->isTimezone($user['timezone'], tr('Please specify a valid timezone'), VALIDATE_ALLOW_EMPTY_NULL);

        if($options['role']){
            if(!empty($user['role'])){
                /*
                 * Role was specified by name
                 */
                $user['roles_id'] = sql_get('SELECT `id` FROM `roles` WHERE `name` = :name', 'id', array(':name' => $user['role']));

                if(!$user['roles_id']){
                    $v->setError(tr('Specified role ":role" does not exist', array(':role' => $user['role'])));
                }

            }else{
                $v->isNotEmpty($user['roles_id'], tr('Please provide a role'));
            }
        }

        if($user['roles_id']){
            if(!$role = sql_get('SELECT `id`, `name` FROM `roles` WHERE `id` = :id AND `status` IS NULL', array(':id' => $user['roles_id']))){
                $v->setError(tr('The specified role does not exist'));
                $user['role'] = null;

            }else{
                $user['roles_id'] = $role['id'];
                $user['role']     = $role['name'];

                /*
                 * God role? god role can only be managed by god users or
                 * command line users!
                 */
                if($role['name'] === 'god'){
                    if((PLATFORM_HTTP) and !has_rights('god')){
                        $v->setError(tr('The god role can only be assigned or changed by users with god role themselves'));
                    }
                }
            }
        }

        if($options['password']){
            if(empty($user['password'])){
                $v->setError(tr('Please specify a password'));

            }else{
                /*
                 * Check password strength
                 */
                if($options['validation_password']){
                    if($user['password'] === $user['password2']){
                        try{
                            $strength = user_password_strength($user['password']);

                        }catch(Exception $e){
                            if($e->getCode() !== 'weak'){
                                /*
                                 * Erw, something went really wrong!
                                 */
                                throw $e;
                            }

                            $v->setError(tr('The specified password is too weak and not accepted'));
                        }

                    }else{
                        $v->setError(tr('Please ensure that the password and validation password match'));
                    }
                }
            }
        }

        /*
         * Only continue testing user if there were no validation errors so far
         */
        $v->isValid();

        if(!$user['type']){
            $user['type'] = null;
        }

        /*
         * Ensure that the phones are not in use
         */
        if(!empty($user['phones'])){
            $user['phones'] = explode(',', $user['phones']);

            foreach($user['phones'] as &$phone){
                $phone = trim($phone);
            }

            unset($phone);

            $user['phones'] = implode(',', $user['phones']);
            $execute        = sql_in($user['phones'], ':phone');

            foreach($execute as &$phone){
                if($v->isPhonenumber($phone, tr('The phone number ":phone" is not valid', array(':phone' => $phone)))){
                    $phone = '%'.$phone.'%';
                }
            }

            unset($phone);

            $where   = array();

            $query   = 'SELECT `id`,
                               `phones`,
                               `username`

                        FROM   `users`

                        WHERE';

            foreach($execute as $key => $value){
                $where[] = '`phones` LIKE '.$key;
            }

            $query .= ' ('.implode(' OR ', $where).')';

            if(!empty($user['id'])){
                $query         .= ' AND `users`.`id` != :id';
                $execute[':id'] = $user['id'];
            }

            $exists = sql_list($query, $execute);

            if($exists){
                /*
                 * One or more phone numbers already exist with one or multiple users. Cross check and
                 * create a list of where the number was found
                 */
                foreach(array_force($user['phones']) as $value){
                    foreach($exists as $exist){
                        $key = array_search($value, array_force($exist['phones']));

                        if($key !== false){
                            /*
                             * The current phone number is already in use by another user
                             */
                            $v->setError(tr('The phone ":phone" is already in use by user ":user"', array(':phone' => $value, ':user' => '<a target="_blank" href="'.domain('/user.html?user='.$exist['username']).'">'.$exist['username'].'</a>')));
                        }
                    }
                }
            }
        }

        $v->isValid();

        return $user;

    }catch(Exception $e){
        throw new BException(tr('user_validate(): Failed'), $e);
    }
}



/*
 * Return data for the specified user
 *
 * This function returns information for the specified user. The user can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @user Function reference
 * @package users
 *
 * @param mixed $user The requested user. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @param string $status
 * @param string $parent
 * @return mixed The user data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified user does not exist, NULL will be returned.
 */
function users_get($user, $column = null, $status = null, $parent = false){
    try{
        if(is_numeric($user)){
            $where[] = ' `users`.`id` = :id ';
            $execute[':id'] = $user;

        }else{
            $where[] = ' `users`.`seoname` = :seoname ';
            $execute[':seoname'] = $user;
        }

        if($status !== false){
            $execute[':status'] = $status;
            $where[] = ' `users`.`status` '.sql_is($status, ':status');
        }

        if($parent){
            /*
             * Explicitly must be a parent user
             */
            $where[] = ' `users`.`parents_id` IS NULL ';

        }elseif($parent === false){
            /*
             * Explicitly cannot be a parent user
             */
            $where[] = ' `users`.`parents_id` IS NOT NULL ';

        }else{
            /*
             * Don't care if its a parent or child user
             */
        }

        $where = ' WHERE '.implode(' AND ', $where).' ';

        if($column){
            $retval = sql_get('SELECT `'.$column.'` FROM `users` '.$where, true, $execute);

        }else{
            $retval = sql_get('SELECT `id`,
                                      `createdby`,
                                      `meta_id`,
                                      `createdon`,
                                      `modifiedby`,
                                      `modifiedon`,
                                      `status`,
                                      `key`,
                                      `apikey`,
                                      `last_signin`,
                                      `auth_fails`,
                                      `locked_until`,
                                      `signin_count`,
                                      `username`,
                                      `password`,
                                      `fingerprint`,
                                      `domain`,
                                      `title`,
                                      `name`,
                                      `nickname`,
                                      `avatar`,
                                      `email`,
                                      `code`,
                                      `phones`,
                                      `verify_code`,
                                      `verifiedon`,
                                      `mailings`,
                                      `role`,
                                      `roles_id`,
                                      `priority`,
                                      `type`,
                                      `keywords`,
                                      `latitude`,
                                      `longitude`,
                                      `accuracy`,
                                      `offset_latitude`,
                                      `offset_longitude`,
                                      `cities_id`,
                                      `states_id`,
                                      `countries_id`,
                                      `redirect`,
                                      `location`,
                                      `language`,
                                      `gender`,
                                      `birthday`,
                                      `country`,
                                      `commentary`,
                                      `description`,
                                      `badges`,
                                      `website`,
                                      `leaders_id`,
                                      `credits`,
                                      `timezone`,
                                      `webpush`,

                               FROM   `users` '.$where, $execute);
        }

        return $retval;

    }catch(Exception $e){
        throw new BException('users_get(): Failed', $e);
    }
}



/*
 * Get user unique key. If none exist, create one on the fly
 */
function user_get_key($user = null, $force = false){
    try{
        if(!$user){
            $user = $_SESSION['user']['username'];
        }

        if(is_numeric($user)){
            $dbuser = sql_get('SELECT `id`, `email`, `key` FROM `users` WHERE `id`    = :id    AND `status` IS NULL', array(':id'    => $user));

        }else{
            $dbuser = sql_get('SELECT `id`, `email`, `key` FROM `users` WHERE `email` = :email AND `status` IS NULL', array(':email' => $user));
        }

        if(!$dbuser){
            throw new BException(tr('user_get_key(): Specified user ":user" does not exist', array(':user' => str_log($user))), 'not-exists');
        }

        if(!$dbuser['key'] or $force){
            $dbuser['key']           = unique_code();
            $_SESSION['user']['key'] = $dbuser['key'];

            sql_query('UPDATE `users`

                       SET    `key` = :key

                       WHERE  `id`  = :id',

                       array(':id'  => $dbuser['id'],
                             ':key' => $dbuser['key']));
        }

        $timestamp = microtime(true);

        return array('user'      => $dbuser['email'],
                     'timestamp' => $timestamp,
                     'key'       => hash('sha256', $dbuser['key'].SEED.$timestamp));

    }catch(Exception $e){
        throw new BException(tr('user_get_key(): Failed'), $e);
    }
}



/*
 * Check if the key supplied for the specified users id matches
 */
function user_check_key($user, $key, $timestamp){
    try{
// :TODO: Make the future and past time differences configurable
        $future = 10;
        $past   = 1800;

        if(is_numeric($user)){
            $dbkey = sql_get('SELECT `key` FROM `users` WHERE `id`    = :id'   , 'key', array(':id'    => $user));

        }elseif(is_string($user)){
            $dbkey = sql_get('SELECT `key` FROM `users` WHERE `email` = :email', 'key', array(':email' => $user));

        }else{
            /*
             * Assume user is an array and contains at least the key
             */
            $dbkey = $user['key'];
        }

        if(!$dbkey){
            /*
             * This user doesn't exist, or doesn't have a key yet!
             */
            return false;
        }

        $diff = microtime(true) - $timestamp;

        if($diff > $past){
            /*
             * More then N seconds differece between timestamps is NOT allowed
             */
            notify(array('code'    => 'invalid',
                         'groups'  => 'developers',
                         'title'   => tr('Received invalid request'),
                         'message' => tr('user_check_key(): Received user key check request with timestamp of ":timestamp" seconds which is larger than the maximum past time of ":max" seconds', array(':max' => $past, ':timestamp' => $timestamp))));

            return false;
        }

        if(-$diff > $future){
            /*
             * More then N seconds differece between timestamps is NOT allowed
             */
            notify(array('code'    => 'invalid',
                         'groups'  => 'developers',
                         'title'   => tr('Received invalid request'),
                         'message' => tr('user_check_key(): Received user key check request with timestamp of ":timestamp" seconds which is larger than the maximum future time of ":max" seconds', array(':max' => $future, ':timestamp' => $timestamp))));

            return false;
        }

        $dbkey = hash('sha256', $dbkey.SEED.$timestamp);

        return $dbkey === $key;

    }catch(Exception $e){
        throw new BException(tr('user_check_key(): Failed'), $e);
    }
}



/*
 * Return HTML hidden input form fields containing user key data
 */
function user_key_form_fields($user = null, $prefix = ''){
    try{
        if(!$user){
            $user = $_SESSION['user']['email'];
        }

        $key    = user_get_key($user);

        $retval = ' <input type="hidden" class="'.$prefix.'timestamp" name="'.$prefix.'timestamp" value="'.$key['timestamp'].'">
                    <input type="hidden" class="'.$prefix.'user" name="'.$prefix.'user" value="'.$key['user'].'">
                    <input type="hidden" class="'.$prefix.'key" name="'.$prefix.'key" value="'.$key['key'].'">';

        return $retval;

    }catch(Exception $e){
        throw new BException(tr('user_key_form_fields(): Failed'), $e);
    }
}



/*
 *
 */
function user_get_from_key($user, $key, $timestamp){
    try{
        $user = user_get($user);

        if(user_check_key($user, $key, $timestamp)){
            return $user;
        }

        return false;

    }catch(Exception $e){
        throw new BException(tr('user_get_from_key(): Failed'), $e);
    }
}



/*
 *
 */
function user_key_or_redirect($user, $key = null, $timestamp = null, $redirect = null){
    global $_CONFIG;

    try{
        if(is_array($user)){
            /*
             * Assume we got an array, like $_POST, and extract data from there
             */
            $redirect  = $key;
            $key       = isset_get($user['key']);
            $timestamp = isset_get($user['timestamp']);
            $user      = isset_get($user['user']);
        }

        $user = user_get($user);

        if(user_check_key($user, $key, $timestamp)){
            return $user;
        }

        if(!$redirect){
            $redirect = $_CONFIG['redirects']['signin'];
        }

        /*
         * Send JSON redirect. json_reply() will end script, so no break needed
         */
        json_reply(isset_get($redirect, $_CONFIG['url_prefix']), 'signin');

    }catch(Exception $e){
        throw new BException(tr('user_get_from_key(): Failed'), $e);
    }
}



/*
 * Test if the given password is strong enough
 */
function user_test_password($password){
    global $_CONFIG;

    try{
// :TODO: Implement!!
under_construction();
        return $password;

    }catch(Exception $e){
        throw new BException(tr('user_test_password(): Failed'), $e);
    }
}



/*
 * Generate a new API key for the (optionally specified) user
 */
function user_update_apikey($users_id = null){
    global $_CONFIG;

    try{
        if(!$users_id){
            $users_id = $_SESSION['user']['id'];
        }

        $apikey = substr(hash('sha512', uniqid().microtime(true)), 0, 64);

        sql_query('UPDATE `users`

                   SET    `apikey` = :apikey

                   WHERE  `id`      = :id',

                   array(':id'     => cfi($users_id),
                         ':apikey' => cfm($apikey)));

        return $apikey;

    }catch(Exception $e){
        throw new BException(tr('user_update_apikey(): Failed'), $e);
    }
}



/*
 * Lock the specified user account
 *
 * This function will unlock the user with the specified $users_id by setting the users' status to "locked"
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package user
 * @see user_lock()
 * @note The users status will be set to "locked", no matter what it was before. If the user, for example, was deleted and as such had status "deleted", this information will be gone
 * @version 1.26.1: Added documentation, updated to return update result
 * @example
 * code
 * user_lock(1);
 * showdie($result);
 * /code
 *
 * @param natural $users_id The id for the user to  be locked
 * @return boolean True if the user was locked, false if not. If the user was not locked, the user already had status "locked"
 */
function user_lock($users_id){
    try{
        $r = sql_query('UPDATE    `users`

                        LEFT JOIN `employees`

                        SET       `users`.`status`     = "locked"
                                  `employees`.`status` = "locked"

                        WHERE     `users`.`id`         = :id',

                        array(':id' => cfi($users_id)));

        return $r->rowCount();

    }catch(Exception $e){
        throw new BException(tr('user_lock(): Failed'), $e);
    }
}



/*
 * Unlock the specified user account
 *
 * This function will unlock the user with the specified $users_id by setting the users' status to NULL
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package user
 * @see user_lock()
 * @note The specified user account will have status NULL and with that be completely accessible again
 * @version 1.26.1: Added function and documentation
 * @example
 * code
 * user_unlock(1);
 * showdie($result);
 * /code
 *
 * @param natural $users_id The id for the user to  be unlocked
 * @return boolean True if the user was unlocked, false if not. If the user was not unlocked, the user already had status NULL
 */
function user_unlock($users_id){
    try{
        $r = sql_query('UPDATE    `users`

                        LEFT JOIN `employees`

                        SET       `users`.`status`     = NULL
                                  `employees`.`status` = NULL

                        WHERE     `users`.`id`         = :id',

                        array(':id' => cfi($users_id)));

        return (boolean) $r->rowCount();

    }catch(Exception $e){
        throw new BException(tr('user_unlock(): Failed'), $e);
    }
}



/*
 * Update all reference codes for the specified user
 */
function user_update_reference_codes($user, $allow_duplicate_reference_codes = null){
    global $_CONFIG;

    try{
        sql_query('DELETE FROM `users_reference_codes` WHERE `users_id` = :users_id', array(':users_id' => cfi($user['id'])));

        if(empty($user['reference_codes'])){
            return false;
        }

        /*
         * Allow duplicate reference codes? Default to configured value
         */
        if($allow_duplicate_reference_codes === null){
            $allow_duplicate_reference_codes = $_CONFIG['users']['duplicate_reference_codes'];
        }

        $fail   = array();
        $codes  = preg_split('/[^0-9]/', $user['reference_codes']);
        $codes  = array_unique($codes);
        $insert = sql_prepare('INSERT INTO `users_reference_codes` (`users_id`, `code`)
                               VALUES                              (:users_id , :code )');

        foreach($codes as $code){
            if(empty($code)) continue;

            if($allow_duplicate_reference_codes){
                $exists = false;

            }else{
                $exists = sql_get('SELECT `users_id` FROM `users_reference_codes` WHERE `code` = :code', true, array(':code' => $code));
            }

            if($exists){
                $user        = sql_get('SELECT `id`, `username`, `email`, `name`, `nickname` FROM `users` WHERE `id` = :id', array(':id' => $exists));
                $fail[$code] = tr('Users reference code ":code" is already in use by user ":user", and has been removed from the list of reference codes', array(':code' => $code, ':user' => name($user)));

            }else{
                $insert->execute(array(':users_id' => cfi($user['id']),
                                       ':code'     => cfm($code)));
            }
        }

        if($fail){
            /*
             * One or multiple reference codes failed to be added because they
             * were already in use.
             */
            throw new BException($fail, 'exists', array_keys($fail));
        }

        return count($codes);

    }catch(Exception $e){
        throw new BException(tr('user_update_reference_codes(): Failed'), $e);
    }
}



/*
 * Update the location information for the specified user from the specified
 * latitude / longitude, or (if specified) the geo data
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package user
 *
 * @param params $user
 * @param integer $user[id]
 * @param integer $user[users_id]
 * @param float $user[longitude]
 * @param float $user[latitude]
 * @param integer $user[cities_id]
 * @param integer $user[states_id]
 * @param integer $user[countries_id]
 * @return
 */
function user_update_location($user){
    global $_CONFIG;
    load_libs('geo');
    try{
        /*
         * Validate data
         */
        array_ensure($user, 'id,users_id,latitude,longitude,accuracy,cities_id,states_id,countries_id');

        if($user['users_id']){
            $user['id'] = $user['users_id'];
        }

        if(!$user['id']){
            throw new BException(tr('user_update_location(): No users id or users_id specified'), 'not-specified');
        }
        $geo     = geo_validate($user);
        $execute = array(':id' => $user['id']);

        /*
         * Set automated location offset?
         */
        if(!$_CONFIG['user']['location']['max_offset']){
            if($_CONFIG['user']['location']['max_offset'] === 0){
                /*
                 * Location offset system has no offset
                 */
                $execute[':offset_latitude']  = $user['latitude'];
                $execute[':offset_longitude'] = $user['longitude'];

            }else{
                /*
                 * Disable location offset system
                 */
                $execute[':offset_latitude']  = null;
                $execute[':offset_longitude'] = null;
            }

        }else{
            /*
             * Automatically generate a random offset
             */
            if(!is_natural($_CONFIG['user']['location']['max_offset'], 0)){
                throw new BException(tr('user_update_location(): Configured max offset ":max_offset" is invalid', array(':max_offset' => $_CONFIG['user']['location']['max_offset'])), 'invalid');
            }

            if($_CONFIG['user']['location']['max_offset'] > 10000){
                notify(array('code'    => 'invalid',
                             'groups'  => 'developers',
                             'title'   => tr('Problematic configuration'),
                             'message' => tr('user_update_location(): Configured max offset ":max_offset" is very high! this may lead to problems!', array(':max_offset' => $_CONFIG['user']['location']['max_offset']))));
            }

// :TODO: Implement random offset
            $execute[':offset_latitude']  = $user['latitude'];
            $execute[':offset_longitude'] = $user['longitude'];
        }

        /*
         * Detect city / state / country information
         *
         * NOTE: The detection will be done on the latitude / longitude
         * information, NOT the offset pairs! This is because though we do want
         * to have an offset to hide the exact location, we do not want to have
         * the user appear in the completely wrong city!
         */
        if($_CONFIG['user']['location']['detect']){
            if(!$user['cities_id'] or !$user['states_id'] or !$user['countries_id']){
                if(!geo_loaded()){
                    throw new BException(tr('user_update_location(): Failed to auto detect city, state, and country because the geo database has not yet been loaded'), 'not-available');
                }

                $city = geo_get_city_from_location($geo['latitude'], $geo['longitude']);

                $execute[':cities_id']    = $city['id'];
                $execute[':states_id']    = $city['states_id'];
                $execute[':countries_id'] = $city['countries_id'];
            }

        }else{
            /*
             * Do not detect city / state / country, rely on what has been specified
             */
            $execute[':cities_id']    = $geo['id'];
            $execute[':states_id']    = $geo['states_id'];
            $execute[':countries_id'] = $geo['countries_id'];
        }

        $execute[':latitude']  = $user['latitude'];
        $execute[':longitude'] = $user['longitude'];
        $execute[':accuracy']  = $user['accuracy'];

        /*
         * Update all user's location information
         */
        sql_query('UPDATE `users`

                   SET    `accuracy`         = :accuracy,
                          `latitude`         = :latitude,
                          `longitude`        = :longitude,
                          `offset_latitude`  = :offset_latitude,
                          `offset_longitude` = :offset_longitude,
                          `cities_id`        = :cities_id,
                          `states_id`        = :states_id,
                          `countries_id`     = :countries_id

                   WHERE  `id`               = :id',

                   $execute);

    }catch(Exception $e){
        throw new BException(tr('user_update_location(): Failed'), $e);
    }
}



/*
 * OBSOLETE WRAPPERS BELOW
 */
function users_validate($user, $options = array()){
    return user_validate($user, $options);
}
?>
