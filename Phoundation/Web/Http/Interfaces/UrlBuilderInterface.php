<?php

namespace Phoundation\Web\Http\Interfaces;

use \Stringable;


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
interface UrlBuilderInterface extends Stringable
{
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
     * Remove the query part from the URL
     *
     * @return static
     */
    public function removeQueries(): static;

    /**
     * Add a specified query to the specified URL and return
     *
     * @param array|string|bool|null ...$queries All the queries to add to this URL
     * @return static
     */
    public function addQueries(array|string|bool|null ...$queries): static;
}