<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Interfaces;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlDataTableInterface;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlTableInterface;
use ReturnTypeWillChange;
use Stringable;

interface DataIteratorInterface extends IteratorInterface
{
    /**
     * Sets what SQL columns will be used in loading data
     *
     * @param string|null $columns
     *
     * @return static
     */
    public function setSqlColumns(?string $columns): static;

    /**
     * Returns if the specified data entry key exists in the data list
     *
     * @param DataEntryInterface|Stringable|string|float|int $key
     *
     * @return bool
     */
    public function keyExists(DataEntryInterface|Stringable|string|float|int $key): bool;


    /**
     * Returns the query for this object when generating internal content
     *
     * @return string|null
     */
    public function getQuery(): ?string;


    /**
     * Set the query for this object when generating internal content
     *
     * @param string|null $query
     * @param array|null  $execute
     *
     * @return static
     */
    public function setQuery(?string $query, ?array $execute = null): static;


    /**
     * Returns the execute array for the query for this object when generating internal content
     *
     * @return array|null
     */
    public function getExecute(): ?array;


    /**
     * Returns the item with the specified identifier
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return DataEntry|null
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, bool $exception = true): ?DataEntryInterface;

    /**
     * Sets the value for the specified key
     *
     * @param DataEntryInterface          $value
     * @param Stringable|string|float|int $key
     * @param bool                        $skip_null_values
     *
     * @return static
     */
    public function set(mixed $value, Stringable|string|float|int $key, bool $skip_null_values = true): static;


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
     *
     * @return HtmlTableInterface
     */
    public function getHtmlTableObject(array|string|null $columns = null): HtmlTableInterface;


    /**
     * Creates and returns a fancy HTML data table for the data in this list
     *
     * @param array|string|null $columns
     *
     * @return HtmlDataTableInterface
     */
    public function getHtmlDataTableObject(array|string|null $columns = null): HtmlDataTableInterface;


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string      $value_column
     * @param string|null $key_column
     * @param string|null $order
     * @param array|null  $joins
     * @param array|null  $filters
     *
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', ?string $key_column = null, ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface;


    /**
     * Creates and returns a CLI table for the data in this list
     *
     * @param array|string|null $columns
     * @param array             $filters
     * @param string|null       $id_column
     *
     * @return static
     */
    public function displayCliTable(array|string|null $columns = null, array $filters = [], ?string $id_column = 'id'): static;


    /**
     * Set the specified status for the specified entries
     *
     * @param string|null $status
     * @param string|null $comments
     *
     * @return int
     */
    public function updateStatus(?string $status, ?string $comments = null): int;


    /**
     * Delete all the entries in this list
     *
     * @param string|null $comments
     *
     * @return int
     */
    public function delete(?string $comments = null): int;


    /**
     * Erase (as in SQL DELETE) the specified entries from the database, also erasing their meta data
     *
     * @return static
     */
    public function erase(): static;


    /**
     * Undelete the specified entries
     *
     * @note This will set the status "NULL" to the entries in this datalist, NOT the original value of their status!
     *
     * @param string|null $comments
     *
     * @return int
     */
    public function undelete(?string $comments = null): int;


    /**
     * Returns an array with all id's for the specified entry identifiers
     *
     * @param array $identifiers
     *
     * @return array
     */
    public function listIds(array $identifiers): array;


    /**
     * Add the specified data entry to the data list
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param bool                             $skip_null_values
     * @param bool                             $exception
     *
     * @return static
     */
    public function append(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null_values = true, bool $exception = true): static;


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
    public function getFirstValue(): ?DataEntryInterface;


    /**
     * Returns the last element contained in this object without changing the internal pointer
     *
     * @return DataEntryInterface|null
     */
    public function getLastValue(): ?DataEntryInterface;


    /**
     * Load the id list from the database
     *
     * @param array|string|int|null $identifiers
     * @param bool                  $clear
     * @param bool                  $only_if_empty
     *
     * @return static
     */
    public function load(array|string|int|null $identifiers = null, bool $clear = true, bool $only_if_empty = false): static;


    /**
     * This method will load ALL database entries into this object
     *
     * @return static
     */
    public function loadAll(): static;


    /**
     * Adds the specified source to the internal source
     *
     * @param IteratorInterface|array|string|null $source
     * @param bool                                $clear_keys
     * @param bool                                $exception
     *
     * @return static
     */
    public function addSource(IteratorInterface|array|string|null $source, bool $clear_keys = false, bool $exception = true): static;


    /**
     * Sets the parent
     *
     * @param DataEntryInterface $parent
     *
     * @return static
     */
    public function setParentObject(DataEntryInterface $parent): static;


    /**
     * Returns an array of
     *
     * @param string|null $word
     *
     * @return array
     */
    public function autoCompleteFind(?string $word = null): array;

    /**
     * Returns the random entry
     *
     * @return DataEntry|null
     */
    #[ReturnTypeWillChange] public function getRandom(): ?DataEntryInterface;

    /**
     * Returns the debug value
     *
     * @return bool
     */
    public function getDebug(): bool;

    /**
     * Sets the debug value
     *
     * @param bool $debug
     *
     * @return static
     */
    public function setDebug(bool $debug): static;

    /**
     * Returns the name of the database connector where this DataEntry is stored
     *
     * @return string
     */
    public function getConnector(): string;


    /**
     * Sets the database connector by name
     *
     * @param string      $connector
     * @param string|null $database
     *
     * @return static
     */
    public function setConnector(string $connector, ?string $database = null): static;

    /**
     * Returns the default database connector to use for this table
     *
     * @return string
     */
    public static function getDefaultConnector(): string;

    /**
     * Returns a database connector for this DataEntry object
     *
     * @return ConnectorInterface
     */
    public static function getDefaultConnectorObject(): ConnectorInterface;

    /**
     * Returns the database connector
     *
     * @return ConnectorInterface
     */
    public function getConnectorObject(): ConnectorInterface;


    /**
     * Sets the database connector
     *
     * @param ConnectorInterface $o_connector
     * @param string|null        $database
     *
     * @return static
     */
    public function setConnectorObject(ConnectorInterface $o_connector, ?string $database = null): static;

    /**
     * Sets the QueryBuilder object to modify the internal query for this object
     *
     * @param QueryBuilderInterface $query_builder
     *
     * @return static
     */
    public function setQueryBuilder(QueryBuilderInterface $query_builder): static;

    /**
     * Shift an entry off the beginning of this Iterator
     *
     * @return DataEntryInterface|null
     */
    #[ReturnTypeWillChange] public function shift(): ?DataEntryInterface;
}
