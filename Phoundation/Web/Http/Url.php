<?php

/**
 * Class Url
 *
 *
 * @todo      Change this from a static class to just a normal class
 * @todo      Add language mapping, see the protected method language_map() at the bottom of this class for more info
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Http;

use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Validator\ArrayValidator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Exception\ConfigPathDoesNotExistsException;
use Phoundation\Utils\Strings;
use Phoundation\Web\Http\Exception\UrlBuilderConfiguredUrlNotFoundException;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use Stringable;

class Url implements UrlInterface
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
            $this->url = Strings::ensureStartsNotWith($url, '/');

        } else {
            // This is a valid URL, continue.
            $this->url = $url;
        }
    }


    /**
     * Returns the URL where to redirect to
     *
     * @param Stringable|string|null ...$urls
     *
     * @return static
     */
    public static function getRedirect(Stringable|string|null ...$urls): static
    {
        foreach ($urls as $url) {
            if (!$url) {
                continue;
            }

            $url = Url::getWww($url);

            if ($url->getUrl(true) === static::getCurrent()->getUrl(true)) {
                continue;
            }

            if ((string) $url === (string) static::getWww('index')) {
                continue;
            }

            return $url;
        }

        return static::getWww('index');
    }


    /**
     * Returns a complete web URL
     *
     * @param UrlInterface|string|int|null $url                        The URL to build
     * @param bool                         $use_configured_root        If true, the builder will not use the root URI
     *                                                                 from the routing parameters but from the static
     *                                                                 configuration
     *
     * @return static
     */
    public static function getWww(UrlInterface|string|int|null $url = null, bool $use_configured_root = false): static
    {
        if (!$url) {
            $url = Url::getCurrent();

        } elseif (is_numeric($url)) {
            $url = 'system/' . $url . 'html';
        }

        return static::renderUrl($url, null, $use_configured_root);
    }


    /**
     * Returns the current URL
     *
     * @param string|int|null $id
     * @param bool            $strip_queries
     *
     * @return static
     */
    public static function getCurrent(string|int|null $id = null, bool $strip_queries = false): static
    {
        $url = static::getCurrentDomainUrl();

        if ($id) {
            // Inject the ID in the URL
            $url = substr((string) $url, 0, -5) . '+' . $id . '.html';
            $url = new static($url);
        }

        if ($strip_queries) {
            return static::getWww(Strings::until((string) $url, '?'));
        }

        return $url;
    }


    /**
     * Returns the URL as requested for the primary domain
     *
     * @return static
     */
    public static function getCurrentDomainUrl(): static
    {
        return new static(Request::getUrl());
    }


    /**
     * When used as string, will always return the internal URL as available
     *
     * @param bool $strip_queries
     *
     * @return string
     */
    public function getUrl(bool $strip_queries = false): string
    {
        // Auto cloak URL's?
        $domain = static::getDomainFromUrl($this->url);

        try {
            if (Domains::getConfigurationKey($domain, 'cloaked')) {
                $this->cloak();
            }

        } catch (ConfigPathDoesNotExistsException) {
            // This domain is not configured, ignore it
        }

        if ($strip_queries) {
            return Strings::until($this->url, '?');
        }

        return $this->url;
    }


    /**
     * Returns the domain for the specified URL, NULL if the URL is invalid
     *
     * @param string $url
     *
     * @return string|null
     */
    public static function getDomainFromUrl(string $url): ?string
    {
        $data = parse_url($url);

        if (!$data) {
            throw new OutOfBoundsException(tr('Failed to parse url ":url" to fetch domain', [
                ':url' => $url,
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
            ':created_by' => isset_get($_SESSION['user']['id']),
        ]);

        if ($cloak) {
            // Found cloaking URL, update the created_on time so that it won't expire too soon
            sql()->query('UPDATE `url_cloaks` 
                                SET    `created_on` = NOW() 
                                WHERE  `url`        = :url', [
                ':url' => $this->url,
            ]);

        } else {
            $cloak = Strings::getRandom(32);
            sql()->insert('url_cloaks', [
                'created_by' => Session::getUser()
                                       ->getId(),
                'cloak'      => $cloak,
                'url'        => $this->url,
            ]);
        }

        $this->url = $cloak;

        return $this;
    }


    /**
     * Builds and returns the domain prefix
     *
     * @param Stringable|string $url
     * @param string|null       $prefix
     * @param bool              $use_configured_root If true, the builder will not use the root URI from the routing
     *                                               parameters but from the static configuration
     *
     * @return static
     */
    protected static function renderUrl(Stringable|string $url, ?string $prefix, bool $use_configured_root): static
    {
        $url = static::applyPredefined($url);
        $url = static::applyVariables($url);

        if (static::isValid($url)) {
            return new static($url);
        }

        // Get the base URL configuration for the domain
        if ($use_configured_root) {
            $base = Domains::getRootUrl();

        } elseif (PLATFORM_WEB) {
            $base = Request::getRoutingParameters()->getRootUrl();

        } else {
            $base = Domains::getRootUrl();
        }

        // Build the URL
        $base = Strings::ensureEndsWith($base, '/');
        $url  = Strings::ensureStartsNotWith($url, '/');
        $url  = $prefix . $url;
        $url  = str_replace(':LANGUAGE', Session::getLanguage(), $base . $url);

        return new static($url);
    }


    /**
     * Apply predefined URL names
     *
     * @param Stringable|string $url
     *
     * @return string
     */
    protected static function applyPredefined(Stringable|string $url): string
    {
        $url    = (string) $url;
        $return = match ($url) {
            'self', 'this', 'here'        => static::getCurrent(),
            'root'                        => static::getCurrentDomainRootUrl(),
            'prev', 'previous', 'referer' => static::getPrevious(),
            default                       => null,
        };

        if ($return) {
            return (string) $return;
        }

        try {
            return (string) static::getConfigured($url);

        } catch (UrlBuilderConfiguredUrlNotFoundException) {
            // This was not a configured URL
            return $url;
        }
    }


    /**
     * Returns the root URL for the primary domain
     *
     * @return static
     */
    public static function getCurrentDomainRootUrl(): static
    {
        return new static(Request::getRootUrl());
    }


    /**
     * Returns a complete web URL for the previous page, or the specified URL
     *
     * This will return either the $_GET[previous], $_GET[redirect], or $_SERVER[referer] URL. If none of these exist,
     * or if they are the current page, then the specified URL will be sent instead.
     *
     * @param Url|string|null $or_else_url                The URL to build if no valid previous page is available
     * @param bool            $use_configured_root        If true, the builder will not use the root URI from the
     *                                                    routing parameters but from the static configuration
     *
     * @return static
     */
    public static function getPrevious(Url|string|null $or_else_url = null, bool $use_configured_root = false): static
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
        return static::getWww($or_else_url, $use_configured_root);
    }


    /**
     * Apply predefined URL names
     *
     * @param Stringable|string $url
     *
     * @return UrlInterface
     */
    public static function getConfigured(Stringable|string $url): UrlInterface
    {
        $url = (string) $url;

        // Configured page?
        $configured = match (Strings::until($url, '.html')) {
            'dashboard', 'index'   => Config::getString('web.pages.index'   , '/index.html'),
            'sign-in'  , 'signin'  => Config::getString('web.pages.sign-in' , '/sign-in.html'),
            'sign-up'  , 'signup'  => Config::getString('web.pages.sign-up' , '/sign-up.html'),
            'sign-out' , 'signout' => Config::getString('web.pages.sign-out', '/sign-out.html'),
            'sign-key' , 'signkey' => Config::getString('web.pages.sign-key', '/sign-key/:key.html'),
            'profile'              => Config::getString('web.pages.profile' , '/my/profile.html'),
            'settings'             => Config::getString('web.pages.settings', '/my/settings.html'),
            default                => Config::getString('web.pages.' . $url , '')
        };

        if ($configured) {
            return new static($configured);
        }

        return new static($url);
    }


    /**
     * Apply variables in the URL
     *
     * @param Stringable|string $url
     *
     * @return Url
     */
    protected static function applyVariables(Stringable|string $url): string
    {
        $url = (string) $url;
        $url = str_replace(':PROTOCOL', Request::getProtocol(), $url);
        $url = str_replace(':DOMAIN'  , Domains::getCurrent(), $url);
        $url = str_replace(':PORT'    , (string) Request::getPort(), $url);
        $url = str_replace(':LANGUAGE', Response::getLanguageCode(), $url);

        return $url;
    }


    /**
     * Returns true if the specified URL is the same as the current URL
     *
     * @param Stringable|string $url
     * @param bool              $strip_queries
     *
     * @return bool
     */
    public static function isCurrent(Stringable|string $url, bool $strip_queries = false): bool
    {
        return (string) $url === (string) static::getCurrent(strip_queries: $strip_queries);
    }


    /**
     * Returns a CDN URL
     *
     * @param Stringable|string $url
     * @param string|null       $extension
     *
     * @return static
     */
    public static function getCdn(Stringable|string $url, ?string $extension = null): static
    {
        $url = (string) $url;

        if (static::isValid($url)) {
            return new static($url);
        }

        return static::renderCdn($url, $extension);
    }


    /**
     * Returns a CDN URL
     *
     * @todo Clean URL strings, escape HTML characters, " etc.
     *
     * @param Stringable|string $url
     * @param string|null       $extension
     *
     * @return static
     * @throws OutOfBoundsException If no URL was specified
     */
    protected static function renderCdn(Stringable|string $url, ?string $extension = null): static
    {
        $url = static::applyPredefined($url);
        $url = static::applyVariables($url);

        if (!$url) {
            throw new OutOfBoundsException(tr('No URL specified'));
        }

        if (static::isValid($url)) {
            return new static($url);
        }

        $url  = Strings::from($url, 'data/content/cdn/');
        $base = Domains::getConfigurationKey(Domains::getCurrent(), 'cdn', $_SERVER['REQUEST_SCHEME'] . '://cdn.' . Domains::getCurrent() . '/:LANGUAGE/');
        $base = Strings::ensureEndsWith($base, '/');
        $url  = Strings::ensureStartsNotWith($url, '/');
        $url  .= static::addExtension($extension);
        $url  = str_replace(':LANGUAGE', Session::getLanguage(), $base . $url);

        return new static($url);
    }


    /**
     * Returns the extension for the URL
     *
     * @param string|null $extension
     *
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
     * Returns the root URL for the parent domain
     *
     * @return static
     */
    public static function getParentDomainRootUrl(): static
    {
        return new static(Domains::from()->getParent() . Request::getRootUri());
    }


    /**
     * Returns the URL as requested for the parent domain
     *
     * @return static
     */
    public static function getParentDomainUrl(): static
    {
        return new static(Domains::from()->getParent() . Request::getUri());
    }


    /**
     * Returns the root URL for the root domain
     *
     * @return static
     */
    public static function getRootDomainRootUrl(): static
    {
        return new static(Domains::from()->getRoot() . Request::getRootUri());
    }


    /**
     * Returns the URL as requested for the root domain
     *
     * @return static
     */
    public static function getRootDomainUrl(): static
    {
        return new static(Domains::from()->getRoot() . Request::getUri());
    }


    /**
     * Returns the current URL for the specified domain
     *
     * @param string $domain
     *
     * @return static
     */
    public static function getDomainCurrentUrl(string $domain): static
    {
        return new static($domain . Request::getUri());
    }


    /**
     * Returns the root URL for the primary domain
     *
     * @return static
     */
    public static function getPrimaryDomainRootUrl(): static
    {
        return new static(Domains::getPrimaryWeb() . Request::getRootUri());
    }


    /**
     * Returns the URL as requested for the primary domain
     *
     * @return static
     */
    public static function getPrimaryDomainUrl(): static
    {
        return new static(Domains::getPrimaryWeb() . Request::getUri());
    }


    /**
     * Returns the "redirect" or referer (previous) URL
     *
     * @param Stringable|string|null $url
     *
     * @return static
     */
    public static function getReferer(Stringable|string|null $url = null): static
    {
        $url = (string) $url;

        // Try to get a "redirect" via GET
        try {
            $get = GetValidator::new()
                               ->select('redirect')
                               ->isOptional()
                               ->isUrl()
                               ->validate(false);

        } catch (ValidationFailedException) {
            Log::warning(tr('Validation for redirect url ":url" failed, ignoring', [
                ':url' => GetValidator::new()
                                      ->get('redirect'),
            ]));
        }
        if (isset_get($get['redirect'])) {
            // Use the redirect URL
            $url = $get['redirect'];

        } else {
            // Try referer
            try {
                $server = ArrayValidator::new($_SERVER)
                                        ->select('HTTP_REFERER')
                                        ->isOptional()
                                        ->isUrl()
                                        ->validate(false);

            } catch (ValidationFailedException) {
                Log::warning(tr('Validation for HTTP_REFERRER ":url" failed, ignoring', [
                    ':url' => $_SERVER['HTTP_REFERER'],
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
     * @param UrlInterface|string|int|null $url                        The URL to build
     * @param bool                         $use_configured_root        If true, the builder will not use the root URI
     *                                                                 from the routing parameters but from the static
     *                                                                 configuration
     *
     * @return static
     */
    public static function getAjax(UrlInterface|string|int|null $url, bool $use_configured_root = false): static
    {
        $url = (string) $url;
        if (!$url) {
            throw new OutOfBoundsException(tr('No URL specified'));

        } elseif (is_numeric($url)) {
            $url = 'system/' . $url . 'json';
        }

        return static::renderUrl($url, 'ajax/', $use_configured_root);
    }


    /**
     * Returns an api URL
     *
     * @param UrlInterface|string|int|null $url                        The URL to build
     * @param bool                         $use_configured_root        If true, the builder will not use the root URI
     *                                                                 from the routing parameters but from the static
     *                                                                 configuration
     *
     * @return static
     */
    public static function getApi(UrlInterface|string|int|null $url, bool $use_configured_root = false): static
    {
        $url = (string) $url;

        if (!$url) {
            throw new OutOfBoundsException(tr('No URL specified'));

        } elseif (is_numeric($url)) {
            $url = 'system/' . $url . 'json';
        }

        return static::renderUrl($url, 'api/', $use_configured_root);
    }


    /**
     * Returns a CSS URL
     *
     * @param Stringable|string $url
     *
     * @return static
     */
    public static function getCss(Stringable|string $url): static
    {
        $url = (string) $url;

        if (static::isValid($url)) {
            return new static($url);
        }

        return static::renderCdn($url, 'css');
    }


    /**
     * Returns a Javascript URL
     *
     * @param Stringable|string $url
     *
     * @return static
     */
    public static function getJs(Stringable|string $url): static
    {
        $url = (string) $url;

        if (static::isValid($url)) {
            return new static($url);
        }

        return static::renderCdn($url, 'js');
    }


    /**
     * Returns an image URL
     *
     * @param Stringable|string $url
     *
     * @return static
     */
    public static function getImg(Stringable|string $url): static
    {
        $url = (string) $url;

        if (static::isValid($url)) {
            return new static($url);
        }

//        if ($directory) {
//            throw new UnderConstructionException();
//            // Return the local filesystem path instead of a public URL
//            if (static::isValid($url)) {
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

        $url = Strings::ensureStartsWith($url, 'img/');

        return static::renderCdn($url);
    }


    /**
     * Cleanup the url_cloaks table
     *
     * Since the URL cloaking table might fill up over time with new entries, this function will be periodically
     * executed by url_decloak() to cleanup the table
     *
     * @return int The number of expired entries removed from the `url_cloaks` table
     * @see static::decloak()
     */
    public static function cleanupCloak(): int
    {
        Log::notice(tr('Cleaning up `url_cloaks` table'));

        $r = sql()->query('DELETE FROM `url_cloaks` 
                                 WHERE `created_on` < DATE_SUB(NOW(), INTERVAL ' . Config::get('web.url.cloaking.expires', 86400) . ' SECOND);');

        Log::success(tr('Removed ":count" expired entries from the `url_cloaks` table', [
            ':count' => $r->rowCount(),
        ]));

        return $r->rowCount();
    }


    /**
     * Returns an array containing all the queries found in the specified URL
     *
     * @param Stringable|string                   $url
     * @param IteratorInterface|array|string|null $remove_keys
     * @param bool                                $unescape
     *
     * @return array
     */
    public static function getQueries(Stringable|string $url, IteratorInterface|array|string|null $remove_keys = null, bool $unescape = true): array
    {
        $return      = [];
        $queries     = Strings::from($url, '?', needle_required: true);
        $queries     = Arrays::force($queries, '&');
        $remove_keys = Arrays::force($remove_keys);

        foreach ($queries as $query) {
            [$key, $value] = explode('=', $query);

            if ($remove_keys and array_key_exists($key, $remove_keys)) {
                continue;
            }

            if ($unescape) {
                $return[$key] = urldecode($value);

            } else {
                $return[$key] = $value;
            }
        }

        return $return;
    }


    /**
     * When used as string, will always return the internal URL as available
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getUrl();
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
                ':url' => $this->url,
            ]));
        }

        sql()->delete('url_cloaks', [':cloak' => $this->url]);

        return $this;
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
     * Remove specified queries from the specified URL and return
     *
     * @param array|string|bool ...$queries All the queries to add to this URL
     *
     * @return static
     */
    public function removeQueries(array|string|bool ...$queries): static
    {
        throw new UnderConstructionException();
        if (!$queries) {
            throw new OutOfBoundsException(tr('No queries specified to remove from the specified URL'));
        }
        foreach ($queries as $query) {
            if (!$query) {
                continue;
            }
            if (is_array($query)) {
                // The queries were specified as an array. Add each individual entry separately and we're done
                foreach ($query as $key => &$value) {
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
                $this->url = preg_replace('/' . substr($query, 1) . '/', '', $this->url);
                $this->url = str_replace('&&', '', $this->url);
                $this->url = Strings::ensureEndsNotWith($this->url, [
                    '?',
                    '&',
                ]);
                continue;
            }
            $this->url = Strings::ensureEndsNotWith($this->url, '?');
            if (!preg_match('/.+?=.*?/', $query)) {
                throw new OutOfBoundsException(tr('Invalid query ":query" specified. Please ensure it has the "key=value" format', [
                    ':query' => $query,
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
                $this->url = $this->url . Strings::ensureEndsWith($this->url, '&');
            }
        }

        return $this;
    }


    /**
     * Add the specified query / queries to the specified URL and return
     *
     * @param array|string|bool|null ...$queries All the queries to add to this URL
     *
     * @return static
     */
    public function addQueries(array|string|bool|null ...$queries): static
    {
        if (!$queries) {
            return $this;
        }

        foreach ($queries as $query) {
            if (!$query) {
                continue;
            }

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

            $this->url = Strings::ensureEndsNotWith($this->url, '?');

            if (!preg_match('/^[a-z0-9-_]+?=.*?$/i', $query)) {
                throw new OutOfBoundsException(tr('Invalid query ":query" specified. Please ensure it has the "key=value" format', [
                    ':query' => $query,
                ]));
            }

            $key   = Strings::until($query, '=');
            $value = Strings::from($query, '=');
            $key   = urlencode($key);
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
        if (!empty(Config::get('language.supported', [])) and ($this->url_params['domain'] !== $_CONFIG['cdn']['domain'] . '/')) {
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
                $return = str_replace('/' . $this->url_params['from_language'] . '/', '/en/', '/' . $return);
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
                    $return = str_replace('en/', $this->url_params['language'] . '/', $return);

                } else {
                    if (empty($core->register['route_map'][$this->url_params['language']])) {
                        Notification(new CoreException(tr('domain(): Failed to update language sections for url ":url", no language routemap specified for requested language ":language"', [
                            ':url'      => $return,
                            ':language' => $this->url_params['language'],
                        ]), 'not-specified'));

                    } else {
                        $return = str_replace('en/', $this->url_params['language'] . '/', $return);
                        foreach ($core->register['route_map'][$this->url_params['language']] as $foreign => $english) {
                            $return = str_replace($english, $foreign, $return);
                        }
                    }
                }
            }
        }
    }


    /**
     * Returns true if the specified string is an external URL
     *
     * External here means that the domain is NOT one of the configured domains
     *
     * @param string $url
     * @param bool   $check_sub_domains
     *
     * @return bool
     */
    public static function isExternal(string $url, bool $check_sub_domains = true): bool
    {
        if (!static::isValid($url)) {
            // This isn't even a complete URL, must be internal, there is no domain name expected here
            return false;
        }

        // We have a complete URL, so there is a domain name in there. Check if it's a "local" (ie, on this server)
        // domain name
        return !static::getDomainType($url, $check_sub_domains);
    }


    /**
     * Returns true if the specified string is a full and VALID URL
     *
     * @param string $url
     *
     * @return bool
     */
    public static function isValid(string $url): bool
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }


    /**
     * Returns true if the specified string is an external URL
     *
     * External here means that the domain is NOT one of the configured domains
     *
     * @param string $url
     * @param bool   $check_sub_domains
     *
     * @return string|null web in case its on a WWW domain, cdn in case its on a CDN domain, NULL if it's on an external
     */
    public static function getDomainType(string $url, bool $check_sub_domains = true): ?string
    {
        // Get all domain names and check if its primary or subdomain of those.
        $url_domain = static::getDomainFromUrl($url);
        $domains    = Config::get('web.domains');

        foreach ($domains as $domain) {
            // Get CDN and WWW domains
            $names = ['web' => Strings::cut($domain['web'], '//', '/')];

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
}
