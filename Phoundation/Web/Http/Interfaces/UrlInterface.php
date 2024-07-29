<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Interfaces;

interface UrlInterface
{
    /**
     * When used as string, will always return the internal URL as available
     *
     * @param bool $strip_queries
     *
     * @return string
     */
    public function getUrl(bool $strip_queries = false): string;


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
     * @param array|string|bool ...$queries All the queries to add to this URL
     *
     * @return static
     */
    public function removeQueries(array|string|bool ...$queries): static;
}