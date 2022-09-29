<?php
/*
 * API library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
 * @package api
 *
 * @return void
 */
function api_library_init() {
    try{
        load_config('api');

    }catch(Exception $e) {
        throw new CoreException('api_library_init(): Failed', $e);
    }
}



/*
 * Validate API account
 *
 * This function will validate all relevant fields in the specified $account array
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package api
 *
 * @param params $account
 * @param string $account[customer]
 * @param string $account[server]
 * @param string $account[name]
 * @param string $account[description]
 * @param string $account[baseurl]
 * @param string $account[apikey]
 * @param string $account[verify_ssl]
 * @return string HTML for a categories select box within the specified parameters
 */
function api_validate_account($account) {

    try{
        load_libs('validate,seo');

        $v = new ValidateForm($account, 'customer,server,environment,name,description,baseurl,apikey,verify_ssl');

        $v->isNotEmpty ($account['name'],  tr('Please specify an API account name'));
        $v->hasMaxChars($account['name'], 64, tr('Please ensure the API account name has less than 64 characters'));

        $v->isNotEmpty ($account['apikey'], tr('Please specify an API key'));

        if(strlen($account['apikey']) != 64) {
            $v->setError(tr('Please ensure the API key has exactly 64 characters'));
        }

        $v->isNotEmpty($account['environment'], tr('Please specify an API account environment'));
        $v->hasMinChars($account['environment'], 3, tr('Please ensure the API account environment has at least 3 characters'));
        $v->hasMaxChars($account['environment'], 32, tr('Please ensure the API account environment has less than 32 characters'));
        $v->isRegex($account['environment'], '/^[a-z0-9-]+$/', tr('Please ensure the API account environment is valid, must be lower case, can only contain a-z, 0-9, - and no spaces'));

        if($account['description']) {
            $v->hasMinChars($account['description'],   16, tr('Please ensure the API account description has at least 16 characters, or empty'));
            $v->hasMaxChars($account['description'], 2047, tr('Please ensure the API account description has less than 2047 characters'));

        } else {
            $account['description'] = '';
        }

        $v->isNotEmpty ($account['baseurl']     , tr('No API root api URL specified'));
        $v->hasMaxChars($account['baseurl'], 127, tr('Please ensure the API root api URL has less than 127 characters'));

        $v->isNotEmpty ($account['customer']    , tr('Please specify a customer'));
        $v->isNotEmpty ($account['server']      , tr('Please specify a server'));

        $account['servers_id']   = sql_get('SELECT `id` FROM `servers`   WHERE `seodomain` = :seodomain AND `status` IS NULL', true, array(':seodomain' => $account['server']));
        $account['customers_id'] = sql_get('SELECT `id` FROM `customers` WHERE `seoname`   = :seoname   AND `status` IS NULL', true, array(':seoname'   => $account['customer']));

        if(!$account['servers_id']) {
            $v->setError(tr('Specified server ":server" does not exist', array(':server' => $account['server'])));
        }

        if(!$account['customers_id']) {
            $v->setError(tr('Specified customer ":customer" does not exist', array(':customer' => $account['customer'])));
        }

        $exists = sql_exists('api_accounts', 'name', $account['name'], $account['id']);

        if($exists) {
            $v->setError(tr('The API account ":account" already exists', array(':account' => $account['name'])));
        }

        $account['seoname']    = seo_unique($account['name'], 'api_accounts', $account['id']);
        $account['verify_ssl'] = (boolean) $account['verify_ssl'];

        if($account['verify_ssl']) {
            if(!strstr($account['baseurl'], 'https://')) {
                $v->setError(tr('The "Verify SSL" option can only be used for base URLs using the HTTPS protocol'));
            }
        }

        $v->isValid();

        return $account;

    }catch(Exception $e) {
        throw new CoreException(tr('api_validate_account(): Failed'), $e);
    }
}



/*
 * Test API account
 */
function api_test_account($account) {

    try{
        sql_query('UPDATE `api_accounts` SET `status` = "testing" WHERE `seoname` = :seoname', array(':seoname' => $account));
        $result = api_call_base($account, '/test');
        sql_query('UPDATE `api_accounts` SET `status` = NULL      WHERE `seoname` = :seoname', array(':seoname' => $account));

        return $result;

    }catch(Exception $e) {
        throw new CoreException(tr('api_test_account(): Failed'), $e);
    }
}



