<?php

namespace Phoundation\Web\Http;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Content\Images\Image;
use Phoundation\Core\Config;
use Phoundation\Core\Exception\ConfigNotExistsException;
use Phoundation\Core\Log;
use Phoundation\Core\Session;
use Phoundation\Core\Strings;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Web\Page;



/**
 * Class Domain
 *
 *
 * @todo Add language mapping, see the protected method language_map() at the bottom of this class for more info
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Http
 */
class UrlBuilder
{
    /**
     * Will be true if the current URL as-is is cloaked
     *
     * @var bool $is_cloaked
     */
    protected bool $is_cloaked = false;

    /**
     * The url to work with
     *
     * @var string $url
     */
    protected string $url;

    /**
     * If true, this URL will be returned cloaked
     *
     * @var bool $cloak
     */
    protected bool $cloak;



    /**
     * UrlBuilder class constructor
     *
     * @param string|null $url
     */
    protected function __construct(string|null $url = null)
    {
        $url = trim((string) $url);

        // This is either part of a URL or a complete URL
        if (!Url::isValid($url)) {
            // This is a section
            $this->url = Strings::startsNotWith($url, '/');
        } else {
            // This is a valid URL, continue.
            $this->url = $url;
        }
    }



    /**
     * When used as string, will always return the internal URL as available
     *
     * @return string
     */
    public function __toString(): string
    {
        // Auto cloak URL's?
        $domain = Url::getDomainFromUrl($this->url);

        try {
            if (Domains::getConfigurationKey($domain, 'cloaked')) {
                $this->cloak();
            }
        } catch (ConfigNotExistsException) {
            // This domain is not configured, ignore it
        }

        return $this->url;
    }



    /**
     * Returns the current URL
     *
     * @return static
     */
    public static function current(): static
    {
        return self::currentDomainUrl();
    }



    /**
     * Returns a www URL
     *
     * @param string|null $url
     * @return static
     */
    public static function www(?string $url = null): static
    {
        if (!$url) {
            return self::current();
        }

        if (Url::isValid($url)) {
            return new UrlBuilder($url);
        }

        return new UrlBuilder(self::buildDomainPrefix('www', $url));
    }



    /**
     * Returns a CDN URL
     *
     * @param string $url
     * @param string|null $extension
     * @return static
     */
    public static function cdn(string $url, ?string $extension = null): static
    {
        if (Url::isValid($url)) {
            return new UrlBuilder($url);
        }

        self::buildCdn($url, $extension);
    }



    /**
     * Returns the root URL for the primary domain
     *
     * @return static
     */
    public static function currentDomainRootUrl(): static
    {
        return new UrlBuilder(Page::getRootUri());
    }



    /**
     * Returns the URL as requested for the primary domain
     *
     * @return static
     */
    public static function currentDomainUrl(): static
    {
        return new UrlBuilder(Page::getRootUri());
    }



    /**
     * Returns the root URL for the parent domain
     *
     * @return static
     */
    public static function parentDomainRootUrl(): static
    {
        return new UrlBuilder(Domains::from()->getParent() . Page::getRootUri());
    }



    /**
     * Returns the URL as requested for the parent domain
     *
     * @return static
     */
    public static function parentDomainUrl(): static
    {
        return new UrlBuilder(Domains::from()->getParent() . Page::getUri());
    }



    /**
     * Returns the root URL for the root domain
     *
     * @return static
     */
    public static function rootDomainRootUrl(): static
    {
        return new UrlBuilder(Domains::from()->getRoot() . Page::getRootUri());
    }



    /**
     * Returns the URL as requested for the root domain
     *
     * @return static
     */
    public static function rootDomainUrl(): static
    {
        return new UrlBuilder(Domains::from()->getRoot() . Page::getUri());
    }



    /**
     * Returns the root URL for the primary domain
     *
     * @return static
     */
    public static function primaryDomainRootUrl(): static
    {
        return new UrlBuilder(Domains::getPrimary() . Page::getRootUri());
    }



