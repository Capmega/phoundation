<?php

namespace Phoundation\Web\Http;

use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Exception\ConfigNotExistsException;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Web\WebPage;



/**
 * Class Domains
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * The configurted primary domain
     *
     * @var string $primary_domain
     */
    protected static string $primary_domain;



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
            $domain = WebPage::getDomain();
        } else {
            $domain = Url::getDomainFromUrl($domain);
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
        if (!isset(self::$primary_domain)) {
            // Build cache
            self::loadConfiguration();
            self::$primary_domain = Url::getDomainFromUrl(isset_get(self::$domains_configuration['primary']['www']));
            show(self::$domains_configuration['primary']);
            show(isset_get(self::$domains_configuration['primary']['www']));
            show(Url::getDomainFromUrl(isset_get(self::$domains_configuration['primary']['www'])));
            showdie(self::$primary_domain);
        }

        // Return cache
        return self::$primary_domain;
    }



    /**
     * Returns true if the specified domain is the primary domain
     *
     * @param string $domain
     * @return string
     */
    public static function isPrimary(string $domain): string
    {
        return self::getPrimary() === $domain;
    }



    /**
     * Returns the current domain
     *
     * @note This is a wrapper for WebPage::getDomain();
     * @return string
     */
    public static function getCurrent(): string
    {
        return WebPage::getDomain();
    }



    /**
     * Returns true if the specified domain is the current domain
     *
     * @param string $domain
     * @return string
     */
    public static function isCurrent(string $domain): string
    {
        return self::getCurrent() === $domain;
    }



    /**
     * Returns a list of all whitelist domains
     *
     * @return array
     */
    public static function getWhitelist(): array
    {
        if (!isset(self::$whitelist_domains)) {
            // Build cache
            self::loadConfiguration();

            foreach (self::$domains_configuration as $domain => $configuration) {
                if ($domain === 'primary') {
                    continue;
                }

                self::$whitelist_domains[] = $domain;
            }
        }

        // Return cache
        return self::$whitelist_domains;
    }



    /**
     * Returns true if the specified domain is the primary domain
     *
     * @param string $domain
     * @return bool
     */
    public static function isWhitelist(string $domain): bool
    {
        return in_array($domain, self::getWhitelist());
    }



    /**
     * Returns the configuration for the specified domain
     *
     * @param string $domain
     * @return array
     */
    public static function getConfiguration(string $domain): array
    {
        if (!self::isPrimary($domain)) {
            if (!self::isWhitelist($domain)) {
                throw ConfigNotExistsException::new(tr('No configuration available for domain ":domain"', [
                    ':domain' => $domain
                ]));
            }
        } else {
            $domain = 'primary';
        }

        $domain_config = &self::$domains_configuration[$domain];

        // Validate configuration
        Arrays::requiredKeys($domain_config, 'domain,www,cdn', ConfigNotExistsException::class);
        Arrays::default($domain_config, 'index'  , '/');
        Arrays::default($domain_config, 'cloaked', false);

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
        $domain_config = self::getConfiguration($domain);
        return isset_get($domain_config[$key], $default);
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
        if (!isset(self::$domains_configuration)) {
            self::$domains_configuration = Config::get('web.domains');
            showdie(self::$domains_configuration);

        }
    }
}