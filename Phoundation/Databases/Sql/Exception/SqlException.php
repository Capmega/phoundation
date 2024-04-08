<?php

declare(strict_types=1);

namespace Phoundation\Databases\Sql\Exception;

use PDOStatement;
use Phoundation\Databases\Exception\DatabasesException;
use Phoundation\Databases\Sql\Exception\Interfaces\SqlExceptionInterface;

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
class SqlException extends DatabasesException implements SqlExceptionInterface
{
    /**
     * Returns the SQL state at this exception
     *
     * @return string|null
     */
    public function getSqlState(): ?string
    {
        return $this->getDataKey('sql_state');
    }


    /**
     * Sets the SQL state at this exception
     *
     * @param string|null $state
     *
     * @return $this
     */
    public function setSqlState(?string $state): static
    {
        return $this->addData($state, 'sql_state');
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
     * @return $this
     */
    public function setQuery(PDOStatement|string|null $query): static
    {
        return $this->addData($query, 'query');
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
     * @return $this
     */
    public function setExecute(?array $execute): static
    {
        return $this->addData($execute, 'execute');
    }
}
