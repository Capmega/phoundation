<?php
/*
 * HTTP library, containing all sorts of HTTP functions
 *
 * This library contains functions to manage BASE HTTP
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package http
 */



/*
 * Return $_POST[dosubmit] value, and reset it to be sure it won't be applied twice
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package http
 *
 * @return mixed The value found in $_POST['dosubmit']
 */
function get_submit() {
    static $submit;

    try {
        if ($submit !== null) {
            /*
             * We have a cached value
             */
            return $submit;
        }

        /*
         * Get submit value
         */
        if (empty($_POST['dosubmit'])) {
            if (empty($_POST['multisubmit'])) {
                $submit = '';

            } else {
                $submit = $_POST['multisubmit'];
                unset($_POST['multisubmit']);
            }

        } else {
            $submit = $_POST['dosubmit'];
            unset($_POST['dosubmit']);
        }

        $submit = strtolower($submit);

        return $submit;

    }catch(Exception $e) {
        throw new CoreException('get_submit(): Failed', $e);
    }
}



/*
 * Redirect to the specified $target
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package http
 *
 * @param string $target
 * @param natural $http_code
 * @param boolean $clear_session_redirect
 * @param natural $time_delay
 * @return void (dies)
 */
function redirect($target = '', $http_code = null, $clear_session_redirect = true, $time_delay = null) {
    return include(__DIR__.'/handlers/http-redirect.php');
}



/*
 * Return the specified URL with a redirect URL stored in $core->register['redirect']
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package http
 * @note If no URL is specified, the current URL will be used
 * @see domain()
 * @see core::register
 * @see url_add_query()
 *
 * @param string $url
 * @return string The specified URL (if not specified, the current URL) with $core->register['redirect'] added to it (if set)
 */
function redirect_url($url = null) {
    try {
        load_libs('inet');

        if (!$url) {
            /*
             * Default to this page
             */
            $url = domain(true);
        }

        if (empty($_GET['redirect'])) {
            return $url;
        }

        return url_add_query($url, 'redirect='.urlencode($_GET['redirect']));

    }catch(Exception $e) {
        throw new CoreException('redirect_url(): Failed', $e);
    }
}



/*
 * Redirect if the session redirector is set
 */
function session_redirect($method = 'http', $force = false) {
    try {
        if (!empty($force)) {
            /*
             * Redirect by force value
             */
            $redirect = $force;

        } elseif (!empty($_GET['redirect'])) {
            /*
             * Redirect by _GET redirect
             */
            $redirect = $_GET['redirect'];
            unset($_GET['redirect']);

        } elseif (!empty($_GET['redirect'])) {
            /*
             * Redirect by _SESSION redirect
             */
            $redirect = $_GET['redirect'];

            unset($_GET['redirect']);
            unset($_SESSION['sso_referrer']);
        }

        switch ($method) {
            case 'json':
                /*
                 * Send JSON redirect. json_reply() will end script, so no break needed
                 */
                json_reply(isset_get($redirect, '/'), 'redirect');

            case 'http':
                /*
                 * Send HTTP redirect. redirect() will end script, so no break
                 * needed
                 *
                 * Also, no need to unset SESSION redirect and sso_referrer,
                 * since redirect() will also do this
                 */
                redirect($redirect);

            default:
                throw new CoreException(tr('session_redirect(): Unknown method ":method" specified. Please speficy one of "json", or "http"', array(':method' => $method)), 'unknown');
        }

    }catch(Exception $e) {
        throw new CoreException('session_redirect(): Failed', $e);
    }
}



/*
 * Store post data in $_SESSION
 */
function store_post($redirect) {
    return include(__DIR__.'/handlers/system_store_post.php');
}



/*
 * Ensure that the $_GET values with the specied keys are also available in $_POST
 */
function http_get_to_post($keys, $overwrite = true) {
    try {
        foreach (Arrays::force($keys) as $key) {
            if (isset($_GET[$key]) and ($overwrite or empty($_POST[$key]))) {
                $_POST[$key] = $_GET[$key];
            }
        }

    }catch(Exception $e) {
        throw new CoreException('http_get_to_post(): Failed', $e);
    }
}



//:OBSOLETE: Use http_response_code() instead
///*
// * Return status message for specified code
// */
//function http_status_message($code) {
//    static $messages = array(  0 => 'Nothing',
//                             200 => 'OK',
//                             304 => 'Not Modified',
//                             400 => 'Bad Request',
//                             401 => 'Unauthorized',
//                             403 => 'Forbidden',
//                             404 => 'Not Found',
//                             406 => 'Not Acceptable',
//                             500 => 'Internal Server Error',
//                             502 => 'Bad Gateway',
//                             503 => 'Service Unavailable');
//
//    if (!is_numeric($code) or ($code < 0) or ($code > 1000)) {
//        throw new CoreException('http_status_message(): Invalid code "'.Strings::Log($code).'" specified');
//    }
//
//    if (!isset($messages[$code])) {
//        throw new CoreException('http_status_message(): Specified code "'.Strings::Log($code).'" is not supported');
//    }
//
//    return $messages[$code];
//}



