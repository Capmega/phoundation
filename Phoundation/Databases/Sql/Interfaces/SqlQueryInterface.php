<?php

namespace Phoundation\Databases\Sql\Interfaces;

use Stringable;


/**
 * SqlQuery class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Medinet
 */
interface SqlQueryInterface extends Stringable
{
    /**
     * Returns the SQL query string
     *
     * @return string
     */
    public function getQuery(): string;

    /**
     * Sets the SQL query string
     *
     * @param string $query
     * @return static
     */
    public function setQuery(string $query): static;
}