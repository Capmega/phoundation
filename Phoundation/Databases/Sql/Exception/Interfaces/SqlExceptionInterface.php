<?php

declare(strict_types=1);

namespace Phoundation\Databases\Sql\Exception\Interfaces;

use PDOStatement;
use Phoundation\Databases\Exception\Interfaces\DatabasesExceptionInterface;

interface SqlExceptionInterface extends DatabasesExceptionInterface
{
    /**
     * Returns the SQL state at this exception
     *
     * @return string|null
     */
    public function getSqlState(): ?string;


    /**
     * Sets the SQL state at this exception
     *
     * @param string|null $state
     *
     * @return static
     */
    public function setSqlState(?string $state): static;


    /**
     * Returns the SQL query at this exception
     *
     * @return PDOStatement|string|null
     */
    public function getQuery(): PDOStatement|string|null;


    /**
     * Sets the SQL query at this exception
     *
     * @param PDOStatement|string|null $query
     *
     * @return static
     */
    public function setQuery(PDOStatement|string|null $query): static;


    /**
     * Returns the SQL execute at this exception
     *
     * @return array|null
     */
    public function getExecute(): ?array;


    /**
     * Sets the SQL execute at this exception
     *
     * @param array|null $execute
     *
     * @return static
     */
    public function setExecute(?array $execute): static;
}