    /**
     * Returns the URL as requested for the primary domain
     *
     * @return static
     */
    public static function primaryDomainUrl(): static
    {
        return new UrlBuilder(Domains::getPrimary() . Page::getUri());
    }



    /**
     * Returns the referer (previous) URL
     *
     * @param string|null $url
     * @return static
     */
    public static function referer(?string $url = null): static
    {
        // The previous page; Assume we came from the HTTP_REFERER page
        $referer = isset_get($_SERVER['HTTP_REFERER']);

        if (!$referer or ($referer === $_SERVER['REQUEST_URI'])) {
            // Don't redirect to the same page! If the referrer was this page, then drop back to the specified page or
            // the index page
            if ($url) {
                $referer = $url;

            } else {
                $referer = self::rootDomainRootUrl();
            }
        }

        return new UrlBuilder($referer);
    }



    /**
     * Returns an ajax URL
     *
     * @param string $url
     * @return static
     */
    public static function ajax(string $url): static
    {
        $url = Strings::startsNotWith($url, '/');
        $url = self::buildDomainPrefix('www', 'ajax/' . $url);

        return self::www($url);
    }



    /**
     * Returns an api URL
     *
     * @param string $url
     * @return static
     */
    public static function api(string $url): static
    {
        $url = Strings::startsNotWith($url, '/');
        $url = self::buildDomainPrefix('www', 'api/' . $url);

        return self::www($url);
    }



    /**
     * Returns a CSS URL
     *
     * @param string $url
     * @return static
     */
    public static function css(string $url): static
    {
        if (Url::isValid($url)) {
            return new UrlBuilder($url);
        }

        $url = Strings::startsNotWith($url, '/');
        $url = self::buildDomainPrefix('cdn', '/' . $url);

        return self::buildCdn($url, 'css');
    }



    /**
     * Returns a Javascript URL
     *
     * @param string $url
     * @return static
     */
    public static function js(string $url): static
    {
        if (Url::isValid($url)) {
            return new UrlBuilder($url);
        }

        $url = Strings::startsNotWith($url, '/');
        $url = self::buildDomainPrefix('cdn', '/' . $url);

        return self::buildCdn($url, 'js');
    }



    /**
     * Returns an image URL
     *
     * @param Image|string $url
     * @return static
     */
    public static function img(Image|string $url): static
    {
        if (is_object($url)) {
            $url->getHtmlElement()->getSrc();
        } elseif (Url::isValid($url)) {
            return new UrlBuilder($url);
        }

//        if ($path) {
//            throw new UnderConstructionException();
//            // Return the local filesystem path instead of a public URL
//            if (Url::isValid($url)) {
//                // This is an external URL, there is no local file
//                return new UrlBuilder($url);
//            }
//
//            $path = Strings::startsNotWith($this->url, '/');
//
//            if (!str_starts_with($path, 'img/')) {
//                $path = 'img/' . $path;
//            }
//
//            return $path;
//        }

        return self::buildCdn($url);
    }



    /**
     * Returns if generated URL's is cloaked or not
     *
     * @return bool
     */
    public function isCloaked(): bool
    {
        return isset_get($this->is_cloaked);
    }



    /**
     * Cloak the specified URL.
     *
     * URL cloaking is nothing more than replacing a full URL (with query) with a random string. This function will
     * register the requested URL
     *
     * @return static
     */
    public function cloak(): static
    {
        $cloak = sql()->getColumn('SELECT `cloak`
                                         FROM   `url_cloaks`
                                         WHERE  `url`        = :url
                                         AND    `created_by` = :created_by', [
            ':url'        => $this->url,
            ':created_by' => isset_get($_SESSION['user']['id'])
        ]);

