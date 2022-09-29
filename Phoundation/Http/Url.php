<?php

namespace Phoundation\Http;

use Phoundation\Core\CoreException;
use Phoundation\Core\Json\Strings;
use Phoundation\Databases\Sql;

/**
 * Class
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
     * @param string|null $url
     * @return string The specified URL (if not specified, the current URL) with $core->register['redirect'] added to it (if set)
     */
    public static function redirect(?string $url = null): string
    {
        if (!$url) {
            /*
             * Default to this page
             */
            $url = self::getDomain(true);
        }

        if (empty($_GET['redirect'])) {
            return $url;
        }

        return Url::addToQuery($url, 'redirect='.urlencode($_GET['redirect']));
    }



    /**
     * Return complete domain with HTTP and all
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @see cdn_domain()
     * @see get_domain()
     * @see mapped_domain()
     * @package system
     *
     * @param null string $url
     * @param null string $query
     * @param null string $prefix
     * @param null string $domain
     * @param null string $language
     * @param null boolean $allow_cloak
     * @return string the URL
     */
    public static function domain($url_params = null, $query = null, $prefix = null, $domain = null, $language = null, $allow_cloak = true): string
    {
        global $_CONFIG, $core;

        if (!is_array($url_params)) {
            if (!is_string($url_params) and !is_bool($url_params) and ($url_params !== null)) {
                throw new CoreException(tr('domain(): Specified $url_params should be either null, a string, or a parameters array but is an ":type"', array(':type' => gettype($url_params))), 'invalid');
            }

            $url_params = array('url'           => $url_params,
                'query'         => $query,
                'prefix'        => $prefix,
                'domain'        => $domain,
                'language'      => $language,
                'allow_cloak'   => $allow_cloak);
        }

        array_default($url_params, 'from_language', LANGUAGE);

        if (preg_match('/^(?:(?:https?)|(?:ftp):)?\/\//i', $url_params['url'])) {
            /*
             * Absolute URL specified, don't modify
             */
            return $url_params['url'];
        }

        if (!$url_params['domain']) {
            /*
             * Use current domain.
             * Current domain MAY not be the same as the configured domain, so
             * always use $_SESSION[domain] unless we're at the point where
             * sessions are not available (yet) or are not available (cli, for
             * example). In that case, fall back on the configured domain
             * $_CONFIG[domain]
             */
            $url_params['domain'] = get_domain();

        } elseif ($url_params['domain'] === true) {
            /*
             * Use current domain name
             */
            $url_params['domain'] = $_SERVER['HTTP_HOST'];
        }

        /*
         * Use url_prefix, for URL's like domain.com/en/admin/page.html, where
         * "/admin/" is the prefix
         */
        if ($url_params['prefix'] === null) {
            $url_params['prefix'] = $_CONFIG['url_prefix'];
        }

        $url_params['prefix']   = Strings::startsNotWith(Strings::endsWith($url_params['prefix'], '/'), '/');
        $url_params['domain']   = slash($url_params['domain']);
        $url_params['language'] = get_language($url_params['language']);

        /*
         * Build up the URL part
         */
        if (!$url_params['url']) {
            $retval = PROTOCOL.$url_params['domain'].($url_params['language'] ? $url_params['language'].'/' : '') . $url_params['prefix'];

        } elseif ($url_params['url'] === true) {
            $retval = PROTOCOL.$url_params['domain'].Strings::startsNotWith($_SERVER['REQUEST_URI'], '/');

        } else {
            $retval = PROTOCOL.$url_params['domain'].($url_params['language'] ? $url_params['language'].'/' : '') . $url_params['prefix'].Strings::startsNotWith($url_params['url'], '/');
        }

        /*
         * Do language mapping, but only if routemap has been set
         */
// :TODO: This will fail when using multiple CDN servers (WHY?)
        if (!empty($_CONFIG['language']['supported']) and ($url_params['domain'] !== $_CONFIG['cdn']['domain'].'/')) {
            if ($url_params['from_language'] !== 'en') {
                /*
                 * Translate the current non-English URL to English first
                 * because the specified could be in dutch whilst we want to end
                 * up with Spanish. So translate always
                 * FOREIGN1 > English > Foreign2.
                 *
                 * Also add a / in front of $retval before replacing to ensure
                 * we don't accidentally replace sections like "services/" with
                 * "servicen/" with Spanish URL's
                 */
                $retval = str_replace('/' . $url_params['from_language'].'/', '/en/', '/' . $retval);
                $retval = substr($retval, 1);

                if (!empty($core->register['route_map'])) {
                    foreach ($core->register['route_map'][$url_params['from_language']] as $foreign => $english) {
                        $retval = str_replace($foreign, $english, $retval);
                    }
                }
            }

            /*
             * From here the URL *SHOULD* be in English. If the URL is not
             * English here, then conversion from local language to English
             * right above failed
             */
            if ($url_params['language'] !== 'en') {
                /*
                 * Map the english URL to the requested non-english URL
                 * Only map if routemap has been set for the requested language
                 */
                if (empty($core->register['route_map'])) {
                    /*
                     * No route_map was set, only translate language selector
                     */
                    $retval = str_replace('en/', $url_params['language'].'/', $retval);

                } else {
                    if (empty($core->register['route_map'][$url_params['language']])) {
                        notify(new CoreException(tr('domain(): Failed to update language sections for url ":url", no language routemap specified for requested language ":language"', array(':url' => $retval, ':language' => $url_params['language'])), 'not-specified'));

                    } else {
                        $retval = str_replace('en/', $url_params['language'].'/', $retval);

                        foreach ($core->register['route_map'][$url_params['language']] as $foreign => $english) {
                            $retval = str_replace($english, $foreign, $retval);
                        }
                    }
                }
            }
        }

        if ($url_params['query']) {
            load_libs('inet');
            $retval = url_add_query($retval, $url_params['query']);

        } elseif ($url_params['query'] === false) {
            $retval = Strings::until($retval, '?');
        }

        if ($url_params['allow_cloak'] and $_CONFIG['security']['url_cloaking']['enabled']) {
            /*
             * Cloak the URL before returning it
             */
            $retval = url_cloak($retval);
        }

        return $retval;
    }



    /**
     * Add specified query to the specified URL and return
     *
     * @param string $url
     * @return array|string|string[]
     * @throws CoreException
     */
    public static function addToQuery(string $url) {
        $queries = func_get_args();
        unset($queries[0]);

        if (!$url) {
            throw new CoreException(tr('No URL specified'));
        }

        if (!$queries) {
            throw new CoreException(tr('No queries specified to add to the specified URL'));
        }

        foreach ($queries as $query) {
            if (!$query) continue;

            if (is_string($query) and strstr($query, '&')) {
                $query = explode('&', $query);
            }

            if (is_array($query)) {
                foreach ($query as $key => $value) {
                    if (is_numeric($key)) {
                        /*
                         * $value should contain key=value
                         */
                        $url = self::addToQuery($url, $value);

                    } else {
                        $url = self::addToQuery($url, $key . '=' . $value);
                    }
                }

                continue;
            }

            if ($query === true) {
                $query = $_SERVER['QUERY_STRING'];
            }

            if ($query[0] === '-') {
                // Remove this query instead of adding it
                $url = preg_replace('/'.substr($query, 1) . '/', '', $url);
                $url = str_replace('&&', '', $url);
                $url = Strings::endsNotWith($url, '?');

                continue;
            }

            $url = Strings::endsNotWith($url, '?');

            if (!preg_match('/.+?=.*?/', $query)) {
                throw new CoreException(tr('inet_add_query(): Invalid query ":query" specified. Please ensure it has the "key=value" format', array(':query' => $query)), 'invalid');
            }

            $key = Strings::until($query, '=');

            if (!str_contains($url, '?')) {
                /*
                 * This URL has no query yet, begin one
                 */
                $url .= '?' . $query;

            } elseif (str_contains($url, $key . '=')) {
                /*
                 * The query already exists in the specified URL, replace it.
                 */
                $replace = Strings::cut($url, $key . '=', '&');
                $url     = str_replace($key . '=' . $replace, $key . '=' . Strings::from($query, '='), $url);

            } else {
                /*
                 * Append the query to the URL
                 */
                $url = Strings::endsWith($url, '&') . $query;
            }
        }

        return $url;
    }



    /**
     * Cloak the specified URL.
     *
     * URL cloaking is nothing more than replacing a full URL (with query) with a random string. This function will register the requested URL
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package url
     * @see Url::decloak()
     * @version 2.4.4: Added function and documentation
     *
     * @param string the URL to be cloaked
     * @return string The cloaked URL
     */
    public static function cloak(string $url): string
    {
        try{
            $cloak = Sql::get('SELECT `cloak`

                          FROM   `url_cloaks`

                          WHERE  `url`       = :url
                          AND    `createdby` = :createdby',

                true, array(':url'       => $url,
                    ':createdby' => isset_get($_SESSION['user']['id'])));

            if($cloak) {
                /*
                 * Found cloaking URL, update the createdon time so that it won't
                 * exipre too soon
                 */
                Sql::query('UPDATE `url_cloaks` SET `createdon` = NOW() WHERE `url` = :url', array(':url' => $url));
                return $cloak;
            }

            $cloak = str_random(32);

            Sql::query('INSERT INTO `url_cloaks` (`createdby`, `url`, `cloak`)
                   VALUES                   (:createdby , :url , :cloak )',

                array(':createdby' => isset_get($_SESSION['user']['id']),
                    ':cloak'     => $cloak,
                    ':url'       => $url));

            return $cloak;

        }catch(Exception $e) {
            throw new CoreException('url_cloak(): Failed', $e);
        }
    }



    /**
     * Uncloak the specified URL.
     *
     * URL cloaking is nothing more than
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package url
     * @see Url::decloak()
     * @version 2.4.4: Added function and documentation
     *
     * @param string the URL to be cloaked
     * @return string The cloaked URL
     */
    public static function decloak(string $cloak): string
    {
        global $_CONFIG, $core;

        try{
            $data = Sql::get('SELECT `createdby`, `url` FROM `url_cloaks` WHERE `cloak` = :cloak', array(':cloak' => $cloak));

            if(mt_rand(0, 100) <= $_CONFIG['security']['url_cloaking']['interval']) {
                url_cloak_cleanup();
            }

            if($data) {
                $core->register['url_cloak_users_id'] = $data['createdby'];
                return $data['url'];
            }

            return '';

        }catch(Exception $e) {
            throw new CoreException('url_decloak(): Failed', $e);
        }
    }



    /**
     * Cleanup the url_cloaks table
     *
     * Since the URL cloaking table might fill up over time with new entries, this function will be periodically executed by url_decloak() to cleanup the table
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package url
     * @see Url::decloak()
     * @version 2.4.4: Added function and documentation
     *
     * @return int The amount of expired entries removed from the `url_cloaks` table
     */
    public static function cloakCleanup(): int
    {
        global $_CONFIG;

        log_console(tr('Cleaning up `url_cloaks` table'), 'VERBOSE/cyan');

        $r = Sql::query('DELETE FROM `url_cloaks` 
                         WHERE `createdon` < DATE_SUB(NOW(), INTERVAL '.$_CONFIG['security']['url_cloaking']['expires'].' SECOND);');

        log_console(tr('Removed ":count" expired entries from the `url_cloaks` table', array(':count' => $r->rowCount())), 'green');

        return $r->rowCount();
    }
}