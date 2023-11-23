<?php

declare(strict_types=1);

namespace Phoundation\Web\Http;

use Phoundation\Core\Exception\ConfigurationDoesNotExistsException;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\ArrayValidator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Phoundation\Web\Http\Exception\UrlBuilderConfiguredUrlNotFoundException;
use Phoundation\Web\Http\Interfaces\UrlBuilderInterface;
use Phoundation\Web\Page;
use Stringable;


/**
 * Class Domain
 *
 *
 * @todo Add language mapping, see the protected method language_map() at the bottom of this class for more info
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Http
 */
class UrlBuilder implements UrlBuilderInterface
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
     * UrlBuilder class constructor
     *
     * @param Stringable|string|null $url
     */
    protected function __construct(Stringable|string|null $url = null)
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
        $domain = static::getDomainFromUrl($this->url);

        try {
            if (Domains::getConfigurationKey($domain, 'cloaked')) {
                $this->cloak();
            }
        } catch (ConfigurationDoesNotExistsException) {
            // This domain is not configured, ignore it
        }

        return $this->url;
    }


    /**
     * Returns the current URL
     *
     * @param string|int|null $id
     * @return static
     */
    public static function getCurrent(string|int|null $id = null): static
    {
        $url = static::getCurrentDomainUrl();

        if ($id) {
            // Inject the ID in the URL
            $url = substr((string) $url, 0, -5) . '-' . $id . '.html';
            $url = new static($url);
        }

        return $url;
    }


    /**
     * Returns true if the specified URL is the same as the current URL
     *
     * @param Stringable|string $url
     * @return bool
     */
    public static function isCurrent(Stringable|string $url): bool
    {
        return (string) $url === (string) static::getCurrent();
    }


    /**
     * Returns a complete www URL
     *
     * @param UrlBuilder|string|null $url The URL to build
     * @param bool $use_configured_root If true, the builder will not use the root URI from the routing parameters but
     *                                  from the static configuration
     * @return static
     */
    public static function getWww(UrlBuilder|string|null $url = null, bool $use_configured_root = false): static
    {
        if (!$url) {
            $url = UrlBuilder::getCurrent();
        }

        return static::buildUrl($url, null, $use_configured_root);
    }


    /**
     * Returns a complete www URL for the previous page, or the specified URL
     *
     * This will return either the $_GET[previous], $_GET[redirect], or $_SERVER[referer] URL. If none of these exist,
     * or if they are the current page, then the specified URL will be sent instead.
     *
     * @param UrlBuilder|string|null $url The URL to build if no valid previous page is available
     * @param bool $use_configured_root If true, the builder will not use the root URI from the routing parameters but
     *                                  from the static configuration
     * @return static
     */
    public static function getPrevious(UrlBuilder|string|null $url = null, bool $use_configured_root = false): static
    {
        if (empty($_SERVER['HTTP_REFERER'])) {
            if (empty($_GET['previous'])) {
                if (!empty($_GET['redirect'])) {
                    $option = $_GET['redirect'];
                }

            } else {
                $option = $_GET['previous'];
            }

        } else {
            $option = $_SERVER['HTTP_REFERER'];
        }

        if (!empty($option)) {
            // We found an option, yay!
            $option  = static::getWww($option, $use_configured_root);
            $current = static::getWww(null, $use_configured_root);

            if ((string) $current !== (string) $option) {
                // Option is not current page, return it!
                return $option;
            }
        }

        // No URL found in any of the options, or option was current page. Use the specified URL
        return static::getWww($url, $use_configured_root);
    }


    /**
     * Returns a CDN URL
     *
     * @param Stringable|string $url
     * @param string|null $extension
     * @return static
     */
    public static function getCdn(Stringable|string $url, ?string $extension = null): static
    {
        $url = (string) $url;

        if (Url::isValid($url)) {
            return new static($url);
        }

        return static::buildCdn($url, $extension);
    }


    /**
     * Returns the root URL for the primary domain
     *
     * @return static
     */
    public static function getCurrentDomainRootUrl(): static
    {
        return new static(Page::getRootUrl());
    }


    /**
     * Returns the URL as requested for the primary domain
     *
     * @return static
     */
    public static function getCurrentDomainUrl(): static
    {
        return new static(Page::getUrl());
    }


    /**
     * Returns the root URL for the parent domain
     *
     * @return static
     */
    public static function getParentDomainRootUrl(): static
    {
        return new static(Domains::from()->getParent() . Page::getRootUri());
    }


    /**
     * Returns the URL as requested for the parent domain
     *
     * @return static
     */
    public static function getParentDomainUrl(): static
    {
        return new static(Domains::from()->getParent() . Page::getUri());
    }


    /**
     * Returns the root URL for the root domain
     *
     * @return static
     */
    public static function getRootDomainRootUrl(): static
    {
        return new static(Domains::from()->getRoot() . Page::getRootUri());
    }


    /**
     * Returns the URL as requested for the root domain
     *
     * @return static
     */
    public static function getRootDomainUrl(): static
    {
        return new static(Domains::from()->getRoot() . Page::getUri());
    }


    /**
     * Returns the current URL for the specified domain
     *
     * @param string $domain
     * @return static
     */
    public static function getDomainCurrentUrl(string $domain): static
    {
        return new static($domain . Page::getUri());
    }


    /**
     * Returns the root URL for the primary domain
     *
     * @return static
     */
    public static function getPrimaryDomainRootUrl(): static
    {
        return new static(Domains::getPrimary() . Page::getRootUri());
    }


    /**
     * Returns the URL as requested for the primary domain
     *
     * @return static
     */
    public static function getPrimaryDomainUrl(): static
    {
        return new static(Domains::getPrimary() . Page::getUri());
    }


    /**
     * Returns the "redirect" or referer (previous) URL
     *
     * @param Stringable|string|null $url
     * @return static
     */
    public static function getReferer(Stringable|string|null $url = null): static
    {
        $url = (string) $url;

        // Try to get a "redirect" via GET
        try {
            $get = GetValidator::new()
                ->select('redirect')->isOptional()->isUrl()
                ->validate(false);

        } catch (ValidationFailedException) {
            Log::warning(tr('Validation for redirect url ":url" failed, ignoring', [
                ':url' => GetValidator::new()->getSourceValue('redirect')
            ]));
        }

        if (isset_get($get['redirect'])) {
            // Use the redirect URL
            $url = $get['redirect'];

        } else {
            // Try referer
            try {
                $server = ArrayValidator::new($_SERVER)
                    ->select('HTTP_REFERER')->isOptional()->isUrl()
                    ->validate(false);

            } catch (ValidationFailedException) {
                Log::warning(tr('Validation for HTTP_REFERRER ":url" failed, ignoring', [
                    ':url' => $_SERVER['HTTP_REFERER']
                ]));
            }

            if (isset_get($server['HTTP_REFERER'])) {
                // Use the referer
                $url = $server['HTTP_REFERER'];

            } elseif (empty($url)) {
                // No url specified either, just go to the root page
                $url = static::getCurrentDomainRootUrl();
            }
        }

        return new static($url);
    }


    /**
     * Returns an ajax URL
     *
     * @param Stringable|string $url The URL to build
     * @param bool $use_configured_root If true, the builder will not use the root URI from the routing parameters but
     *                                  from the static configuration
     * @return static
     */
    public static function getAjax(Stringable|string $url, bool $use_configured_root = false): static
    {
        $url = (string) $url;

        if (!$url) {
            throw new OutOfBoundsException(tr('No URL specified'));
        }

        return static::buildUrl($url, 'ajax/', $use_configured_root);
    }


    /**
     * Returns an api URL
     *
     * @param Stringable|string $url The URL to build
     * @param bool $use_configured_root If true, the builder will not use the root URI from the routing parameters but
     *                                  from the static configuration
     * @return static
     */
    public static function getApi(Stringable|string $url, bool $use_configured_root = false): static
    {
        $url = (string) $url;

        if (!$url) {
            throw new OutOfBoundsException(tr('No URL specified'));
        }

        return static::buildUrl($url, 'api/', $use_configured_root);
    }


    /**
     * Returns a CSS URL
     *
     * @param Stringable|string $url
     * @return static
     */
    public static function getCss(Stringable|string $url): static
    {
        $url = (string) $url;

        if (Url::isValid($url)) {
            return new static($url);
        }

        return static::buildCdn($url, 'css');
    }


    /**
     * Returns a Javascript URL
     *
     * @param Stringable|string $url
     * @return static
     */
    public static function getJs(Stringable|string $url): static
    {
        $url = (string) $url;

        if (Url::isValid($url)) {
            return new static($url);
        }

        return static::buildCdn($url, 'js');
    }


    /**
     * Returns an image URL
     *
     * @param Stringable|string $url
     * @return static
     */
    public static function getImg(Stringable|string $url): static
    {
        $url = (string) $url;

        if (Url::isValid($url)) {
            return new static($url);
        }

//        if ($directory) {
//            throw new UnderConstructionException();
//            // Return the local filesystem path instead of a public URL
//            if (Url::isValid($url)) {
//                // This is an external URL, there is no local file
//                return new static($url);
//            }
//
//            $directory = Strings::startsNotWith($this->url, '/');
//
//            if (!str_starts_with($directory, 'img/')) {
//                $directory = 'img/' . $directory;
//            }
//
//            return $directory;
//        }

        return static::buildCdn($url);
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
            // Found cloaking URL, update the created_on time so that it won't expire too soon
            sql()->query('UPDATE `url_cloaks` 
                                SET    `created_on` = NOW() 
                                WHERE  `url`        = :url', [
                ':url' => $this->url
            ]);
        } else {
            $cloak = Strings::random(32);

            sql()->dataEntryInsert('url_cloaks', [
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

        sql()->dataEntryDelete('url_cloaks', [':cloak' => $this->url]);
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
     * Clear the query part from the URL
     *
     * @return static
     */
    public function clearQueries(): static
    {
        $this->url = Strings::until($this->url, '?');
        return $this;
    }


    /**
     * Add the specified query / queries to the specified URL and return
     *
     * @param array|string|bool|null ...$queries All the queries to add to this URL
     * @return static
     */
    public function addQueries(array|string|bool|null ...$queries): static
    {
        if (!$queries) {
            return $this;
        }

        foreach ($queries as $query) {
            if (!$query) continue;

            // Break the query up in multiple entries, if specified
            if (is_string($query) and str_contains($query, '&')) {
                $query = explode('&', $query);
            }

            // If the specified query is an array, then add each element individually
            if (is_array($query)) {
                foreach ($query as $key => $value) {
                    if (is_numeric($key)) {
                        // $value should contain key=value
                        static::addQueries($value);

                    } else {
                        static::addQueries($key . '=' . $value);
                    }
                }

                continue;
            }

            if ($query === true) {
                // Add the original query string
                $query = $_SERVER['QUERY_STRING'];
            }

            $this->url = Strings::endsNotWith($this->url, '?');

            if (!preg_match('/^[a-z0-9-_]+?=.*?$/i', $query)) {
                throw new OutOfBoundsException(tr('Invalid query ":query" specified. Please ensure it has the "key=value" format', [
                    ':query' => $query
                ]));
            }

            $key = Strings::until($query, '=');
            $value = Strings::from($query, '=');

            $key = urlencode($key);
            $value = urlencode($value);

            if (!str_contains($this->url, '?')) {
                // This URL has no query yet, begin one
                $this->url .= '?' . $key . '=' . $value;

            } elseif (str_contains($this->url, $key . '=')) {
                // The query already exists in the specified URL, replace it.
                $replace   = Strings::cut($this->url, $key . '=', '&');
                $this->url = str_replace($key . '=' . $replace, $key . '=' . $value, $this->url);

            } else {
                // Append the query to the URL
                $this->url .= '&' . $key . '=' . $value;
            }
        }

        return $this;
    }


    /**
     * Remove specified queries from the specified URL and return
     *
     * @param array|string|bool ...$queries All the queries to add to this URL
     * @return static
     */
    public function removeQueries(array|string|bool ...$queries): static
    {
throw new UnderConstructionException();
        if (!$queries) {
            throw new OutOfBoundsException(tr('No queries specified to remove from the specified URL'));
        }

        foreach ($queries as $query) {
            if (!$query) continue;

            if (is_array($query)) {
                // The queries were specified as an array. Add each individual entry separately and we're done
                foreach($query as $key => &$value) {
                    $this->addQueries($key . '=' . $value);
                }

                continue;
            }

            // Break the query up in multiple entries, if specified
            if (is_string($query) and str_contains($query, '&')) {
                $query = explode('&', $query);

                foreach ($query as $key => $value) {
                    if (is_numeric($key)) {
                        // $value should contain key=value
                        static::addQueries($this->url, $value);

                    } else {
                        static::addQueries($this->url, $key . '=' . $value);
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
     * Returns the domain for the specified URL, NULL if the URL is invalid
     *
     * @param string $url
     * @return string|null
     */
    public static function getDomainFromUrl(string $url): ?string
    {
        $data = parse_url($url);

        if (!$data) {
            throw new OutOfBoundsException(tr('Failed to parse url ":url" to fetch domain', [
                ':url' => $url
            ]));
        }

        $domain = isset_get($data['host']);

        if ($domain === null) {
            // Since there is no domain, assume we need the current domain
            return Domains::getCurrent();
        }

        return $domain;
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
     * @param Stringable|string $url
     * @param string|null $prefix
     * @param bool $use_configured_root If true, the builder will not use the root URI from the routing parameters but
     *                                  from the static configuration
     * @return static
     */
    protected static function buildUrl(Stringable|string $url, ?string $prefix, bool $use_configured_root): static
    {
        $url = static::applyPredefined($url);
        $url = static::applyVariables($url);

        if (Url::isValid($url)) {
            return new static($url);
        }

        // Get the base URL configuration for the domain
        if ($use_configured_root) {
            $base = Domains::getRootUrl();

        } elseif (PLATFORM_WEB) {
            $base = Page::getRoutingParameters()->getRootUrl();

        } else {
            $base = Domains::getRootUrl();
        }

        // Build the URL
        $base = Strings::endsWith($base, '/');
        $url  = Strings::startsNotWith($url, '/');
        $url  = $prefix . $url;
        $url  = str_replace(':LANGUAGE', Session::getLanguage(), $base . $url);

        return new static($url);
    }


    /**
     * Returns a CDN URL
     *
     * @todo Clean URL strings, escape HTML characters, " etc.
     * @param Stringable|string $url
     * @param string|null $extension
     * @return static
     * @throws OutOfBoundsException If no URL was specified
     */
    protected static function buildCdn(Stringable|string $url, ?string $extension = null): static
    {
        $url = static::applyPredefined($url);
        $url = static::applyVariables($url);

        if (!$url) {
            throw new OutOfBoundsException(tr('No URL specified'));
        }

        if (Url::isValid($url)) {
            return new static($url);
        }

        $url  = Strings::from($url,'data/content/cdn/');
        $base = Domains::getConfigurationKey(Domains::getCurrent(), 'cdn', $_SERVER['REQUEST_SCHEME'] . '://cdn.' . Domains::getCurrent() . '/:LANGUAGE/');
        $base = Strings::endsWith($base, '/');
        $url  = Strings::startsNotWith($url, '/');
        $url .= static::addExtension($extension);
        $url  = str_replace(':LANGUAGE', Session::getLanguage(), $base . $url);

        return new static($url);
    }


    /**
     * Apply predefined URL names
     *
     * @param Stringable|string $url
     * @return string
     */
    protected static function applyPredefined(Stringable|string $url): string
    {
        $url    = (string) $url;
        $return = match ($url) {
            'self', 'this' , 'here'       => static::getCurrent(),
            'root'                        => static::getCurrentDomainRootUrl(),
            'prev', 'previous', 'referer' => static::getPrevious(),
            default                       => null,
        };

        if ($return) {
            return (string) $return;
        }

        try {
            return (String) static::getConfigured($url);

        } catch (UrlBuilderConfiguredUrlNotFoundException) {
            // This was not a configured URL
            return new $url;
        }
    }


    /**
     * Apply predefined URL names
     *
     * @param Stringable|string $url
     * @return UrlBuilderInterface
     */
    public static function getConfigured(Stringable|string $url): UrlBuilderInterface
    {
        $url = (string) $url;

        // Configured page?
        $configured = match ($url) {
            'index'    => Config::getString('web.pages.index'   , '/index.html'),
            'sign-in'  => Config::getString('web.pages.sign-in' , '/sign-in.html'),
            'sign-up'  => Config::getString('web.pages.sign-up' , '/sign-up.html'),
            'sign-out' => Config::getString('web.pages.sign-out', '/sign-out.html'),
            'sign-key' => Config::getString('web.pages.sign-key', '/sign-key/:key.html'),
            default    => Config::getString('web.pages.' . $url , '')
        };

        if ($configured) {
            return new static($configured);
        }

        throw new UrlBuilderConfiguredUrlNotFoundException(tr('Specified configured URL ":url" not found', [
            ':url' => $url
        ]));
    }


    /**
     * Apply variables in the URL
     *
     * @param Stringable|string $url
     * @return UrlBuilder
     */
    protected static function applyVariables(Stringable|string $url): string
    {
        $url = (string) $url;
        $url = str_replace(':PROTOCOL', Protocols::getCurrent() , $url);
        $url = str_replace(':DOMAIN'  , Domains::getCurrent()   , $url);
        $url = str_replace(':PORT'    , (string) Page::getPort(), $url);
        $url = str_replace(':LANGUAGE', Page::getLanguageCode() , $url);

        return $url;
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
