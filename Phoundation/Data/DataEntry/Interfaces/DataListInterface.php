<?php

namespace Phoundation\Data\DataEntry\Interfaces;


use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Web\Http\Html\Components\DataTable;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;
use Phoundation\Web\Http\Html\Components\Table;
use ReturnTypeWillChange;
use Stringable;

/**
 * Class DataList
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
interface DataListInterface extends IteratorInterface
{
    /**
     * Returns if the specified data entry exists in the data list
     *
     * @param DataEntryInterface|Stringable|string|float|int $key
     * @return bool
     */
    public function exists(DataEntryInterface|Stringable|string|float|int $key): bool;

    /**
     * Returns a list of items that are specified, but not available in this DataList
     *
     * @param DataListInterface|array|string $list
     * @param string|null $always_match
     * @return array
     */
    public function getMissingKeys(DataListInterface|array|string $list, string $always_match = null): array;

    /**
     * Returns if all (or optionally any) of the specified entries are in this list
     *
     * @param DataListInterface|array|string $list
     * @param bool $all
     * @param string|null $always_match
     * @return bool
     */
    public function containsKeys(DataListInterface|array|string $list, bool $all = true, string $always_match = null): bool;

    /**
     * Returns if all (or optionally any) of the specified entries are in this list
     *
     * @param DataListInterface|array|string $list
     * @param bool $all
     * @param string|null $always_match
     * @return bool
     */
    public function containsValues(DataListInterface|array|string $list, bool $all = true, string $always_match = null): bool;

    /**
     * Returns the internal list filtered by the specified keyword
     *
     * @param string|null $keyword
     * @return array
     */
    public function filteredList(?string $keyword): array;

    /**
     * Set the query for this object when generating internal content
     *
     * @param string $query
     * @param array|null $execute
     * @return static
     */
    public function setQuery(string $query, ?array $execute = null): static;

    /**
     * Returns the query for this object when generating internal content
     *
     * @return string
     */
    public function getQuery(): string;

    /**
     * Returns the execute array for the query for this object when generating internal content
     *
     * @return array|null
     */
    public function getExecute(): ?array;

    /**
     * Returns the table name that is the source for this DataList object
     *
     * @return string
     */
    public function getTable(): string;

    /**
     * Returns the schema Table object for the table that is the source for this DataList object
     *
     * @return \Phoundation\Databases\Sql\Schema\Table
     */
    public function getTableSchema(): \Phoundation\Databases\Sql\Schema\Table;

    /**
     * Returns a QueryBuilder object to modify the internal query for this object
     *
     * @return QueryBuilderInterface
     */
    public function getQueryBuilder(): QueryBuilderInterface;

    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @return Table
     */
    public function getHtmlTable(): Table;

    /**
     * Creates and returns a fancy HTML data table for the data in this list
     *
     * @return DataTable
     */
    public function getHtmlDataTable(): DataTable;

    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @return SelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', string $key_column = 'id'): SelectInterface;

    /**
     * Creates and returns a CLI table for the data in this list
     *
     * @param array|null $columns
     * @param array $filters
     * @param string|null $id_column
     * @return void
     */
    public function CliDisplayTable(?array $columns = null, array $filters = [], ?string $id_column = 'id'): void;

    /**
     * Set the specified status for the specified entries
     *
     * @param string|null $status
     * @param array $entries
     * @param string|null $comments
     * @return int
     */
    public function setStatus(?string $status, array $entries, ?string $comments = null): int;

    /**
     * Delete the specified entries
     *
     * @param array $entries
     * @param string|null $comments
     * @return int
     */
    public function dbDelete(array $entries, ?string $comments = null): int;

    /**
     * Undelete the specified entries
     *
     * @param array $entries
     * @param string|null $comments
     * @return int
     */
    public function dbUndelete(array $entries, ?string $comments = null): int;

    /**
     * Returns an array with all id's for the specified entry identifiers
     *
     * @param array $identifiers
     * @return array
     */
    public function listIds(array $identifiers): array;

    /**
     * Add the specified data entry to the data list
     *
     * @param DataEntry|null $entry
     * @return static
     */
    public function addDataEntry(?DataEntryInterface $entry): static;

    /**
     * Remove the specified key(s) from the data list
     *
     * @param DataEntryInterface|array|string|float|int $keys
     * @return static
     */
    public function delete(DataEntryInterface|array|string|float|int $keys): static;

    /**
     * Returns the current item
     *
     * @return DataEntryInterface|null
     */
    #[ReturnTypeWillChange] public function current(): ?DataEntryInterface;

    /**
     * Returns value for the specified key
     *
     * @param Stringable|string|float|int $key
     * @param bool $exception
     * @return DataEntryInterface|null
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, bool $exception = false): ?DataEntryInterface;

    /**
     * Returns the first element contained in this object without changing the internal pointer
     *
     * @return DataEntryInterface|null
     */
    #[ReturnTypeWillChange] public function getFirst(): ?DataEntryInterface;

    /**
     * Returns the last element contained in this object without changing the internal pointer
     *
     * @return DataEntryInterface|null
     */
    #[ReturnTypeWillChange] public function getLast(): ?DataEntryInterface;

    /**
     * Load the id list from database
     *
     * @param string|null $id_column
     * @return static
     * @deprecated This function will be replaced by the QueryBuilder. DO NOT USE
     */
    public function load(?string $id_column = null): static;
}