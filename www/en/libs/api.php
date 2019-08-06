<?php
/*
 * API library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package api
 *
 * @return void
 */
function api_library_init(){
    try{
        load_config('api');

    }catch(Exception $e){
        throw new BException('api_library_init(): Failed', $e);
    }
}



/*
 * Validate API account
 *
 * This function will validate all relevant fields in the specified $account array
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
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
function api_validate_account($account){

    try{
        load_libs('validate,seo');

        $v = new ValidateForm($account, 'customer,server,name,description,baseurl,apikey,verify_ssl');

        $v->isNotEmpty ($account['name']    ,  tr('Please specify an API account name'));
        $v->hasMaxChars($account['name'], 64, tr('Please ensure the API account name has less than 64 characters'));

        $v->isNotEmpty ($account['apikey'] ,  tr('Please specify an API key'));

        if(strlen($account['apikey']) != 64){
            $v->setError(tr('Please ensure the API key has exactly 64 characters'));
        }

        if($account['description']){
            $v->hasMinChars($account['description'],   16, tr('Please ensure the API account description has at least 16 characters, or empty'));
            $v->hasMaxChars($account['description'], 2047, tr('Please ensure the API account description has less than 2047 characters'));

        }else{
            $account['description'] = '';
        }

        $v->isNotEmpty ($account['baseurl']     , tr('No API root api URL specified'));
        $v->hasMaxChars($account['baseurl'], 127, tr('Please ensure the API root api URL has less than 127 characters'));

        $v->isNotEmpty ($account['customer']    , tr('Please specify a customer'));
        $v->isNotEmpty ($account['server']      , tr('Please specify a server'));

        $account['servers_id']   = sql_get('SELECT `id` FROM `servers`   WHERE `seodomain` = :seodomain AND `status` IS NULL', true, array(':seodomain' => $account['server']));
        $account['customers_id'] = sql_get('SELECT `id` FROM `customers` WHERE `seoname`     = :seoname     AND `status` IS NULL', true, array(':seoname'     => $account['customer']));

        if(!$account['servers_id']){
            $v->setError(tr('Specified server ":server" does not exist', array(':server' => $account['server'])));
        }

        if(!$account['customers_id']){
            $v->setError(tr('Specified customer ":customer" does not exist', array(':customer' => $account['customer'])));
        }

        $exists = sql_exists('api_accounts', 'name', $account['name'], $account['id']);

        if($exists){
            $v->setError(tr('The API account ":account" already exists', array(':account' => $account['name'])));
        }

        $account['seoname']    = seo_unique($account['name'], 'api_accounts', $account['id']);
        $account['verify_ssl'] = (boolean) $account['verify_ssl'];

        if($account['verify_ssl']){
            if(!strstr($account['baseurl'], 'https://')){
                $v->setError(tr('The "Verify SSL" option can only be used for base URLs using the HTTPS protocol'));
            }
        }

        $v->isValid();

        return $account;

    }catch(Exception $e){
        throw new BException(tr('api_validate_account(): Failed'), $e);
    }
}



/*
 * Test API account
 */
function api_test_account($account){

    try{
        sql_query('UPDATE `api_accounts` SET `status` = "testing" WHERE `seoname` = :seoname', array(':seoname' => $account));
        $result = api_call_base($account, '/test');
        sql_query('UPDATE `api_accounts` SET `status` = NULL      WHERE `seoname` = :seoname', array(':seoname' => $account));

        return $result;

    }catch(Exception $e){
        throw new BException(tr('api_test_account(): Failed'), $e);
    }
}



/*
 * Ensure that the remote IP is on the API whitelist and
 */
function api_whitelist(){
    global $_CONFIG;

    try{
        if(empty($_CONFIG['api']['whitelist'])){
            if(!in_array($_SERVER['REMOTE_ADDR'], $_CONFIG['api']['whitelist'])){
                $block = true;
            }
        }

        if(empty($block) and !empty($_CONFIG['api']['blacklist'])){
            if(in_array($_SERVER['REMOTE_ADDR'], $_CONFIG['api']['blacklist'])){
                $block = true;
            }
        }

        if(isset($block)){
            throw new BException(tr('api_whitelist(): The IP ":ip" is not allowed access', array(':ip' => $_SERVER['REMOTE_ADDR'])), 'access-denied');
        }

        return true;

    }catch(Exception $e){
        throw new BException('api_encode(): Failed', $e);
    }
}



