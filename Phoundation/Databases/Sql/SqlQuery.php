<?php

declare(strict_types=1);

namespace Phoundation\Databases\Sql;

use Phoundation\Databases\Sql\Interfaces\SqlQueryInterface;

/**
 * SqlQuery class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */
class SqlQuery implements SqlQueryInterface
{
    /**
     * @var string $query
     */
    protected string $query;


    /**
     * SqlQuery class constructor
     *
     * @param SqlQueryInterface|string $query
     */
    public function __construct(SqlQueryInterface|string $query)
    {
        $this->query = (string) $query;
    }


    /**
     * Returns a new SqlQuery object
     *
     * @param SqlQueryInterface|string $query
     *
     * @return SqlQuery
     */
    public static function new(SqlQueryInterface|string $query): static
    {
        return new static($query);
    }


    /**
     * SqlQuery __toString()
     */
    public function __toString(): string
    {
        return (string) $this->query;
    }


    /**
     * Returns the SQL query string
     *
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }


    /**
     * Sets the SQL query string
     *
     * @param string $query
     *
     * @return static
     */
    public function setQuery(string $query): static
    {
        $this->query = $query;

        return $this;
    }
}