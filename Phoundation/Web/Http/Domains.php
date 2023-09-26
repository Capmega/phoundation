<?php

declare(strict_types=1);

namespace Phoundation\Web\Http;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\ConfigurationDoesNotExistsException;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Core\Strings;
use Phoundation\Web\Page;


/**
 * Class Domains
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Domains {
    /**
     * The domain with which this object will work
     *
     * @var string $domain
     */
    protected string $domain;

    /**
     * Configuration cache for all domains
     *
     * @var array $domains_configuration
     */
    protected static array $domains_configuration;

    /**
     * Configuration cache for all whitelisted domains
     *
     * @var array $whitelist_domains
     */
    protected static array $whitelist_domains;

    /**
     * The configured primary domain
     *
     * @var string|null $primary_domain
     */
    protected static ?string $primary_domain = null;


    /**
     * Domains class constructor
     *
     * @param string|null $domain The domain to work with. If no domain was specified, the current domain will be used
     *                            on the HTTP platform. On the CLI platform the primary domain will be used.
     *
     * @note The used domain will ALWAYS be lower case!
     */
    protected function __construct(?string $domain = null)
    {
        if (!$domain) {
            $domain = Page::getDomain();
        } else {
            $domain = UrlBuilder::getDomainFromUrl($domain);
        }

        $this->domain = strtolower($domain);
    }


    /**
     * Returns a Domains object which will work from the specified domain
     *
     * @param string|null $domain
     * @return Domains
     */
    public static function from(?string $domain = null): Domains
    {
        return new Domains($domain);
    }


    /**
     * Returns the primary domain
     *
     * @return string
     */
    public static function getPrimary(): string
    {
        if (!static::$primary_domain) {
            // Build cache
            static::loadConfiguration();
            static::$primary_domain = (string) UrlBuilder::getDomainFromUrl((string) isset_get(static::$domains_configuration['primary']['www']));

            if (!static::$primary_domain) {
                // Whoops! We didn't get our primary domain from configuration, likely configuration isn't available yet
                // Assume the current domain is the primary domain instead
                static::$primary_domain = Domains::getCurrent();

                Log::warning(tr('Failed to get primary domain from configuration, assuming current domain ":domain" is the primary domain', [
                    ':domain' => static::$primary_domain
                ]));
            }
        }

        // Return cache
        return static::$primary_domain;
    }


    /**
     * Returns the current domain
     *
     * @note This is a wrapper for Page::getDomain();
     * @return string
     */
    public static function getCurrent(): string
    {
        if (PLATFORM_HTTP) {
            return $_SERVER['HTTP_HOST'];
        }

        // Ensure $domain doesn't end with . (which IS valid, but would mess up
        $domain = Strings::from(Config::getString('web.domains.primary.www'), '//');
        $domain = Strings::until($domain, '/');
        $domain = Strings::endsNotWith($domain, '.');

        return $domain;
    }


    /**
     * Returns true if the specified domain is the current domain
     *
     * @param string $domain
     * @return bool
     */
    public static function isCurrent(string $domain): bool
    {
        return static::getCurrent() === $domain;
    }


    /**
     * Returns a list of all whitelist domains
     *
     * @return array
     */
    public static function getWhitelist(): array
    {
        if (!isset(static::$whitelist_domains)) {
            // Build cache
            static::loadConfiguration();

            foreach (static::$domains_configuration as $domain => $configuration) {
                if ($domain === 'primary') {
                    continue;
                }

                static::$whitelist_domains[] = $domain;
            }
        }

        // Return cache
        return static::$whitelist_domains;
    }


    /**
     * Returns true if the specified domain is the primary domain
     *
     * @param string $domain
     * @return bool
     */
    public static function isPrimary(string $domain): bool
    {
        return static::getPrimary() === $domain;
    }


    /**
     * Returns true if the specified domain is whitelisted
     *
     * @param string $domain
     * @return bool
     */
    public static function isWhitelist(string $domain): bool
    {
        return in_array($domain, static::getWhitelist());
    }


    /**
     * Returns true if the specified domain is configured either as primary or whitelisted domain
     *
     * @param string $domain
     * @return bool
     */
    public static function isConfigured(string $domain): bool
    {
        if (static::isPrimary($domain)) {
            return true;
        }

        return static::isWhitelist($domain);
    }


    /**
     * Returns the configuration for the specified domain
     *
     * @param string $domain
     * @return array
     */
    public static function getConfiguration(string $domain): array
    {
        if (!static::isPrimary($domain)) {
            if (!static::isWhitelist($domain)) {
                throw ConfigurationDoesNotExistsException::new(tr('No configuration available for domain ":domain"', [
                    ':domain' => $domain
                ]));
            }
        } else {
            $domain = 'primary';
        }

        $domain_config = &static::$domains_configuration[$domain];

        // Validate configuration
        try {
            Arrays::requiredKeys($domain_config, 'www,cdn', ConfigurationDoesNotExistsException::class);
            Arrays::default($domain_config, 'index'  , '/');
            Arrays::default($domain_config, 'cloaked', false);
        } catch (ConfigurationDoesNotExistsException $e) {
            if (!Core::stateIs('setup')) {
                // In setup mode we won't have any configuration but we will be able to continue
                throw $e;
            }
        }

        return $domain_config;
    }


    /**
     * Returns the value for the specified domain key
     *
     * @param string $domain
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public static function getConfigurationKey(string $domain, string $key, mixed $default = null): mixed
    {
        $domain_config = static::getConfiguration($domain);
        return isset_get($domain_config[$key], $default);
    }


    /**
     * Returns the base URL for the specified domain and its type (www or cdn)
     *
     * @param string|null $domain
     * @param string $type
     * @param string|null $language
     * @return string
     * @throws ConfigurationDoesNotExistsException If the specified domain does not exist
     */

    public static function getRootUri(?string $domain = null, #[ExpectedValues('www', 'cdn')] string $type = 'www', ?string $language = null): string
    {
        // Get the root URL for the specified domain and strip the protocol and domain parts
        $uri = static::getRootUrl($domain, $type, $language);
        $uri = Strings::from($uri, '://');
        $uri = Strings::from($uri, '/');

        return $uri;
    }


    /**
     * Returns the base URL for the specified domain and its type (www or cdn)
     *
     * @param string|null $domain
     * @param string $type
     * @param string|null $language
     * @return string
     * @throws ConfigurationDoesNotExistsException If the specified domain does not exist
     */

    public static function getRootUrl(?string $domain = null, #[ExpectedValues('www', 'cdn')] string $type = 'www', ?string $language = null): string
    {
        try {
            if (!$domain) {
                $empty  = true;
                $domain = static::getCurrent();
            }

            $language = $language ?? Session::getLanguage();
            $url      = static::getConfigurationKey($domain, $type);

            return str_replace(':LANGUAGE', $language, $url);

        } catch (ConfigurationDoesNotExistsException) {
            if (isset($empty)) {
                // Okay, this is a bit of a problem. The CURRENT domain apparently is not configured anywhere.
                // This MIGHT be caused by "http://phoundation.org./foobar" instead of "http://phoundation.org/foobar"
                // Log this, and redirect to main-domain/current-url
                Log::warning(tr('The current domain ":domain" is not configured. Redirecting', [
                    ':domain' => $domain
                ]));

                Page::redirect(UrlBuilder::getRootDomainUrl());
            }

            // The specified domain isn't configured
            throw new ConfigurationDoesNotExistsException(tr('Cannot get root URL for domain ":domain", there is no configuration for that domain', [
                ':domain' => $domain
            ]));
        }
    }


    /**
     * Returns the current object domain
     *
     * @return string
     */
    public function getThis(): string
    {
        return $this->domain;
    }


    /**
     * Returns the parent domain for the object domain
     *
     * This method will return the parent domain for the object domain, so for example "cdn1.list.google.com" would
     * return "list.google.com"
     *
     * @return string
     */
    public function getParent(): string
    {
        $return = Strings::from($this->domain, '.');

        if (!filter_var($return, FILTER_VALIDATE_DOMAIN)) {
            // We probably were at the parent domain, return the domain itself.
            return $this->domain;
        }

        return $return;
    }


    /**
     * Returns the root domain for the object domain
     *
     * This method will return the root domain for the object domain, so for example "cdn1.list.google.com" would return
     * "google.com"
     *
     * @return string
     */
    public function getRoot(): string
    {
        return Strings::skip($this->domain, '.', 1);
    }


    /**
     * Ensures that the domains configuration is loaded
     *
     * @return void
     */
    protected static function loadConfiguration(): void
    {
        if (!isset(static::$domains_configuration)) {
            $configuration = Config::get('web.domains');

            if ($configuration === null) {
                if (!Core::stateIs('setup')) {
                    // In set up we won't have configuration and that is fine. If we're not in set up, then it is not
                    // so fine
                    throw new ConfigurationDoesNotExistsException(tr('The configuration path "web.domains" does not exist'));
                }

                // Core has already failed, yet we are here, likely this is the setup page
                Log::warning(tr('The configuration path "web.domains" does not exist'));
                static::$domains_configuration = [];

            } else {
                static::$domains_configuration = &$configuration;
            }

            static::$whitelist_domains = [];
        }
    }
}
