<?php

namespace Phoundation\Web\Http;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Exception\ConfigNotExistsException;
use Phoundation\Core\Log;
use Phoundation\Core\Session;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;



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
     * The domain to build the URL with
     *
     * @var string $domain
     */
    protected string $domain;

    /**
     * If true, the URL will be built, if false, the URL is a full external URL and will not be built
     *
     * @var bool $process
     */
    protected bool $process = true;

    /**
     * The configuration for the domain to build the URL with
     *
     * @var array $configuration
     */
    protected array $configuration;

    /**
     * The url to work with
     *
     * @var string $url
     */
    protected string $url;



    /**
     * UrlBuilder constructor
     *
     * @param string|bool|null $url
     * @param bool|null $cloaked
     */
    public function __construct(string|bool|null $url = null, ?bool $cloaked = null)
    {
        // Apply URL presets. Any of these presets will result in full URLs, and we will not have to build anything so
        // $this->process will be set to false.
        if (($url === true) or ($url === 'self')) {
            // THIS URL.
            $this->useCurrentDomain();
            $this->url = $_SERVER['REQUEST_URI'];

        } elseif ($url === false) {
            // Special redirect. Redirect to this very page, but without queries
            $this->useCurrentDomain();
            $this->url = Strings::until($_SERVER['REQUEST_URI'], '?');

        } elseif ($url === 'prev') {
            // Previous page; Assume we came from the HTTP_REFERER page
            $this->url = isset_get($_SERVER['HTTP_REFERER']);
            $this->process = false;

            if (!$this->url or ($this->url == $_SERVER['REQUEST_URI'])) {
                // Don't redirect to the same page! If the referrer was this page, then drop back to the index page
                $this->url = $this->getIndexUrl();
                $this->process = true;
            }

        } elseif (!$url) {
            // No target specified, redirect to index page
            $this->url = $this->getIndexUrl();
            $this->process = false;
        } else {
            // This is a URL section
            $this->url = Strings::startsNotWith($url, '/');
        }

        $this->setCloaked($cloaked);
    }



    /**
     * Returns if generated URL's will be cloaked or not
     *
     * @return bool
     */
    public function getCloaked(): bool
    {
        return (bool) isset_get($this->configuration['cloaked']);
    }



    /**
     * Sets if generated URL's will be cloaked or not
     *
     * @param bool|null $cloaked
     * @return UrlBuilder
     */
    public function setCloaked(?bool $cloaked): UrlBuilder
    {
        $this->ensureDomain();

        if ($cloaked === null) {
            $cloaked = (bool) Config::get('web.domains.' . $this->configuration['domain'] . '.cloaked', false);
        }

        $this->cloaked = $cloaked;
        return $this;
    }



    /**
     * Returns the used domain
     *
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }



    /**
     * Sets the used domain
     *
     * @param string $domain
     * @return UrlBuilder
     */
    public function useDomain(string $domain): UrlBuilder
    {
        $this->setDomainConfiguration($domain);
        $this->domain = $domain;
        return $this;
    }



    /**
     * Sets the used domain to the current domain (WEB only)
     *
     * @return UrlBuilder
     */
    public function useCurrentDomain(): UrlBuilder
    {
        $this->setDomainConfiguration($_SERVER['HTTP_HOST']);
        $this->domain = $_SERVER['HTTP_HOST'];
        return $this;
    }



    /**
     * Returns the used domain
     *
     * @return UrlBuilder
     */
    public function usePrimaryDomain(): UrlBuilder
    {
        $this->setDomainConfiguration('primary');
        $this->domain = $this->configuration['domain'];
        return $this;
    }



    /**
     * Returns the URL as it has been built
     *
     * @return string
     */
    public function get(): string
    {
        return $this->url;
    }



    /**
     * Returns a CDN URL
     *
     * @param string|null $extension
     * @return string
     */
    public function cdn(?string $extension = null): string
    {
        if (Url::is($this->url)) {
            return $this->url;
        }

        $this->url = $this->buildDomainPrefix('cdn', $this->url);
        $this->url = $this->buildExtension($extension);

        return $this->url;
    }



    /**
     * Returns a www URL
     *
     * @return string
     */
    public function www(): string
    {
        if (Url::is($this->url)) {
            return $this->url;
        }

        $this->url = $this->buildDomainPrefix('www', $this->url);

        return $this->url;
    }



    /**
     * Returns an ajax URL
     *
     * @return string
     */
    public function ajax(): string
    {
        if (Url::is($this->url)) {
            return $this->url;
        }

        $this->url = $this->buildDomainPrefix('www', 'ajax/' . $this->url);

        return $this->url;
    }



    /**
     * Returns an api URL
     *
     * @return string
     */
    public function api(): string
    {
        if (Url::is($this->url)) {
            return $this->url;
        }

        $this->url = $this->buildDomainPrefix('www', 'api' . $this->url);

        return $this->url;
    }



    /**
     * Returns a CSS URL
     *
     * @return string
     */
    public function css(): string
    {
        if (Url::is($this->url)) {
            return $this->url;
        }

        $this->url = Strings::startsNotWith($this->url, '/');

        if (!str_starts_with($this->url, 'css/')) {
            $this->url = 'css/' . $this->url;
        }

        return $this->cdn('css');
    }



    /**
     * Returns a Javascript URL
     *
     * @return string
     */
    public function js(): string
    {
        if (Url::is($this->url)) {
            return $this->url;
        }

        $this->url = Strings::startsNotWith($this->url, '/');

        if (!str_starts_with($this->url, 'js/')) {
            $this->url = 'js/' . $this->url;
        }

        return $this->cdn('js');
    }



    /**
     * Returns an image URL
     *
     * @return string|null
     */
    public function img(bool $path = false): ?string
    {
        if ($path) {
            // Return the local filesystem path instead of a public URL
            if (Url::is($this->url)) {
                // This is an external URL, there is no local file
                return null;
            }

            $path = Strings::startsNotWith($this->url, '/');

            if (!str_starts_with($path, 'img/')) {
                $path = 'img/' . $path;
            }

            return $path;
        }

        if (Url::is($this->url)) {
            return $this->url;
        }

        $this->url = Strings::startsNotWith($this->url, '/');

        if (!str_starts_with($this->url, 'img/')) {
            $this->url = 'img/' . $this->url;
        }

        return $this->cdn();
    }



    /**
     * Returns true if the specified string is a full URL
     *
     * @return string
     */
    public function getIndexUrl(): string
    {
        $this->useCurrentDomain();
        return $this->buildDomainPrefix('www', $this->configuration['index']);
    }



    /**
     * Uncloak the specified URL.
     *
     * URL cloaking is nothing more than
     *
     * @return UrlBuilder
     */
    public function decloak(): UrlBuilder
    {
        $data = sql()->getColumn('SELECT `created_by`, `url` 
                                        FROM   `url_cloaks` 
                                        WHERE  `cloak` = :cloak', [':cloak' => $this->url]);

        if (!$data) {
        }

        // Auto cleanup?
        // TODO Redo this. We can't cleanup once in a 100 clicks or something that is stupid with any traffic at all. Clean up all after 24 hours, cleanup once every 24 hours, something like that.

        //        $interval = Config::get('web.url.cloaking.interval', 86400);
        //
        //        if (mt_rand(0, 100) <=  {
        //            self::cleanupCloak();
        //        }

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
    public function cleanupCloak(): int
    {
        global $_CONFIG;

        Log::notice(tr('Cleaning up `url_cloaks` table'));

        $r = sql()->query('DELETE FROM `url_cloaks` 
                                 WHERE `created_on` < DATE_SUB(NOW(), INTERVAL ' . Config::get('web.url.cloaking.expires', 86400).' SECOND);');

        Log::success(tr('Removed ":count" expired entries from the `url_cloaks` table', [
            ':count' => $r->rowCount()
        ]));

        return $r->rowCount();
    }



    /**
     * Builds and returns the domain prefix
     *
     * @param string $type
     * @param string $url
     * @return string
     */
    protected function buildDomainPrefix(#[ExpectedValues(values: ['www', 'cdn'])] string $type, string $url): string
    {
        $this->ensureDomain();

        if ($this->process) {
            $this->configuration[$type] = Strings::endsWith($this->configuration[$type], '/');

            $url = Strings::startsNotWith($url, '/');
            $url = str_replace(':LANGUAGE', Session::getLanguage(), $this->configuration[$type]) . $url;
        }

        if ($this->configuration['cloaked']) {
            $url = $this->cloak($url);
        }

        $this->url = $url;
        return $url;
    }



    /**
     * Build a URL with extension
     *
     * @param string|null $extension
     * @return string
     */
    protected function buildExtension(?string $extension): string
    {
        if (!$extension) {
            return $this->url;
        }

        if (Config::get('web.minify', true)) {
            return $this->url . '.min.' . $extension;
        }

        return $this->url . $extension;
    }



    /**
     * Sets the internal configuration for the selected domain
     *
     * @param string $domain
     * @return void
     */
    protected function setDomainConfiguration(string $domain): void
    {
        // Use current domain
        $domains = Config::get('web.domains');

        if ($domains['primary']['domain'] === $domain) {
            // Specified domain is the primary domain
            $domain = 'primary';
        } else {
            // It's not the primary domain
            if (!array_key_exists($domain, $domains)) {
                // It's not a listed domain either, we don't know this domain, oh noes!
                throw ConfigNotExistsException::new(tr('No configuration available for domain ":domain"', [
                    ':domain' => $domain
                ]));
            }
        }

        $configuration = $domains[$domain];

        unset($domains);

        // Validate configuration
        // TODO implement
        Arrays::requiredKeys($configuration, 'domain,www,cdn', ConfigNotExistsException::class);
        Arrays::default($configuration, 'index'  , '/');
        Arrays::default($configuration, 'cloaked', false);

        $this->configuration = $configuration;
    }



    /**
     * @return void
     */
    protected function ensureDomain(): void
    {
        if (!isset($this->domain)) {
            // We have no domain selected yet! Assume current domain
            $this->useCurrentDomain();
        }
    }



    /**
     * Add specified query to the specified URL and return
     *
     * @param string $query [$query] ... All the queries to add to this URL
     * @return UrlBuilder
     */
    public function addQueries(): UrlBuilder
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
     * Cloak the specified URL.
     *
     * URL cloaking is nothing more than replacing a full URL (with query) with a random string. This function will
     * register the requested URL
     *
     * @return string
     */
    protected function cloak(string $url): string
    {
        $cloak = sql()->getColumn('SELECT `cloak`
                                  FROM   `url_cloaks`
                                  WHERE  `url`        = :url
                                  AND    `created_by` = :created_by', [
            ':url'        => $url,
            ':created_by' => isset_get($_SESSION['user']['id'])
        ]);

        if ($cloak) {
            // Found cloaking URL, update the created_on time so that it won't exipre too soon
            sql()->query('UPDATE `url_cloaks` 
                                SET    `created_on` = NOW() 
                                WHERE  `url` = :url', [':url' => $url]);
        } else {
            $cloak = Strings::random(32);

            sql()->insert('url_cloaks', [
                'created_by' => isset_get($_SESSION['user']['id']),
                'cloak'      => $cloak,
                'url'        => $url
            ]);
        }

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