        if ($cloak) {
            // Found cloaking URL, update the created_on time so that it won't exipre too soon
            sql()->query('UPDATE `url_cloaks` 
                                SET    `created_on` = NOW() 
                                WHERE  `url`        = :url', [
                ':url' => $this->url
            ]);
        } else {
            $cloak = Strings::random(32);

            sql()->insert('url_cloaks', [
                'created_by' => Session::getUser()->getId(),
                'cloak'      => $cloak,
                'url'        => $this->url
            ]);
        }

        $this->url = $cloak;
        return $this;
    }



    /**
     * Uncloak the specified URL.
     *
     * URL cloaking is nothing more than
     *
     * @return static
     */
    public function decloak(): static
    {
        $url = sql()->getColumn('SELECT `created_by`, `url` 
                                       FROM   `url_cloaks` 
                                       WHERE  `cloak` = :cloak', [':cloak' => $this->url]);

        if (!$url) {
            throw new NotExistsException(tr('The specified cloaked URL ":url" does not exist', [
                ':url' => $this->url
            ]));
        }

        sql()->delete('url_cloaks', [':cloak' => $this->url]);
        return $this;
    }



    /**
     * Cleanup the url_cloaks table
     *
     * Since the URL cloaking table might fill up over time with new entries, this function will be periodically executed by url_decloak() to cleanup the table
     *
     * @see Url::decloak()
     * @return int The amount of expired entries removed from the `url_cloaks` table
     */
    public static function cleanupCloak(): int
    {
        Log::notice(tr('Cleaning up `url_cloaks` table'));

        $r = sql()->query('DELETE FROM `url_cloaks` 
                                 WHERE `created_on` < DATE_SUB(NOW(), INTERVAL ' . Config::get('web.url.cloaking.expires', 86400).' SECOND);');

        Log::success(tr('Removed ":count" expired entries from the `url_cloaks` table', [
            ':count' => $r->rowCount()
        ]));

        return $r->rowCount();
    }



    /**
     * Remove the query part from the URL
     *
     * @return static
     */
    public function removeQueries(): static
    {
        $this->url = Strings::until($this->url, '?');
        return $this;
    }



    /**
     * Add specified query to the specified URL and return
     *
     * @param string $query [$query] ... All the queries to add to this URL
     * @return static
     */
    public function addQueries(): static
    {
        $queries = func_get_args();

        if (!$queries) {
            throw new OutOfBoundsException(tr('No queries specified to add to the specified URL'));
        }

        foreach ($queries as $query) {
            if (!$query) continue;

            if (is_string($query) and str_contains($query, '&')) {
                $query = explode('&', $query);
            }

            if (is_array($query)) {
                foreach ($query as $key => $value) {
                    if (is_numeric($key)) {
                        // $value should contain key=value
                        self::addQueries($this->url, $value);

                    } else {
                        self::addQueries($this->url, $key . '=' . $value);
                    }
                }

                continue;
            }

            if ($query === true) {
                $query = $_SERVER['QUERY_STRING'];
            }

            if ($query[0] === '-') {
                // Remove this query instead of adding it
                $this->url = preg_replace('/'.substr($query, 1) . '/', '', $this->url);
                $this->url = str_replace('&&', '', $this->url);
                $this->url = Strings::endsNotWith($this->url, ['?', '&']);

                continue;
            }

            $this->url = Strings::endsNotWith($this->url, '?');

            if (!preg_match('/.+?=.*?/', $query)) {
                throw new OutOfBoundsException(tr('Invalid query ":query" specified. Please ensure it has the "key=value" format', [
                    ':query' => $query
                ]));
            }

            $key = Strings::until($query, '=');

            if (!str_contains($this->url, '?')) {
                // This URL has no query yet, begin one
                $this->url .= '?' . $query;

            } elseif (str_contains($this->url, $key . '=')) {
                // The query already exists in the specified URL, replace it.
                $replace   = Strings::cut($this->url, $key . '=', '&');
                $this->url = str_replace($key . '=' . $replace, $key . '=' . Strings::from($query, '='), $this->url);

            } else {
                // Append the query to the URL
                $this->url = $this->url . Strings::endsWith($this->url, '&');
            }
        }

        return $this;
    }



    /**
     * Returns the extension for the URL
     *
     * @param string|null $extension
     * @return string|null
     */
    protected static function addExtension(?string $extension): ?string
    {
        if (!$extension) {
            return $extension;
        }

        if (Config::get('web.minify', true)) {
            return '.min.' . $extension;
        }

        return '.' . $extension;
    }



    /**
     * Builds and returns the domain prefix
     *
     * @param string $type
     * @param string $url
     * @param string|null $domain
     * @return string
     */
    protected static function buildDomainPrefix(#[ExpectedValues(values: ['www', 'cdn'])] string $type, string $url, ?string $domain = null): string
    {
        if (!Url::isValid($url)) {
            // Get the base URL configuration for the domain
            if (!$domain) {
                $domain = Domains::getCurrent();
            }

            $base = Domains::getConfigurationKey($domain, $type);
            $base = $base ?? 'http://cdn.localhost/:LANGUAGE/';
            $base = Strings::endsWith($base, '/');
            $url  = Strings::startsNotWith($url, '/');
            $url  = str_replace(':LANGUAGE', Session::getLanguage(), $base . $url);
        }

        return $url;
    }



    /**
     * Returns a CDN URL
     *
     * @param string $url
     * @param string|null $extension
     * @return static
     * @throws OutOfBoundsException If no URL was specified
     */
    public static function buildCdn(string $url, ?string $extension = null): static
    {
        if (!$url) {
            throw new OutOfBoundsException(tr('No URL specified'));
        }

        $url  = Strings::startsNotWith($url, '/');
        $url  = self::buildDomainPrefix('cdn', $url);
        $url .= self::addExtension($extension);

        return new UrlBuilder(self::buildDomainPrefix('cdn', $url));
    }



    /**
     * GARBAGE!
     *
     * Do not use this method, only use it as a reference to implement language mapping
     */
    protected function language_map($url_params = null, $query = null, $prefix = null, $domain = null, $language = null, $allow_cloak = true): string
    {
        throw new UnderConstructionException('UrlBuilder::domain() is GARBAGE! DO NOT USE');
        /*
         * Do language mapping, but only if routemap has been set
         */
        // :TODO: This will fail when using multiple CDN servers (WHY?)
        if (!empty(Config::get('languages.supported', [])) and ($this->url_params['domain'] !== $_CONFIG['cdn']['domain'].'/')) {
            if ($this->url_params['from_language'] !== 'en') {
                /*
                 * Translate the current non-English URL to English first
                 * because the specified could be in dutch whilst we want to end
                 * up with Spanish. So translate always
                 * FOREIGN1 > English > Foreign2.
                 *
                 * Also add a / in front of $return before replacing to ensure
                 * we don't accidentally replace sections like "services/" with
                 * "servicen/" with Spanish URL's
                 */
                $return = str_replace('/' . $this->url_params['from_language'].'/', '/en/', '/' . $return);
                $return = substr($return, 1);

                if (!empty($core->register['route_map'])) {
                    foreach ($core->register['route_map'][$this->url_params['from_language']] as $foreign => $english) {
                        $return = str_replace($foreign, $english, $return);
                    }
                }
            }

            /*
             * From here the URL *SHOULD* be in English. If the URL is not
             * English here, then conversion from local language to English
             * right above failed
             */
            if ($this->url_params['language'] !== 'en') {
                /*
                 * Map the english URL to the requested non-english URL
                 * Only map if routemap has been set for the requested language
                 */
                if (empty($core->register['route_map'])) {
                    /*
                     * No route_map was set, only translate language selector
                     */
                    $return = str_replace('en/', $this->url_params['language'].'/', $return);

                } else {
                    if (empty($core->register['route_map'][$this->url_params['language']])) {
                        Notification(new CoreException(tr('domain(): Failed to update language sections for url ":url", no language routemap specified for requested language ":language"', array(':url' => $return, ':language' => $this->url_params['language'])), 'not-specified'));

                    } else {
                        $return = str_replace('en/', $this->url_params['language'].'/', $return);

                        foreach ($core->register['route_map'][$this->url_params['language']] as $foreign => $english) {
                            $return = str_replace($english, $foreign, $return);
                        }
                    }
                }
            }
        }
    }
}