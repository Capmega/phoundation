<?php
/*
 * Users library
 *
 * This library contains user functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@ingiga.com>
 */



 /*
  * Get user data with MC write through cache
  */
function user_data($id) {
    global $_CONFIG;

    try{
        load_libs('memcached');

        $key  = 'USER-'.$id;
        $user = mc_get($key);

        if(empty($user)) {
            $user = sql_get('SELECT * FROM users WHERE id = :id', array(':id' => cfi($id)));
            mc_put($key, $user);

        } else {
            return $user;
        }

    }catch(Exception $e){
        throw new bException('user_data(): Failed', $e);
    }
}



/*
 * Make sure some avatar is being displayed
 */
function user_avatar($avatar, $type) {
    global $_CONFIG;

    try{
        if(empty($avatar)) {
            return $_CONFIG['avatars']['default'].'-'.$type.'.jpg';
        }

        return $avatar.'-'.$type.'.jpg';

    }catch(Exception $e){
        throw new bException('user_avatar(): Failed', $e);
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
                throw new bException('user_update_avatar(): Invalid user specified');
            }

            $user = $user['id'];
        }

        sql_query('UPDATE `users` SET `avatar` = :avatar WHERE `id` = :id', array(':avatar' => cfm($avatar), ':user' => $user));

        return $avatar;

    }catch(Exception $e){
        throw new bException('user_update_avatar(): Failed', $e);
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
                throw new bException('user_find_avatar(): Invalid user specified');
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
        throw new bException('user_find_avatar(): Failed', $e);
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
                throw new bException(tr('user_add_to_group(): No user specified'), 'not-specified');
            }

            if(is_numeric($user)){
                $users_id = sql_get('SELECT `id` FROM `users` WHERE `id` = :id', 'id', array(':id' => $user));

            }else{
                $users_id = sql_get('SELECT `id` FROM `users` WHERE (`username` = :username OR `email` = :email)', 'id', array(':username' => $user, ':email' => $user));

                if(!$users_id){
                    throw new bException(tr('user_add_to_group(): Specified user ":user" does not exist', array(':user' => $user)), 'not-exist');
                }
            }
        }

        sql_query('DELETE FROM `users_groups` WHERE `users_id` = :users_id', array(':users_id' => $users_id));

        if($groups){
            return user_add_to_group($user, $groups, false);
        }

        return 0;

    }catch(Exception $e){
        throw new bException('user_update_groups(): Failed', $e);
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
                throw new bException(tr('user_add_to_group(): No user specified'), 'not-specified');
            }

            if(is_numeric($user)){
                $users_id = sql_get('SELECT `id` FROM `users` WHERE `id` = :id', 'id', array(':id' => $user));

            }else{
                $users_id = sql_get('SELECT `id` FROM `users` WHERE (`username` = :username OR `email` = :email)', 'id', array(':username' => $user, ':email' => $user));

                if(!$users_id){
                    throw new bException(tr('user_add_to_group(): Specified user ":user" does not exist', array(':user' => $user)), 'not-exist');
                }
            }
        }

        /*
         * Validate group
         */
        if(!$groups){
            throw new bException(tr('user_add_to_group(): No groups specified'), 'not-specified');
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
                throw new bException(tr('user_add_to_group(): Specified group ":group" does not exist', array(':group' => $groups)), 'not-exist');
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
        throw new bException('user_add_to_group(): Failed', $e);
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
                throw new bException(tr('user_remove_from_group(): No user specified'), 'not-specified');
            }

            if(is_numeric($user)){
                $users_id = sql_get('SELECT `id` FROM `users` WHERE `id` = :id', 'id', array(':id' => $user));

            }else{
                $users_id = sql_get('SELECT `id` FROM `users` WHERE (`username` = :username OR `email` = :email)', 'id', array(':username' => $user, ':email' => $user));

                if(!$users_id){
                    throw new bException(tr('user_remove_from_group(): Specified user ":user" does not exist', array(':user' => $user)), 'not-exist');
                }
            }
        }

        /*
         * Validate group
         */
        if(!$groups){
            throw new bException(tr('user_remove_from_group(): No groups specified'), 'not-specified');
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
                throw new bException(tr('user_remove_from_group(): Specified group ":group" does not exist', array(':group' => $groups)), 'not-exist');
            }
        }

        /*
         * Delete user from specified group
         */
        $r = sql_query('DELETE FROM `users_groups` WHERE `users_id` = :users_id AND `groups_id` = :groups_id', 'users_id', array(':users_id'  => $users_id, ':groups_id' => $groups_id));

        return $r->rowCount();

    }catch(Exception $e){
        throw new bException('user_remove_from_group(): Failed', $e);
    }
}



