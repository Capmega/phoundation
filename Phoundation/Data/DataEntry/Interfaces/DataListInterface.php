<?php

namespace Phoundation\Data\DataEntry\Interfaces;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Databases\Sql\Schema\Table;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Phoundation\Web\Html\Components\Interfaces\HtmlDataTableInterface;
use Phoundation\Web\Html\Components\Interfaces\HtmlTableInterface;
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
     * Returns if the specified data entry key exists in the data list
     *
     * @param DataEntryInterface|Stringable|string|float|int $key
     * @return bool
     */
    public function keyExists(DataEntryInterface|Stringable|string|float|int $key): bool;

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
     * Returns the schema Table object for the table that is the source for this DataList object
     *
     * @return Table
     */
    public function getTableSchema(): Table;

    /**
     * Returns the item with the specified identifier
     *
     * @param Stringable|string|float|int $key
     * @param bool $exception
     * @return DataEntry|null
     */
    public function get(Stringable|string|float|int $key, bool $exception = false): ?DataEntryInterface;

    /**
     * Sets the value for the specified key
     *
     * @param DataEntryInterface $value
     * @param Stringable|string|float|int $key
     * @param bool $skip_null
     * @return static
     */
    public function set(mixed $value, Stringable|string|float|int $key, bool $skip_null = true): static;

    /**
     * Returns a QueryBuilder object to modify the internal query for this object
     *
     * @return QueryBuilderInterface
     */
    public function getQueryBuilder(): QueryBuilderInterface;

    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @param array|string|null $columns
     * @return HtmlTableInterface
     */
    public function getHtmlTable(array|string|null $columns = null): HtmlTableInterface;

    /**
     * Creates and returns a fancy HTML data table for the data in this list
     *
     * @param array|string|null $columns
     * @return HtmlDataTableInterface
     */
    public function getHtmlDataTable(array|string|null $columns = null): HtmlDataTableInterface;

    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @param string|null $order
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', string $key_column = 'id', ?string $order = null): InputSelectInterface;

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
     * @param string|null $comments
     * @param bool $meta_enabled
     * @return int
     */
    public function updateStatusAll(?string $status, ?string $comments = null, bool $meta_enabled = true): int;

    /**
     * Delete the specified entries
     *
     * @param string|null $comments
     * @return int
     */
    public function deleteAll(?string $comments = null): int;

    /**
     * Undelete the specified entries
     *
     * @param string|null $comments
     * @return int
     */
    public function undeleteAll(?string $comments = null): int;

    /**
     * Returns an array with all id's for the specified entry identifiers
     *
     * @param array $identifiers
     * @return array
     */
    public function listIds(array $identifiers): array;

    /**
     * Returns the current item
     *
     * @return DataEntry|null
     */
    public function current(): ?DataEntryInterface;

    /**
     * Returns the first element contained in this object without changing the internal pointer
     *
     * @return DataEntryInterface|null
     */
    public function getFirst(): ?DataEntryInterface;

    /**
     * Returns the last element contained in this object without changing the internal pointer
     *
     * @return DataEntryInterface|null
     */
    public function getLast(): ?DataEntryInterface;

    /**
     * Load the id list from the database
     *
     * @param bool $clear
     * @return static
     */
    public function load(bool $clear = true): static;

    /**
     * Returns the total amounts for all columns together
     *
     * @note This specific method will just return a row with empty values. Its up to the classes implementing DataList
     *       to override this method and return meaningful totals.
     *
     * @param array|string $columns
     * @return array
     */
    public function getTotals(array|string $columns): array;

    /**
     * Adds the specified source to the internal source
     *
     * @param IteratorInterface|array|string|null $source
     * @return $this
     */
    public function addSources(IteratorInterface|array|string|null $source): static;

    /**
     * Access the direct list operations for this class
     *
     * @return ListOperationsInterface
     */
    public static function directOperations(): ListOperationsInterface;
}
