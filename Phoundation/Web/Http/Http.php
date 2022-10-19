<?php

namespace Phoundation\Web\Http;

use DateTime;
use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Date\Date;
use Phoundation\Date\Time;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Web\Http\Exception\HttpException;
use Phoundation\Processes\Commands;
use Phoundation\Users\Users;
use Throwable;



/**
 * Class Http
 *
 * This class contains various HTTP processing methods
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * The status code that will be returned to the client
     *
     * @var int $status_code
     */
    protected static int $status_code = 200;



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
     * Returns the status code that will be sent to the client
     *
     * @return int
     */
    public static function getStatusCode(): int
    {
        return self::$status_code;
    }



    /**
     * Sets the status code that will be sent to the client
     *
     * @param int $code
     */
    public static function setStatusCode(int $code)
    {
        self::validateStatusCode($code);
        self::$status_code = $code;
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
            Http::cacheEtag();

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
                    $headers[] = 'Last-Modified: ' . Date::convert(filemtime($_SERVER['SCRIPT_FILENAME']), 'D, d M Y H:i:s', 'GMT') . ' GMT';

                } else {
                    $headers[] = 'Last-Modified: ' . Date::convert($params['last_modified'], 'D, d M Y H:i:s', 'GMT') . ' GMT';
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
                                $headers[] = 'Access-Control-Allow-' . Strings::capitalize($key) . ': ' . $value;
                            }

                            break;

                        default:
                            throw new HttpException(tr('Unknown CORS header ":header" specified', [':header' => $key]));
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

            // Set correct headers
            http_response_code($params['http_code']);

            if (($params['http_code'] != 200)) {
                Log::warning(tr('Phoundation sent :http for URL ":url"', array(':http' => ($params['http_code'] ? 'HTTP' . $params['http_code'] : 'HTTP0'), ':url' => (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])));

            } elseif (VERBOSE) {
                Log::success(tr('Phoundation sent :http for URL ":url"', array(':http' => ($params['http_code'] ? 'HTTP' . $params['http_code'] : 'HTTP0'), ':url' => (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])), 'http', 'green');
            }

            if (VERYVERBOSE) {
                Log::notice(tr('Page ":script" was processed in :time with ":usage" peak memory usage', array(':script' => $core->register['script'], ':time' => Time::difference(STARTTIME, microtime(true), 'auto', 5), ':usage' => bytes(memory_get_peak_usage()))));
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

        } catch (Throwable $e) {
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
     * Returns requested main mimetype, or if requested mimetype is accepted or not
     *
     * If $mimetype is specified, the function will return true if the specified mimetype is supported, or false, if not
     *
     * If $mimetype is not specified, the function will return the first mimetype that was specified in the HTTP ACCEPT header
     *
     * @see acceptsLanguages()
     * @version 2.4.11: Added function and documentation
     * @version 2.5.170: Added documentation, added support for $mimetype
     * @example
     * code
     * // This will return true
     * $result = accepts('image/webp');
     *
     * // This will return false
     * $result = accepts('image/foobar');
     *
     * // On a browser, this typically would return text/html
     * $result = accepts();
     * /code
     *
     * This would return
     * code
     * Foo...bar
     * /code
     *
     * @param null string $mimetype If specified, the mimetype that must be tested if accepted by the client
     * @return mixed If $mimetype was specified, true if the client accepts it, false if not. If $mimetype was not specified, a string will be returned containing the first requested mimetype
     */
    public static function accepts($mimetype)
    {
        static $headers = null;

        if (!$headers) {
            /*
             * Cleanup the HTTP accept headers (opera aparently puts spaces in
             * there, wtf?), then convert them to an array where the accepted
             * headers are the keys so that they are faster to access
             */
            $headers = isset_get($_SERVER['HTTP_ACCEPT']);
            $headers = str_replace(', ', '', $headers);
            $headers = Arrays::force($headers);
            $headers = array_flip($headers);
        }

        if ($mimetype) {
            // Return if the browser supports the specified mimetype
            return isset($headers[$mimetype]);
        }

        reset($headers);
        return key($headers);
    }



    /**
     * Parse the HTTP_ACCEPT_LANGUAGES header and return requested / available languages by priority and return a list of languages / locales accepted by the HTTP client
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @see accepts()
     * @note: This function is called by the startup system and its output stored in $core->register['accept_language']. There is typically no need to execute this function on any other places
     * @version 1.27.0: Added function and documentation
     *
     * @return array The list of accepted languages and locales as specified by the HTTP client
     */
    public static function acceptsLanguages(): array
    {
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // No accept language headers were specified
           $retval  = array('1.0' => array('language' => isset_get($_CONFIG['language']['default'], 'en'),
                                            'locale'   => Strings::cut(isset_get($_CONFIG['locale'][LC_ALL], 'US'), '_', '.')));

        } else {
            $headers = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            $headers = Arrays::force($headers, ',');
            $default = array_shift($headers);
            $retval  = array('1.0' => array('language' => Strings::until($default, '-'),
                                            'locale'   => (str_contains($default, '-') ? Strings::from($default, '-') : null)));

            if (empty($retval['1.0']['language'])) {
                // Specified accept language headers contain no language
                $retval['1.0']['language'] = isset_get($_CONFIG['language']['default'], 'en');
            }

            if (empty($retval['1.0']['locale'])) {
                // Specified accept language headers contain no locale
                $retval['1.0']['locale'] = Strings::cut(isset_get($_CONFIG['locale'][LC_ALL], 'US'), '_', '.');
            }

            foreach ($headers as $header) {
                $requested =  Strings::until($header, ';');
                $requested =  array('language' => Strings::until($requested, '-'),
                                    'locale'   => (str_contains($requested, '-') ? Strings::from($requested, '-') : null));

                if (empty($_CONFIG['language']['supported'][$requested['language']])) {
                    continue;
                }

                $retval[Strings::from(Strings::from($header, ';'), 'q=')] = $requested;
            }
        }

        krsort($retval);
        return $retval;
    }



    /**
     * Set the default context for SSL requests that phoundation has to make when using (for example) file_get_contents()
     *
     * @param bool|null $verify_peer
     * @param bool|null $verify_peer_name
     * @param bool|null $allow_self_signed
     * @return resource
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
    public static function validateGet()
    {
        global $_CONFIG;

        foreach ($_GET as $key => &$value) {
            if (!is_scalar($value)) {
                if ($value) {
                    throw new HttpException(tr('http_validate_get(): The $_GET key ":key" contains a value with the content ":content" while only scalar values are allowed', array(':key' => $key, ':content' => $value)), 400);
                }

                // The value is NULL
                $value = '';
            }
        }

        unset($value);

        $_GET['limit'] = (integer) ensure_value(isset_get($_GET['limit'], $_CONFIG['paging']['limit']), array_keys($_CONFIG['paging']['list']), $_CONFIG['paging']['limit']);
    }



    /**
     *
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @todo Remove $core dependancy
     * @todo Remove $_CONFIG dependancy
     */
    public static function done()
    {
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
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
     * @return array
     *
     * @todo Remove $params dependancy
     * @todo Remove $core dependancy
     * @todo Remove $_CONFIG dependancy
     */
    protected static function cache(array $params, int $http_code, array $headers = []): array
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
                switch (Core::getCallType()) {
                    case 'api':
                        // no-break
                    case 'ajax':
                        // no-break
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
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package http
     * @see Http::cache()
     * @version 2.5.92: Added function and documentation
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
        $core->register['etag'] = sha1(PROJECT.$_SERVER['SCRIPT_FILENAME'].filemtime($_SERVER['SCRIPT_FILENAME']) . $etag);

        if (!$_CONFIG['cache']['http']['enabled']) {
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
    }



    /*
     * Test HTTP caching headers
     *
     * Sends out 304 - Not modified header if ETag matches
     *
     * For more information, see https://developers.google.com/speed/docs/insights/LeverageBrowserCaching
     * and https://developers.google.com/web/fundamentals/performance/optimizing-content-efficiency/http-caching
     */
    protected static function cacheEtag()
    {
        /*
         * ETAG requires HTTP caching enabled
         * Ajax and API calls do not use ETAG
         */
        if (!$_CONFIG['cache']['http']['enabled'] or Core::getCallType('ajax') or Core::getCallType('api')) {
            unset($core->register['etag']);
            return false;
        }

        /*
         * Create local ETAG
         */
        $core->register['etag'] = sha1(PROJECT.$_SERVER['SCRIPT_FILENAME'].filemtime($_SERVER['SCRIPT_FILENAME']) . Core::readRegister('etag'));

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
    }


    /**
     * Add a variable to the specified URL
     *
     * @param $url
     * @param $key
     * @param $value
     * @return mixed|string
     * @throws HttpException
     */
    public static function addVariable(string $url, string $key, int|float|string|array $value): string
    {
        if (!$key or !$value) {
            return $url;
        }

        if (str_contains($url, '?')) {
            return $url.'&'.urlencode($key) . '='.urlencode($value);
        }

        return $url.'?'.urlencode($key) . '='.urlencode($value);
    }



    /**
     * Remove a variable from the specified URL
     *
     * @param string $url
     * @param string $key
     * @return string
     * @throws HttpException
     */
    public static function removeVariable(string $url, string $key): string
    {
        throw new UnderConstructionException('Http::removeVariable() is under construction!');
        //if (!$key) {
        //    return $url;
        //}
        //
        //if ($pos = strpos($url, $key . '=') === false) {
        //    return $url;
        //}
        //
        //if ($pos2 = strpos($url, '&', $pos) === false) {
        //    return substr($url, 0, $pos).;
        //}
        //
        //return substr($url, 0, );
    }



    /**
     * Redirect to the specified $target
     *
     * @param string $url
     * @param integer|null $http_code
     * @param boolean $clear_session_redirect
     * @param integer $time_delay
     * @return void
     */
    #[NoReturn] public static function redirect(string $url = '', ?int $http_code = null, bool $clear_session_redirect = true, ?int $time_delay = null): void
    {
        global $_CONFIG;

        if (PLATFORM != 'http') {
            throw new CoreException(tr('redirect(): This function can only be called on webservers'));
        }

        /*
         * Special targets?
         */
        if (($url === true) or ($url === 'self')) {
            /*
             * Special redirect. Redirect to this very page. Usefull for right after POST requests to avoid "confirm post submissions"
             */
            $url = $_SERVER['REQUEST_URI'];

        } elseif ($url === 'prev') {
            /*
             * Special redirect. Redirect to this very page. Usefull for right after POST requests to avoid "confirm post submissions"
             */
            $url = isset_get($_SERVER['HTTP_REFERER']);

            if (!$url or ($url == $_SERVER['REQUEST_URI'])) {
                /*
                 * Don't redirect to the same page! If the referrer was this page, then drop back to the index page
                 */
                $url = $_CONFIG['redirects']['index'];
            }

        } elseif ($url === false) {
            /*
             * Special redirect. Redirect to this very page, but without query
             */
            $url = Strings::until($_SERVER['REQUEST_URI'], '?');

        } elseif (!$url) {
            /*
             * No target specified, redirect to index page
             */
            $url = $_CONFIG['redirects']['index'];
        }

        if (empty($http_code)) {
            if (is_numeric($clear_session_redirect)) {
                $http_code              = $clear_session_redirect;
                $clear_session_redirect = true;

            } else {
                $http_code              = 301;
            }

        } else {
            if (is_numeric($clear_session_redirect)) {
                $clear_session_redirect = true;
            }
        }

        /*
         * Validate the specified http_code, must be one of
         *
         * 301 Moved Permanently
         * 302 Found
         * 303 See Other
         * 307 Temporary Redirect
         */
        switch ($http_code) {
            case 301:
                // no-break
            case 302:
                // no-break
            case 303:
                // no-break
            case 307:
                /*
                 * All valid
                 */
                break;

            default:
                throw new CoreException(tr('redirect(): Invalid HTTP code ":code" specified', array(':code' => $http_code)), 'invalid-http-code');
        }

        if ($clear_session_redirect) {
            if (!empty($_SESSION)) {
                unset($_GET['redirect']);
                unset($_SESSION['sso_referrer']);
            }
        }

        if ((substr($url, 0, 1) != '/') and (substr($url, 0, 7) != 'http://') and (substr($url, 0, 8) != 'https://')) {
            $url = $_CONFIG['url_prefix'] . $url;
        }

        $url = Url::redirect($url);

        if ($time_delay) {
            log_file(tr('Redirecting with ":time" seconds delay to url ":url"', array(':time' => $time_delay, ':url' => $url)), null, 'cyan');
            header('Refresh: ' . $time_delay.';' . $url, true, $http_code);
            die();
        }

        log_file(tr('Redirecting to url ":url"', array(':url' => $url)), null, 'cyan');
        header('Location:' . Url::redirect($url), true, $http_code);
        die();
    }



    /**
     * Redirect if the session redirector is set
     *
     * @param string $method
     * @param false $force
     * @throws CoreException
     */
    public static function sessionRedirect(string $method = 'http', bool $force = false)
    {
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
                Json::reply(isset_get($redirect, '/'), 'redirect');

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
    }



    /**
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
    public static function getSubmit() {
        static $submit;

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
    }



//    /*
//     * Returns requested main mimetype, or if requested mimetype is accepted or not
//     *
//     * If $mimetype is specified, the function will return true if the specified mimetype is supported, or false, if not
//     *
//     * If $mimetype is not specified, the function will return the first mimetype that was specified in the HTTP ACCEPT header
//     *
//     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
//     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
//     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
//     * @category Function reference
//     * @package system
//     * @see accepts_languages()
//     * @version 2.4.11: Added function and documentation
//     * @version 2.5.170: Added documentation, added support for $mimetype
//     * @example
//     * code
//     * // This will return true
//     * $result = accepts('image/webp');
//     *
//     * // This will return false
//     * $result = accepts('image/foobar');
//     *
//     * // On a browser, this typically would return text/html
//     * $result = accepts();
//     * /code
//     *
//     * This would return
//     * code
//     * Foo...bar
//     * /code
//     *
//     * @param null string $mimetype If specified, the mimetype that must be tested if accepted by the client
//     * @return mixed If $mimetype was specified, true if the client accepts it, false if not. If $mimetype was not specified, a string will be returned containing the first requested mimetype
//     */
//    function accepts($mimetype = null)
//    {
//        static $headers = null;
//
//        try {
//            if (!$headers) {
//                /*
//                 * Cleanup the HTTP accept headers (opera aparently puts spaces in
//                 * there, wtf?), then convert them to an array where the accepted
//                 * headers are the keys so that they are faster to access
//                 */
//                $headers = isset_get($_SERVER['HTTP_ACCEPT']);
//                $headers = str_replace(', ', '', $headers);
//                $headers = Arrays::force($headers);
//                $headers = array_flip($headers);
//            }
//
//            if ($mimetype) {
//                /*
//                 * Return if the browser supports the specified mimetype
//                 */
//                return isset($headers[$mimetype]);
//            }
//
//            reset($headers);
//            return key($headers);
//
//        } catch (Exception $e) {
//            throw new OutOfBoundsException(tr('accepts(): Failed'), $e);
//        }
//    }
//
//
//    /*
//     * Parse the HTTP_ACCEPT_LANGUAGES header and return requested / available languages by priority and return a list of languages / locales accepted by the HTTP client
//     *
//     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
//     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
//     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
//     * @category Function reference
//     * @package system
//     * @see accepts()
//     * @note: This function is called by the startup system and its output stored in $core->register['accept_language']. There is typically no need to execute this function on any other places
//     * @version 1.27.0: Added function and documentation
//     *
//     * @return array The list of accepted languages and locales as specified by the HTTP client
//     */
//    function accepts_languages()
//    {
//        global $_CONFIG;
//
//        try {
//            if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
//                /*
//                 * No accept language headers were specified
//                 */
//                $retval = array('1.0' => array('language' => isset_get($_CONFIG['language']['default'], 'en'),
//                    'locale' => Strings::cut(isset_get($_CONFIG['locale'][LC_ALL], 'US'), '_', '.')));
//
//            } else {
//                $headers = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
//                $headers = Arrays::force($headers, ',');
//                $default = array_shift($headers);
//                $retval = array('1.0' => array('language' => Strings::until($default, '-'),
//                    'locale' => (str_contains($default, '-') ? Strings::from($default, '-') : null)));
//
//                if (empty($retval['1.0']['language'])) {
//                    /*
//                     * Specified accept language headers contain no language
//                     */
//                    $retval['1.0']['language'] = isset_get($_CONFIG['language']['default'], 'en');
//                }
//
//                if (empty($retval['1.0']['locale'])) {
//                    /*
//                     * Specified accept language headers contain no locale
//                     */
//                    $retval['1.0']['locale'] = Strings::cut(isset_get($_CONFIG['locale'][LC_ALL], 'US'), '_', '.');
//                }
//
//                foreach ($headers as $header) {
//                    $requested = Strings::until($header, ';');
//                    $requested = array('language' => Strings::until($requested, '-'),
//                        'locale' => (str_contains($requested, '-') ? Strings::from($requested, '-') : null));
//
//                    if (empty($_CONFIG['language']['supported'][$requested['language']])) {
//                        continue;
//                    }
//
//                    $retval[Strings::from(Strings::from($header, ';'), 'q=')] = $requested;
//                }
//            }
//
//            krsort($retval);
//            return $retval;
//
//        } catch (Exception $e) {
//            throw new OutOfBoundsException(tr('accepts_languages(): Failed'), $e);
//        }
//    }
//

    /**
     * Download the specified single file to the specified path
     *
     * If the path is not specified then by default the function will download to the TMP directory; ROOT/data/tmp
     *
     * @param string $url             The URL of the file to be downloaded
     * @param bool $contents          If set to false, will return the contents of the downloaded file instead of the
     *                                target filename. As the caller function will not know the exact filename used, the
     *                                target file will be deleted automatically! If set to a string
     * @param callable|null $callback If specified, download will execute this callback with either the filename or file
     *                                contents (depending on $section)
     * @return string The path to the downloaded file
     * @example This shows how to download a single file
     * code
     * $result = download('https://capmega.com', TMP);
     * showdie($result);
     * /code
     *
     * This would display
     * code
     * ROOT/data/tmp/capmega.com
     * /code
     *
     */
    function download(string $url, bool $contents = false, callable $callback = null): string
    {
        $file = Commands::wget($url);

        if ($contents) {
            /*
             * Do not return the filename but the file contents instead
             * When doing this, automatically delete the temporary file in
             * question, since the caller will not know the exact file name used
             */
            $retval = file_get_contents($file);
            file_delete($file);

            if ($callback) {
                $callback($retval);
            }

            return $retval;
        }

        /*
         * No section was specified, return contents of file instead.
         */
        if ($callback) {
            /*
             * Execute the callbacks before returning the data, delete the
             * temporary file after
             */
            $callback($file);
            file_delete($file);
        }

        return $file;
    }



    /**
     * Checks if an extended session is available for this user
     *
     * @return bool
     */
    function check_extended_session(): bool
    {
        if (empty($_CONFIG['sessions']['extended']['enabled'])) {
            return false;
        }

        if (isset($_COOKIE['extsession']) and !isset($_SESSION['user'])) {
            // Pull  extsession data
            $ext = sql_get('SELECT `users_id` FROM `extended_sessions` WHERE `session_key` = ":session_key" AND DATE(`addedon`) < DATE(NOW());', array(':session_key' => cfm($_COOKIE['extsession'])));

            if ($ext['users_id']) {
                $user = sql_get('SELECT * FROM `users` WHERE `users`.`id` = :id', array(':id' => cfi($ext['users_id'])));

                if ($user['id']) {
                    // Auto sign in user
                    Users::signin($user, true);
                    return true;

                } else {
                    // Remove cookie
                    setcookie('extsession', 'stub', 1);
                }

            } else {
                // Remove cookie
                setcookie('extsession', 'stub', 1);
            }
        }

        return false;
    }



    /**
     * Generate a CSRF code and set it in the $_SESSION[csrf] array
     *
     * @param string|null $prefix
     * @return string
     */
    function set_csrf(?string $prefix = null): string
    {
        if (empty($_CONFIG['security']['csrf']['enabled'])) {
            /*
             * CSRF check system has been disabled
             */
            return false;
        }

        if (Core::readRegister('csrf')) {
            return Core::readRegister('csrf');
        }

        /*
         * Avoid people messing around
         */
        if (isset($_SESSION['csrf']) and (count($_SESSION['csrf']) >= $_CONFIG['security']['csrf']['buffer_size'])) {
            /*
             * Too many csrf, so too many post requests open. Remove the oldest
             * CSRF code and add a new one
             */
            if (count($_SESSION['csrf']) >= ($_CONFIG['security']['csrf']['buffer_size'] + 5)) {
                /*
                 * WTF? How did we get so many?? Throw it all away, start over
                 */
                unset($_SESSION['csrf']);

            } else {
                array_shift($_SESSION['csrf']);
            }
        }

        $csrf = $prefix . Strings::unique('sha256');

        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = array();
        }

        $_SESSION['csrf'][$csrf] = new DateTime();
        $_SESSION['csrf'][$csrf] = $_SESSION['csrf'][$csrf]->getTimestamp();

        Core::readRegister('csrf', $csrf);
        return $csrf;
    }



    /**
     * Check that the CSRF was valid
     *
     * @return bool
     */
    function checkCsrf(): bool
    {
        global $_CONFIG, $core;

        try {
            if (empty($_CONFIG['security']['csrf']['enabled'])) {
                /*
                 * CSRF check system has been disabled
                 */
                return false;
            }

            if (!Core::getCallType('http') and !Core::getCallType('admin')) {
                /*
                 * CSRF only works for HTTP or ADMIN requests
                 */
                return false;
            }

            if (!empty($core->register['csrf_ok'])) {
                /*
                 * CSRF check has already been executed for this post, all okay!
                 */
                return true;
            }

            if (empty($_POST)) {
                /*
                 * There is no POST data
                 */
                return false;
            }

            if (empty($_POST['csrf'])) {
                log_file(Core::getCallType());
                throw new OutOfBoundsException(tr('check_csrf(): No CSRF field specified'), 'warning/not-specified');
            }

            if (Core::getCallType('ajax')) {
                if (substr($_POST['csrf'], 0, 5) != 'ajax_') {
                    /*
                     * Invalid CSRF code is sppokie, don't make this a warning
                     */
                    throw new OutOfBoundsException(tr('check_csrf(): Specified CSRF ":code" is invalid'), 'invalid');
                }
            }

            if (empty($_SESSION['csrf'][$_POST['csrf']])) {
                throw new OutOfBoundsException(tr('check_csrf(): Specified CSRF ":code" does not exist', array(':code' => $_POST['csrf'])), 'warning/not-exist');
            }

            /*
             * Get the code from $_SESSION and delete it so it won't be used twice
             */
            $timestamp = $_SESSION['csrf'][$_POST['csrf']];
            $now = new DateTime();

            unset($_SESSION['csrf'][$_POST['csrf']]);

            /*
             * Code timed out?
             */
            if ($_CONFIG['security']['csrf']['timeout']) {
                if (($timestamp + $_CONFIG['security']['csrf']['timeout']) < $now->getTimestamp()) {
                    throw new OutOfBoundsException(tr('check_csrf(): Specified CSRF ":code" timed out', array(':code' => $_POST['csrf'])), 'warning/timeout');
                }
            }

            $core->register['csrf_ok'] = true;

            if (Core::getCallType('ajax')) {
                /*
                 * Send new CSRF code with the AJAX return payload
                 */
                $core->register['ajax_csrf'] = set_csrf('ajax_');
            }

            return true;

        } catch (Throwable $e) {
            /*
             * CSRF check failed, drop $_POST
             */
            foreach ($_POST as $key => $value) {
                if (substr($key, -6, 6) === 'submit') {
                    unset($_POST[$key]);
                }
            }

            log_file('aaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
            log_file(Core::getCallType('http'));
            log_file($e);
            html_flash_set(tr('The form data was too old, please try again'), 'warning');
        }
    }



    /**
     * Limit the HTTP request to the specified request type, typically GET or POST
     *
     * If the HTTP request is not of the specified type, this function will throw an exception
     *
     * @version 2.7.98: Added function and documentation
     *
     * @param string $method
     * @return void
     */
    function limitRequestMethod(string $method): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== $method) {
            throw new OutOfBoundsException(tr('limit_request_method(): This request was made with HTTP method ":server_method" but for this page or call only HTTP method ":method" is allowed', array(':method' => $method, ':server_method' => $_SERVER['REQUEST_METHOD'])), 'warning/method-not-allowed');
        }
    }



    /**
     * Throws an exception if the specified status code is invalid
     *
     * @param int $code
     */
    protected static function validateStatusCode(int $code): void
    {
        // TODO Implement
        throw new OutOfBoundsException(tr('The specified status code ":code" is invalid', [':code' => $code]));
    }

}