<?php

namespace Phoundation\Web\Http;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Numbers;
use Phoundation\Core\Strings;
use Phoundation\Date\Date;
use Phoundation\Date\Time;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Notifications\Notification;
use Phoundation\Web\Http\Exception\HttpException;
use Phoundation\Processes\Commands\Command;
use Phoundation\Web\Page;
use Throwable;



/**
 * Class Http
 *
 * This class contains various HTTP processing methods
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
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
     * Tracks if Http::sendHeaders() sent headers already or not.
     *
     * @note IMPORTANT: Since flush() and ob_flush() will NOT lock headers until the buffers are actually flushed, and
     *                  they will neither actually flush the buffers as long as the process is running AND the buffers
     *                  are not full yet, weird things can happen. With a buffer of 4096 bytes (typically), echo 100
     *                  characters, and then execute Http::sendHeaders(), then ob_flush() and flush() and headers_sent()
     *                  will STILL be false, and REMAIN false until the buffer has reached 4096 characters OR the
     *                  process ends. This variable just keeps track if Http::sendHeaders() has been executed (and it
     *                  won't execute again), but headers might still be sent out manually. This is rather messed up,
     *                  because it really shows as if information was sent, the buffers are flushed, yet nothing is
     *                  actually flushed, so the headers are also not sent. This is just messed up PHP.
     * @var bool $sent
     */
    protected static bool $sent = false;

    /**
     * The status code that will be returned to the client
     *
     * @var int $http_code
     */
    protected static int $http_code = 200;

    /**
     * The client specified ETAG for this request
     *
     * @var string|null $etag
     */
    protected static ?string $etag = null;

    /**
     * The list of meta data that the client accepts
     *
     * @var array|null $accepts
     */
    protected static ?array $accepts = null;

    /**
     * CORS headers
     *
     * @var array $cors
     */
    protected static array $cors = [];

    /**
     * Content-type header
     *
     * @var string|null $content_type
     */
    protected static ?string $content_type = null;



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
    public static function getHttpCode(): int
    {
        return self::$http_code;
    }



    /**
     * Sets the status code that will be sent to the client
     *
     * @param int $code
     * @return Http
     */
    public static function setHttpCode(int $code): Http
    {
        // Validate status code
        // TODO implement

        self::$http_code = $code;
        return self::getInstance();
    }



    /**
     * Returns the mimetype / content type
     *
     * @return string|null
     */
    public static function getContentType(): ?string
    {
        return self::$content_type;
    }



    /**
     * Sets the mimetype / content type
     *
     * @param string $content_type
     * @return Http
     */
    public static function setContentType(string $content_type): Http
    {
        // Validate status code
        // TODO implement

        self::$content_type = $content_type;
        return self::getInstance();
    }



    /**
     * Returns the CORS headers
     *
     * @return array
     */
    public static function getCors(): array
    {
        return self::$cors;
    }


    /**
     * Sets the status code that will be sent to the client
     *
     * @param string $origin
     * @param string $methods
     * @param string $headers
     * @return void
     */
    public static function setCors(string $origin, string $methods, string $headers): void
    {
        // Validate CORS data
        // TODO implement validation

        self::$cors = [
            'origin'  => '*.',
            'methods' => 'GET, POST',
            'headers' => ''
        ];
    }



    /**
     * Builds and returns all the HTTP headers
     *
     * @return array|null
     * @todo Refactor and remove $_CONFIG dependancies
     * @todo Refactor and remove $core dependancies
     * @todo Refactor and remove $params dependancies
     */
    public static function buildHeaders(): ?array
    {
        if (self::headersSent()) {
            return null;
        }

        // Remove incorrect or insecure headers
        header_remove('Expires');
        header_remove('Pragma');

        /*
         * Ensure that from this point on we have a language configuration available
         *
         * The startup systems already configures languages but if the startup itself fails, or if a show() or showdie()
         * was issued before the startup finished, then this could leave the system without defined language
         */
        if (!defined('LANGUAGE')) {
            define('LANGUAGE', Config::get('http.language.default', 'en'));
        }

        // Create ETAG, possibly send out HTTP304 if client sent matching ETAG
        Http::cacheEtag();

        // What to do with the PHP signature?
        $signature = Config::get('security.expose.php-signature', false);

        if (!$signature) {
            // Remove the PHP signature
            header_remove('X-Powered-By');

        } elseif (!is_bool($signature)) {
            // Send custom (fake) X-Powered-By header
            $headers[] = 'X-Powered-By: ' . $signature;
        }

        // Add Phoundation signature?
        if (Config::getBoolean('security.expose.phoundation-signature', false)) {
            header('X-Powered-By: Phoundation ' . Core::FRAMEWORKCODEVERSION);
        }

        $headers[] = 'Content-Type: ' . self::$content_type . '; charset=' . Config::get('languages.encoding.charset', 'UTF-8');
        $headers[] = 'Content-Language: ' . LANGUAGE;
        $headers[] = 'Content-Length: ' . Page::getContentLength();

        if (self::$http_code == 200) {
            if (empty($params['last_modified'])) {
                $headers[] = 'Last-Modified: ' . Date::convert(filemtime($_SERVER['SCRIPT_FILENAME']), 'D, d M Y H:i:s', 'GMT') . ' GMT';

            } else {
                $headers[] = 'Last-Modified: ' . Date::convert($params['last_modified'], 'D, d M Y H:i:s', 'GMT') . ' GMT';
            }
        }

        // Add noidex, nofollow and nosnipped headers for non production environments and non normal HTTP pages.
        // These pages should NEVER be indexed
        if (!Debug::production() or !Core::getCallType('http') or Config::get('web.noindex', false)) {
            $headers[] = 'X-Robots-Tag: noindex, nofollow, nosnippet, noarchive, noydir';
        }

        // CORS headers
        if (Config::get('web.security.cors', true) or self::$cors) {
            // Add CORS / Access-Control-Allow-.... headers
            // TODO This will cause issues if configured web.cors is not an array!
            self::$cors = array_merge(Arrays::force(Config::get('web.cors', [])), self::$cors);

            foreach (self::$cors as $key => $value) {
                switch ($key) {
                    case 'origin':
                        if ($value == '*.') {
                            // Origin is allowed from all subdomains
                            $origin = Strings::from(isset_get($_SERVER['HTTP_ORIGIN']), '://');
                            $length = strlen(isset_get($_SESSION['domain']));

                            if (substr($origin, -$length, $length) === isset_get($_SESSION['domain'])) {
                                // Sub domain matches. Since CORS does not support sub domains, just show the
                                // current sub domain.
                                $value = $_SERVER['HTTP_ORIGIN'];

                            } else {
                                // Sub domain does not match. Since CORS does not support sub domains, just show no
                                // allowed origin domain at all
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

        // Add cache headers and store headers in object headers list
        return self::cache($headers);
    }



    /**
     * Send all the specified HTTP headers
     *
     * @note The amount of sent bytes does NOT include the bytes sent for the HTTP response code header
     * @param array $headers
     * @return int The amount of bytes sent. -1 if Http::sendHeaders() was called for the second time.
     */
    public static function sendHeaders(array $headers = []): int
    {
        if (self::headersSent(true)) {
            return -1;
        }

        try {
            $length = 0;

            // Set correct headers
            http_response_code(self::$http_code);

            if ((self::$http_code != 200)) {
                Log::warning(tr('Phoundation sent "HTTP:http" for URL ":url"', [
                    ':http' => (self::$http_code ? 'HTTP' . self::$http_code : 'HTTP0'),
                    ':url'  => (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
                ]));
            } else {
                Log::success(tr('Phoundation sent :http for URL ":url"', [
                    ':http' => (self::$http_code ? 'HTTP' . self::$http_code : 'HTTP0'),
                    ':url' => (empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
                ]));
            }

            // Send all available headers
            foreach ($headers as $header) {
                $length += strlen($header);
                header($header);
            }

            return $length;

        } catch (Throwable $e) {
            Notification::new()
                ->setException($e)
                ->setTitle(tr('Failed to send headers to client'))
                ->send();

            // Http::sendHeaders() itself crashed. Since Http::sendHeaders() would send out http 500, and since it
            // crashed, it no longer can do this, send out the http 500 here.
            http_response_code(500);
            throw new $e;
        }
    }



    /**
     * Returns requested main mimetype, or if requested mimetype is accepted or not
     *
     * The function will return true if the specified mimetype is supported, or false, if not
     *
     * @see Http::acceptsLanguages()
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
     * @param string $mimetype The mimetype that hopefully is accepted by the client
     * @return mixed True if the client accepts it, false if not
     */
    public static function accepts(string $mimetype): bool
    {
        static $headers = null;

        if (!$mimetype) {
            throw new OutOfBoundsException(tr('No mimetype specified'));
        }

        if (!$headers) {
            // Cleanup the HTTP accept headers (opera aparently puts spaces in there, wtf?), then convert them to an
            // array where the accepted headers are the keys so that they are faster to access
            $headers = isset_get($_SERVER['HTTP_ACCEPT']);
            $headers = str_replace(', ', '', $headers);
            $headers = Arrays::force($headers);
            $headers = array_flip($headers);
        }

        // Return if the client supports the specified mimetype
        return isset($headers[$mimetype]);
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
           $return  = [
               '1.0' => [
                   'language' => Config::get('languages.default', 'en'),
                   'locale'   => Strings::cut(Config::get('locale.LC_ALL', 'US'), '_', '.')
               ]
           ];

        } else {
            $headers = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            $headers = Arrays::force($headers, ',');
            $default = array_shift($headers);
            $return  = [
                '1.0' => [
                    'language' => Strings::until($default, '-'),
                    'locale'   => (str_contains($default, '-') ? Strings::from($default, '-') : null)
                ]
            ];

            if (empty($return['1.0']['language'])) {
                // Specified accepts language headers contain no language
                $return['1.0']['language'] = isset_get($_CONFIG['language']['default'], 'en');
            }

            if (empty($return['1.0']['locale'])) {
                // Specified accept language headers contain no locale
                $return['1.0']['locale'] = Strings::cut(isset_get($_CONFIG['locale'][LC_ALL], 'US'), '_', '.');
            }

            foreach ($headers as $header) {
                $requested =  Strings::until($header, ';');
                $requested =  [
                    'language' => Strings::until($requested, '-'),
                    'locale'   => (str_contains($requested, '-') ? Strings::from($requested, '-') : null)
                ];

                if (empty(Config::get('languages.supported', [])[$requested['language']])) {
                    continue;
                }

                $return[Strings::from(Strings::from($header, ';'), 'q=')] = $requested;
            }
        }

        krsort($return);
        return $return;
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
        $verify_peer = not_null($verify_peer, Config::get('security.ssl.verify.peer', true));
        $verify_peer_name = not_null($verify_peer, Config::get('security.ssl.verify.peer_name', true));
        $allow_self_signed = not_null($verify_peer, Config::get('security.ssl.verify.self_signed', true));

        return stream_context_set_default([
            'ssl' => [
                'verify_peer'       => $verify_peer,
                'verify_peer_name'  => $verify_peer_name,
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

// TODO Implement
// TODO This would break Route class when no query variables may be passed!
//        $_GET['limit'] = (integer) ensure_value(isset_get($_GET['limit'], Config::get('paging.limit', 50)), array_keys(Config::get('paging.list', [10 => tr('Show 10 entries')])), Config::get('paging.limit', 50));
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

     * @param array $headers Any extra headers that are required
     * @return array
     */
    protected static function cache(array $headers): array
    {
        if (Config::get('web.cache.enabled', 'auto') === 'auto') {
            // PHP will take care of the cache headers

        } elseif (Config::get('web.cache.enabled', 'auto') === true) {
            // Place headers using phoundation algorithms
            if (!Config::get('web.cache.enabled', 'auto') or (self::$http_code != 200)) {
                // Non HTTP 200 / 304 pages should NOT have cache enabled! For example 404, 503 etc...
                $headers[] = 'Cache-Control: no-store, max-age=0';
                self::$etag = null;

            } else {
                // Send caching headers. Ajax, API, and admin calls do not have proxy caching
                switch (Core::getCallType()) {
                    case 'api':
                        // no-break
                    case 'ajax':
                        // no-break
                    case 'admin':
                        break;

                    default:
                        // Session pages for specific users should not be stored on proxy servers either
                        if (!empty($_SESSION['user']['id'])) {
                            Config::get('web.cache.cacheability', 'private');
                        }

                        $headers[] = 'Cache-Control: ' . Config::get('web.cache.cacheability', 'private') . ', ' . Config::get('web.cache.expiration', 'max-age=604800') . ', ' . Config::get('web.cache.revalidation', 'must-revalidate') . Config::get('web.cache.other', 'no-transform');

                        if (!empty(self::$etag)) {
                            $headers[] = 'ETag: "' . self::$etag . '"';
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
        self::$etag = sha1(PROJECT.$_SERVER['SCRIPT_FILENAME'].filemtime($_SERVER['SCRIPT_FILENAME']) . $etag);

        if (!Config::get('web.cache.enabled', 'auto')) {
            return false;
        }

        if (Core::getCallType('ajax') or Core::getCallType('api')) {
            return false;
        }

        if ((strtotime(isset_get($_SERVER['HTTP_IF_MODIFIED_SINCE'])) == filemtime($_SERVER['SCRIPT_FILENAME'])) or trim(isset_get($_SERVER['HTTP_IF_NONE_MATCH']), '') == self::$etag) {
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
    protected static function cacheEtag(): bool
    {
        // ETAG requires HTTP caching enabled. Ajax and API calls do not use ETAG
        if (!Config::get('web.cache.enabled', 'auto') or Core::getCallType('ajax') or Core::getCallType('api')) {
            self::$etag = null;
            return false;
        }

        // Create local ETAG
        self::$etag = sha1(PROJECT.$_SERVER['SCRIPT_FILENAME'].filemtime($_SERVER['SCRIPT_FILENAME']) . Core::readRegister('etag'));

// :TODO: Document why we are trimming with an empty character mask... It doesn't make sense but something tells me we're doing this for a good reason...
        if (trim(isset_get($_SERVER['HTTP_IF_NONE_MATCH']), '') == self::$etag) {
            if (empty($core->register['flash'])) {
                // The client sent an etag which is still valid, no body (or anything else) necessary
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
        if (!PLATFORM_HTTP) {
            throw new HttpException(tr('This function can only be called on webservers'));
        }

        // Special targets?
        if (($url === true) or ($url === 'self')) {
            // Special redirect. Redirect to this very page. Usefull for right after POST requests to avoid
            // "confirm post submissions"
            $url = $_SERVER['REQUEST_URI'];

        } elseif ($url === 'prev') {
            // Special redirect. Redirect to this very page. Usefull for right after POST requests to avoid
            // "confirm post submissions"
            $url = isset_get($_SERVER['HTTP_REFERER']);

            if (!$url or ($url == $_SERVER['REQUEST_URI'])) {
                // Don't redirect to the same page! If the referrer was this page, then drop back to the index page
                $url = Config::get('web.redirects.index', '/');
            }

        } elseif ($url === false) {
            // Special redirect. Redirect to this very page, but without query
            $url = Strings::until($_SERVER['REQUEST_URI'], '?');

        } elseif (!$url) {
            // No target specified, redirect to index page
            $url = Config::get('web.redirects.index', '/');
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
                throw new HttpException(tr('Invalid HTTP code ":code" specified', [':code' => $http_code]));
        }

        if ($clear_session_redirect) {
            if (!empty($_SESSION)) {
                unset($_GET['redirect']);
                unset($_SESSION['sso_referrer']);
            }
        }

        if ((!str_starts_with($url, '/')) and (!str_starts_with($url, 'http://')) and (!str_starts_with($url, 'https://'))) {
            $url = Config::get('web.url.prefix', '') . $url;
        }

        $url = Url::redirect($url);

        if ($time_delay) {
            Log::action(tr('Redirecting with ":time" seconds delay to url ":url"', [':time' => $time_delay, ':url' => $url]));
            header('Refresh: ' . $time_delay.';' . $url, true, $http_code);
            die();
        }

        Log::action(tr('Redirecting to url ":url"', [':url' => $url]));
        header('Location:' . Url::redirect($url), true, $http_code);
        die();
    }



    /**
     * Redirect if the session redirector is set
     *
     * @param string $method
     * @param false $force
     * @throws HttpException
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
                throw new HttpException(tr('session_redirect(): Unknown method ":method" specified. Please speficy one of "json", or "http"', array(':method' => $method)), 'unknown');
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



    /**
     * Checks if headers have already been sent and logs warnings if so
     *
     * @param bool $send_now
     * @return bool
     */
    protected static function headersSent(bool $send_now = false): bool
    {
        if (headers_sent($file, $line)) {
            Log::warning(tr('Will not send headers again, output started at ":file@:line. Adding backtrace to debug this request', [
                ':file' => $file,
                ':line' => $line
            ]));
            Log::backtrace();
            return true;
        }

        if (self::$sent) {
            // Since
            Log::warning(tr('Headers already sent by Http::sendHeaders(). This can happen with PHP due to PHP ignoring output buffer flushes, causing this to be called over and over. just ignore this message.'), 2);
            return true;
        }

        if ($send_now) {
            self::$sent = true;
        }

        return false;
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
//                $return = array('1.0' => array('language' => isset_get($_CONFIG['language']['default'], 'en'),
//                    'locale' => Strings::cut(isset_get($_CONFIG['locale'][LC_ALL], 'US'), '_', '.')));
//
//            } else {
//                $headers = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
//                $headers = Arrays::force($headers, ',');
//                $default = array_shift($headers);
//                $return = array('1.0' => array('language' => Strings::until($default, '-'),
//                    'locale' => (str_contains($default, '-') ? Strings::from($default, '-') : null)));
//
//                if (empty($return['1.0']['language'])) {
//                    /*
//                     * Specified accept language headers contain no language
//                     */
//                    $return['1.0']['language'] = isset_get($_CONFIG['language']['default'], 'en');
//                }
//
//                if (empty($return['1.0']['locale'])) {
//                    /*
//                     * Specified accept language headers contain no locale
//                     */
//                    $return['1.0']['locale'] = Strings::cut(isset_get($_CONFIG['locale'][LC_ALL], 'US'), '_', '.');
//                }
//
//                foreach ($headers as $header) {
//                    $requested = Strings::until($header, ';');
//                    $requested = array('language' => Strings::until($requested, '-'),
//                        'locale' => (str_contains($requested, '-') ? Strings::from($requested, '-') : null));
//
//                    if (empty(Config::get('languages.supported', [])[$requested['language']])) {
//                        continue;
//                    }
//
//                    $return[Strings::from(Strings::from($header, ';'), 'q=')] = $requested;
//                }
//            }
//
//            krsort($return);
//            return $return;
//
//        } catch (Exception $e) {
//            throw new OutOfBoundsException(tr('accepts_languages(): Failed'), $e);
//        }
//    }
//

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



    /*
     * ???
     *
     * Generate and return a randon name for the specified $name, and store the
     * link between the two under "group"
     */
    public static function encodePostVariable(string $key)
    {
        static $translations = [];

        if (!isset($translations[$name])) {
            $translations[$name] = '__HT'.$name.'__'.substr(unique_code('sha256'), 0, 16);
        }

        return $translations[$name];
    }



    /*
     * Return the $_POST value for the translated specified key
     */
    function untranslate() {
        $count = 0;

        foreach ($_POST as $key => $value) {
            if (substr($key, 0, 4) == '__HT') {
                $_POST[Strings::until(substr($key, 4), '__')] = $_POST[$key];
                unset($_POST[$key]);
                $count++;
            }
        }

        return $count;
    }


}