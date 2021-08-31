<?php

use Phoundation\Core\Config;
use Phoundation\Core\Json\Arrays;

/**
 * Class Http
 *
 * This class contains various HTTP processing methods
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2021 <copyright@capmega.com>
 * @package Phoundation\Http
 */
class Http
{
    /**
     * Singleton variable
     *
     * @var Http|null $instance
     */
    protected static ?Http $instance = null;


    /**
     * Singleton
     *
     * @return Http
     */
    public static function getInstance(): Http
    {
        if (!isset(self::$instance)) {
            self::$instance = new Http();
        }

        return self::$instance;
    }


    /**
     * Send all the HTTP headers
     *
     * @param array $params
     * @param int $content_length
     * @return bool
     * @throws HttpException
     * @todo Refactor and remove $_CONFIG dependancies
     * @todo Refactor and remove $core dependancies
     *
     * @todo Refactor and remove $params dependancies
     */
    public static function headers(array $params, int $content_length): bool
    {
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
            define('LANGUAGE', Config::get('http.language.default', 'en'));
        }

        try {
            /*
             * Create ETAG, possibly send out HTTP304 if client sent matching ETAG
             */
            http_cacheEtag();

            Arrays::params($params, null, 'http_code', null);
            Arrays::default($params, 'http_code', $core->register['http_code']);
            Arrays::default($params, 'cors', false);
            Arrays::default($params, 'mimetype', $core->register['accepts']);
            Arrays::default($params, 'headers', array());
            Arrays::default($params, 'cache', array());

            $headers = $params['headers'];

            if ($_CONFIG['security']['expose_php'] === false) {
                header_remove('X-Powered-By');

            } elseif ($_CONFIG['security']['expose_php'] !== true) {
                /*
                 * Send custom expose header to fake X-Powered-By header
                 */
                $headers[] = 'X-Powered-By: ' . $_CONFIG['security']['expose_php'];
            }

            $headers[] = 'Content-Type: ' . $params['mimetype'] . '; charset=' . $_CONFIG['encoding']['charset'];
            $headers[] = 'Content-Language: ' . LANGUAGE;

            if ($content_length) {
                $headers[] = 'Content-Length: ' . $content_length;
            }

            if ($params['http_code'] == 200) {
                if (empty($params['last_modified'])) {
                    $headers[] = 'Last-Modified: ' . date_convert(filemtime($_SERVER['SCRIPT_FILENAME']), 'D, d M Y H:i:s', 'GMT') . ' GMT';

                } else {
                    $headers[] = 'Last-Modified: ' . date_convert($params['last_modified'], 'D, d M Y H:i:s', 'GMT') . ' GMT';
                }
            }

            /*
             * Add noidex, nofollow and nosnipped headers for non production
             * environments and non normal HTTP pages.
             *
             These pages should NEVER be indexed
             */
            if (!$_CONFIG['production'] or $_CONFIG['noindex'] or !$core->callType('http')) {
                $headers[] = 'X-Robots-Tag: noindex, nofollow, nosnippet, noarchive, noydir';
            }

            /*
             * CORS headers
             */
            if ($_CONFIG['cors'] or $params['cors']) {
                /*
                 * Add CORS / Access-Control-Allow-.... headers
                 */
                $params['cors'] = array_merge($_CONFIG['cors'], array_force($params['cors']));

                foreach ($params['cors'] as $key => $value) {
                    switch ($key) {
                        case 'origin':
                            if ($value == '*.') {
                                /*
                                 * Origin is allowed from all sub domains
                                 */
                                $origin = str_from(isset_get($_SERVER['HTTP_ORIGIN']), '://');
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

                        // FALLTHROUGH

                        case 'methods':
                            // FALLTHROUGH
                        case 'headers':
                            if ($value) {
                                $headers[] = 'Access-Control-Allow-' . str_capitalize($key) . ': ' . $value;
                            }

                            break;

                        default:
                            throw new HttpException(tr('http_headers(): Unknown CORS header ":header" specified', array(':header' => $key)), 'unknown');
                    }
                }
            }

            $headers = self::cache($params, $params['http_code'], $headers);

            /*
             * Remove incorrect or insecure headers
             */
            header_remove('X-Powered-By');
            header_remove('Expires');
            header_remove('Pragma');

            /*
             * Set correct headers
             */
            http_response_code($params['http_code']);

            if (($params['http_code'] != 200)) {
                log_file(tr('Phoundation sent :http for URL ":url"', array(':http' => ($params['http_code'] ? 'HTTP' . $params['http_code'] : 'HTTP0'), ':url' => (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])), 'warning', 'yellow');

            } elseif (VERBOSE) {
                log_file(tr('Phoundation sent :http for URL ":url"', array(':http' => ($params['http_code'] ? 'HTTP' . $params['http_code'] : 'HTTP0'), ':url' => (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])), 'http', 'green');
            }

            if (VERYVERBOSE) {
                load_libs('time,numbers');
                log_console(tr('Page ":script" was processed in :time with ":usage" peak memory usage', array(':script' => $core->register['script'], ':time' => time_difference(STARTTIME, microtime(true), 'auto', 5), ':usage' => bytes(memory_get_peak_usage()))));
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
                    // FALLTHROUGH

                case 429:
                    /*
                     * 429 Tell the client that it made too many requests, send
                     * nothing
                     */
                    die();
            }

            return true;

        } catch (Exception $e) {
            /*
             * http_headers() itself crashed. Since http_headers()
             * would send out http 500, and since it crashed, it no
             * longer can do this, send out the http 500 here.
             */
            http_response_code(500);
            throw new HttpException('http_headers(): Failed', $e);
        }
    }