/*
 * Send HTTP headers to the client
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package http
 * @see html_header()
 * @note This function will set the LANGUAGE constant in case it hasn't been defined yet. This can happen in certain instances (For example with errors during Phoundation startup, show() or showdie() being used before the startup finishes, etc) and would cause more errors if left undefined
 * @version 2.8.20: Added function and documentation
 *
 * @param params $params The HTTP header parameters
 * @param null natural $params[http_code]
 * @param null string $params[mimetype]
 * @param null array $params[headers]
 * @param boolean $params[cors]
 * @param array $params[cache]
 * @param null date $params[last_modified]
 * @param natural $content_length The length of the content sent to the client
 */
function http_headers($params, $content_length) {
    global $_CONFIG, $core;
    static $sent = false;

    if ($sent) return false;
    $sent = true;

    /*
     * Ensure that from this point on we have a language configuration available
     *
     * The startup systems already configures languages but if the startup
     * itself fails, or if a show() or showdie() was issued before the startup
     * finished, then this could leave the system without defined language
     */
    if (!defined('LANGUAGE')) {
        define('LANGUAGE', isset_get($_CONFIG['language']['default'], 'en'));
    }

    try {
        /*
         * Create ETAG, possibly send out HTTP304 if client sent matching ETAG
         */
        http_cache_etag();

        array_params($params, null, 'http_code', null);
        array_default($params, 'http_code', $core->register['http_code']);
        array_default($params, 'cors'     , false);
        array_default($params, 'mimetype' , $core->register['accepts']);
        array_default($params, 'headers'  , array());
        array_default($params, 'cache'    , array());

        $headers = $params['headers'];

        if ($_CONFIG['security']['expose_php'] === false) {
            header_remove('X-Powered-By');

        } elseif ($_CONFIG['security']['expose_php'] !== true) {
            /*
             * Send custom expose header to fake X-Powered-By header
             */
            $headers[] = 'X-Powered-By: '.$_CONFIG['security']['expose_php'];
        }

        $headers[] = 'Content-Type: '.$params['mimetype'].'; charset='.$_CONFIG['encoding']['charset'];
        $headers[] = 'Content-Language: '.LANGUAGE;

        if ($content_length) {
            $headers[] = 'Content-Length: '.$content_length;
        }

        if ($params['http_code'] == 200) {
            if (empty($params['last_modified'])) {
                $headers[] = 'Last-Modified: '.date_convert(filemtime($_SERVER['SCRIPT_FILENAME']), 'D, d M Y H:i:s', 'GMT').' GMT';

            } else {
                $headers[] = 'Last-Modified: '.date_convert($params['last_modified'], 'D, d M Y H:i:s', 'GMT').' GMT';
            }
        }

        /*
         * Add noidex, nofollow and nosnipped headers for non production
         * environments and non normal HTTP pages.
         *
         These pages should NEVER be indexed
         */
        if (!Debug::production() or $_CONFIG['noindex'] or !Core::getCallType('http')) {
            $headers[] = 'X-Robots-Tag: noindex, nofollow, nosnippet, noarchive, noydir';
        }

        /*
         * CORS headers
         */
        if ($_CONFIG['cors'] or $params['cors']) {
            /*
             * Add CORS / Access-Control-Allow-.... headers
             */
            $params['cors'] = array_merge($_CONFIG['cors'], Arrays::force($params['cors']));

            foreach ($params['cors'] as $key => $value) {
                switch ($key) {
                    case 'origin':
                        if ($value == '*.') {
                            /*
                             * Origin is allowed from all sub domains
                             */
                            $origin = Strings::from(isset_get($_SERVER['HTTP_ORIGIN']), '://');
                            $length = strlen(isset_get($_SESSION['domain']));

                            if (substr($origin, -$length, $length) === isset_get($_SESSION['domain'])) {
                                /*
                                 * Sub domain matches. Since CORS does
                                 * not support sub domains, just show
                                 * the current sub domain.
                                 */
                                $value = $_SERVER['HTTP_ORIGIN'];

                            } else {
                                /*
                                 * Sub domain does not match. Since CORS does
                                 * not support sub domains, just show no
                                 * allowed origin domain at all
                                 */
                                $value = '';
                            }
                        }

                        // no-break

                    case 'methods':
                        // no-break
                    case 'headers':
                        if ($value) {
                            $headers[] = 'Access-Control-Allow-'.str_capitalize($key).': '.$value;
                        }

                        break;

                    default:
                        throw new CoreException(tr('http_headers(): Unknown CORS header ":header" specified', array(':header' => $key)), 'unknown');
                }
            }
        }

        $headers = http_cache($params, $params['http_code'], $headers);

        /*
         * Remove incorrect headers
         */
        header_remove('X-Powered-By');
        header_remove('Expires');
        header_remove('Pragma');

        /*
         * Set correct headers
         */
        http_response_code($params['http_code']);

        if (($params['http_code'] != 200)) {
            log_file(tr('Phoundation sent :http for URL ":url"', array(':http' => ($params['http_code'] ? 'HTTP'.$params['http_code'] : 'HTTP0'), ':url' => (empty($_SERVER['HTTPS']) ? 'http' : 'https').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'])), 'warning', 'yellow');

        } elseif (VERBOSE) {
            log_file(tr('Phoundation sent :http for URL ":url"', array(':http' => ($params['http_code'] ? 'HTTP'.$params['http_code'] : 'HTTP0'), ':url' => (empty($_SERVER['HTTPS']) ? 'http' : 'https').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'])), 'http', 'green');
        }

        if (VERYVERBOSE) {
            load_libs('time,numbers');
            log_console(tr('Page ":script" was processed in :time with ":usage" peak memory usage', array(':script' => $core->register['script'], ':time' => Time:difference(STARTTIME, microtime(true), 'auto', 5), ':usage' => bytes(memory_get_peak_usage()))));
        }

        foreach ($headers as $header) {
            header($header);
        }

        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'HEAD') {
            /*
             * HEAD request, do not return a body
             */
            die();
        }

        switch ($params['http_code']) {
            case 304:
                /*
                 * 304 requests indicate the browser to use it's local cache,
                 * send nothing
                 */
                // no-break

            case 429:
                /*
                 * 429 Tell the client that it made too many requests, send
                 * nothing
                 */
                die();
        }

        return true;

    }catch(Exception $e) {
        /*
         * http_headers() itself crashed. Since http_headers()
         * would send out http 500, and since it crashed, it no
         * longer can do this, send out the http 500 here.
         */
        http_response_code(500);
        throw new CoreException('http_headers(): Failed', $e);
    }
}



/*
 * Add a variable to the specified URL
 */
function http_add_variable($url, $key, $value) {
    try {
        if (!$key or !$value) {
            return $url;
        }

        if (strpos($url, '?') !== false) {
            return $url.'&'.urlencode($key).'='.urlencode($value);
        }

        return $url.'?'.urlencode($key).'='.urlencode($value);

    }catch(Exception $e) {
        throw new CoreException('http_add_variable(): Failed', $e);
    }
}



/*
 * Remove a variable from the specified URL
 */
function http_remove_variable($url, $key) {
    try {
throw new CoreException('http_remove_variable() is under construction!');
        //if (!$key) {
        //    return $url;
        //}
        //
        //if ($pos = strpos($url, $key.'=') === false) {
        //    return $url;
        //}
        //
        //if ($pos2 = strpos($url, '&', $pos) === false) {
        //    return substr($url, 0, $pos).;
        //}
        //
        //return substr($url, 0, );

    }catch(Exception $e) {
        throw new CoreException('http_remove_variable(): Failed', $e);
    }
}



/*
 * Test HTTP caching headers
 *
 * Sends out 304 - Not modified header if ETag matches
 *
 * For more information, see https://developers.google.com/speed/docs/insights/LeverageBrowserCaching
 * and https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching
 */
function http_cache_etag() {
    global $_CONFIG, $core;

    try {
        /*
         * ETAG requires HTTP caching enabled
         * Ajax and API calls do not use ETAG
         */
        if (!Config::get('web.cache.enabled', 'auto') or Core::getCallType('ajax') or Core::getCallType('api')) {
            unset($core->register['etag']);
            return false;
        }

        /*
         * Create local ETAG
         */
        $core->register['etag'] = sha1(PROJECT.$_SERVER['SCRIPT_FILENAME'].filemtime($_SERVER['SCRIPT_FILENAME']).Core::readRegister('etag'));

// :TODO: Document why we are trimming with an empty character mask... It doesn't make sense but something tells me we're doing this for a good reason...
        if (trim(isset_get($_SERVER['HTTP_IF_NONE_MATCH']), '') == $core->register['etag']) {
            if (empty($core->register['flash'])) {
                /*
                 * The client sent an etag which is still valid, no body (or anything else) necesary
                 */
                http_response_code(304);
                die();
            }
        }

        return true;

    }catch(Exception $e) {
        throw new CoreException('http_cache_etag(): Failed', $e);
    }
}



/*
 * Test HTTP caching headers
 *
 * Sends out 304 - Not modified header if ETag matches
 *
 * For more information, see https://developers.google.com/speed/docs/insights/LeverageBrowserCaching
 * and https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching
 */
function http_cache_test($etag = null) {
    global $_CONFIG, $core;

    try {
        $core->register['etag'] = sha1(PROJECT.$_SERVER['SCRIPT_FILENAME'].filemtime($_SERVER['SCRIPT_FILENAME']).$etag);

        if (!Config::get('web.cache.enabled', 'auto')) {
            return false;
        }

        if (Core::getCallType('ajax') or Core::getCallType('api')) {
            return false;
        }

        if ((strtotime(isset_get($_SERVER['HTTP_IF_MODIFIED_SINCE'])) == filemtime($_SERVER['SCRIPT_FILENAME'])) or trim(isset_get($_SERVER['HTTP_IF_NONE_MATCH']), '') == $core->register['etag']) {
            if (empty($core->register['flash'])) {
                /*
                 * The client sent an etag which is still valid, no body (or anything else) necesary
                 */
                http_headers(304, 0);
            }
        }

        return true;

    }catch(Exception $e) {
        throw new CoreException('http_cache_test(): Failed', $e);
    }
}





/*
 * Return the URL the client requested
 */
function requested_url() {
    try {
        return $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

    }catch(Exception $e) {
        throw new CoreException('requested_url(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package http
 *
 *
 */
function http_done() {
    global $core, $_CONFIG;

    try {
        if (!isset($core)) {
            /*
             * We died very early in startup. For more information see either
             * the PATH_ROOT/data/log/syslog file, or your webserver log file
             */
            die('Exception: See log files');
        }

        if ($core === false) {
            /*
             * Core wasn't created yet, but uncaught exception handler basically
             * is saying that's okay, just warning stuff
             */
            die();
        }

        $exit_code = isset_get($core->register['exit_code'], 0);

        /*
         * Do we need to run other shutdown functions?
         */
        shutdown();

    }catch(Exception $e) {
        throw new CoreException('http_done(): Failed', $e);
    }
}



/*
 * Validates the $_GET array and ensures that all values are scalar
 *
 * This function will walk over the $_GET array and test each value. If a value is found that is not scalar, a 400 code exception will be thrown, which would lead to an HTTP 400 BAD REQUEST
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package http
 * @note This function is called by all HTTP type startup sequences, there should be no need to run this anywhere else
 * @version 1.26.1: Added function and documentation
 *
 * @return void
 */
function http_validate_get() {
    global $_CONFIG;

    try {
        foreach ($_GET as $key => &$value) {
            if (!is_scalar($value)) {
                if ($value) {
                    throw new CoreException(tr('http_validate_get(): The $_GET key ":key" contains a value with the content ":content" while only scalar values are allowed', array(':key' => $key, ':content' => $value)), 400);
                }

                /*
                 * The value is NULL
                 */
                $value = '';
            }
        }

        unset($value);

        $_GET['limit'] = (integer) ensure_value(isset_get($_GET['limit'], $_CONFIG['paging']['limit']), array_keys($_CONFIG['paging']['list']), $_CONFIG['paging']['limit']);

    }catch(Exception $e) {
        throw new CoreException('http_validate_get(): Failed', $e);
    }
}



/*
 * Here be dragons and depreciated wrappers
 */
function http_build_url($url, $query) {
    try {
        return http_add_variable($url, Strings::until($query, '='), Strings::from($query, '='));

    }catch(Exception $e) {
        throw new CoreException('http_build_url(DEPRECIATED): Failed', $e);
    }
}



/*
 * Set the default context for SSL requests that phoundation has to make when using (for example) file_get_contents()
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package http
 * @version 2.8.29: Added function and documentation
 *
 * @param null params $params A parameters array
 * @param string $params[verify_peer]
 * @param string $params[verify_peer_name]
 * @param string $params[allow_self_signed]
 * @return
 */
function http_set_ssl_default_context($params = null) {
    global $_CONFIG;

    try {
        Arrays::ensure($params);
        array_default($params, 'verify_peer'      , isset_get($_CONFIG['security']['ssl']['verify_peer']));
        array_default($params, 'verify_peer_name' , isset_get($_CONFIG['security']['ssl']['verify_peer_name']));
        array_default($params, 'allow_self_signed', isset_get($_CONFIG['security']['ssl']['allow_self_signed']));

        return stream_context_set_default(array('ssl' => array('verify_peer'       => $params['verify_peer'],
                                                               'verify_peer_name'  => $params['verify_peer_name'],
                                                               'allow_self_signed' => $params['allow_self_signed'])));

    }catch(Exception $e) {
        throw new CoreException(tr('http_ssl_context(): Failed'), $e);
    }
}