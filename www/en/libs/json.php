<?php
/*
 * JSON library
 *
 * This library contains JSON functions
 *
 * All function names contain the json_ prefix
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>, Johan Geuze
 */



/*
 * Send correct JSON reply
 */
function json_reply($data = null, $result = 'OK', $http_code = null, $after = 'die'){
    global $core;

    try{
        if(!$data){
            $data = array_force($data);
        }

        /*
         * Auto assume result = "OK" entry if not specified
         */
        if(empty($data['data'])){
            $data = array('data' => $data);
        }

        if($result){
            if(isset($data['result'])){
                throw new BException(tr('json_reply(): Result was specifed both in the data array as ":result1" as wel as the separate variable as ":result2"', array(':result1' => $data['result'], ':result2' => $result)), 'invalid');
            }

            /*
             * Add result to the reply
             */
            $data['result'] = $result;
        }

        /*
         * Send a new CSRF code with this payload?
         */
        if(!empty($core->register['csrf_ajax'])){
            $data['csrf'] = $core->register['csrf_ajax'];
            unset($core->register['csrf_ajax']);
        }

        $data['result'] = strtoupper($data['result']);
        $data           = json_encode_custom($data);

        $params = array('http_code' => $http_code,
                        'mimetype'  => 'application/json');

        http_headers($params, strlen($data));

        echo $data;

        switch($after){
            case 'die':
                /*
                 * We're done, kill the connection % process (default)
                 */
                die();

            case 'continue':
                /*
                 * Continue running
                 */
                return;

            case 'close_continue':
                /*
                 * Close the current HTTP connection but continue in the background
                 */
                session_write_close();
                fastcgi_finish_request();
                return;

            default:
                throw new BException(tr('json_reply(): Unknown after ":after" specified. Use one of "die", "continue", or "close_continue"', array(':after' => $after)), 'unknown');
        }

    }catch(Exception $e){
        throw new BException('json_reply(): Failed', $e);
    }
}



/*
 * Send correct JSON reply
 */
function json_error($message, $data = null, $result = null, $http_code = 500){
    global $_CONFIG;

    try{
        if(!$message){
            $message = '';

        }elseif(is_scalar($message)){

        }elseif(is_array($message)){
            if(empty($message['default'])){
                $default = tr('Something went wrong, please try again later');

            }else{
                $default = $message['default'];
                unset($message['default']);
            }

            if(empty($message['e'])){
                if($_CONFIG['production']){
                    $message = $default;
                    log_console('json_error(): No exception object specified for following error', 'yellow');
                    log_console($message, 'yellow');

                }else{
                    if(count($message) == 1){
                        $message = array_pop($message);
                    }
                }

            }else{
                if($_CONFIG['production']){
                    log_console($message['e']);

                    $code = $message['e']->getCode();

                    if(empty($message[$code])){
                        $message = $default;

                    }else{
                        $message = $message[$code];
                    }

                }else{
                    $message = $message['e']->getMessages("\n<br>");
                }
            }

            $message = trim(str_from($message, '():'));

        }elseif(is_object($message)){
            /*
             * Assume this is an BException object
             */
            if(!($message instanceof BException)){
                if(!($message instanceof Exception)){
                    $type = gettype($message);

                    if($type === 'object'){
                        $type .= '/'.get_class($message);
                    }

                    throw new BException(tr('json_error(): Specified message must either be a string or an BException ojbect, or PHP Exception ojbect, but is a ":type"', array(':type' => $type)), 'invalid');
                }

                $code = $message->getCode();

                if(debug()){
                    /*
                     * This is a user visible message
                     */
                    $message = $message->getMessage();

                }elseif(!empty($default)){
                    $message = $default;
                }

            }else{
                $result = $message->getCode();

                switch($result){
                    case 'access-denied':
                        $http_code = '403';
                        break;

                    case 'ssl-required':
                        $http_code = '403.4';
                        break;

                    default:
                        $http_code = '500';
                }

                if(str_until($result, '/') == 'warning'){
                    $data = $message->getMessage();

                }else{
                    if(debug()){
                        /*
                         * This is a user visible message
                         */
                        $messages = $message->getMessages();

                        foreach($messages as $id => &$message){
                            $message = trim(str_from($message, '():'));

                            if($message == tr('Failed')){
                                unset($messages[$id]);
                            }
                        }

                        unset($message);

                        $data = implode("\n", $messages);

                    }elseif(!empty($default)){
                        $message = $default;
                    }
                }
            }
        }

        $data = array_force($data);

        json_reply($data, ($result ? $result : 'ERROR'), $http_code);

    }catch(Exception $e){
        throw new BException('json_error(): Failed', $e);
    }
}



/*
 *
 */