    /**
     * Set the default context for SSL requests that phoundation has to make when using (for example) file_get_contents()
     *
     * @param bool|null $verify_peer
     * @param bool|null $verify_peer_name
     * @param bool|null $allow_self_signed
     * @return resource
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package http
     * @version 2.8.29: Added function and documentation
     *
     */
    public static function set_ssl_default_context(?bool $verify_peer = null, ?bool $verify_peer_name = null, ?bool $allow_self_signed = null)
    {
        $verify_peer = not_null($verify_peer, Config::get('security.ssl.verify_peer', true));
        $verify_peer_name = not_null($verify_peer, Config::get('security.ssl.verify_peer', true));
        $allow_self_signed = not_null($verify_peer, Config::get('security.ssl.verify_peer', true));

        return stream_context_set_default([
            'ssl' => [
                'verify_peer' => $verify_peer,
                'verify_peer_name' => $verify_peer_name,
                'allow_self_signed' => $allow_self_signed
            ]
        ]);
    }



    /**
     * Validates the $_GET array and ensures that all values are scalar
     *
     * This function will walk over the $_GET array and test each value. If a value is found that is not scalar, a 400 code exception will be thrown, which would lead to an HTTP 400 BAD REQUEST
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package http
     * @note This function is called by all HTTP type startup sequences, there should be no need to run this anywhere else
     * @version 1.26.1: Added function and documentation
     *
     * @return void
     */
    public static function validateGet()
    {
        global $_CONFIG;

        try {
            foreach ($_GET as $key => &$value) {
                if (!is_scalar($value)) {
                    if ($value) {
                        throw new HttpException(tr('http_validate_get(): The $_GET key ":key" contains a value with the content ":content" while only scalar values are allowed', array(':key' => $key, ':content' => $value)), 400);
                    }

                    /*
                     * The value is NULL
                     */
                    $value = '';
                }
            }

            unset($value);

            $_GET['limit'] = (integer)ensure_value(isset_get($_GET['limit'], $_CONFIG['paging']['limit']), array_keys($_CONFIG['paging']['list']), $_CONFIG['paging']['limit']);

        } catch (Exception $e) {
            throw new HttpException('http_validate_get(): Failed', $e);
        }
    }



