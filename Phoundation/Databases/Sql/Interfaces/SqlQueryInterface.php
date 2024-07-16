<?php

declare(strict_types=1);

namespace Phoundation\Databases\Sql\Interfaces;

use Stringable;

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
     *
     * @return static
     */
    public function setQuery(string $query): static;
}