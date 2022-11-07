<?php

namespace Phoundation\Web\Http;

use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Exception\WebException;


/**
 * Class Url
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Http
 */
class Url {
    /**
     * Return the specified URL with a redirect URL stored in $core->register['redirect']
     *
     * @note If no URL is specified, the current URL will be used
     * @see UrlBuilder
     * @see UrlBuilder::addQueries()
     *
     * @param string|bool|null $url
     * @param int $http_code
     * @param bool $clear_session_redirect
     * @param int|null $time_delay
     * @return string The specified URL (if not specified, the current URL) with $core->register['redirect'] added to it (if set)
     */
    public static function redirect(string|bool|null $url = null, int $http_code = 301, bool $clear_session_redirect = true, ?int $time_delay = null): string
    {
        if (!PLATFORM_HTTP) {
            throw new WebException(tr('Url::redirect() can only be called on web sessions'));
        }

        // Build URL
        $url = self::build($url)->www();

        if ($_GET['redirect']) {
            $url = self::build($url)->addQueries('redirect=' . urlencode($_GET['redirect']));
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
            case 0:
                // no-break
            case 301:
                $http_code = 301;
                break;
            case 302:
                // no-break
            case 303:
                // no-break
            case 307:
                // All valid
                break;

            default:
                throw new OutOfBoundsException(tr('Invalid HTTP code ":code" specified', [
                    ':code' => $http_code
                ]));
        }

        // ???
        if ($clear_session_redirect) {
            if (!empty($_SESSION)) {
                unset($_GET['redirect']);
                unset($_SESSION['sso_referrer']);
            }
        }

        // Redirect with time delay
        if ($time_delay) {
            Log::action(tr('Redirecting with ":time" seconds delay to url ":url"', [
                ':time' => $time_delay,
                ':url' => $url
            ]));

            header('Refresh: '.$time_delay.';'.$url, true, $http_code);
            die();
        }

        // Redirect immediately
        Log::action(tr('Redirecting to url ":url"', [':url' => $url]));
        header('Location:' . $url , true, $http_code);
        die();
    }



    /**
     * Build URL's
     *
     * @param string|bool|null $url
     * @param bool|null $cloaked
     * @return UrlBuilder
     */
    public static function build(string|bool|null $url = null, ?bool $cloaked = null): UrlBuilder
    {
        return new UrlBuilder($url, $cloaked);
    }
}