    /**
     *
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @todo Remove $core dependancy
     * @todo Remove $_CONFIG dependancy
     */
    public static function done()
    {
        global $core, $_CONFIG;

        try {
            if (!isset($core)) {
                /*
                 * We died very early in startup. For more information see either
                 * the ROOT/data/log/syslog file, or your webserver log file
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
            Core::shutdown();

        } catch (Exception $e) {
            throw new HttpException('http_done(): Failed', $e);
        }
    }



    /**
     * Return the URL the client requested
     *
     * @return string
     */
    public static function getRequestedUrl(): string
    {
        return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }



    /**
     * Return HTTP caching headers
     *
     * Returns headers Cache-Control and ETag
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package http
     * @see htt_noCache()
     * @see https://developers.google.com/speed/docs/insights/LeverageBrowserCaching
     * @see https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching
     * @version 2.5.92: Added function and documentation

     * @param array $params The caching parameters
     * @param int $http_code The HTTP code that will be sent to the client
     * @param array $headers Any extra headers that are required
     * @return void
     * @todo Remove $params dependancy
     * @todo Remove $core dependancy
     * @todo Remove $_CONFIG dependancy
     */
    protected static function cache(array $params, int $http_code, array $headers = []): void
    {
        global $_CONFIG, $core;

        Arrays::ensure($params);

        if ($_CONFIG['cache']['http']['enabled'] === 'auto') {
            /*
             * PHP will take care of the cache headers
             */

        } elseif ($_CONFIG['cache']['http']['enabled'] === true) {
            /*
             * Place headers using phoundation algorithms
             */
            if (!$_CONFIG['cache']['http']['enabled'] or ($http_code != 200)) {
                /*
                 * Non HTTP 200 / 304 pages should NOT have cache enabled!
                 * For example 404, 503 etc...
                 */
                $headers[] = 'Cache-Control: no-store, max-age=0';
                unset($core->register['etag']);

            } else {
                /*
                 * Send caching headers
                 * Ajax, API, and admin calls do not have proxy caching
                 */
                switch ($core->callType()) {
                    case 'api':
                        // FALLTHROUGH
                    case 'ajax':
                        // FALLTHROUGH
                    case 'admin':
                        break;

                    default:
                        /*
                         * Session pages for specific users should not be stored
                         * on proxy servers either
                         */
                        if (!empty($_SESSION['user']['id'])) {
                            $_CONFIG['cache']['http']['cacheability'] = 'private';
                        }

                        $headers[] = 'Cache-Control: ' . $_CONFIG['cache']['http']['cacheability'] . ', ' . $_CONFIG['cache']['http']['expiration'] . ', ' . $_CONFIG['cache']['http']['revalidation'] . ($_CONFIG['cache']['http']['other'] ? ', ' . $_CONFIG['cache']['http']['other'] : '');

                        if (!empty($core->register['etag'])) {
                            $headers[] = 'ETag: "' . $core->register['etag'] . '"';
                        }
                }
            }
        }

        return $headers;
    }


    /**
     * Send the required headers to ensure that the page will not be cached ever
     *
     * @return void
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package http
     * @see Http::cache()
     * @version 2.5.92: Added function and documentation
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     */
    protected static function noCache(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);
        header('Cache-Control: post-check=0, pre-check=0', true);
        header('Pragma: no-cache', true);
        header('Expires: Wed, 10 Jan 2000 07:00:00 GMT', true);
    }



    /*
     * Test HTTP caching headers
     *
     * Sends out 304 - Not modified header if ETag matches
     *
     * For more information, see https://developers.google.com/speed/docs/insights/LeverageBrowserCaching
     * and https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching
     */
    protected static function cacheTest($etag = null): bool
    {
        global $_CONFIG, $core;

        try{
            $core->register['etag'] = sha1(PROJECT.$_SERVER['SCRIPT_FILENAME'].filemtime($_SERVER['SCRIPT_FILENAME']).$etag);

            if(!$_CONFIG['cache']['http']['enabled']){
                return false;
            }

            if($core->callType('ajax') or $core->callType('api')){
                return false;
            }

            if((strtotime(isset_get($_SERVER['HTTP_IF_MODIFIED_SINCE'])) == filemtime($_SERVER['SCRIPT_FILENAME'])) or trim(isset_get($_SERVER['HTTP_IF_NONE_MATCH']), '') == $core->register['etag']){
                if(empty($core->register['flash'])){
                    /*
                     * The client sent an etag which is still valid, no body (or anything else) necesary
                     */
                    http_headers(304, 0);
                }
            }

            return true;

        }catch(Exception $e){
            throw new HttpException('http_cacheTest(): Failed', $e);
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
    protected static function cacheEtag(){
        global $_CONFIG, $core;

        try{
            /*
             * ETAG requires HTTP caching enabled
             * Ajax and API calls do not use ETAG
             */
            if(!$_CONFIG['cache']['http']['enabled'] or $core->callType('ajax') or $core->callType('api')){
                unset($core->register['etag']);
                return false;
            }

            /*
             * Create local ETAG
             */
            $core->register['etag'] = sha1(PROJECT.$_SERVER['SCRIPT_FILENAME'].filemtime($_SERVER['SCRIPT_FILENAME']).$core->register('etag'));

// :TODO: Document why we are trimming with an empty character mask... It doesn't make sense but something tells me we're doing this for a good reason...
            if(trim(isset_get($_SERVER['HTTP_IF_NONE_MATCH']), '') == $core->register['etag']){
                if(empty($core->register['flash'])){
                    /*
                     * The client sent an etag which is still valid, no body (or anything else) necesary
                     */
                    http_response_code(304);
                    die();
                }
            }

            return true;

        }catch(Exception $e){
            throw new BException('http_cacheEtag(): Failed', $e);
        }
    }



    /*
     * Remove a variable from the specified URL
     */
    public static function remove_variable(string $url, string $key): string
    {
        try{
            throw new BException('http_remove_variable() is under construction!');
            //if(!$key){
            //    return $url;
            //}
            //
            //if($pos = strpos($url, $key.'=') === false){
            //    return $url;
            //}
            //
            //if($pos2 = strpos($url, '&', $pos) === false){
            //    return substr($url, 0, $pos).;
            //}
            //
            //return substr($url, 0, );

        }catch(Exception $e){
            throw new BException('http_remove_variable(): Failed', $e);
        }
    }
}