/*
 * Ensure that the remote IP is on the API whitelist and
 */
function api_whitelist() {
    global $_CONFIG;

    try{
        if(empty($_CONFIG['api']['whitelist'])) {
            if(!in_array($_SERVER['REMOTE_ADDR'], $_CONFIG['api']['whitelist'])) {
                $block = true;
            }
        }

        if(empty($block) and !empty($_CONFIG['api']['blacklist'])) {
            if(in_array($_SERVER['REMOTE_ADDR'], $_CONFIG['api']['blacklist'])) {
                $block = true;
            }
        }

        if(isset($block)) {
            throw new CoreException(tr('api_whitelist(): The IP ":ip" is not allowed access', array(':ip' => $_SERVER['REMOTE_ADDR'])), 'access-denied');
        }

        return true;

    }catch(Exception $e) {
        throw new CoreException('api_encode(): Failed', $e);
    }
}



/*
 * Encode the given data for use with BASE APIs
 */
function api_encode($data) {
    try{
        if(is_array($data)) {
            $data = str_replace('@', '\@', $data);

        } elseif(is_string($data)) {
            foreach($listing as &$value) {
                $value = str_replace('@', '\@', $value);
            }

            unset($value);

        } else {
            throw new CoreException(tr('api_encode(): Specified data is datatype ":type", only string and array are allowed', array(':type' => gettype($data))), $e);
        }

        return $data;

    }catch(Exception $e) {
        throw new CoreException('api_encode(): Failed', $e);
    }
}



/*
 *
 */
function api_authenticate($api_key = null) {
    global $_CONFIG;

    try{
        if($_CONFIG['production']) {
            /*
             * This is a production platform, only allow JSON API key
             * authentications over a secure connection
             */
            if((PROTOCOL !== 'https://') and !empty($_CONFIG['production'])) {
                throw new CoreException(tr('api_authenticate(): No API key authentication allowed on unsecure connections over non HTTPS connections'), 'ssl-required');
            }
        }

        if(empty($api_key)) {
            /*
             * Search for the API key
             */
            if(!isset($_POST['api_key'])) {
                if(!isset($_POST['apikey'])) {
                    throw new CoreException(tr('api_authenticate(): No api key specified'), 'not-specified');
                }

                $api_key = isset_get($_POST['apikey']);

            } else {
                $api_key = isset_get($_POST['api_key']);
            }
        }

        /*
         * Authenticate using the supplied key
         */
        if(empty($_CONFIG['api']['apikey'])) {
            /*
             * Check in database if the authorization key exists
             */
            $user = sql_get('SELECT * FROM `users` WHERE `apikey` = :apikey', array(':apikey' => $api_key));

            if(!$user) {
                throw new CoreException(tr('api_authenticate(): Specified apikey does not exist'), 'access-denied');
            }

        } else {
            /*
             * Use one system wide API key
             */
            if($api_key !== $_CONFIG['api']['apikey']) {
                throw new CoreException(tr('api_authenticate(): Specified auth key does not match configured api key'), 'access-denied');
            }
        }

        $session_id = api_generate_key();
        $session    = api_insert_session(array('createdby'   => $user['id'],
                                               'ip'          => $_SERVER['REMOTE_ADDR'],
                                               'sessions_id' => $session_id,
                                               'apikey'      => $api_key));

        return $session['sessions_id'];

    }catch(Exception $e) {
        throw new CoreException('api_authenticate(): Failed', $e);
    }
}



/*
 *
 */
