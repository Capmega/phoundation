<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Interfaces;

use Phoundation\Accounts\Rights\Interfaces\RightInterface;
use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Interfaces\AnchorInterface;
use Phoundation\Web\Html\Enums\EnumAnchorTarget;
use Phoundation\Web\Http\Domains;
use Phoundation\Web\Http\Url;

interface UrlInterface
{
    /**
     * When used as string, will always return the internal URL as available
     *
     * @param bool $strip_queries
     *
     * @return string|int|null
     */
    public function getSource(bool $strip_queries = false): string|int|null;


    /**
     * Sets the source URL of this URL object
     *
     * @param UrlInterface|string|int|null $source
     *
     * @return static
     */
    public function setSource(UrlInterface|string|int|null $source): static;


    /**
     * Returns if generated URL's is cloaked or not
     *
     * @return bool
     */
    public function isCloaked(): bool;


    /**
     * Cloak the specified URL.
     *
     * URL cloaking is nothing more than replacing a full URL (with query) with a random string. This function will
     * register the requested URL
     *
     * @return static
     */
    public function cloak(): static;


    /**
     * Uncloak the specified URL.
     *
     * URL cloaking is nothing more than
     *
     * @return static
     */
    public function decloak(): static;


    /**
     * Clear the query part from the URL
     *
     * @return static
     */
    public function clearQueries(): static;


    /**
     * Add the specified query / queries to the specified URL and return
     *
     * @param array|string|bool|null ...$queries All the queries to add to this URL
     *
     * @return static
     */
    public function addQueries(array|string|bool|null ...$queries): static;


    /**
     * Remove specified queries from the specified URL and return
     *
     * @param array|string|null $keys All the queries to add to this URL
     *
     * @return static
     */
    public function removeQueryKeys(array|string|null $keys): static;

    /**
     * Adds a redirect=URL query to this URL
     *
     * @param Url|string|null $redirect                          The URL that should be added as "?redirect=URL" in this
     *                                                           URL. If NULL, will not add anything. If empty string,
     *                                                           will default to the current URL
     * @param IteratorInterface|array|string|null $strip_queries If specified, will strip the specified query keys from
     *                                                           the redirect URL before adding it to this URL
     *
     * @return static
     */
    public function addRedirect(Url|string|null $redirect = null, IteratorInterface|array|string|null $strip_queries = 'redirect'): static;

    /**
     * Add the specified key=URL to this URL safely
     *
     * @param UrlInterface|string|null $value
     * @param string|int               $key
     *
     * @return static
     */
    public function addUrlQuery(UrlInterface|string|null $value, string|int $key): static;

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
    public static function ensureQueriesUrlEncoding(string $url, bool $allow_encoded_plus = false): string;

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
    public static function ensureQueryUrlEncoding(string $query, bool $allow_encoded_plus = false): string;

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
    public static function ensureStringUrlEncoding(string $source, bool $allow_encoded_plus = false): string;

    /**
     * Returns if the queries in this URL are properly encoded, or not
     *
     * @return bool
     */
    public function isEncoded(): bool;

    /**
     * Ensures the queries this URL object are properly URL-encoded
     *
     * @return static
     */
    public function ensureQueriesEncoded(): static;

    /**
     * URL-encodes the URL queries in this object
     *
     * @return static
     */
    public function encodeQueries(): static;

    /**
     * Adds the specified single key/value query to this URL
     *
     * @param mixed      $value
     * @param string|int $key
     *
     * @return static
     */
    public function addQuery(mixed $value, string|int $key): static;

    /**
     * Removes all queries from this URL
     *
     * @return static
     */
    public function removeAllQueries(): static;


    /**
     * Returns the roles for this user
     *
     * @param bool $use_cache
     *
     * @return RightsInterface
     */
    public function getRightsObject(bool $use_cache = true): RightsInterface;

    /**
     * Returns true if the user has SOME of the specified rights
     *
     * @param array|string $rights
     * @param string|null  $always_match
     *
     * @return bool
     */
    public function hasSomeRights(array|string $rights, ?string $always_match = 'god'): bool;