function json_message($message, $data = null){
    global $_CONFIG;

    try{
        if(is_object($message)){
            /*
             * This is (presumably) an exception
             */
            $message = $message->getRealCode();
        }

        switch($message){
            case 301:
                // FALLTHROUGH
            case 'redirect':
                json_error(null, array('location' => $data), 'REDIRECT', 301);

            case 302:
                // FALLTHROUGH
            case 'signin':
                json_error(null, array('location' => domain($_CONFIG['redirects']['signin'])), 'SIGNIN', 302);

            case 304:
                // FALLTHROUGH
            case 'not-modified':
                json_error(null, null, ($data ? $data : 'BAD-REQUEST'), 304);

            case 400:
                // FALLTHROUGH
            case 'unknown':
                // FALLTHROUGH
            case 'invalid':
                // FALLTHROUGH
            case 'validation':
                json_error(null, null, ($data ? $data : 'BAD-REQUEST'), 400);

            case 403:
                // FALLTHROUGH
            case 'forbidden':
                // FALLTHROUGH
            case 'access-denied':
                json_error(null, null, ($data ? $data : 'FORBIDDEN'), 403);

            case 404:
                // FALLTHROUGH
            case 'not-found':
                json_error(null, null, ($data ? $data : 'NOT-FOUND'), 404);

            case 406:
                // FALLTHROUGH
            case 'not-acceptable':
                json_error(null, null, ($data ? $data : 'NOT-ACCEPTABLE'), 406);

            case 409:
                // FALLTHROUGH
            case 'conflict':
                json_error(null, null, ($data ? $data : 'CONFLICT'), 409);

            case 412:
                // FALLTHROUGH
            case 'expectation-failed':
                json_error(null, null, ($data ? $data : 'EXPECTATION-FAILED'), 412);

            case 418:
                // FALLTHROUGH
            case 'im-a-teapot':
                json_error(null, null, ($data ? $data : 'IM-A-TEAPOT'), 418);

            case 429:
                // FALLTHROUGH
            case 'too-many-requests':
                json_error(null, null, ($data ? $data : 'TOO-MANY-REQUESTS'), 429);

            case 451:
                // FALLTHROUGH
            case 'unavailable-for-legal-reasons':
                json_error(null, null, ($data ? $data : 'UNAVAILABLE-FOR-LEGAL-REASONS'), 451);

            case 500:
                // FALLTHROUGH
            case 'error':
                json_error(null, (debug() ? $data : null), 'ERROR', 500);

            case 503:
                // FALLTHROUGH
            case 'maintenance':
                // FALLTHROUGH
            case 'service-unavailable':
                json_error(null, null, 'SERVICE-UNAVAILABLE', 503);

            case 'reload':
                json_reply(null, 'RELOAD');

            default:
                notify(array('code'    => 'unknown',
                             'groups'  => 'developers',
                             'title'   => tr('Unknown message specified'),
                             'message' => tr('json_message(): Unknown message ":message" specified', array(':message' => $message))));

                json_error(null, (debug() ? $data : null), 'ERROR', 500);
        }

    }catch(Exception $e){
        throw new BException('json_message(): Failed', $e);
    }
}



/*
 * Custom JSON encoding function
 */
function json_encode_custom($source = false, $internal = true){
    try{
        if($internal){
            $source = json_encode($source);

            switch(json_last_error()){
                case JSON_ERROR_NONE:
                    break;

                case JSON_ERROR_DEPTH:
                    throw new BException('json_decode_custom(): Maximum stack depth exceeded', 'invalid');

                case JSON_ERROR_STATE_MISMATCH:
                    throw new BException('json_decode_custom(): Underflow or the modes mismatch', 'invalid');

                case JSON_ERROR_CTRL_CHAR:
                    throw new BException('json_decode_custom(): Unexpected control character found', 'invalid');

                case JSON_ERROR_SYNTAX:
                    throw new BException('json_decode_custom(): Syntax error, malformed JSON', 'invalid', $json);

                case JSON_ERROR_UTF8:
                    /*
                     * PHP and UTF, yay!
                     */
                    load_libs('mb');
                    return json_encode_custom(mb_utf8ize($source), true);

                default:
                    throw new BException('json_decode_custom(): Unknown JSON error occured', 'error');
            }

            return $source;

        }else{
            if(is_null($source)){
                return 'null';
            }

            if($source === false){
                return 'false';
            }

            if($source === true){
                return 'true';
            }

            if(is_scalar($source)){
                if(is_numeric($source)){
                    if(is_float($source)){
                        // Always use "." for floats.
                        $source = floatval(str_replace(',', '.', strval($source)));
                    }

                    // Always use "" for numerics.
                    return '"'.strval($source).'"';
                }

                if(is_string($source)){
                    static $json_replaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
                    return '"'.str_replace($json_replaces[0], $json_replaces[1], $source).'"';
                }

                return $source;
            }

            $is_list = true;

            for($i = 0, reset($source); $i < count($source); $i++, next($source)){
                if(key($source) !== $i){
                    $is_list = false;
                    break;
                }
            }

            $result = array();

            if($is_list){
                foreach ($source as $v){
                    $result[] = json_encode_custom($v);
                }

                return '['.join(',', $result).']';
            }

            foreach ($source as $k => $v){
                $result[] = json_encode_custom($k).':'.json_encode_custom($v);
            }

            return '{'.join(',', $result).'}';
        }

    }catch(Exception $e){
        throw new BException('json_encode_custom(): Failed', $e);
    }
}



/*
 * Validate the given JSON string
 */
function json_decode_custom($json, $as_array = true){
    try{
        if($json === null){
            return null;
        }

        /*
         * Decode the JSON data
         */
        $retval = json_decode($json, $as_array);

        /*
         * Switch and check possible JSON errors
         */
        switch(json_last_error()){
            case JSON_ERROR_NONE:
                break;

            case JSON_ERROR_DEPTH:
                throw new BException('json_decode_custom(): Maximum stack depth exceeded', 'invalid');

            case JSON_ERROR_STATE_MISMATCH:
                throw new BException('json_decode_custom(): Underflow or the modes mismatch', 'invalid');

            case JSON_ERROR_CTRL_CHAR:
                throw new BException('json_decode_custom(): Unexpected control character found', 'invalid');

            case JSON_ERROR_SYNTAX:
                throw new BException('json_decode_custom(): Syntax error, malformed JSON', 'invalid', $json);

            case JSON_ERROR_UTF8:
                throw new BException('json_decode_custom(): Syntax error, UTF8 issue', 'invalid', $json);

            default:
                throw new BException('json_decode_custom(): Unknown JSON error occured', 'error');
        }

        return $retval;

    }catch(Exception $e){
        throw new BException('json_decode_custom(): Failed', $e);
    }
}
?>
