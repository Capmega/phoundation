<?php

declare(strict_types=1);

namespace Phoundation\Web;

use Phoundation\Core\Libraries\Libraries;


/**
 * Class Web
 *
 * This class is the basic web page management class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Web
{
    /**
     * Instructs the Libraries class to clear the commands cache
     *
     * @return void
     */
    public static function clearCache(): void
    {
        Libraries::clearWebCache();
    }


    /**
     * Instructs the Libraries class to have each library rebuild its command cache
     *
     * @return void
     */
    public static function rebuildCache(): void
    {
        Libraries::rebuildWebCache();;
    }



//    /**
//     * Return complete domain with HTTP and all
//     *
//     * @param string|null $url
//     * @param string|null $query
//     * @param string|null $prefix
//     * @param string|null $domain
//     * @param string|null $language
//     * @param string|null $from_language
//     * @param boolean $allow_cloak
//     * @return string the URL
//     *
//     * @see cdn_domain()
//     * @see get_domain()
//     * @see mapped_domain()
//     */
//    public static function buildUrl(?string $url = null, ?string $query = null, ?string $prefix = null, ?string $domain = null, ?string $language = null, ?string $from_language = null, bool $allow_cloak = true): string
//    {
//        $url = (string) $url;
//
//        if (preg_match('/^(?:(?:https?)|(?:ftp):)?\/\//i', $url)) {
//            // Absolute URL specified, don't modify
//            return $url;
//        }
//
//        if (!$domain) {
//            /*
//             * Use current domain.
//             * Current domain MAY not be the same as the configured domain, so
//             * always use $_SESSION[domain] unless we're at the point where
//             * sessions are not available (yet) or are not available (cli, for
//             * example). In that case, fall back on the configured domain
//             * $_CONFIG[domain]
//             */
//            $domain = static::getDomain();
//        }
//
//        // Use url_prefix, for URL's like domain.com/en/admin/page.html, where "/admin/" is the prefix
//        if ($prefix === null) {
//            $prefix = Config::get('web.url.prefix', '');
//        }
//
//        $prefix   = Strings::startsNotWith(Strings::endsWith($prefix, '/'), '/');
//        $domain   = Strings::slash($domain);
//        $language = static::getLanguage($language);
//
//        // Build up the URL part
//        if (!$url) {
//            $return = PROTOCOL . $domain . ($language ? $language . '/' : '') . $prefix;
//
//        } elseif ($url === true) {
//            $return = PROTOCOL . $domain . Strings::startsNotWith($_SERVER['REQUEST_URI'], '/');
//
//        } else {
//            $return = PROTOCOL . $domain . ($language ? $language . '/' : '') . $prefix . Strings::startsNotWith($url, '/');
//        }
//
//        // Do language mapping, but only if routemap has been set
//// :TODO: This will fail when using multiple CDN servers (WHY?)
//        if (!empty(Config::get('language.supported', [])) and ($domain !== Config::get('cdn.domain', '') . '/')) {
//            if ($from_language !== 'en') {
//                /*
//                 * Translate the current non-English URL to English first
//                 * because the specified could be in dutch whilst we want to end
//                 * up with Spanish. So translate always
//                 * FOREIGN1 > English > Foreign2.
//                 *
//                 * Also add a / in front of $return before replacing to ensure
//                 * we don't accidentally replace sections like "services/" with
//                 * "servicen/" with Spanish URL's
//                 */
//                $return = str_replace('/' . $from_language . '/', '/en/', '/' . $return);
//                $return = substr($return, 1);
//
//                if (!empty($core->register['route_map'])) {
//                    foreach ($core->register['route_map'][$from_language] as $foreign => $english) {
//                        $return = str_replace($foreign, $english, $return);
//                    }
//                }
//            }
//
//            // From here the URL *SHOULD* be in English. If the URL is not English here, then conversion from local
//            // language to English right above failed
//            if ($language !== 'en') {
//                // Map the english URL to the requested non-english URL. Only map if routemap has been set for the
//                // requested language
//                if (empty($core->register['route_map'])) {
//                    // No route_map was set, only translate language selector
//                    $return = str_replace('en/', $language . '/', $return);
//
//                } else {
//                    if (empty($core->register['route_map'][$language])) {
//                        Notification::new()
//                            ->setException(new OutOfBoundsException(tr('domain(): Failed to update language sections for url ":url", no language routemap specified for requested language ":language"', [':url' => $return, ':language' => $language])))
//                            ->send();
//
//                    } else {
//                        $return = str_replace('en/', $language . '/', $return);
//
//                        foreach ($core->register['route_map'][$language] as $foreign => $english) {
//                            $return = str_replace($english, $foreign, $return);
//                        }
//                    }
//                }
//            }
//        }
//
//        if ($query) {
//            $return = url_add_query($return, $query);
//
//        } elseif ($query === false) {
//            $return = Strings::until($return, '?');
//        }
//
//        if ($allow_cloak and Config::get('web.url.cloaking.enabled', false)) {
//            // Cloak the URL before returning it
//            $return = Url::cloak($return);
//        }
//
//        return $return;
//    }


//    /**
//     * Returns the language for this process
//     *
//     * @return string
//     */
//    public static function getLanguage(): string
//    {
//        // TODO implement
//        return 'en';
//    }


//    /**
//     * Return complete URL for the specified API URL section with HTTP and all
//     *
//     * @param null string $url
//     * @param null string $query
//     * @param null string $prefix
//     * @param null string $domain
//     * @param null string $language
//     * @param null boolean $allow_url_cloak
//     * @return string the URL
//     *@author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
//     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
//     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
//     * @category Function reference
//     * @package system
//     * @see getDomain()
//     * @see cdn_domain()
//     * @version 2.7.102: Added function and documentation
//     *
//     */
//    public static function api_domain($url = null, $query = null, $prefix = null, $domain = null, $language = null, $allow_url_cloak = true): string
//    {
//        return static::getDomain($url, $query, $prefix, $_CONFIG['api']['domain'], $language, $allow_url_cloak);
//    }


//    /**
//     * Return complete URL for the specified AJAX URL section with HTTP and all
//     *
//     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
//     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
//     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
//     * @category Function reference
//     * @package system
//     * @see getDomain()
//     * @see cdn_domain()
//     * @version 2.7.102: Added function and documentation
//     *
//     * @param null string $url
//     * @param null string $query
//     * @param null string $prefix
//     * @param null string $domain
//     * @param null string $language
//     * @param null boolean $allow_url_cloak
//     * @return string the URL
//     */
//    public static function ajaxDomain(?string $url = null, ?string $query = null, $language = null, $allow_url_cloak = true): string
//    {
//        if ($_CONFIG['ajax']['prefix']) {
//            $prefix = $_CONFIG['ajax']['prefix'];
//
//        } else {
//            $prefix = null;
//        }
//
//        if ($_CONFIG['ajax']['domain']) {
//            return static::getDomain($url, $query, $prefix, $_CONFIG['ajax']['domain'], $language, $allow_url_cloak);
//        }
//
//        return static::getDomain($url, $query, $prefix, null, $language, $allow_url_cloak);
//    }
}