function api_start_session($session_id) {
    global $_CONFIG, $core;

    try{
        load_libs('validate,user');

        if(!$session_id) {
            switch($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    $session_id = isset_get($_GET['sessions_id']);
                    break;

                case 'POST':
                    $session_id = isset_get($_POST['sessions_id']);
            }
        }

        $v = new ValidateForm();
        $v->isNotEmpty($session_id, tr('Please specify a sessions_id'));
        $v->hasMaxChars($session_id, 64, tr('Please specify a valid sessions_id'));
        $v->hasMaxChars($session_id, 64, tr('Please specify a valid sessions_id'));
        $v->isValid();

        /*
         * Check session token
         */
        if(empty($session_id)) {
            throw new CoreException(tr('api_start_session(): No session key specified'), 'not-specified');
        }

        /*
         * Yay, we have an valid token, check if it has a session
         */
        $_SESSION = api_get_session($session_id);

        if(!$_SESSION) {
            throw new CoreException(tr('api_start_session(): The specified session_id key ":session_id" does not exist', array(':session_id' => $session_id)), 'access-denied');
        }

        if($_SESSION['closedon']) {
            throw new CoreException(tr('api_start_session(): The session for the session_id key ":session_id" has already been closed', array(':session_id' => $session_id)), 'sign-in');
        }

        /*
         * Update the last start column to now
         */
        sql_query('UPDATE `api_sessions` SET `last` = NOW() WHERE `id` = :id', array(':id' => $_SESSION['id']));

        $_SESSION['user'] = user_get($_SESSION['createdby']);

        if(!$_SESSION['user']) {
            throw new CoreException(tr('api_start_session(): Session ":sessions_id" was created by users_id ":users_id" but that apparently does not exist', array(':sessions_id' => $session_id, ':users_id' => $_SESSION['createdby'])), 'not-exists');
        }

        return $_SESSION;

    }catch(Exception $e) {
        throw new CoreException('api_start_session(): Failed', $e);
    }
}



/*
 * Close the currently open session
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package api
 * @version 2.7.99: Added function and documentation
 *
 * @param params $session The session params
 * @param params $session[createdby]
 * @param params $session[key]
 * @param params $session[ip]
 * @param params $session[apikey]
 * @return params The specified $session params with the database id added
 */
function api_stop_session() {
    global $_CONFIG, $core;

    try{
        if(!isset($core->register['session'])) {
            throw new CoreException(tr('api_stop_session(): Currently there is no open session'), 'sign-in');
        }

        sql_query('UPDATE `api_sessions`

                   SET    `closedon`    = NOW()

                   WHERE  `sessions_id` = :sessions_id',

                   array('sessions_id' => isset_get($core->register['session']['sessions_id'])));

        unset($core->register['session']);

        return true;

    }catch(Exception $e) {
        throw new CoreException('api_close_session(): Failed', $e);
    }
}



/*
 * Register session open or close in the api_session database table
 */
function api_call($call, $result = null) {
    static $time, $id;

    try{
        if($result) {
            sql_query('UPDATE `api_calls`

                       SET    `time`   = :time,
                              `result` = :result

                       WHERE  `id`     = :id',

                       array(':time'   => microtime(true) - $time,
                             ':result' => $result,
                             ':id'     => $id));

        } else {
            sql_query('INSERT INTO `api_calls` (`sessions_id`, `call`)
                       VALUES                  (:sessions_id , :call )',

                       array('sessions_id' => isset_get($_SESSION['user']['id']),
                             'ip'          => $_SESSION['api']['session_id'],
                             'apikey'      => $api_key));

            $time = microtime(true);
            $id   = sql_insert_id();
        }

    }catch(Exception $e) {
        throw new CoreException('api_call(): Failed', $e);
    }
}



/*
 * Encode the given data from a BASE API back to its original form
 */
function api_decode($data) {
    try{
        if(is_array($data)) {
            $data = str_replace('\@', '@', $data);

        } elseif(is_string($data)) {
            foreach($listing as &$value) {
                $value = str_replace('\@', '@', $value);
            }

            unset($value);

        } else {
            throw new CoreException(tr('api_decode(): Specified data is datatype ":type", only string and array are allowed', array(':type' => gettype($data))), $e);
        }

        return $data;

    }catch(Exception $e) {
        throw new CoreException('api_decode(): Failed', $e);
    }
}



/*
 * Make an API call to a BASE framework
 */
