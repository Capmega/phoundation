<?php

/**
 * Class Url
 *
 *
 * @todo      Change this from a static class to just a normal class
 * @todo      Add language mapping, see the protected method language_map() at the bottom of this class for more info
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Http;

use Phoundation\Accounts\Config\Exception\ConfigPathDoesNotExistsException;
use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Rights\RightsBySeoName;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataBoolRenderToNull;
use Phoundation\Data\Traits\TraitDataObjectRights;
use Phoundation\Data\Validator\ArrayValidator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Developer\Project\Project;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhpException;
use Phoundation\Exception\RegexException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Components\Interfaces\AnchorInterface;
use Phoundation\Web\Html\Components\P;
use Phoundation\Web\Html\Enums\EnumAnchorTarget;
use Phoundation\Web\Http\Exception\UrlConfiguredUrlNotFoundException;
use Phoundation\Web\Http\Exception\UrlException;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Phoundation\Web\Requests\Enums\EnumDomainAllowed;
use Phoundation\Web\Requests\Request;
use Stringable;


class Url implements UrlInterface
{
    use TraitDataObjectRights {
        getRightsObject as protected __getRightsObject;
    }
    use TraitDataBoolRenderToNull;


    /**
     * Will be true if the current URL as-is is cloaked
     *
     * @var bool $is_cloaked
     */
    protected bool $is_cloaked = false;

    /**
     * The url to work with
     *
     * @var string|int|null $source
     */
    protected string|int|null $source;

    /**
     * Tracks if this URL is properly encoded, or not
     *
     * @var bool $encoded
     */
    protected bool $encoded = false;

    /**
     * Tracks the components for this URL
     *
     * @var array|null $parsed_url
     */
    protected ?array $parsed_url = null;

    /**
     * Tracks if the URL is absolute or not
     *
     * @var bool|null $is_absolute
     */
    protected ?bool $is_absolute = null;


    /**
     * Url class constructor
     *
     * @param UrlInterface|string|int|null $source The source URL for the new Url object
     */
    protected function __construct(UrlInterface|string|int|null $source = null)
    {
        if ($source instanceof UrlInterface) {
            // Copy URL data from the specified source into this URL object
            $this->setSource($source->getSource())
                 ->setRightsObject($source->getRightsObject());

        } else {
            // If the specified was a non UrlInterface stringable object, convert to string and use
            $this->setSource($source);
        }
    }


    /**
     * When used as string, will always return the internal URL as available
     *
     * @return string
     */
    public function __toString(): string
    {
        // Empty URL's are considered absolute
        if (!$this->isAbsolute()) {
            $this->makeWww();
        }

        return (string) $this->getSource();
    }


    /**
     * Returns a new Url object
     *
     * @param UrlInterface|string|int|null $source The source URL for the new Url object
     *
     * @return static
     */
    public static function new(UrlInterface|string|int|null $source = null): static
    {
        return new static($source);
    }


    /**
     * Returns a new Url object unless the specified source URL was NULL, then NULL will be returned
     *
     * @param UrlInterface|string|int|null $source The source URL for the new Url object
     *
     * @return static|null
     */
    public static function newOrNull(UrlInterface|string|int|null $source = null): ?static
    {
        if ($source === null) {
            return null;
        }

        return static::new($source);
    }


    /**
     * Returns a new Url object
     *
     * @param UrlInterface|string|int|null $source The source URL for the new Url object
     *
     * @return static
     */
    public static function newFromPath(?PhoPathInterface $source = null): static
    {
        $source = Strings::from($source, DIRECTORY_PROJECT_CDN);
        $source = Strings::from($source, DIRECTORY_CDN);
        $source = Strings::from($source, DIRECTORY_ROOT);

        return new static($source);
    }


    /**
     * Returns the current URL
     *
     * @param string|int|null $id
     * @param bool            $strip_queries
     *
     * @return static
     */
    public static function newCurrent(string|int|null $id = null, bool $strip_queries = false): static
    {
        $url = static::newCurrentDomainUrl();

        if ($id) {
            // Inject the ID in the URL
            $url = (string) $url;
            $ext = Strings::fromReverse($url, '.');
            $url = Strings::untilReverse($url, '.');
            $url = $url . '+' . $id . $ext;
            $url = new static($url);
        }

        if ($strip_queries) {
            return static::new(Strings::until((string) $url, '?'))->makeWww();
        }

        return $url;
    }


    /**
     * Performs a search / replace on this URL's source
     *
     * @param array $replace Contains all the search keys to replace with the values, key => value is search => replace.
     * @param bool  $regex   If true, the keys should be regular expressions to perform more complex replacements
     *
     * @return static
     */
    public function replace(array $replace, bool $regex = false): static
    {
        if ($regex) {
            foreach ($replace as $key => $value) {
                $this->source = preg_replace($key, $value, $this->source);
            }

        } else {
            foreach ($replace as $key => $value) {
                $this->source = str_replace($key, $value, $this->source);
            }
        }

        return $this;
    }


    /**
     * Returns a base URL
     *
     * @param bool $use_configured_root
     *
     * @return string
     */
    public static function getBase(bool $use_configured_root = true): string
    {
        // Get the base URL configuration for the domain
        if ($use_configured_root) {
            $base = Domains::getRootUrl();

        } elseif (PLATFORM_WEB) {
            try {
                $base = Request::getRoutingParametersObject()->getRootUrl();

            } catch (OutOfBoundsException $e) {
                // Routing parameters are not yet available, assume the project root URL
                $base = Domains::getRootUrl();
            }

        } else {
            $base = Domains::getRootUrl();
        }

        // Build the URL
        return Strings::ensureEndsWith($base, '/');
    }


    /**
     * Returns the URL as requested for the primary domain
     *
     * @return static
     */
    public static function newCurrentDomainUrl(): static
    {
        return new static(Request::getUrl());
    }


    /**
     * Returns the URL where to redirect to
     *
     * @param Stringable|string|int|null ...$urls
     *
     * @return static
     */
    public static function newRedirect(Stringable|string|int|null ...$urls): static
    {
        foreach ($urls as $url) {
            if (!$url) {
                continue;
            }

            $url = Url::new($url)->makeWww();

            if ($url->getSource(true) === static::newCurrent()->getSource(true)) {
                continue;
            }

            if ((string) $url === (string) static::new('index')->makeWww()) {
                continue;
            }

            return $url;
        }

        return static::new('index')->makeWww();
    }


    /**
     * Returns the root URL for the primary domain
     *
     * @return static
     */
    public static function newCurrentDomainRootUrl(): static
    {
        return new static(Request::getRootUrl());
    }


    /**
     * Returns a complete web URL for the previous page, or the specified URL
     *
     * This will return either the $_GET[previous], $_GET[redirect], or $_SERVER[referer] URL. If none of these exist,
     * or if they are the current page, then the specified URL will be sent instead.
     *
     * @param Stringable|string|int|null $or_else_url         The URL to build if no valid previous page is available
     * @param bool                       $use_configured_root If true, the builder will not use the root URI from the
     *                                                        routing parameters but from the static configuration
     * @param string|null                $strip_query_keys    If specified, will strip the specified keys from the URL
     *                                                        queries
     *
     * @return static
     */
    public static function newPrevious(Stringable|string|int|null $or_else_url = null, bool $use_configured_root = false, ?string $strip_query_keys = 'previous,redirect'): static
    {
        $url = GetValidator::getRedirectValue();

        if (!empty($url)) {
            // We got a previous, redirect, or referer value
            $url     = static::new($url)->makeWww($use_configured_root);
            $current = static::new(null)->makeWww($use_configured_root);

            if ((string) $current !== (string) $url) {
                // Option is not current page, return it with previous and redirect keys stripped
                return $url->removeQueryKeys($strip_query_keys);
            }
        }

        // No URL found in any of the options, or option was current page. Use the specified URL
        return static::new($or_else_url)->makeWww($use_configured_root)->removeQueryKeys($strip_query_keys);
    }


    /**
     * Apply predefined URL names
     *
     * @param Stringable|string|int|null $url
     *
     * @return static
     */
    public static function newConfigured(Stringable|string|int|null $url): static
    {
        $url        = (string) $url;
        $configured = match (Strings::until($url, '.html')) {
            'index'        , 'dashboard' => config()->getString('web.pages.index'        , '/index'),
            'sign-in'      , 'signin'    => config()->getString('web.pages.sign-in'      , '/sign-in'),
            'sign-up'      , 'signup'    => config()->getString('web.pages.sign-up'      , '/sign-up'),
            'sign-out'     , 'signout'   => config()->getString('web.pages.sign-out'     , '/sign-out'),
            'sign-key'     , 'signkey'   => config()->getString('web.pages.sign-key'     , '/sign-key+:key'),
            'profile'                    => config()->getString('web.pages.profile'      , '/my/profile'),
            'settings'                   => config()->getString('web.pages.settings'     , '/my/settings'),
            'mfa-create'                 => config()->getString('web.pages.mfa.create'   , '/mfa/create'),
            'mfa-verify'                 => config()->getString('web.pages.mfa.verify'   , '/mfa/verify'),
            'lost-password'              => config()->getString('web.pages.password.lost', '/lost-password'),
            default                      => config()->getString('web.pages.' . $url      , '')
        };

        if ($configured) {
            return new static($configured);
        }

        return new static($url);
    }


    /**
     * Returns the root URL for the parent domain
     *
     * @return static
     */
    public static function newParentDomainRootUrl(): static
    {
        return new static(Domains::from()->getParent() . Request::getRootUri());
    }


    /**
     * Returns the URL as requested for the parent domain
     *
     * @return static
     */
    public static function newParentDomainUrl(): static
    {
        return new static(Domains::from()->getParent() . Request::getUri());
    }


    /**
     * Returns the root URL for the root domain
     *
     * @return static
     */
    public static function newRootDomainRootUrl(): static
    {
        return new static(Domains::from()->getRoot() . Request::getRootUri());
    }


    /**
     * Returns the URL as requested for the root domain
     *
     * @return static
     */
    public static function newRootDomainUrl(): static
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
    public static function newDomainCurrentUrl(string $domain): static
    {
        return new static($domain . Request::getUri());
    }


    /**
     * Returns the root URL for the primary domain
     *
     * @return static
     */
    public static function newPrimaryCdnDomainRootUrl(?string $language = null): string
    {
        if (empty($language)) {
            $language = Session::getLanguage();
        }

        $return = config()->getString('web.domains.primary.cdn');
        $return = str_replace(':LANGUAGE', $language, $return);

        return $return;
    }


    /**
     * Returns the root URL for the primary domain
     *
     * @return static
     */
    public static function newPrimaryCdnDomainRootUrlObject(): static
    {
        return Url::newPrimaryCdnDomainRootUrl();
    }


    /**
     * Returns the root URL for the primary domain
     *
     * @return static
     */
    public static function newPrimaryDomainRootUrl(): static
    {
        return Url::new(config()->getString('web.domains.primary.web'))->makeWww();
    }


    /**
     * Returns the URL as requested for the primary domain
     *
     * @return static
     */
    public static function newPrimaryDomainUrl(): static
    {
        return new static(Domains::getPrimaryWeb() . Request::getUri());
    }


    /**
     * Returns the "redirect" or referer (previous) URL
     *
     * @param Stringable|string|int|null $url
     *
     * @return static
     */
    public static function newReferer(Stringable|string|int|null $url = null): static
    {
        $url = trim((string) $url);

        // Try to get a "redirect" via GET
        try {
            $get = GetValidator::new()
                               ->select('redirect')
                               ->isOptional()
                               ->isUrl()
                               ->validate(false);

        } catch (ValidationFailedException) {
            Log::warning(ts('Validation for redirect url ":url" failed, ignoring', [
                ':url' => GetValidator::new()->get('redirect'),
            ]));
        }

        if (array_get_safe($get, 'redirect')) {
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
                Log::warning(ts('Validation for HTTP_REFERRER ":url" failed, ignoring', [
                    ':url' => $_SERVER['HTTP_REFERER'],
                ]));
            }

            if (isset_get($server['HTTP_REFERER'])) {
                // Use the referer
                $url = $server['HTTP_REFERER'];

            } elseif (empty($url)) {
                // No url specified either, just go to the root page
                $url = static::newCurrentDomainRootUrl();
            }
        }

        return new static($url);
    }


    /**
     * Returns true if the current URL for this object is absolute (has a scheme and host), false otherwise
     *
     * @return bool
     */
    public function isAbsolute(): bool
    {
        if ($this->is_absolute === null) {
            if (!$this->canMakeAbsolute()) {
                // Links that cannot be made absolute (#, mailto:, etc) are considered already absolute
                $this->is_absolute = true;

            } else {
                $parsed            = $this->getParsed();
                $this->is_absolute = !(empty($parsed['scheme']) and empty($parsed['host']));
            }
        }

        return $this->is_absolute;
    }


    /**
     * Ensures that the URL for this object is absolute
     *
     * @return static
     */
    public function ensureAbsolute(): static
    {
        if (!$this->isAbsolute()) {
            if ($this->canMakeAbsolute()) {
                $this->makeWww();
            }
        }

        return $this;
    }


    /**
     * Returns true if this URL can be transformed into www, cdn, image url, etc.
     *
     * @return bool
     */
    public function canMakeAbsolute(): bool
    {
        if (empty($this->source)) {
            // This is a NULL URL
            return true;
        }

        if (str_starts_with($this->source, '#')) {
            // This is a valid but "do nothing" link, do not do anything
            return false;
        }

        if (str_starts_with($this->source, 'mailto:')) {
            // This is a valid "mailto" link, do not do anything
            return false;
        }

        if (str_starts_with($this->source, 'tel:')) {
            // This is a valid "tel:" link, do not do anything
            return false;
        }

        if (str_starts_with($this->source, 'javascript:')) {
            // This is a valid "javascript:" link, do not do anything
            return false;
        }

        return true;
    }


    /**
     * Returns a complete web URL
     *
     * @param bool $use_configured_root If true, the builder will not use the root URI from the routing parameters but
     *                                  from the static configuration
     *
     * @return static
     */
    public function makeWww(bool $use_configured_root = false): static
    {
        if (!$this->canMakeAbsolute()) {
            // This URL cannot be made into something else
            return $this;
        }

        // Use configured page extension
        $extension = config()->getString('web.pages.extension', 'html');

        if (is_numeric($this->source)) {
            $this->source = 'system/' . $this->source . $extension;

        } elseif ($this->source === '/') {
            return $this->renderUrl('', null, $use_configured_root);
        }

        return $this->renderUrl($extension, null, $use_configured_root);
    }


    /**
     * Returns a CDN URL
     *
     * @param string|null $extension
     *
     * @return static
     */
    public function makeCdn(?string $extension = null): static
    {
        if (!$this->canMakeAbsolute()) {
            // This URL cannot be made into something else
            return $this;
        }

        return $this->renderCdn($extension);
    }


    /**
     * Returns an ajax URL
     *
     * @param bool $use_configured_root If true, the builder will not use the root URI from the routing parameters but
     *                                  from the static configuration
     *
     * @return static
     */
    public function makeAjax(bool $use_configured_root = false): static
    {
        return $this->makeJson('ajax', $use_configured_root);
    }


    /**
     * Returns an api URL
     *
     * @param bool $use_configured_root If true, the builder will not use the root URI from the routing parameters but
     *                                  from the static configuration
     *
     * @return static
     */
    public function makeApi(bool $use_configured_root = false): static
    {
        return $this->makeJson('api', $use_configured_root);
    }


    /**
     * Returns a JSON type URL
     *
     * @param string $type
     * @param bool $use_configured_root If true, the builder will not use the root URI from the routing parameters but
     *                                  from the static configuration
     *
     * @return static
     */
    protected function makeJson(string $type, bool $use_configured_root = false): static
    {
        if (!$this->canMakeAbsolute()) {
            // This URL cannot be made into something else
            return $this;
        }

        if (empty($this->source)) {
            throw new OutOfBoundsException(tr('No URL specified'));
        }

        if (is_numeric($this->source)) {
            $this->source = 'system/' . $this->source . 'json';

        } elseif (!str_contains($this->source, '.json?')) {
            $this->source = Strings::ensureEndsWith($this->source, '.json');
        }

        return $this->renderUrl('json', $type . '/', $use_configured_root);
    }


    /**
     * Returns a CSS URL
     *
     * @return static
     */
    public function makeCss(): static
    {
        return $this->renderCdn('css');
    }


    /**
     * Returns a Javascript URL
     *
     * @return static
     */
    public function makeJs(): static
    {
        return $this->renderCdn('js');
    }


    /**
     * Returns an image URL
     *
     * @return static
     */
    public function makeImg(): static
    {
        if ($this->isValid()) {
            return $this;
        }

        if (!$this->canMakeAbsolute()) {
            // This URL cannot be made into something else
            return $this;
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

        $this->source = Strings::ensureBeginsNotWith($this->source, 'data/content/cdn/');
        $this->source = Strings::ensureBeginsWith($this->source, 'img/');

        return $this->renderCdn();
    }


    /**
     * Returns the URL if it did not match any of the filter URL's
     *
     * @note Specified filters may be URL strings or UrlInterface objects. The filter will be converted to a URL object,
     *       so it also may be a pre-defined URL like "sign-in" or "index"
     *
     * @param Stringable|string|int|null $url             The URL to test
     * @param IteratorInterface|array    $filters         The URL's to test against
     * @param bool                       $include_queries If true, will compare the complete URL with queries. If false,
     *                                                    will only test the URI part, the queries stripped
     *
     * @return UrlInterface|null
     */
    public static function filter(Stringable|string|int|null $url, IteratorInterface|array $filters, bool $include_queries = false): ?UrlInterface
    {
        // Prepare test value
        $url = Url::new($url)->makeWww();

        if (!$include_queries) {
            $test = $url->removeAllQueries()->getSource();

        } else {
            $test = $url->getSource();
        }

        // Go over all filters
        foreach ($filters as $filter) {
            // Prepare filter value
            $filter = Url::new($filter)->makeWww();

            if (!$include_queries) {
                $filter = $filter->removeAllQueries();
            }

            // If test matches filter, return NULL
            if ($test === $filter->getSource()) {
                return null;
            }
        }

        return $url;
    }


    /**
     * Returns true if this Url object contains no URL
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->source);
    }


    /**
     * Returns the source URL of this URL object
     *
     * @param bool $strip_queries
     *
     * @return string|int|null
     */
    public function getSource(bool $strip_queries = false): string|int|null
    {
        if ($this->render_to_null) {
            // This component renders to NULL
            return null;
        }

        // Auto cloak URL's?
        try {
            if ($this->source) {
                if (Domains::getConfigurationKey($this->getHost(), 'cloaked', false)) {
                    $this->cloak();
                }
            }

        } catch (ConfigPathDoesNotExistsException) {
            // This domain is not configured, ignore it
        }

        if ($strip_queries) {
            return Strings::until($this->source, '?');
        }

        return $this->source;
    }


    /**
     * Returns the source URL of this URL object
     *
     * @return string|int|null
     */
    public function getSourceUnprocessed(): string|int|null
    {
        return $this->source;
    }


    /**
     * Sets the source URL of this URL object
     *
     * @param UrlInterface|string|int|null $source
     *
     * @return static
     */
    public function setSource(UrlInterface|string|int|null $source): static
    {
        $source = get_null(trim((string) $source));

        $this->parsed_url  = null;
        $this->is_absolute = null;

        if ($source === null) {
            $this->source = null;

        } else {
            // This is either part of a URL or a complete URL
            if (!Url::isValidUrl($source)) {
                // This is (as we will assume) a section of a URL
                if ($source) {
                    $source = Strings::ensureBeginsNotWith($source, '/');

                    if (empty($source)) {
                        $source = '/';
                    }
                }
            }

            $this->source = $source;
        }

        return $this;
    }


    /**
     * Returns the components for the current URL.
     *
     * Possible components are:
     *
     * scheme   (http, https, etc)
     * host     (Domain / host name / IP address)
     * port     port (very optional)
     * user     user (very optional)
     * pass     password (very optional)
     * path     The URL path
     * query    (After the ?)
     * fragment (After the #)
     *
     * @note Depending on the current URL, not all components may be available
     *
     * @note will return NULL if the current source is empty
     *
     * @return array|null
     */
    public function getParsed(): ?array
    {
        if (empty($this->parsed_url)) {
            if (empty($this->source)) {
                return [];
            }

            $parsed_url = parse_url($this->source);

            if ($parsed_url === false) {
                // parse_url() failed to parse the source URL of this object
                Log::warning(ts('Failed to parse url ":url"', [
                    ':url' => $this->source,
                ]));
            }

            $this->parsed_url = $parsed_url;
        }

        return $this->parsed_url;
    }


    /**
     * Returns the components for the current URL.
     *
     * Possible components are:
     *
     * scheme   (http, https, etc)
     * host     (Domain / host name / IP address)
     * port     port (very optional)
     * user     user (very optional)
     * pass     password (very optional)
     * path     The URL path
     * query    (After the ?)
     * fragment (After the #)
     *
     * @note Depending on the current URL, not all components may be available
     *
     * @note will return NULL if the current source is empty
     *
     * @param string $section
     *
     * @return string|int|null
     */
    public function getParsedSection(string $section): string|int|null
    {
        if (!in_array($section, ['scheme', 'host', 'port', 'user', 'pass', 'path', 'query', 'fragment'])) {
            throw new OutOfBoundsException(tr('Cannot return parsed section ":section", only sections "scheme", "host", "port", "user", "pass", "path", "query", "fragment" cam be returned', [
                ':section' => $section,
            ]));
        }

        return array_get_safe($this->getParsed(), $section);
    }


    /**
     * Returns the scheme part of the current URL
     *
     * @note Will return NULL if the scheme is not specified, empty, or invalid
     *
     * @return string|null
     */
    public function getScheme(): ?string
    {
        return array_get_safe($this->getParsed(), 'scheme');
    }


    /**
     * Returns the user part of the current URL
     *
     * @note Will return NULL if the user is not specified, empty, or invalid
     *
     * @return string|null
     */
    public function getUser(): ?string
    {
        return array_get_safe($this->getParsed(), 'user');
    }


    /**
     * Returns the password part of the current URL
     *
     * @note Will return NULL if the password is not specified, empty, or invalid
     *
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return array_get_safe($this->getParsed(), 'pass');
    }


    /**
     * Returns the host for the current URL
     *
     * @note Will return NULL if the host is not specified, empty, or invalid
     *
     * @param bool $default_domain_is_current
     *
     * @return string|null
     */
    public function getHost(bool $default_domain_is_current = true): ?string
    {
        if ($this->source) {
            $host = $this->getParsedSection('host');

            if ($host === null) {
                if ($default_domain_is_current) {
                    // Since there is no domain, assume we need the current domain
                    return Domains::getCurrent();
                }
            }

            return get_null((string) $host);
        }

        return null;
    }


    /**
     * Returns the port part of the current URL
     *
     * @note Will return NULL if the port is not specified, empty, or invalid
     *
     * @return int|null
     */
    public function getPort(): ?int
    {
        return get_null((int) array_get_safe($this->getParsed(), 'port'));
    }


    /**
     * Returns the path part of the current URL
     *
     * @note Will return NULL if the host is not specified, empty, or invalid
     *
     * @param bool $skip_language
     *
     * @return string|null
     */
    public function getPath(bool $skip_language = false): ?string
    {
        $return = array_get_safe($this->getParsed(), 'path');

        if ($skip_language) {
            $return = Strings::ensureBeginsNotWith($return, '/');
            $return = '/' . Strings::from($return, '/');
        }

        return $return;
    }


    /**
     * Returns the file part of the current URL
     *
     * @note Will return NULL if the host is not specified, empty, or invalid
     *
     * @return string|null
     */
    public function getFile(): ?string
    {
        $return = $this->getPath();
        $return = Strings::fromReverse($return, '/');

        return $return;
    }


    /**
     * Returns the path part of the current URL
     *
     * @note Will return NULL if the query is not specified, empty, or invalid
     *
     * @return string|null
     */
    public function getQuery(): ?string
    {
        return array_get_safe($this->getParsed(), 'query');
    }


    /**
     * Returns the fragment part of the current URL
     *
     * @note Will return NULL if the fragment is not specified, empty, or invalid
     *
     * @return string|null
     */
    public function getFragment(): ?string
    {
        return array_get_safe($this->getParsed(), 'fragment');
    }


    /**
     * Returns the URL starting from the path
     *
     * @return string|null
     */
    public function getFromHost(): ?string
    {
        $parsed = $this->getParsed();
        return array_get_safe($parsed, 'path') . array_get_safe($parsed, 'query') . array_get_safe($parsed, 'fragment');
    }


    /**
     * Returns the URL starting from the path, and skipping the language selector (Typical for Phoundation sites)
     *
     * @return string|null
     */
    public function getFromHostAndLanguage(): ?string
    {
        $path = $this->getFromHost();

        // Strip the language, if specified
        if (preg_match('/^\/\w{2}\//', $path)) {
            $path = '/' . Strings::from(substr($path, 1), '/');
        }

        return $path;
    }


    /**
     * Returns true if the URL for this object points to a page within THIS project
     *
     * A page is considered part of this project if the host name matches
     *
     * @return bool
     */
    public function isProjectUrl(): bool
    {
        return Domains::getCurrent() === $this->getHost();
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
            ':url'        => $this->source,
            ':created_by' => isset_get($_SESSION['user']['id']),
        ]);

        if ($cloak) {
            // Found cloaking URL, update the created_on time so that it will not expire too soon
            sql()->query('UPDATE `url_cloaks` 
                          SET    `created_on` = NOW() 
                          WHERE  `url`        = :url', [
                ':url' => $this->source,
            ]);

        } else {
            $cloak = Strings::getRandom(32);

            sql()->insert('url_cloaks', [
                'created_by' => Session::getUserObject()->getId(),
                'cloak'      => $cloak,
                'url'        => $this->source,
            ]);
        }

        $this->source = $cloak;
        return $this;
    }


    /**
     * Builds and returns the domain prefix
     *
     * @param string|null $extension
     * @param string|null $prefix
     * @param bool        $use_configured_root       If true, the builder will not use the root URI from the routing
     *                                               parameters but from the static configuration
     *
     * @return static
     */
    protected function renderUrl(?string $extension, ?string $prefix, bool $use_configured_root): static
    {
        // Reset the parsed_url cache
        $this->parsed_url  = null;
        $this->is_absolute = null;

        if (empty($this->source)) {
            // There is no URL to work with, we are done
            $this->source = Request::getUrl();
            $extension    = '';
        }

        $url = $this->source;

        if (static::isValidUrl($url)) {
            // TODO Add check that the URL is local! If its not local, how can we verify its a CDN URL?
            return $this;
        }

        $url = static::applyPredefined($url);
        $url = static::applyVariables($url);
        $url = new static($url);

        if ($url->isValid()) {
            return $url;
        }

        // Build the URL
        $base  = Url::getBase($use_configured_root);
        $url   = $url->getSourceUnprocessed();
        $url   = Strings::ensureBeginsNotWith($url, '/');
        $url   = $prefix . $url;
        $url   = str_replace(':LANGUAGE', Session::getLanguage(), $base . $url);
        $query = Strings::from($url , '?', needle_required: true);
        $url   = Strings::until($url, '?');

        if ($extension) {
            $extension = Strings::ensureBeginsWith($extension, '.');
        }

        if (!preg_match('/\.[a-z0-9]{3,5}$/i', $url)) {
            $url .= $extension;
        }

        if ($query) {
            $this->source = $url . '?' . $query;

        } else {
            $this->source = $url;
        }

        return $this;
    }


    /**
     * Returns a CDN URL
     *
     * @todo Clean URL strings, escape HTML characters, " etc.
     *
     * @param string|null $extension
     *
     * @return static
     */
    protected function renderCdn(?string $extension = null): static
    {
        $url = $this->source;

        if (empty($url)) {
            throw new OutOfBoundsException(tr('No URL specified'));
        }

        if (!$this->canMakeAbsolute()) {
            // This URL cannot be made into something else
            return $this;
        }

        if (static::isValidUrl($url)) {
            // TODO Add check that the URL is local! If its not local, how can we verify its a CDN URL?
            return $this;
        }

        // Apply predefined / configured URL words
        // Apply special variables
        // Form the CDN URL
        if (!$extension) {
            $url  = Strings::ensureBeginsWith($url, Project::getSeoFullName() . '/');

        } else {
            $url  = Strings::ensureBeginsWith($url, 'templates' . '/');
        }

        $url  = static::applyPredefined($url);
        $url  = static::applyVariables($url);
        $base = Url::newPrimaryCdnDomainRootUrl();
        $base = Strings::ensureEndsWith($base, '/');
        $base = str_replace(':LANGUAGE', Session::getLanguage(), $base);
        $url  = Strings::ensureBeginsNotWith($url, '/');
        $url  = static::addExtension($url, $extension);

        $this->source = $base . $url;

        return $this;
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
            'self', 'this', 'here'        => static::newCurrent(),
            'root'                        => static::newCurrentDomainRootUrl(),
            'prev', 'previous', 'referer' => static::newPrevious(),
            default                       => null,
        };

        if ($return) {
            return (string) $return;
        }

        try {
            return static::newConfigured($url)->getSourceUnprocessed();

        } catch (UrlConfiguredUrlNotFoundException) {
            // This was not a configured URL
            return $url;
        }
    }


    /**
     * Apply variables in the URL
     *
     * @param string $url
     *
     * @return Url
     */
    protected static function applyVariables(string $url): string
    {
        $url = str_replace(':PROTOCOL', Request::getProtocol()     , $url);
        $url = str_replace(':DOMAIN'  , Domains::getCurrent()      , $url);
        $url = str_replace(':PORT'    , (string) Request::getPort(), $url);

        return str_replace(':LANGUAGE', Session::getLanguage()     , $url);
    }


    /**
     * Returns true if the specified URL is the same as the current URL
     *
     * @param bool $strip_queries
     *
     * @return bool
     */
    public function isCurrent(bool $strip_queries = false): bool
    {
        return $this->source === (string) static::newCurrent(strip_queries: $strip_queries);
    }


    /**
     * Returns the extension for the URL
     *
     * @param string      $url
     * @param string|null $extension
     *
     * @return string|null
     */
    protected static function addExtension(string $url, ?string $extension): ?string
    {
        if (empty($extension)) {
            return $url;
        }

        if (config()->get('web.cdn.resources.minified', true)) {
            $extension = '.min.' . $extension;

        } else {
            $extension = '.' . $extension;
        }

        if (str_ends_with($url, $extension)) {
            return $url;
        }

        return $url . $extension;
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
        Log::notice(ts('Cleaning up `url_cloaks` table'));

        $r = sql()->query('DELETE FROM `url_cloaks` 
                           WHERE       `created_on` < DATE_SUB(NOW(), INTERVAL ' . config()->get('web.url.cloaking.expires', 86400) . ' SECOND);');

        Log::success(ts('Removed ":count" expired entries from the `url_cloaks` table', [
            ':count' => $r->rowCount(),
        ]));

        return $r->rowCount();
    }


    /**
     * Returns an array containing all the queries found in the specified URL
     *
     * @param IteratorInterface|array|string|null $remove_keys
     * @param bool                                $unescape
     *
     * @return array
     */
    public function getQueries(IteratorInterface|array|string|null $remove_keys = null, bool $unescape = true): array
    {
        $return      = [];
        $queries     = Strings::from($this->source, '?', needle_required: true);
        $queries     = Arrays::force($queries, '&');
        $remove_keys = Arrays::force($remove_keys);

        foreach ($queries as $query) {
            $query = Strings::ensureBeginsNotWith($query, 'amp;');
            // TODO why does this show as "amp;" after Arrays::force()
            if (empty($query)) {
                continue;
            }

            if (str_contains($query, '=')) {
                [$key, $value] = explode('=', $query);

                if ($remove_keys and array_key_exists($key, $remove_keys)) {
                    continue;
                }

                if ($unescape) {
                    $return[$key] = urldecode($value);

                } else {
                    $return[$key] = $value;
                }

            } else {
                $return[$key] = null;
            }
        }

        return $return;
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
                                       WHERE  `cloak` = :cloak', [':cloak' => $this->source]);

        if (!$url) {
            throw new NotExistsException(tr('The specified cloaked URL ":url" does not exist', [
                ':url' => $this->source,
            ]));
        }

        sql()->delete('url_cloaks', [':cloak' => $this->source]);

        return $this;
    }


    /**
     * Clear the query part from the URL
     *
     * @return static
     */
    public function clearQueries(): static
    {
        $this->source = Strings::until($this->source, '?');

        return $this;
    }


    /**
     * Removes all queries from this URL
     *
     * @return static
     */
    public function removeAllQueries(): static
    {
        $this->source = Strings::until($this->source, '?');
        return $this;
    }


    /**
     * Remove specified queries from the specified URL and return
     *
     * @param IteratorInterface|array|string|null $keys
     *
     * @return static
     */
    public function removeQueryKeys(IteratorInterface|array|string|null $keys): static
    {
        if (!$keys) {
            return $this;
        }

        $keys    = Arrays::force($keys);
        $queries = Strings::from($this->source, '?', needle_required: true);
        $queries = Arrays::force($queries, '&');

        if (!$queries) {
            return $this;
        }

        foreach ($keys as $key) {
            foreach ($queries as $id => $query) {
                if (str_starts_with($query, $key . '=')) {
                    unset($queries[$id]);
                }
            }
        }

        $this->source = Strings::until($this->source, '?') . '?' . implode('&', $queries);

        return $this;
    }


    /**
     * Adds a ?redirect=URL query to this URL
     *
     * @note ?redirect= queries only permit redirects to non root pages on project domains
     *
     * @param Url|string|null $redirect                          [null]     The URL that should be added as "?redirect=URL" in this URL. If NULL, will not add
     *                                                                      anything. If empty string, will default to the current URL
     * @param IteratorInterface|array|string|null $strip_queries [redirect] If specified, will strip the specified query keys from the redirect URL before
     *                                                                      adding it to this URL
     *
     * @return static
     */
    public function addRedirect(Url|string|null $redirect = null, IteratorInterface|array|string|null $strip_queries = 'redirect'): static
    {
        // Use what URL?
        if (empty($redirect)) {
            $redirect = Url::newCurrent();

        } else {
            $redirect = Url::new($redirect)->makeWww();
        }

        if (!$redirect->isProjectUrl()) {
            Incident::new()
                    ->setLog(10)
                    ->setNotifyRoles('developer')
                    ->setType('security')
                    ->setSeverity(EnumSeverity::high)
                    ->setTitle(ts('Encountered redirect to non project page, ignoring'))
                    ->setBody(ts('The requested redirect URL ":url" points to a non project page, it will be removed', [
                        ':url' => $redirect->getSource()
                    ]))
                    ->save();

            $redirect = null;

        } elseif ($redirect->isRootLevelPage()) {
            Incident::new()
                    ->setLog(10)
                    ->setNotifyRoles('developer')
                    ->setType('security')
                    ->setSeverity(EnumSeverity::high)
                    ->setTitle(ts('Encountered redirect to root level page, ignoring'))
                    ->setBody(ts('The requested redirect URL ":url" points to a root level page, it will be removed', [
                        ':url' => $redirect->getSource()
                    ]))
                    ->save();

            $redirect = null;

        } elseif ($strip_queries) {
            // Strip redirect
            $redirect->removeQueryKeys($strip_queries);
        }

        // Done
        return $this->addUrlQuery($redirect, 'redirect');
    }


    /**
     * Returns true if the page for this URL is on this site and is a root level page
     *
     * @return bool
     * @todo Add support for sites that do not start at root!
     */
    public function isRootLevelPage(): bool
    {
        return $this->isProjectUrl() and preg_match('/^https?:\/\/[^\/]+\/\w{2}\/[^\/]+\.html(\?.*)?$/', $this->getSource());
    }


    /**
     * Add the specified key=URL to this URL safely
     *
     * @param UrlInterface|string|null $value
     * @param string|int               $key
     *
     * @return static
     */
    public function addUrlQuery(UrlInterface|string|null $value, string|int $key): static
    {
        if ($value === null) {
            return $this;
        }

        $key   = Url::ensureStringUrlEncoding((string) $key);
        $value = Url::ensureStringUrlEncoding((string) $value, true);

        return $this->addQueries($key . '=' . $value);
    }


    /**
     * Adds the queries from this page to this URL
     *
     * @return static
     */
    public function addThisPageQueries(): static
    {
        return $this->addQueries(explode('&', array_get_safe($_SERVER, 'QUERY_STRING')));
    }


    /**
     * Adds the queries from the current page to this URL object
     *
     * @return static
     */
    public function addCurrentQueries(): static
    {
        return $this->addQueries(Url::newCurrent()->getQueries());
    }


    /**
     * Add the specified query / queries to the specified URL and return
     *
     * @note Do NOT add queries like key=URL (where URL is not URL encoded) in here, as URL may contain "=" and "&"
     *       symbols which will be detected and cause exceptions as the system will not know where one query starts and the
     *       other ends. Use Url::addUrlQuery() instead
     *
     * @note Do NOT add queries where either the key or value contains one of not URL encoded "? = + &"
     *       (not counting the = in key=value) as these characters will cause problems
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

        $this->ensureQueriesEncoded();

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

            // Clean the URL string
            $this->source = Strings::ensureEndsNotWith($this->source, '?');
            $this->source = Strings::ensureEndsNotWith($this->source, '&');

            if (!preg_match('/^[a-z0-9-_]+?=.*?$/i', $query)) {
                throw new OutOfBoundsException(tr('Invalid query ":query" specified. Please ensure it has the "key=value" format and that key matches regex "[a-z0-9-_]+"', [
                    ':query' => $query,
                ]));
            }

            $this->addQuery(Strings::from($query , '='), Strings::until($query, '='));
        }

        return $this;
    }


    /**
     * This method will replace the current file value after the + with the specified value
     *
     * @param Stringable|string|int|null $value                        The new value for the + value
     * @param bool                       $auto_fix_missing_plus [true] If true, and the file does not contain a required plus symbol, the method will add the
     *                                                                 "+ID" part right before the .extension of the filename
     *
     * @return static
     */
    public function setPlusValue(Stringable|string|int|null $value, bool $auto_fix_missing_plus = true): static
    {
        $file = Strings::fromReverse($this->source, '/');

        if ($value === null) {
            // Remove the + value
            if (str_contains($file, '+')) {
                $file         = preg_replace('/\+\d+\./', '.', $file);
                $this->source = Strings::untilReverse($this->source, '/') . '/' . $file;
            }

            return $this;
        }

        if (!str_contains($file, '+')) {
            // The URL contains no plus value, add it?
            if (!$auto_fix_missing_plus) {
                throw UrlException::new(ts('Cannot replace plus value for URL ":url", it contains no plus value', [
                    ':url' => $this->source
                ]))->addHint(ts('This method can only be used on URLs like for example "https://domain.com/path/path/file+23874.html", the filename of the URL MUST have the format filename+ID.extension'));
            }

            // Add a fake + value that can be replaced
            $extension = Strings::fromReverse($file, '.');
            $file      = Strings::untilReverse($file, '.') . '+0000.' . $extension;
        }

        $file         = preg_replace('/\+\d+\./', '+' . $value . '.', $file);
        $this->source = Strings::untilReverse($this->source, '/') . '/' . $file;
        return $this;
    }


    /**
     * This method will replace the current file value after the + with the specified value
     *
     * @return static
     */
    public function clearPlusValue(): static
    {
        return $this->setPlusValue(null, false);
    }


    /**
     * Adds the specified single key/value query to this URL
     *
     * @param mixed      $value                   The value for the query to be added to the URL
     * @param string|int $key                     The key for the query to be added to the URL
     * @param bool       $skip_null_values [true] If true will not add the key/value combination if the value equals NULL
     *
     * @return static
     */
    public function addQuery(mixed $value, string|int $key, bool $skip_null_values = true): static
    {
        if ($value === null) {
            return $this;
        }

        $key   = Url::ensureStringUrlEncoding((string) $key);
        $value = Url::ensureStringUrlEncoding((string) $value);

        if (str_contains($this->source, '?')) {
            if (str_contains($this->source, $key . '=')) {
                // The query already exists in the specified URL, replace it.
                $replace      = Strings::cut($this->source, $key . '=', '&', needles_required: false);
                $this->source = str_replace($key . '=' . $replace, $key . '=' . $value, $this->source);

            } else {
                // Append the query to the URL
                $this->source .= '&' . $key . '=' . $value;
            }

        } else {
            // This URL has no queries yet, so we do not need to check anything, we can just attach the query
            $this->source .= '?' . $key . '=' . $value;
        }

        return $this;
    }


    /**
     * GARBAGE!
     *
     * Do not use this method, only use it as a reference to implement language mapping
     */
    protected function mapLanguage($url_params = null, $query = null, $prefix = null, $domain = null, $language = null, $allow_cloak = true): string
    {
        throw new UnderConstructionException('Url::domain() is GARBAGE! DO NOT USE');

        /*
         * Do language mapping, but only if routemap has been set
         */
        // :TODO: This will fail when using multiple CDN servers (WHY?)
        if (!empty(config()->get('locale.languages.supported', [])) and ($this->url_params['domain'] !== $_CONFIG['cdn']['domain'] . '/')) {
            if ($this->url_params['from_language'] !== 'en') {
                /*
                 * Translate the current non-English URL to English first
                 * because The specified request could be in dutch whilst we want to end
                 * up with Spanish. So translate always
                 * FOREIGN1 > English > Foreign2.
                 *
                 * Also add a / in front of $return before replacing to ensure
                 * we do not accidentally replace sections like "services/" with
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
     * @param bool $check_sub_domains
     *
     * @return bool
     */
    public function isExternal(bool $check_sub_domains = true): bool
    {
        if ($this->isValid()) {
            // This  is not even a complete URL, must be internal, there is no domain name expected here
            return false;
        }

        // We have a complete URL, so there is a domain name in there. Check if it is a "local" (ie, on this server)
        // domain name
        return !$this->getDomainType($check_sub_domains);
    }


    /**
     * Returns true if this Url object contains a full and VALID URL
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return (bool) filter_var($this->source, FILTER_VALIDATE_URL);
    }


    /**
     * Returns true if the specified string is a full and VALID URL
     *
     * @param string $url
     *
     * @return bool
     */
    public static function isValidUrl(string $url): bool
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }


    /**
     * Returns true if the specified string is an external URL
     *
     * External here means that the domain is NOT one of the configured domains
     *
     * @param bool $check_sub_domains
     *
     * @return string|null web in case its on a WWW domain, cdn in case its on a CDN domain, NULL if it is on an external
     */
    public function getDomainType(bool $check_sub_domains = true): ?string
    {
        // Get all domain names and check if its primary or subdomain of those.
        $url_domain = $this->getHost();
        $domains    = config()->get('web.domains');

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


    /**
     * Ensures the specified URL queries are properly encoded
     *
     * @note  This method will not verify if the specified URL is valid, it will only encode the queries key/values
     *
     * @param string $url
     * @param bool   $allow_encoded_plus
     *
     * @return string
     */
    public static function ensureQueriesUrlEncoding(string $url, bool $allow_encoded_plus = false): string
    {
        // Get queries from URL string
        $queries = Strings::from($url, '?', needle_required: true);

        if ($queries) {
            // Get all individual queries and ensure they are encoded
            $url     = Strings::until($url, '?', needle_required: true);
            $queries = explode('&', $queries);

            foreach ($queries as &$query) {
                $query = static::ensureQueryUrlEncoding($query, $allow_encoded_plus);
            }

            // Rebuild the URL back together again
            unset($query);

            $queries = implode('&', $queries);
            $url     = $url . '?' . $queries;
        }

        return $url;
    }


    /**
     * Ensures the specified query is properly encoded
     *
     * @note This will forcibly allow + symbols, so GET queries may NOT contain %20B characters
     *
     * @param string $query
     * @param bool   $allow_encoded_plus
     *
     * @return string
     */
    public static function ensureQueryUrlEncoding(string $query, bool $allow_encoded_plus = false): string
    {
        if (mb_substr_count('?', $query)) {
            throw new OutOfBoundsException(tr('Cannot ensure query URL encoding, the specified query ":query" contains multiple "?" symbols', [
                ':query' => $query,
            ]));
        }

        $parts = explode('=', $query);

        switch (count($parts)) {
            case 2:
                // This is perfect
                break;

            case 1:
                // Missing =, assume empty value string
                $parts = [$parts[0], ''];
                break;

            default:
                throw new OutOfBoundsException(tr('Cannot ensure query URL encoding, the specified query ":query" contains multiple "=" symbols', [
                    ':query' => $query,
                ]));
        }

        // Decode and encode again, this way we will not double-encode and can be sure the value is encoded
        // This might mangle the + sign, so unmangle that manually
        foreach ($parts as &$part) {
            $part = Url::ensureStringUrlEncoding($part, $allow_encoded_plus);
        }

        // Return the query
        unset($part);
        return implode('=', $parts);
    }


    /**
     * Ensures that the specified string is URL encoded
     *
     * @note This will forcibly allow + symbols, so GET queries may NOT contain %20B characters
     *
     * @param string $source
     * @param bool   $allow_encoded_plus
     *
     * @return string
     */
    public static function ensureStringUrlEncoding(string $source, bool $allow_encoded_plus = false): string
    {
        $source = urldecode($source);
        $source = urlencode($source);

        if (!$allow_encoded_plus) {
            $source = str_replace('%20b', '+', $source);
        }

        return $source;
    }


    /**
     * Returns if the queries in this URL are properly encoded, or not
     *
     * @return bool
     */
    public function isEncoded(): bool
    {
        return $this->encoded;
    }


    /**
     * Ensures the queries this URL object are properly URL-encoded
     *
     * @return static
     */
    public function ensureQueriesEncoded(): static
    {
        if (!$this->encoded) {
            $this->encodeQueries();
        }

        return $this;
    }


    /**
     * URL-encodes the URL queries in this object
     *
     * @return static
     */
    public function encodeQueries(): static
    {
        $this->source  = Url::ensureQueriesUrlEncoding($this->source);
        $this->encoded = true;

        return $this;
    }


    /**
     * Returns true if the current URL has the specified domain
     *
     * @param EnumDomainAllowed|string|null $host
     *
     * @return bool
     */
    public function hasHost(EnumDomainAllowed|string|null $host = null): bool
    {
        if ($host === null) {
            return true;
        }

        if ($host instanceof EnumDomainAllowed) {
            switch ($host) {
                case EnumDomainAllowed::any:
                    break;

                case EnumDomainAllowed::current:
                    $host = Url::newCurrentDomainRootUrl()->getHost();
                    break;

                case EnumDomainAllowed::whitelist:
                    throw UnderConstructionException::new();
            }
        }

        return $host === $this->getHost();
    }


    /**
     * Returns static if the URL domain matches the specified domain. Throws an exception if URL does not match
     *
     * @param EnumDomainAllowed|string $host
     *
     * @return static
     */
    public function checkHost(EnumDomainAllowed|string $host): static
    {
        if ($this->hasHost($host)) {
            return $this;
        }

        throw UrlException::new(tr('The URL ":source" does not match the required host ":host"', [
            ':source' => $this->source,
            ':host'   => $host,
        ]));
    }


    /**
     * Returns the rights required by any user to view this URL using the current routing parameters
     *
     * @param bool $use_cache
     *
     * @return array
     */
    public function getRights(bool $use_cache = true): array
    {
        $this->ensureAbsolute();

        if (Request::isMyPage($this->getPath())) {
            // My pages do not require rights at all
            return [];
        }

        return $this->getRightsObject($use_cache)
                   ?->getSource() ?? [];
    }


    /**
     * Gets a list of configured minimum rights to access any page
     *
     * @return array
     */
    public function getMimimumRights(): array
    {
        return config()->getArray('security.web.pages.rights.minimum', []);
    }


    /**
     * Gets a list of configured files that are exempt from rights checks (and as such, accessible by the guest user)
     *
     * @return array
     */
    public function getRightsExceptions(): array
    {
        return config()->getArray('security.web.pages.rights.exceptions', [
            '>/sign-',
            '>/force-password-update.',
            '>/lost-password.',
            '>/public/'
        ]);
    }


    /**
     * Returns the Rights object containing the rights required by any user to view this URL using the current routing parameters
     *
     * @param bool $use_cache
     *
     * @return RightsInterface
     */
    public function getRightsObject(bool $use_cache = true): RightsInterface
    {
        if (empty($this->_rights) or !$use_cache) {
            $this->_rights = RightsBySeoName::new()->setParentObject($this);

            if ($this->source) {
                // Only check rights on local URL's. This means only URL's without host, or with internal / configured hosts
                $host  = $this->getHost();
                $check = (empty($host) or Domains::isConfigured($host));

                if ($check) {
                    $parsed = $this->parseRights();

                    if ($parsed === null) {
                        // No rights are required at all, we can ignore minimum rights
                        return $this->_rights;
                    }

                    // Is this an internal / configured host? If not, this is not ours to check and no rights will be required
                    $this->_rights->setSource(array_merge($this->getMimimumRights(),
                                                           $parsed,
                                                           $this->_rights?->getSourceKeys() ?? []));
                }
            }
        }

        return $this->_rights;
    }


    /**
     * Parses the rights required from the URL
     *
     * @return array|null
     */
    protected function parseRights(): ?array
    {
        if ($this->source) {
            $this->ensureAbsolute();

            if ($this->hasRightsException()) {
                // No rights are required at all
                return null;
            }

            $path = $this->getPath();

            if ($path) {
                // Filter out the rights from the given URL
                if (preg_match_all('/^\/?(?:\w{2}\/)?(?:(.+?)\/)?[^\/]+(?:\.\w+)?$/', $path, $matches)) {
                    if ($matches[1][0]) {
                        return explode('/', $matches[1][0]);
                    }
                }
            }

            // No rights found in the URL
        }

        return  [];
    }


    /**
     * Returns true if the current URL for this object is exempt from rights checks according to the configuration path "security.web.pages.rights.exceptions"
     *
     * @return bool
     */
    public function hasRightsException(): bool
    {
        $path = $this->getPath(true);
        $path = Strings::untilReverse($path, '.') . '.';

        foreach ($this->getRightsExceptions() as $exception) {
            switch (substr($exception, 0, 1)) {
                case '=':
                    if ($path === substr($exception, 1)) {
                        // No rights are required at all
                        return true;
                    }

                    break;

                case '>':
                    if (str_starts_with($path, substr($exception, 1))) {
                        // No rights are required at all
                        return true;
                    }

                    break;

                case '<':
                    if (str_ends_with($path, substr($exception, 1))) {
                        // No rights are required at all
                        return true;
                    }

                    break;

                case '*':
                    try {
                        if (preg_match(substr($exception, 1), $path)) {
                            // No rights are required at all
                            return true;
                        }

                    } catch (PhpException $e) {
                        throw new RegexException(tr('Failed to execute regular expression ":regex"', [
                            ':regex' => substr($exception, 1),
                        ]), $e);
                    }

                    break;

                default:
                    throw new OutOfBoundsException(tr('Cannot parse URL rights exception ":exception", it must start with "<" or ">" or "$". Please check your configuration path "security.web.pages.rights.exceptions"', [
                        ':exception' => $exception,
                    ]));
            }
        }

        return false;
    }


    /**
     * Returns true if the current session user (or the specified one) has access to this URL
     *
     * @param UserInterface|null $_user
     * @param bool               $use_cache
     *
     * @return bool
     */
    public function userHasAccess(?UserInterface $_user = null, bool $use_cache = true): bool
    {
        return ($_user ?? Session::getUserObject())->hasAllRights($this->getRights($use_cache));
    }


    /**
     * Throws an AccessDeniedException if the current session user (or the specified one) does not have access to this URL
     *
     * @param UserInterface|null $_user
     * @param bool               $use_cache
     *
     * @return static
     */
    public function checkUserAccess(?UserInterface $_user = null, bool $use_cache = true): static
    {
        if ($this->userHasAccess($_user, $use_cache)) {
            return $this;
        }

        throw AccessDeniedException::new(tr('The user ":user" does not have access to URL ":url"', [
            ':user' => $_user->getLogId(),
            ':url'  => $this->getSource(),
        ]));
    }


    /**
     * Returns an Anchor object with this URL
     *
     * @param string|null           $content
     * @param EnumAnchorTarget|null $_target
     *
     * @return AnchorInterface
     */
    public function getAnchorObject(?string $content = null, ?EnumAnchorTarget $_target = null): AnchorInterface
    {
        return Anchor::new($this, $content)->setTargetObject($_target);
    }
}