/*
 * `cate the specified user with the specified password
 */
function user_authenticate($username, $password, $captcha = null){
    global $_CONFIG;

    try{
        /*
         * Data validation and get user data
         */
        if(!is_scalar($username)){
            throw new bException('user_authenticate(): Specified username is not valid', 'invalid');
        }

        $user = sql_get('SELECT *,
                                `locked_until` - UTC_TIMESTAMP() AS `locked_left`

                         FROM   `users`

                         WHERE  `status`   IS NULL
                         AND   (`email`    = :email
                         OR     `username` = :username)',

                         array(':email'    => $username,
                               ':username' => $username));

        if(!$user){
            log_database(tr('user_authenticate(): Specified user account ":username" not found', array(':username' => $username)), 'authentication/notfound');
            throw new bException(tr('user_authenticate(): Specified user account ":username" not found', array(':username' => $username)), 'notfound');
        }

        if($user['status'] !== null){
            throw new bException(tr('user_authenticate(): Specified user account has status ":status" and cannot be authenticated', array(':status' => $user['status'])), 'inactive');
        }



        /*
         * Check authentication failures and account locking
         */
        if($user['locked_left'] > 0){
            /*
             * Only lock if configured to do so
             */
            if($_CONFIG['security']['authentication']['auto_lock_fails'] and $_CONFIG['security']['authentication']['auto_lock_time']){
                throw new bException(tr('user_authenticate(): Specified user account is locked'), 'locked', array('locked' => $user['locked_left']));
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

                throw new bException(tr('user_authenticate(): Specified user account is locked'), 'locked', array('locked' => $user['locked_left']));
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
            log_database(tr('user_authenticate(): Specified user account ":username" has status ":type" and cannot be authenticated', array(':username' => str_log($username), ':type' => str_log($user['type']))), 'authentication/notfound');
            throw new bException(tr('user_authenticate(): Specified user account has status ":type" and cannot be authenticated', array(':type' => $user['type'])), 'type');
        }



        /*
         * Check captcha
         */
        $failures = $_CONFIG['security']['authentication']['captcha_failures'] - 1;

        if($failures < 0){
            $failures = 0;
        }

        $captcha_required = user_authentication_requires_captcha($failures);

        if($captcha_required){
// :TODO: There might be a configuration issue where $_CONFIG['captcha']['type'] is disabled, but $captcha_required does require captcha..
            load_libs('captcha');

            try{
                captcha_verify_response($captcha);

            }catch(Exception $e){
                throw new bException(tr('user_authenticate(): CAPTCHA test failed'), 'captcha');
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

        try{
            $password = get_hash($password, $algorithm, false);

        }catch(Exception $e){
            switch($e->getCode()){
                case 'unknown-algorithm':
                    throw new bException(tr('user_authenticate(): User account ":name" has an unknown algorithm ":algorithm" specified', array(':user' => name($user), ':algorithm' => $algorithm)), $e);

                default:
                    throw new bException(tr('user_authenticate(): Password hashing failed for user account ":name"', array(':user' => name($user))), $e);
            }
        }

        if($password != str_rfrom($user['password'], '*')){
            log_database(tr('user_authenticate(): Specified password does not match stored password for user ":username"', array(':username' => $username)), 'authentication/failed');
            throw new bException(tr('user_authenticate(): Specified password does not match stored password'), 'password');
        }



        /*
         * Apply IP locking system
         */
        if($_CONFIG['security']['signin']['ip_lock'] and (PLATFORM == 'http')){
            include(__DIR__.'/handlers/user_ip_lock.php');
        }



        /*
         * Check if authentication for this user is limited to a specific domain
         */
        if(($_CONFIG['whitelabels']['enabled'] === true) and $user['domain']){
            if($user['domain'] !== $_SERVER['HTTP_HOST']){
                throw new bException(tr('user_autohenticate(): User account ":name" is limited to authenticate only in domain ":domain"', array(':name' => name($user), ':domain' => $user['domain'])), 'domain-limit');
            }
        }



        /*
         * Use two factor authentication, the user has to authenticate by SMS as well
         */
        if($_CONFIG['security']['signin']['two_factor']){
            if(empty($user['phone'])){
                throw new bException('user_autohenticate(): Two factor authentication impossible for user account "'.$user['id'].' / '.$user['name'].'" because no phone is registered', 'twofactor_nophone');
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

        log_database(tr('user_authenticate(): Authenticated user account ":username"', array(':username' => $username)), 'authentication/success');
        user_log_authentication($username, $user['id'], $captcha_required);

        $user['authenticated'] = true;
        return $user;

    }catch(Exception $e){
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
                throw new bException('user_authenticate(): Your password was updated on "'.str_log($date->format($_CONFIG['formats']['human_date'])).'"', 'oldpassword');
            }
        }

        throw new bException('user_authenticate(): Failed', $e);
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

                   array(':status'            => $status,
                         ':createdby'         => isset_get($_SESSION['user']['id']),
                         ':users_id'          => $users_id,
                         ':username'          => $username,
                         ':failed_reason'     => str_truncate(trim(str_from(isset_get($failed_reason), '():')), 127),
                         ':captcha_required'  => (boolean) $captcha_required,
                         ':ip'                => isset_get($_SERVER['REMOTE_ADDR'])));

    }catch(Exception $e){
        throw new bException('user_log_authentication(): Failed', $e);
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
        throw new bException('user_log_authentication(): Failed', $e);
    }
}



/*
 * Do a user signin
 */
function user_signin($user, $extended = false, $redirect = null, $html_flash = null) {
    global $_CONFIG;

    try{
        if($redirect === null){
            if(isset_get($_GET['redirect'])){
                $redirect = $_GET['redirect'];

            }elseif(isset_get($_GET['redirect'])){
                $redirect = $_SESSION['redirect'];
            }
        }

        if(!is_array($user)){
            throw new bException('user_signin(): Specified user variable is not an array', 'invalid');
        }

        /*
         * HTTP signin requires cookie support and an already active session!
         * Shell signin requires neither
         */
        if((PLATFORM == 'http') and (empty($_COOKIE) or !session_id())){
            throw new bException('user_signin(): This user has no active session or no session id, so probably has no cookies', 'cookies-required');
        }

        if(session_status() == PHP_SESSION_ACTIVE){
            /*
             * Reset session data
             */
            if($_CONFIG['security']['signin']['destroy_session']){
                session_destroy();
                session_start();
                session_reset_domain();
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
                log_error($e, 'avatar');
            }
        }

        $_SESSION['user']         = $user;
        $_SESSION['user']['role'] = sql_get('SELECT `roles`.`name` FROM `roles` WHERE `id` = :id', 'name', array(':id' => $_SESSION['user']['roles_id']));

        if($html_flash){
            html_flash_set(isset_get($html_flash['text']), isset_get($html_flash['type']), isset_get($html_flash['class']));
        }

        if($redirect and (PLATFORM == 'http')){
            /*
             * Do not redirect to signin page
             */
            if($redirect == $_CONFIG['redirects']['signin']){
                $redirect = $_CONFIG['redirects']['index'];
            }

            session_redirect('http', $redirect);
        }

        log_database(tr('user_signin(): Signed in user ":user"', array(':user' => user_name($user))), 'signin/success');

    }catch(Exception $e){
        log_database(tr('user_signin(): User sign in failed for user ":user" because ":message"', array(':user' => user_name($user), ':message' => $e->getMessage())), 'signin/failed');
        throw new bException('user_signin(): Failed', $e);
    }
}



/*
 * Do a user signout
 */
function user_signout() {
    global $_CONFIG;

    try{
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

    }catch(Exception $e){
        throw new bException('user_signout(): Failed', $e);
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
        throw new bException('user_create_extended_session(): Failed', $e);
    }
}



/*
 * Set a users verification code
 */
function user_set_verification_code($user){
    try{
        if(is_array($user)){
            if(!empty($user['id'])){
                $user = $user['id'];

            }elseif(!empty($user['email'])){
                $user = $user['email'];
            }
        }

        /*
         * Create a unique code.
         */
        $code = uniqid(ENVIRONMENT, true);

        /*
         * Update user validation with that code
         */
        if(is_numeric($user)){
            sql_query('UPDATE `users` SET `validated` = :code WHERE `id` = :id', array(':id'   => cfi($user),
                                                                                       ':code' => cfm($code)));

        }elseif(is_string($user)){
            sql_query('UPDATE `users` SET `validated` = :code WHERE `email` = :email', array(':email' => cfm($user),
                                                                                             ':code'  => cfm($code)));

        }else{
            throw new bException('user_set_verification_code(): Invalid user specified');
        }

        if(!sql_affected_rows()){
            throw new bException(tr('user_set_verification_code(): Specified user ":user" does not exist', array(':user' => $user)));
        }

        return $code;

    }catch(Exception $e){
        throw new bException('user_set_verification_code(): Failed', $e);
    }
}



/*
 * Returns if some of the userdata is blacklisted or not
 */
function user_check_blacklisted($name){
    try{
//:TODO: Implement. THROW EXCEPTION IF BLACKLISTED!

    }catch(Exception $e){
        throw new bException('user_blacklisted(): Failed', $e);
    }
}



/*
 * Wrapper for user_signup
 */
function user_create($params){
    try{
        return user_signup($params);

    }catch(Exception $e){
        throw new bException('user_create(): Failed', $e);
    }
}



/*
 * Add a new user
 */
function user_signup($params){
    global $_CONFIG;

    try{
        if(empty($params['password']) and (isset_get($params['status']) !== '_new')){
            throw new bException(tr('user_signup(): Please specify a password'), 'not-specified');
        }

        sql_query('INSERT INTO `users` (`status`, `createdby`, `username`, `password`, `name`, `email`, `roles_id`, `role`)
                   VALUES              (:status , :createdby , :username , :password , :name , :email , :roles_id , :role )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':username'  => get_null(isset_get($params['username'])),
                         ':status'    => isset_get($params['status']),
                         ':name'      => isset_get($params['name']),
                         ':password'  => ((isset_get($params['status']) === '_new') ? '' : get_hash($params['password'], $_CONFIG['security']['passwords']['hash'])),
                         ':email'     => get_null(isset_get($params['email'])),
                         ':role'      => get_null(isset_get($params['role'])),
                         ':roles_id'  => get_null(isset_get($params['roles_id']))));

        return sql_insert_id();

    }catch(Exception $e){
        throw new bException('user_signup(): Failed', $e);
    }
}


// :DELETE: There should not be an update function for tables!!!! This should be implemented in the pages directly
///*
// * Update specified user
// */
//function user_update($params){
//    try{
//        array_params($params);
//        array_default($params, 'validated', false);
//
//        if(!is_array($params)){
//            throw new bException('user_update(): Invalid params specified', 'invalid');
//        }
//
//        if(empty($params['email'])){
//            throw new bException('user_update(): No email specified', 'not-specified');
//        }
//
//        if(empty($params['id'])){
//            throw new bException('user_update(): No users id specified', 'not-specified');
//        }
//
//        if(empty($params['name'])){
//            if(!empty($params['username'])){
//                $params['name'] = isset_get($params['username']);
//
//            }else{
//                $params['name'] = isset_get($params['email']);
//            }
//        }
//
//        if(!$user = user_get($params['id'])){
//            throw new bException('user_update(): User with id "'.str_log($params['id']).'" does not exists', 'not-exist');
//        }
//
//        $exists = sql_get('SELECT `id`, `email`, `username`
//                           FROM   `users`
//                           WHERE  `id`      != :id
//                           AND   (`email`    = :email
//                           OR     `username` = :username )',
//
//                           array(':id'       => $params['id'],
//                                 ':email'    => $params['email'],
//                                 ':username' => $params['username']));
//
//        if($exists){
//            if($exists['username'] == $params['username']){
//                throw new bException('user_update(): Another user already has the username "'.str_log($params['username']).'"', 'exists');
//
//            }else{
//                throw new bException('user_update(): Another user already has the email "'.str_log($params['email']).'"', 'exists');
//            }
//        }
//
//        $r = sql_query('UPDATE `users`
//                        SET    `username`  = :username,
//                               `name`      = :name,
//                               `email`     = :email,
//                               `language`  = :language,
//                               `gender`    = :gender,
//                               `latitude`  = :latitude,
//                               `longitude` = :longitude,
//                               `country`   = :country
//                        WHERE  `id`        = :id',
//
//                        array(':id'        => isset_get($params['id']),
//                              ':username'  => isset_get($params['username']),
//                              ':name'      => $params['name'],
//                              ':email'     => $params['email'],
//                              ':language'  => isset_get($params['language']),
//                              ':gender'    => isset_get($params['gender']),
//                              ':latitude'  => isset_get($params['latitude']),
//                              ':longitude' => isset_get($params['longitude']),
//                              ':country'   => isset_get($params['country'])));
//
//        return $r->rowCount();
//
//    }catch(Exception $e){
//        throw new bException('user_update(): Failed', $e);
//    }
//}



/*
 * Update user password. This can be used either by the current user, or by an
 * admin user updating the users password
 */
function user_update_password($params, $current = true){
    global $_CONFIG;

    try{
        array_params($params);
        array_default($params, 'validated'             , false);
        array_default($params, 'check_banned_passwords', true);

        if(!is_array($params)){
            throw new bException(tr('user_update_password(): Invalid params specified'), 'invalid');
        }

        if(empty($params['id'])){
            throw new bException(tr('user_update_password(): No users id specified'), 'not-specified');
        }

        if(empty($params['password'])){
            throw new bException(tr('user_update_password(): Please specify a password'), 'not-specified');
        }

        if(empty($params['password2'])){
            throw new bException(tr('user_update_password(): No validation password specified'), 'not-specified');
        }

        /*
         * Check if password is equal to password2
         */
        if($params['password'] != $params['password2']){
            throw new bException(tr('user_update_password(): Specified password does not match the validation password'), 'mismatch');
        }

        /*
         * Check if password is NOT equal to cpassword
         */
        if($current and ($params['password'] == $params['cpassword'])){
            throw new bException(tr('user_update_password(): Specified new password is the same as the current password'), 'same-as-current');
        }

        if($current){
            if(empty($params['cpassword'])){
                throw new bException(tr('user_update_password(): Please specify the current password'), 'not-specified');
            }

            user_authenticate($_SESSION['user']['email'], $params['cpassword']);
        }

        /*
         * Check password strength
         */
        $strength = user_password_strength($params['password'], $params['check_banned_passwords']);

        /*
         * Prepare new password
         */
        $password = get_hash($params['password'], $_CONFIG['security']['passwords']['hash']);

        /*
         * Ensure that this new password is not the same as one of the N
         * previous passwords in N previous days
         */
        if($_CONFIG['security']['passwords']['unique_days']){
            $list = sql_query(' SELECT   `password`

                               FROM     `passwords`

                               WHERE    `users_id` = :users_id
                               AND      `createdon` > DATE_SUB(UTC_TIMESTAMP(), INTERVAL :days DAY)

                               ORDER BY `id`

                               LIMIT    '.$_CONFIG['security']['passwords']['unique_updates'],

                               array(':users_id' => $_SESSION['user']['id'],
                                     ':days'     => $_CONFIG['security']['passwords']['unique_days']));

            while($previous = sql_fetch($list)){
                if($previous == $password){
                    /*
                     * This password has been used before
                     */
                    throw new bException(tr('user_update_password(): The specified password has already been used before'), 'not-specified');
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
                throw new bException(tr('user_update_password(): The specified users_id "'.str_log($params['id']).'" does not exist'), 'not-exist');
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

                   array(':createdby' => $_SESSION['user']['id'],
                         ':users_id'  => $params['id'],
                         ':password'  => $password));

        return $strength;

    }catch(Exception $e){
        throw new bException('user_update_password(): Failed', $e);
    }
}



/*
 * Return requested data for specified user
 */
function user_get($user = null){
    global $_CONFIG;

    try{
        if($user){
            if(!is_scalar($user)){
                throw new bException(tr('user_get(): Specified user data ":data" is not scalar', array(':data' => $user)), 'invalid');
            }

            if(is_numeric($user)){
                $retval = sql_get('SELECT    `users`.*,

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

                                   WHERE     `users`.`id` = :id
                                   AND       `users`.`status` IS NULL',

                                   array(':id' => $user));

            }else{
                $retval = sql_get('SELECT    `users`.*,

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
                                   AND       `users`.`status` IS NULL',

                                   array(':email'    => $user,
                                         ':username' => $user));
            }

        }else{
            /*
             * Pre-create a new user
             */
            $retval = sql_get('SELECT    `users`.*,

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
                $id = user_signup(array('status' => '_new'));
                return user_get(null);
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('user_get(): Failed', $e);
    }
}



/*
 * Return either (in chronological order) name, username, or email for the user
 */
function user_name($user = null, $key_prefix = ''){
    try{
        /*
         * Compatibility
         */
        if($key_prefix === null){
            throw new bException('user_name(): WARNING! user_name() ARGUMENTS HAVE CHANGED, $guest is no longer supported, and has been removed. Adjust your function call accordingly');
        }

        if($user){
            if(is_scalar($user)){
                if(!is_numeric($user)){
                    /*
                     * String, assume its a username
                     */
                    return $user;
                }

                /*
                 * This is not a user assoc array, but a user ID.
                 * Fetch user data from DB, then treat it as an array
                 */
                if(!$user = sql_get('SELECT `name` `username`, `email` FROM `users` WHERE `id` = :id', array(':id' => $user))){
                   throw new bException('user_name(): Specified user id "'.str_log($user).'" does not exist', 'not-exist');
                }
            }

            if(!is_array($user)){
                throw new bException(tr('user_name(): Invalid data specified, please specify either user id, name, or an array containing username, email and or id'), 'invalid');
            }

            $user = not_empty(isset_get($user[$key_prefix.'name']), isset_get($user[$key_prefix.'username']), isset_get($user[$key_prefix.'email']), isset_get($user[$key_prefix.'id']));

            if($user){
                return $user;
            }
        }

        /*
         * No user data found, assume guest user.
         */
        return tr('Guest');

    }catch(Exception $e){
        throw new bException(tr('user_name(): Failed'), $e);
    }
}



// :DELETE: has_rights() can now perform the same functionality
///*
// * Returns true either if the user has the specified right and not the devil right, or the "god" right
// */
//function user_has_rights($user, $right) {
//    try{
//        if(!isset($user['rights'])){
//            $user['rights'] = user_load_rights($user);
//        }
//
//        if(!empty($user['rights']['god'])){
//            return true;
//        }
//
//        return !empty($user['rights'][$right]) and empty($user['rights']['devil']);
//
//    }catch(Exception $e){
//        throw new bException('user_has_rights(): Failed', $e);
//    }
//}



/*
 * Load the rights for the specified user
 */
function user_load_rights($user){
    try{
        if(!is_numeric($user)){
            if(!is_array($user)){
                throw new bException('user_load_rights(): Invalid user, please specify either users_id or user array with id', 'invalid');
            }

            $user = isset_get($user['id']);
        }

        return sql_list('SELECT `name`,
                                `name` AS `right`

                         FROM   `users_rights`

                         WHERE  `users_id` = :users_id',

                         array(':users_id' => $user));

    }catch(Exception $e){
        throw new bException('user_load_rights(): Failed', $e);
    }
}



/*
 * Make the current session the specified user
 * NOTE: Since this function is rarely used, it it implemented by a handler
 */
function user_switch($users_id, $redirect = '/'){
    include(__DIR__.'/handlers/user_switch.php');
}



/*
 * Find the password field when browser password saving has been disabled
 */
function user_process_signin_fields($post){
    global $_CONFIG;

    try{
        if(empty($_CONFIG['security']['signin']['save_password'])){
            /*
             * Clear username and password fields, to ensure they are not being used
             */
            unset($post['username']);
            unset($post['password']);

            /*
             * Password field is password********
             */
            foreach(array_max($post) as $key => $value){
                if((substr($key, 0, 8) == 'password') and (strlen($key) == 16)){
                    /*
                     * This is the password field, set it.
                     */
                    $post['password'] = $post[$key];
                    unset($post[$key]);

                }elseif((substr($key, 0, 8) == 'username') and (strlen($key) == 16)){
                    /*
                     * This is the username field, set it.
                     */
                    $post['username'] = $post[$key];
                    unset($post[$key]);
                    continue;
                }

                if(isset($post['username']) and isset($post['password'])){
                    break;
                }
            }
        }

        return $post;

    }catch(Exception $e){
        throw new bException('user_process_signin_fields(): Failed', $e);
    }
}



/*
 * Update the rights for this user.
 * Requires a user array with $user['id'], and $user['roles_id']
 */
function user_update_rights($user){
    try{
        if(empty($user['id'])){
            throw new bException('user_update_rights(): Cannot update rights, no user specified', 'not-specified');
        }

        if(empty($user['roles_id'])){
            throw new bException('user_update_rights(): Cannot update rights, no role specified', 'not-specified');
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
        throw new bException('user_update_rights(): Failed', $e);
    }
}



/*
 * Simple function to test password strength
 * Found on http://www.phpro.org/examples/Password-Strength-Tester.html
 *
 * Rewritten for use in BASE project by Sven Oostenbrink
 */
// :TODO: Improve. This function uses some bad algorithms that could cause false high ranking passwords
function user_password_strength($password, $check_banned = true){
    try{
        /*
         * Get the length of the password
         */
        $strength = 10;
        $length   = strlen($password);

        if($length < 8){
            if(!$length){
                throw new bException(tr('user_password_strength(): No password specified'), 'no-password');
            }

            throw new bException(tr('user_password_strength(): Specified password is too short'), 'short-password');
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
        if($length >= 40){
            $strength += 40;

        }elseif($length >= 30){
            $strength += 30;

        }elseif($length >= 20){
            $strength += 20;

        }elseif($length >= 12){
            $strength += 15;

        }elseif($length >= 8){
            $strength += 10;
        }

        ///*
        // * Get the upper case letters in the password
        // */
        //preg_match_all('/[A-Z]/', $password, $matches);
        //$strength += (count($matches[0]) / 2);
        //
        ///*
        // * Get the lower case letters in the password
        // */
        //preg_match_all('/[a-z]/', $password, $matches);
        //$strength += (count($matches[0]) / 2);

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
         * Strength is a number 1-10;
         */
        $strength = (($strength > 99) ? 99 : $strength);
        $strength = floor(($strength / 10) + 1);

        if($strength < 4){
            throw new bException(tr('user_password_strength(): The specified password is too weak, please use a better password. Use more characters, add numbers, special characters, caps characters, etc. On a scale of 1-10, current strength is ":strength"', array(':strength' => $strength)), 'weak');
        }

        return $strength;

    }catch(Exception $e){
        throw new bException('user_password_strength(): Failed', $e);
    }
}



/*
 *
 */
function user_password_banned($password){
    global $_CONFIG;

    try{
        if(($password == $_SESSION['domain']) or ($password == str_until($_SESSION['domain'], '.'))){
            throw new bException(tr('user_password_banned(): The default password is not allowed to be used'), 'short-password');
        }

// :TODO: Add more checks

    }catch(Exception $e){
        throw new bException('user_password_banned(): Failed', $e);
    }
}



/*
 * Validate the specified user. Validations is done in sections, and sections
 * can be disabled if needed
 */
function user_validate($user, $sections = array()){
    global $_CONFIG;

    try{
        array_default($sections, 'password'           , true);
        array_default($sections, 'validation_password', true);
        array_default($sections, 'role'               , true);

        load_libs('validate');
        $v = new validate_form($user, 'name,username,nickname,email,password,password2,redirect,description,role,roles_id,commentary,gender,latitude,longitude,language,country,fb_id,fb_token,gp_id,gp_token,ms_id,ms_token_authentication,ms_token_access,tw_id,tw_token,yh_id,yh_token,status,validated,avatar,phones,type,domain');

        $user['email2'] = $user['email'];
        $user['terms']  = true;

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

        if(!$user['username']){
            if(!$user['email']){
                $v->setError(tr('Please provide at least an email or username'));

            }else{
                $v->isValidEmail($user['email'], tr('Please provide a valid email address'));
            }

        }else{
            $v->isAlphaNumeric($user['username'], tr('Please provide a valid username, it can only contain letters and numbers'));
        }

        if($user['nickname']){
            $v->hasMinChars($user['name'], 2, tr('Please ensure that the users nick name has a minimum of 2 characters'));
        }

        if($user['name']){
            $v->hasMinChars($user['name'], 2, tr('Please ensure that the users name has a minimum of 2 characters'));
        }

        if($sections['role']){
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
                    if((PLATFORM == 'http') and !has_rights('god')){
                        $v->setError(tr('The god role can only be assigned or changed by users with god role themselves'));
                    }
                }
            }
        }

        if($sections['password']){
            if(empty($user['password'])){
                $v->setError(tr('Please specify a password'));

            }else{
                /*
                 * Check password strength
                 */
                if($sections['validation_password']){
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
         * Ensure that the username and or email are not in use
         */
        $query   = 'SELECT `email`,
                           `username`

                    FROM   `users`';

        $where   = array();
        $execute = array();

        if($user['username']){
            $where[] = ' `username` = :username ';
            $execute[':username'] = $user['username'];
        }

        if($user['email']){
            $where[] = ' `email` = :email ';
            $execute[':email'] = $user['email'];
        }

        /*
         * Only continue testing user if there were no validation errors so far
         */
        $v->isValid();

        if(empty($user['id'])){
            $where   = ' WHERE ('.implode(' OR ', $where).')';

        }else{
            $where   = ' WHERE  `id`      != :id
                         AND   ('.implode(' OR ', $where).')';

            $execute[':id'] = $user['id'];
        }

        $exists = sql_get($query.$where, $execute);

        if($exists){
            if($user['username'] and ($exists['username'] == $user['username'])){
                $v->setError(tr('The username ":username" is already in use by another user', array(':username' => str_log($user['username']))));
            }

            if($user['email'] and ($exists['email'] == $user['email'])){
                $v->setError(tr('The email ":email" is already in use by another user', array(':email' => str_log($user['email']))));
            }
        }

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
                if($v->isValidPhonenumber($phone, tr('The phone number ":phone" is not valid', array(':phone' => $phone)))){
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
        throw new bException(tr('user_validate(): Failed'), $e);
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
            $dbuser = sql_get('SELECT `id`, `username`, `key` FROM `users` WHERE `id`       = :id       AND `status` IS NULL', array(':id' => $user));

        }else{
            $dbuser = sql_get('SELECT `id`, `username`, `key` FROM `users` WHERE (`username` = :username OR `email` = :email) AND `status` IS NULL', array(':username' => $user, ':email' => $user));
        }

        if(!$dbuser){
            throw new bException(tr('user_get_key(): Specified user ":user" does not exist', array(':user' => str_log($user))), 'not-exist');
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

        return array('user'      => $dbuser['username'],
                     'timestamp' => $timestamp,
                     'key'       => hash('sha256', $dbuser['key'].SEED.$timestamp));

    }catch(Exception $e){
        throw new bException(tr('user_get_key(): Failed'), $e);
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
            $dbkey = sql_get('SELECT `key` FROM `users` WHERE `id`        = :id'      , 'key', array(':id' => $user));

        }elseif(is_string($user)){
            $dbkey = sql_get('SELECT `key` FROM `users` WHERE `username` = :username', 'key', array(':username' => $user));

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
            notify('user_check_key()', tr('Received user key check request with timestamp of ":timestamp" seconds which is larger than the maximum past time of ":max" seconds', array(':max' => $past, ':timestamp' => $timestamp)), 'security');
            return false;
        }

        if(-$diff > $future){
            /*
             * More then N seconds differece between timestamps is NOT allowed
             */
            notify('user_check_key()', tr('Received user key check request with timestamp of ":timestamp" seconds which is larger than the maximum future time of ":max" seconds', array(':max' => $future, ':timestamp' => $timestamp)), 'security');
            return false;
        }

        $dbkey = hash('sha256', $dbkey.SEED.$timestamp);

        return $dbkey === $key;

    }catch(Exception $e){
        throw new bException(tr('user_check_key(): Failed'), $e);
    }
}



/*
 * Return HTML hidden input form fields containing user key data
 */
function user_key_form_fields($user = null, $prefix = ''){
    try{
        if(!$user){
            $user = $_SESSION['user']['username'];
        }

        $key    = user_get_key($user);

        $retval = ' <input type="hidden" class="'.$prefix.'timestamp" name="'.$prefix.'timestamp" value="'.$key['timestamp'].'">
                    <input type="hidden" class="'.$prefix.'user" name="'.$prefix.'user" value="'.$key['user'].'">
                    <input type="hidden" class="'.$prefix.'key" name="'.$prefix.'key" value="'.$key['key'].'">';

        return $retval;

    }catch(Exception $e){
        throw new bException(tr('user_key_form_fields(): Failed'), $e);
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
        throw new bException(tr('user_get_from_key(): Failed'), $e);
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
        load_libs('json');
        json_reply(isset_get($redirect, $_CONFIG['root']), 'signin');

    }catch(Exception $e){
        throw new bException(tr('user_get_from_key(): Failed'), $e);
    }
}



/*
 * Test if the given password is strong enough
 */
function user_test_password($password){
    global $_CONFIG;

    try{
// :TODO: Implement!!
notify('not-implemented', 'user_test_password() has not yet been implemented!!');
        return $password;

    }catch(Exception $e){
        throw new bException(tr('user_test_password(): Failed'), $e);
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

                   array(':id'     => $users_id,
                         ':apikey' => $apikey));

        return $apikey;

    }catch(Exception $e){
        throw new bException(tr('user_update_apikey(): Failed'), $e);
    }
}



/*
 * OBSOLETE WRAPPERS BELOW
 */
function users_validate($user, $sections = array()){
    return user_validate($user, $sections);
}
?>