/*
 * Encode the given data for use with BASE APIs
 */
function api_encode($data){
    try{
        if(is_array($data)){
            $data = str_replace('@', '\@', $data);

        }elseif(is_string($data)){
            foreach($listing as &$value){
                $value = str_replace('@', '\@', $value);
            }

            unset($value);

        }else{
            throw new BException(tr('api_encode(): Specified data is datatype ":type", only string and array are allowed', array(':type' => gettype($data))), $e);
        }

        return $data;

    }catch(Exception $e){
        throw new BException('api_encode(): Failed', $e);
    }
}



/*
 *
 */
function api_authenticate($apikey){
    global $_CONFIG;

    try{
        if($_CONFIG['production']){
            /*
             * This is a production platform, only allow JSON API key
             * authentications over a secure connection
             */
            if((PROTOCOL !== 'https://') and !empty($_CONFIG['production'])){
                throw new BException(tr('api_authenticate(): No API key authentication allowed on unsecure connections over non HTTPS connections'), 'ssl-required');
            }
        }

        if(empty($apikey)){
            throw new BException(tr('api_authenticate(): No auth key specified'), 'not-specified');
        }

        /*
         * Authenticate using the supplied key
         */
        if(empty($_CONFIG['api']['apikey'])){
            /*
             * Check in database if the authorization key exists
             */
            $user = sql_get('SELECT * FROM `users` WHERE `apikey` = :apikey', array(':apikey' => $apikey));

            if(!$user){
                throw new BException(tr('api_authenticate(): Specified apikey is not valid'), 'access-denied');
            }

        }else{
            /*
             * Use one system wide API key
             */
            if($apikey !== $_CONFIG['api']['apikey']){
                throw new BException(tr('api_authenticate(): Specified auth key is not valid'), 'access-denied');
            }
        }

        /*
         * Yay, auth worked, create session and send client the session token
         */
        if(session_id()){
            if($_CONFIG['api']['signin_reset_session']){
                session_destroy();
                session_start();
            }

        }else{
            session_start();
        }

        session_regenerate_id();

        sql_query('INSERT INTO `api_sessions` (`createdby`, `ip`, `apikey`)
                   VALUES                     (:createdby , :ip , :apikey )',

                   array('createdby' => isset_get($_SESSION['user']['id']),
                         'ip'        => $_SERVER['REMOTE_ADDR'],
                         'apikey'    => $apikey));

        $_SESSION['user'] = $user;
        $_SESSION['api']  = array('session_start' => time(),
                                  'session_id'    => sql_insert_id());

        return session_id();

    }catch(Exception $e){
        throw new BException('api_authenticate(): Failed', $e);
    }
}



/*
 *
 */
function api_start_session($sessionkey){
    global $_CONFIG;

    try{
        /*
         * Check session token
         */
        if(empty($sessionkey)){
            throw new BException(tr('api_start_session(): No auth key specified'), 'not-specified');
        }

        /*
         * Yay, we have an actual token, create session!
         */
        session_write_close();
        session_id(isset_get($_POST['PHPSESSID']));
        session_start();

        if(empty($_SESSION['api']['session_start'])){
            /*
             * Not a valid session!
             */
            session_destroy();

            json_reply(tr('api_start_session(): Specified token ":token" has no session', array(':token' => isset_get($_POST['PHPSESSID']))), 'signin');
        }

        return session_id();

    }catch(Exception $e){
        throw new BException('api_start_session(): Failed', $e);
    }
}



/*
 *
 */
function api_stop_session(){
    global $_CONFIG;

    try{
        /*
         * Yay, we have an actual token, create session!
         */
        sql_query('UPDATE `api_sessions`

                   SET    `closedon` = NOW()

                   WHERE  `id`       = :id',

                   array('id' => isset_get($_SESSION['api']['sessions_id'])));

        session_destroy();
        return true;

    }catch(Exception $e){
        throw new BException('api_close_session(): Failed', $e);
    }
}