function api_call_base($account, $call, $data = array(), $files = null) {
    global $_CONFIG;

    try{
        load_libs('curl');

        if(empty($account)) {
            throw new CoreException(tr('api_call_base(): No API specified'), 'not-specified');
        }

        /*
         * Get account information
         */
        $account_data = sql_get('SELECT `id`, `baseurl`, `apikey`, `verify_ssl` FROM `api_accounts` WHERE `seoname` = :seoname', array(':seoname' => $account));

        if(!$account_data) {
            throw new CoreException(tr('api_call_base(): Specified API account ":account" does not exist', array(':account' => $account)), 'not-exists');
        }

        /*
         * Check if we have cached session key
         */
        if(empty($_SESSION['api']['session_keys'][$account])) {
            /*
             * Auto authenticate
             */
            try{
                $json = curl_get(array('url'            => Strings::startsNotWith($account_data['baseurl'], '/').'/authenticate',
                                       'posturlencoded' => true,
                                       'verify_ssl'     => isset_get($account_data['verify_ssl']),
                                       'getheaders'     => false,
                                       'post'           => array('api_key' => $account_data['apikey'])));

                if(!$json) {
                    throw new CoreException(tr('api_call_base(): Authentication on API account ":account" returned no response', array(':account' => $account)), 'empty');
                }

                if(!$json['data']) {
                    throw new CoreException(tr('api_call_base(): Authentication on API account ":account" returned no data in response', array(':account' => $account)), 'empty');
                }

                $result = json_decode_custom($json['data']);

                if(isset_get($result['result']) !== 'OK') {
                    throw new CoreException(tr('api_call_base(): Authentication on API account ":account" returned result ":result"', array('":account' => $account, ':result' => $result['result'])), 'failed', $result);
                }

                if(empty($result['data']['token'])) {
                    throw new CoreException(tr('api_call_base(): Authentication on API account ":account" returned ok result but no token', array('":account' => $account)), 'failed');
                }

                $_SESSION['api']['session_keys'][$account] = $result['data']['token'];
                $signin = true;

            }catch(Exception $e) {
                $url = Strings::startsNotWith($account_data['baseurl'], '/').'/authenticate';

                switch($e->getCode()) {
                    case 'HTTP403':
                        throw new CoreException(tr('api_call_base(): [403] API authentication URL ":url" gave access denied', array(':url' => $url)), $e);

                    case 'HTTP404':
                        throw new CoreException(tr('api_call_base(): [404] API authentication URL ":url" was not found', array(':url' => $url)), $e);

                    case 'HTTP500':
                        throw new CoreException(tr('api_call_base(): [500] API server encountered an internal server error on authentication URL ":url"', array(':url' => $url)), $e);

                    case 'HTTP503':
                        throw new CoreException(tr('api_call_base(): [503] API server is in maintenance mode on authentication URL ":url"', array(':url' => $url)), $e);

                    default:
                        throw new CoreException(tr('api_call_base(): [:code] Failed to authenticate on authentication URL ":url"', array(':code' => $e->getCode(), ':url' => $url)), $e);
                }
            }
        }

        $data['sessions_id'] = $_SESSION['api']['session_keys'][$account];

        if($files) {
            /*
             * Add the specified files
             */
            $count = 0;

            foreach(Arrays::force($files) as $url => $file) {
                $data['file'.$count++] =  curl_file_create($file, file_mimetype($file), str_replace('/', '_', str_replace('_', '', $url)));
            }
        }

        /*
         * Make the API call
         */
        try{
            $json = curl_get(array('url'        => Strings::startsNotWith($account_data['baseurl'], '/').Strings::startsWith($call, '/'),
                                   'verify_ssl' => isset_get($account_data['verify_ssl']),
                                   'getheaders' => false,
                                   'post'       => $data));

            if(!$json) {
                throw new CoreException(tr('api_call_base(): API call ":call" on account ":account" returned no response', array(':account' => $account, ':call' => $call)), 'empty');
            }

            if(!$json['data']) {
                throw new CoreException(tr('api_call_base(): API call ":call" on account ":account" returned no data in response', array(':account' => $account, ':call' => $call)), 'empty');
            }

            $result = json_decode_custom($json['data']);

            switch(isset_get($result['result'])) {
                case 'OK':
                    /*
                     * All ok!
                     */
                    return isset_get($result['data']);

                case 'SIGNIN':
                    // FALLTHROUGH
                case 'SIGN-IN':
                    /*
                     * Session key is not valid
                     * Remove session key, signin, and try again
                     */
                    if(isset($signin)) {
                        /*
                         * Oops, we already tried to signin, and that signin failed
                         * with a signin request which, in this case, would cause
                         * endless recursion
                         */
                        throw new CoreException(tr('api_call_base(): API call ":call" on ":api" required auto signin but that failed with a request to signin as well. Stopping to avoid endless signin loop', array(':api' => $account, ':call' => $call)), 'failed');
                    }

                    unset($_SESSION['api']['session_keys'][$account]);
                    return api_call_base($account, $call, $data);

                default:
                    throw new CoreException(tr('api_call_base(): API call ":call" on account ":account" returned result ":result"', array(':account' => $account, ':call' => $call, ':result' => $result['result'])), 'failed', $result);
            }

        }catch(Exception $e) {
            $url = Strings::startsNotWith($account_data['baseurl'], '/').Strings::startsWith($call, '/');

            switch($e->getCode()) {
                case 'HTTP403':
                    throw new CoreException(tr('api_call_base(): [403] API URL ":url" gave access denied', array(':url' => $url)), $e);

                case 'HTTP404':
                    throw new CoreException(tr('api_call_base(): [404] API URL ":url" was not found', array(':url' => $url)), $e);

                case 'HTTP500':
                    throw new CoreException(tr('api_call_base(): [500] API server encountered an internal server error on URL ":url"', array(':url' => $url)), $e);

                case 'HTTP503':
                    throw new CoreException(tr('api_call_base(): [503] API server is in maintenance mode on URL ":url"', array(':url' => $url)), $e);

                default:
                    throw new CoreException(tr('api_call_base(): [:code] Failed to call API on URL ":url"', array(':code' => $e->getCode(), ':url' => $url)), $e);
            }
        }

    }catch(Exception $e) {
//show(isset_get($json));
//showdie($e);

        if($account_data) {
            sql_query('UPDATE `api_accounts` SET `last_error` = :last_error WHERE `id` = :id', array(':id' => $account_data['id'], ':last_error' => print_r($e, true)));
        }

//show(isset_get($json));
//show(isset_get($result));
//showdie($e);
        throw new CoreException(tr('api_call_base(): Failed for account ":account"', array(':account' => $account)), $e);
    }
}



