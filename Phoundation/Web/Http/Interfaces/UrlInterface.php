<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Http\Url;

interface UrlInterface
{
    /**
     * When used as string, will always return the internal URL as available
     *
     * @param bool $strip_queries
     *
     * @return string
     */
    public function getSource(bool $strip_queries = false): string;


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
}
