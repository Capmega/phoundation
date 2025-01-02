<?php

/**
 * Class SqlException
 *
 * This is the standard exception for the Phoundation Databases Sql classes
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Sql\Exception;

use PDOStatement;
use Phoundation\Databases\Exception\DatabasesException;
use Phoundation\Databases\Sql\Exception\Interfaces\SqlExceptionInterface;
use Phoundation\Utils\Strings;

class SqlException extends DatabasesException implements SqlExceptionInterface
{
    /**
     * Returns the SQL state for this exception
     *
     * @return string|null
     */
    public function getSqlState(): ?string
    {
        return $this->getDataKey('sql_state');
    }


    /**
     * Sets the SQL state for this exception
     *
     * @param string|null $state
     *
     * @return static
     */
    public function setSqlState(?string $state): static
    {
        return $this->addData($state, 'sql_state');
    }


    /**
     * Returns the driver state for this exception
     *
     * @return string|int|null
     */
    public function getDriverState(): string|int|null
    {
        return $this->getDataKey('driver_state');
    }



    /**
     * Sets the driver state for this exception
     *
     * @param string|int|null $state
     *
     * @return static
     */
    public function setDriverState(string|int|null $state): static
    {
        return $this->addData($state, 'driver_state');
    }


    /**
     * Returns the SQL query at this exception
     *
     * @return PDOStatement|string|null
     */
    public function getQuery(): PDOStatement|string|null
    {
        return $this->getDataKey('query');
    }


    /**
     * Sets the SQL query at this exception
     *
     * @param PDOStatement|string|null $query
     *
     * @return static
     */
    public function setQuery(PDOStatement|string|null $query): static
    {
        if ($query instanceof PDOStatement) {
            $query = $query->queryString;
        }

        return $this->addData($query, 'query')
                    ->setParsedQuery();
    }


    /**
     * Returns the SQL host at this exception
     *
     * @return PDOStatement|string|null
     */
    public function getHost(): PDOStatement|string|null
    {
        return $this->getDataKey('host');
    }


    /**
     * Sets the SQL host at this exception
     *
     * @param PDOStatement|string|null $host
     *
     * @return static
     */
    public function setHost(PDOStatement|string|null $host): static
    {
        return $this->addData($host, 'host');
    }


    /**
     * Returns the SQL execute at this exception
     *
     * @return array|null
     */
    public function getExecute(): ?array
    {
        return $this->getDataKey('execute');
    }


    /**
     * Sets the SQL execute at this exception
     *
     * @param array|null $execute
     *
     * @return static
     */
    public function setExecute(?array $execute): static
    {
        return $this->addData($execute, 'execute')
                    ->setParsedQuery();
    }


    /**
     * Returns the SQL parsed query
     *
     * @return string|null
     */
    public function getParsedQuery(): ?string
    {
        return $this->getDataKey('parsed_query');
    }


    /**
     * Parses the query with the specified variables and stores it as "parsed_query"
     *
     * @return static
     */
    protected function setParsedQuery(): static
    {
        return $this->addData($this->parseQuery(), 'parsed_query');
    }


    /**
     * Parses and returns the query with the execute variables
     *
     * @return string|null
     */
    protected function parseQuery(): ?string
    {
        if ($this->getQuery() and $this->getExecute()) {
            $query = $this->getQuery();

            foreach ($this->getExecute() as $key => $value) {
                $value = Strings::fromDatatype($value, '"');
                $query = str_replace($key, $value, $query);
            }

            return Strings::ensureEndsWith($query, ';');
        }

        return null;
    }
}