    /**
     * Returns true if the user has ALL the specified rights
     *
     * @param array|string $rights
     * @param string|null  $always_match
     *
     * @return bool
     */
    public function hasAllRights(array|string $rights, ?string $always_match = 'god'): bool;

    /**
     * Adds the specified right to the list
     *
     * @param RightInterface|string|null $o_right
     *
     * @return static
     */
    public function addRight(RightInterface|string|null $o_right): static;

    /**
     * Removes the specified right from the list
     *
     * @param RightInterface|string|null $o_right
     *
     * @return static
     */
    public function removeRight(RightInterface|string|null $o_right): static;

    /**
     * Returns the rights required by any user to view this URL using the current routing parameters
     *
     * @return array
     */
    public function getRights(): array;


    /**
     * Returns true if the current session user (or the specified one) has access to this URL
     *
     * @param UserInterface|null $o_user
     * @param bool               $use_cache
     *
     * @return bool
     */
    public function userHasAccess(?UserInterface $o_user = null, bool $use_cache = true): bool;


    /**
     * Throws an AccessDeniedException if the current session user (or the specified one) doesn't have access to this URL
     *
     * @param UserInterface|null $o_user
     * @param bool               $use_cache
     *
     * @return static
     * @throws AccessDeniedException
     */
    public function checkUserAccess(?UserInterface $o_user = null, bool $use_cache = true): static;

    /**
     * Returns an Anchor object with this URL
     *
     * @param string|null           $content
     * @param EnumAnchorTarget|null $o_target
     *
     * @return AnchorInterface
     */
    public function getAnchorObject(?string $content = null, ?EnumAnchorTarget $o_target = null): AnchorInterface;

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
    public function getParsed(): ?array;

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
    public function getParsedSection(string $section): string|int|null;

    /**
     * Returns the scheme part of the current URL
     *
     * @note Will return NULL if the scheme is not specified, empty, or invalid
     *
     * @return string|null
     */
    public function getScheme(): ?string;

    /**
     * Returns the user part of the current URL
     *
     * @note Will return NULL if the user is not specified, empty, or invalid
     *
     * @return string|null
     */
    public function getUser(): ?string;

    /**
     * Returns the password part of the current URL
     *
     * @note Will return NULL if the password is not specified, empty, or invalid
     *
     * @return string|null
     */
    public function getPassword(): ?string;

    /**
     * Returns the host for the current URL
     *
     * @note Will return NULL if the host is not specified, empty, or invalid
     *
     * @param bool $default_domain_is_current
     *
     * @return string|null
     */
    public function getHost(bool $default_domain_is_current = true): ?string;

    /**
     * Returns the port part of the current URL
     *
     * @note Will return NULL if the port is not specified, empty, or invalid
     *
     * @return int|null
     */
    public function getPort(): ?int;

    /**
     * Returns the path part of the current URL
     *
     * @note Will return NULL if the host is not specified, empty, or invalid
     *
     * @param bool $skip_language
     *
     * @return string|null
     */
    public function getPath(bool $skip_language = false): ?string;

    /**
     * Returns the file part of the current URL
     *
     * @note Will return NULL if the host is not specified, empty, or invalid
     *
     * @return string|null
     */
    public function getFile(): ?string;

    /**
     * Returns the path part of the current URL
     *
     * @note Will return NULL if the query is not specified, empty, or invalid
     *
     * @return string|null
     */
    public function getQuery(): ?string;

    /**
     * Returns the fragment part of the current URL
     *
     * @note Will return NULL if the fragment is not specified, empty, or invalid
     *
     * @return string|null
     */
    public function getFragment(): ?string;

    /**
     * Returns true if the current URL for this object is absolute (has a scheme and host), false otherwise
     *
     * @return bool
     */
    public function isAbsolute(): bool;

    /**
     * Returns the URL starting from the path
     *
     * @return string|null
     */
    public function getFromHost(): ?string;

    /**
     * Returns the URL starting from the path, and skipping the language selector (Typical for Phoundation sites)
     *
     * @return string|null
     */
    public function getFromHostAndLanguage(): ?string;
}
