<?php

declare(strict_types=1);

namespace Phoundation\Web\Http;

use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Utils\Config;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Http\Exception\HttpException;


/**
 * Class Http
 *
 * This class contains various HTTP processing methods
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * Singleton
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
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
    public static function setSslDefaultContext(?bool $verify_peer = null, ?bool $verify_peer_name = null, ?bool $allow_self_signed = null)
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
                    throw new ValidationFailedException(tr('The $_GET key ":key" contains a value with the content ":content" while only scalar values are allowed', [
                        ':key'     => $key,
                        ':content' => $value
                    ]));
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


//    /**
//     * Redirect to the specified $target
//     *
//     * @param string $url
//     * @param integer|null $http_code
//     * @param boolean $clear_session_redirect
//     * @param integer $time_delay
//     * @return never
//     */
//    #[NoReturn] public static function redirect(string $url = '', ?int $http_code = null, bool $clear_session_redirect = true, ?int $time_delay = null): never
//    {
//        if (!PLATFORM_WEB) {
//            throw new HttpException(tr('This function can only be called on webservers'));
//        }
//
//        // Special targets?
//        if (($url === true) or ($url === 'self')) {
//            // Special redirect. Redirect to this very page. Usefull for right after POST requests to avoid
//            // "confirm post submissions"
//            $url = $_SERVER['REQUEST_URI'];
//
//        } elseif ($url === 'prev') {
//            // Special redirect. Redirect to this very page. Usefull for right after POST requests to avoid
//            // "confirm post submissions"
//            $url = isset_get($_SERVER['HTTP_REFERER']);
//
//            if (!$url or ($url == $_SERVER['REQUEST_URI'])) {
//                // Don't redirect to the same page! If the referrer was this page, then drop back to the index page
//                $url = Config::get('web.redirects.index', '/');
//            }
//
//        } elseif ($url === false) {
//            // Special redirect. Redirect to this very page, but without query
//            $url = Strings::until($_SERVER['REQUEST_URI'], '?');
//
//        } elseif (!$url) {
//            // No target specified, redirect to index page
//            $url = Config::get('web.redirects.index', '/');
//        }
//
//        if (empty($http_code)) {
//            if (is_numeric($clear_session_redirect)) {
//                $http_code              = $clear_session_redirect;
//                $clear_session_redirect = true;
//
//            } else {
//                $http_code              = 301;
//            }
//
//        } else {
//            if (is_numeric($clear_session_redirect)) {
//                $clear_session_redirect = true;
//            }
//        }
//
//        /*
//         * Validate the specified http_code, must be one of
//         *
//         * 301 Moved Permanently
//         * 302 Found
//         * 303 See Other
//         * 307 Temporary Redirect
//         */
//        switch ($http_code) {
//            case 301:
//                // no-break
//            case 302:
//                // no-break
//            case 303:
//                // no-break
//            case 307:
//                /*
//                 * All valid
//                 */
//                break;
//
//            default:
//                throw new HttpException(tr('Invalid HTTP code ":code" specified', [':code' => $http_code]));
//        }
//
//        if ($clear_session_redirect) {
//            if (!empty($_SESSION)) {
//                unset($_GET['redirect']);
//                unset($_SESSION['sso_referrer']);
//            }
//        }
//
//        if ((!str_starts_with($url, '/')) and (!str_starts_with($url, 'http://')) and (!str_starts_with($url, 'https://'))) {
//            $url = Config::get('web.url.prefix', '') . $url;
//        }
//
//        $url = Page::redirect($url);
//
//        if ($time_delay) {
//            Log::action(tr('Redirecting with ":time" seconds delay to url ":url"', [':time' => $time_delay, ':url' => $url]));
//            header('Refresh: ' . $time_delay.';' . $url, true, $http_code);
//            exit();
//        }
//
//        Log::action(tr('Redirecting to url ":url"', [':url' => $url]));
//        header('Location:' . Page::redirect($url), true, $http_code);
//        exit();
//    }


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
                throw new HttpException(tr('Unknown method ":method" specified. Please speficy one of "json", or "http"', [
                    ':method' => $method
                ]));
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
            throw new OutOfBoundsException(tr('This request was made with HTTP method ":server_method" but for this page or call only HTTP method ":method" is allowed', [
                ':method'        => $method,
                ':server_method' => $_SERVER['REQUEST_METHOD']
            ]));
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