/*
 * Return a new, cryptographically secure API key in hexadecimal format
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package api
 * @note: Since this function returns the API key in HEX format, the amount of bytes used will be twice the bytes specified!
 * @version 2.7.98: Added function and documentation
 *
 * @return string The generated API key
 */
function api_generate_key($bytes = 32) {
    try{
        return bin2hex(random_bytes($bytes));

    }catch(Exception $e) {
        throw new CoreException(tr('api_generate_key(): Failed'), $e);
    }
}



/*
 * Returns the session data for the specified sessions_id key supplied by the client
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package api
 * @version 2.7.99: Added function and documentation
 *
 * @param string $sessions_id The session_id specified by the client
 * @return params A parameter array containing all the session data
 */
function api_get_session($sessions_id) {
    try{
        $session = sql_get('SELECT `id`,
                                   `createdon`,
                                   `createdby`,
                                   `closedon`,
                                   `last`,
                                   `ip`,
                                   `sessions_id`,
                                   `apikey`

                            FROM   `api_sessions`

                            WHERE  `sessions_id` = :sessions_id',

                            array(':sessions_id' => $sessions_id));

        return $session;

    }catch(Exception $e) {
        throw new CoreException(tr('api_get_session(): Failed'), $e);
    }
}



/*
 * Insert the specified session params array into the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package api
 * @version 2.7.99: Added function and documentation
 *
 * @param params $session The session params
 * @param params $session[createdby]
 * @param params $session[key]
 * @param params $session[ip]
 * @param params $session[apikey]
 * @return params The specified $session params with the database id added
 */
function api_insert_session($session) {
    try{
        sql_query('INSERT INTO `api_sessions` (`createdby`, `sessions_id`, `ip`, `apikey`)
                   VALUES                     (:createdby , :sessions_id , :ip , :apikey )',

                   array(':createdby'   => $session['createdby'],
                         ':ip'          => $session['ip'],
                         ':sessions_id' => $session['sessions_id'],
                         ':apikey'      => $session['apikey']));

        $session['id'] = sql_insert_id();

        return $session;

    }catch(Exception $e) {
        throw new CoreException(tr('api_insert_session(): Failed'), $e);
    }
}



/*
 * Close all API sessions for the specified users_id
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package api
 * @version 2.7.99: Added function and documentation
 *
 * @param natural $users_id The ID of the user for which all sessions must be closed
 * @return void
 */
function api_close_all($users_id) {
    try{
        sql_query('UPDATE `api_sessions` SET `closedon` = NOW WHERE `createdby` = :createdby AND `closedon` IS NULL', array(':createdby' => $users_id));

    }catch(Exception $e) {
        throw new CoreException(tr('api_close_all(): Failed'), $e);
    }
}
