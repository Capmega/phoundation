<?php

namespace Phoundation\Web\Http;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Exception\PageException;
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



    /**
     * Returns true if the specified string APPEARS to be a URL
     *
     * @param string $url
     * @return bool
     */
    public static function is(string $url): bool
    {
        return preg_match('/http(?:s)?:\/\//i', $url);
    }



    /**
     * Returns true if the specified string is a full and VALID URL
     *
     * @param string $url
     * @return bool
     */
    public static function isValid(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }



    /**
     * Returns true if the specified string is an external URL
     *
     * External here means that the domain is NOT one of the configured domains
     *
     * @param string $url
     * @param bool $check_sub_domains
     * @return bool
     */
    public static function isExternal(string $url, bool $check_sub_domains = true): bool
    {
        if (!self::is($url)) {
            // This isn't even a complete URL, must be internal, there is no domain name expected here
            return false;
        }

        // We have a complete URL, so there is a domain name in there. Check if it's a "local" (ie, on this server)
        // domain name
        return !self::getDomainType($url, $check_sub_domains);
    }



    /**
     * Returns true if the specified string is an external URL
     *
     * External here means that the domain is NOT one of the configured domains
     *
     * @param string $url
     * @param bool $check_sub_domains
     * @return string|null www in case its on a WWW domain, cdn in case its on a CDN domain, NULL if it's on an external
     */
    public static function getDomainType(string $url, bool $check_sub_domains = true): ?string
    {
        // Get all domain names and check if its primary or subdomain of those.
        $url_domain = self::getDomain($url);
        $domains    = Config::get('web.domains');

        foreach ($domains as $domain) {
            // Get CDN and WWW domains
            $names = ['www' => Strings::cut($domain['www'], '//', '/')];

            if (array_key_exists('cdn', $domain)) {
                // CDN domain is configured, use it
                $names['cdn'] = Strings::cut($domain['cdn'], '//', '/');
            }

            // Check against domain and subdomain of WWW and CDN
            foreach ($names as $type => $name) {
                if ($name === $url_domain) {
                    // The URL is on the main domain
                    return $type;
                }

                if ($check_sub_domains and str_ends_with($url_domain, $name)) {
                    // The URL is on a subdomain
                    return $type;
                }
            }
        }

        // This is an external URL
        return null;
    }



    /**
     * Returns the domain for the specified URL, NULL if the URL is invalid
     *
     * @param string $url
     * @return string|null
     */
    public static function getDomain(string $url): ?string
    {
        $url = parse_url($url);
        return isset_get($url['host']);
    }
}