/*
 * Register session open or close in the api_session database table
 */
function api_call($call, $result = null){
    static $time, $id;

    try{
        if($result){
            sql_query('UPDATE `api_calls`

                       SET    `time`   = :time,
                              `result` = :result

                       WHERE  `id`     = :id',

                       array(':time'   => microtime(true) - $time,
                             ':result' => $result,
                             ':id'     => $id));

        }else{
            sql_query('INSERT INTO `api_calls` (`sessions_id`, `call`)
                       VALUES                  (:sessions_id , :call )',

                       array('sessions_id' => isset_get($_SESSION['user']['id']),
                             'ip'          => $_SESSION['api']['session_id'],
                             'apikey'      => $apikey));

            $time = microtime(true);
            $id   = sql_insert_id();
        }

    }catch(Exception $e){
        throw new BException('api_session(): Failed', $e);
    }
}



/*
 * Encode the given data from a BASE API back to its original form
 */
function api_decode($data){
    try{
        if(is_array($data)){
            $data = str_replace('\@', '@', $data);

        }elseif(is_string($data)){
            foreach($listing as &$value){
                $value = str_replace('\@', '@', $value);
            }

            unset($value);

        }else{
            throw new BException(tr('api_decode(): Specified data is datatype ":type", only string and array are allowed', array(':type' => gettype($data))), $e);
        }

        return $data;

    }catch(Exception $e){
        throw new BException('api_decode(): Failed', $e);
    }
}



/*
 * Make an API call to a BASE framework
 */
