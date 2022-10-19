<?php

namespace Phoundation\Web;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\Validator\Validator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Notify\Notification;
use Phoundation\Web\Exception\WebException;
use Phoundation\Web\Http\Http;
use Phoundation\Web\Http\Url;


/**
 * Class Web
 *
 * This class is the basic web page management class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Web
{
    /**
     * Storage for the $_GET array data to hide it from the devs until validation is done
     *
     * @var array|null
     */
    protected ?array $get = null;

    /**
     * Storage for the $_POST array data to hide it from the devs until validation is done
     *
     * @var array|null
     */
    protected ?array $post = null;



    /**
     * Execute the specified webpage
     *
     * @param string $page
     * @param bool $return
     * @return string
     */
    public static function execute(string $page, bool $return = false): string
    {
        Core::startup();
        Validator::hideUserData();

//        if ($get) {
//            if (!is_array($get)) {
//                throw new WebException(tr('Specified $get MUST be an array, but is an ":type"', array(':type' => gettype($get))), 'invalid');
//            }
//
//            $_GET = $get;
//        }

        if (defined('LANGUAGE')) {
            $language = LANGUAGE;

        } else {
            $language = 'en';
        }

        if (is_numeric($page)) {
            // This is a system page, HTTP code. Use the page code as http code as well
             Http::setStatusCode($page);
        }

        Core::writeRegister($page, 'system', 'script_file');

        switch (Core::getCallType()) {
            case 'ajax':
                $include = ROOT.'www/' . $language.'/ajax/' . $page.'.php';

                // Execute ajax page
                Log::notice(tr('Showing ":language" language ajax page ":page"', [':page' => $page, ':language' => $language]));
                return include($include);

            case 'api':
                $include = ROOT.'www/api/'.(is_numeric($page) ? 'system/' : '').$page.'.php';

                // Execute ajax page
                Log::notice(tr('Showing ":language" language api page ":page"', [':page' => $page, ':language' => $language]));
                return include($include);

            case 'admin':
                $admin = '/admin';
                // no-break

            default:
                if (is_numeric($page)) {
                    $include = ROOT.'www/' . $language.isset_get($admin).'/system/' . $page.'.php';

                    Log::notice(tr('Showing ":language" language system page ":page"', [':page' => $page, ':language' => $language]));

                    // Wait a small random time to avoid timing attacks on system pages
                    usleep(mt_rand(1, 250));

                } else {
                    $include = ROOT.'www/' . $language.isset_get($admin).'/' . $page.'.php';
                    Log::notice(tr('Showing ":language" language http page ":page"', [':page' => $page, ':language' => $language]));
                }

                $result = include($include);

                if ($return) {
                    return $result;
                }
        }

        die();
    }



    /**
     *
     *
     * @todo Remove $core dependancy
     * @todo Remove $_CONFIG dependancy
     */
    #[NoReturn] public static function die(): void
    {
        // Do we need to run other shutdown functions?
        Core::shutdown();
    }



    /**
     * Return complete domain with HTTP and all
     *
     * @param string|null $url
     * @param string|null $query
     * @param string|null $prefix
     * @param string|null $domain
     * @param string|null $language
     * @param string|null $from_language
     * @param boolean $allow_cloak
     * @return string the URL
     *
     * @see cdn_domain()
     * @see get_domain()
     * @see mapped_domain()
     */
    public static function getDomain(?string $url = null, ?string $query = null, ?string $prefix = null, ?string $domain = null, ?string $language = null, ?string $from_language = null, bool $allow_cloak = true): string
    {
        $url = (string) $url;

        if (preg_match('/^(?:(?:https?)|(?:ftp):)?\/\//i', $url)) {
            // Absolute URL specified, don't modify
            return $url;
        }

        if (!$domain) {
            /*
             * Use current domain.
             * Current domain MAY not be the same as the configured domain, so
             * always use $_SESSION[domain] unless we're at the point where
             * sessions are not available (yet) or are not available (cli, for
             * example). In that case, fall back on the configured domain
             * $_CONFIG[domain]
             */
            if (PLATFORM_HTTP) {
                $domain = $_SERVER['HTTP_HOST'];
            } else {
                $domain = Config::get('web.domains.primary');
            }
        }

        // Use url_prefix, for URL's like domain.com/en/admin/page.html, where "/admin/" is the prefix
        if ($prefix === null) {
            $prefix = Config::get('web.url.prefix', '');
        }

        $prefix = Strings::startsNotWith(Strings::endsWith($prefix, '/'), '/');
        $domain = Strings::slash($domain);
        $language = self::getLanguage($language);

        // Build up the URL part
        if (!$url) {
            $retval = PROTOCOL . $domain . ($language ? $language . '/' : '') . $prefix;

        } elseif ($url === true) {
            $retval = PROTOCOL . $domain . Strings::startsNotWith($_SERVER['REQUEST_URI'], '/');

        } else {
            $retval = PROTOCOL . $domain . ($language ? $language . '/' : '') . $prefix . Strings::startsNotWith($url, '/');
        }

        // Do language mapping, but only if routemap has been set
// :TODO: This will fail when using multiple CDN servers (WHY?)
        if (!empty(Config::get('languages.supported', [])) and ($domain !== Config::get('cdn.domain', '') . '/')) {
            if ($from_language !== 'en') {
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
                $retval = str_replace('/' . $from_language . '/', '/en/', '/' . $retval);
                $retval = substr($retval, 1);

                if (!empty($core->register['route_map'])) {
                    foreach ($core->register['route_map'][$from_language] as $foreign => $english) {
                        $retval = str_replace($foreign, $english, $retval);
                    }
                }
            }

            // From here the URL *SHOULD* be in English. If the URL is not English here, then conversion from local
            // language to English right above failed
            if ($language !== 'en') {
                // Map the english URL to the requested non-english URL. Only map if routemap has been set for the
                // requested language
                if (empty($core->register['route_map'])) {
                    // No route_map was set, only translate language selector
                    $retval = str_replace('en/', $language . '/', $retval);

                } else {
                    if (empty($core->register['route_map'][$language])) {
                        Notification::getInstance()
                            ->setException(new OutOfBoundsException(tr('domain(): Failed to update language sections for url ":url", no language routemap specified for requested language ":language"', [':url' => $retval, ':language' => $language])))
                            ->send();

                    } else {
                        $retval = str_replace('en/', $language . '/', $retval);

                        foreach ($core->register['route_map'][$language] as $foreign => $english) {
                            $retval = str_replace($english, $foreign, $retval);
                        }
                    }
                }
            }
        }

        if ($query) {
            $retval = url_add_query($retval, $query);

        } elseif ($query === false) {
            $retval = Strings::until($retval, '?');
        }

        if ($allow_cloak and Config::get('web.url.cloaking.enabled', false)) {
            // Cloak the URL before returning it
            $retval = Url::cloak($retval);
        }

        return $retval;
    }



    /**
     * Returns the language for this process
     *
     * @return string
     */
    public static function getLanguage(): string
    {
        // TODO implement
        return 'en';
    }

    /**
     * Return complete URL for the specified API URL section with HTTP and all
     *
     * @param null string $url
     * @param null string $query
     * @param null string $prefix
     * @param null string $domain
     * @param null string $language
     * @param null boolean $allow_url_cloak
     * @return string the URL
     *@author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @see getDomain()
     * @see cdn_domain()
     * @version 2.7.102: Added function and documentation
     *
     */
    public static function api_domain($url = null, $query = null, $prefix = null, $domain = null, $language = null, $allow_url_cloak = true): string
    {
        return self::getDomain($url, $query, $prefix, $_CONFIG['api']['domain'], $language, $allow_url_cloak);
    }



    /**
     * Return complete URL for the specified AJAX URL section with HTTP and all
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package system
     * @see getDomain()
     * @see cdn_domain()
     * @version 2.7.102: Added function and documentation
     *
     * @param null string $url
     * @param null string $query
     * @param null string $prefix
     * @param null string $domain
     * @param null string $language
     * @param null boolean $allow_url_cloak
     * @return string the URL
     */
    public static function ajaxDomain(?string $url = null, ?string $query = null, $language = null, $allow_url_cloak = true): string
    {
        if ($_CONFIG['ajax']['prefix']) {
            $prefix = $_CONFIG['ajax']['prefix'];

        } else {
            $prefix = null;
        }

        if ($_CONFIG['ajax']['domain']) {
            return self::getDomain($url, $query, $prefix, $_CONFIG['ajax']['domain'], $language, $allow_url_cloak);
        }

        return self::getDomain($url, $query, $prefix, null, $language, $allow_url_cloak);
    }
}