function api_call_base($account, $call, $data = array(), $files = null){
    global $_CONFIG;

    try{
        load_libs('curl');

        if(empty($account)){
            throw new BException(tr('api_call_base(): No API specified'), 'not-specified');
        }

        /*
         * Get account information
         */
        $account_data = sql_get('SELECT `id`, `baseurl`, `apikey`, `verify_ssl` FROM `api_accounts` WHERE `seoname` = :seoname', array(':seoname' => $account));

        if(!$account_data){
            throw new BException(tr('api_call_base(): Specified API account ":account" does not exist', array(':account' => $account)), 'not-exists');
        }

        /*
         * Check if we have cached session key
         */
        if(empty($_SESSION['api']['session_keys'][$account])){
            /*
             * Auto authenticate
             */
            try{
                $json = curl_get(array('url'            => str_starts_not($account_data['baseurl'], '/').'/authenticate',
                                       'posturlencoded' => true,
                                       'verify_ssl'     => isset_get($account_data['verify_ssl']),
                                       'getheaders'     => false,
                                       'post'           => array('PHPSESSID' => $account_data['apikey'])));

                if(!$json){
                    throw new BException(tr('api_call_base(): Authentication on API account ":account" returned no response', array(':account' => $account)), 'empty');
                }

                if(!$json['data']){
                    throw new BException(tr('api_call_base(): Authentication on API account ":account" returned no data in response', array(':account' => $account)), 'empty');
                }

                $result = json_decode_custom($json['data']);

                if(isset_get($result['result']) !== 'OK'){
                    throw new BException(tr('api_call_base(): Authentication on API account ":account" returned result ":result"', array('":account' => $account, ':result' => $result['result'])), 'failed', $result);
                }

                if(empty($result['data']['token'])){
                    throw new BException(tr('api_call_base(): Authentication on API account ":account" returned ok result but no token', array('":account' => $account)), 'failed');
                }

                $_SESSION['api']['session_keys'][$account] = $result['data']['token'];
                $signin = true;

            }catch(Exception $e){
                $url = str_starts_not($account_data['baseurl'], '/').'/authenticate';

                switch($e->getCode()){
                    case 'HTTP403':
                        throw new BException(tr('api_call_base(): [403] API authentication URL ":url" gave access denied', array(':url' => $url)), $e);

                    case 'HTTP404':
                        throw new BException(tr('api_call_base(): [404] API authentication URL ":url" was not found', array(':url' => $url)), $e);

                    case 'HTTP500':
                        throw new BException(tr('api_call_base(): [500] API server encountered an internal server error on authentication URL ":url"', array(':url' => $url)), $e);

                    case 'HTTP503':
                        throw new BException(tr('api_call_base(): [503] API server is in maintenance mode on authentication URL ":url"', array(':url' => $url)), $e);

                    default:
                        throw new BException(tr('api_call_base(): [:code] Failed to authenticate on authentication URL ":url"', array(':code' => $e->getCode(), ':url' => $url)), $e);
                }
            }
        }

        $data['PHPSESSID'] = $_SESSION['api']['session_keys'][$account];

        if($files){
            /*
             * Add the specified files
             */
            $count = 0;

            foreach(array_force($files) as $url => $file){
                $data['file'.$count++] =  curl_file_create($file, file_mimetype($file), str_replace('/', '_', str_replace('_', '', $url)));
            }
        }

        /*
         * Make the API call
         */
        try{
            $json = curl_get(array('url'        => str_starts_not($account_data['baseurl'], '/').str_starts($call, '/'),
                                   'verify_ssl' => isset_get($account_data['verify_ssl']),
                                   'getheaders' => false,
                                   'post'       => $data));

            if(!$json){
                throw new BException(tr('api_call_base(): API call ":call" on account ":account" returned no response', array(':account' => $account, ':call' => $call)), 'empty');
            }

            if(!$json['data']){
                throw new BException(tr('api_call_base(): API call ":call" on account ":account" returned no data in response', array(':account' => $account, ':call' => $call)), 'empty');
            }

            $result = json_decode_custom($json['data']);

            switch(isset_get($result['result'])){
                case 'OK':
                    /*
                     * All ok!
                     */
                    return isset_get($result['data']);

                case 'SIGNIN':
                    /*
                     * Session key is not valid
                     * Remove session key, signin, and try again
                     */
                    if(isset($signin)){
                        /*
                         * Oops, we already tried to signin, and that signin failed
                         * with a signin request which, in this case, would cause
                         * endless recursion
                         */
                        throw new BException(tr('api_call_base(): API call ":call" on ":api" required auto signin but that failed with a request to signin as well. Stopping to avoid endless signin loop', array(':api' => $api, ':call' => $call)), 'failed');
                    }

                    unset($_SESSION['api']['session_keys'][$account]);
                    return api_call_base($account, $call, $data);

                default:
                    throw new BException(tr('api_call_base(): API call ":call" on account ":account" returned result ":result"', array(':account' => $account, ':call' => $call, ':result' => $result['result'])), 'failed', $result);
            }

        }catch(Exception $e){
            $url = str_starts_not($account_data['baseurl'], '/').str_starts($call, '/');

            switch($e->getCode()){
                case 'HTTP403':
                    throw new BException(tr('api_call_base(): [403] API URL ":url" gave access denied', array(':url' => $url)), $e);

                case 'HTTP404':
                    throw new BException(tr('api_call_base(): [404] API URL ":url" was not found', array(':url' => $url)), $e);

                case 'HTTP500':
                    throw new BException(tr('api_call_base(): [500] API server encountered an internal server error on URL ":url"', array(':url' => $url)), $e);

                case 'HTTP503':
                    throw new BException(tr('api_call_base(): [503] API server is in maintenance mode on URL ":url"', array(':url' => $url)), $e);

                default:
                    throw new BException(tr('api_call_base(): [:code] Failed to call API on URL ":url"', array(':code' => $e->getCode(), ':url' => $url)), $e);
            }
        }

    }catch(Exception $e){
showdie($e);
        if($account_data){
            sql_query('UPDATE `api_accounts` SET `last_error` = :last_error WHERE `id` = :id', array(':id' => $account_data['id'], ':last_error' => print_r($e, true)));
        }

//show(isset_get($json));
//show(isset_get($result));
//showdie($e);
        throw new BException(tr('api_call_base(): Failed for account ":account"', array(':account' => $account)), $e);
    }
}
?>
