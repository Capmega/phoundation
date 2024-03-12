<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry;

use Phoundation\Cli\CliAutoComplete;
use Phoundation\Core\Meta\Meta;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Phoundation\Data\DataEntry\Interfaces\ListOperationsInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataParent;
use Phoundation\Data\Traits\TraitDataReadonly;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Databases\Sql\SqlQueries;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Input\InputSelect;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Phoundation\Web\Html\Components\Tables\HtmlDataTable;
use Phoundation\Web\Html\Components\Tables\HtmlTable;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlDataTableInterface;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlTableInterface;
use Phoundation\Web\Html\Enums\EnumTableIdColumn;
use ReturnTypeWillChange;
use Stringable;


/**
 * Class DataList
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
abstract class DataList extends Iterator implements DataListInterface
{
    use TraitDataReadonly;
    use TraitDataParent {
        setParent as protected __setParent;
    }


    /**
     * The iterator position
     *
     * @var int $position
     */
    protected int $position = 0;

    /**
     * The query to display an HTML table for this list
     *
     * @var string $query
     */
    protected string $query;

    /**
     * The execution array for the HTML query
     *
     * @var array|null $execute
     */
    protected ?array $execute = null;

    /**
     * If set, use the query generated by this builder instead of the default query
     *
     * @var QueryBuilderInterface
     */
    protected QueryBuilderInterface $query_builder;

    /**
     * If true it means that this data list has data loaded from a database
     *
     * @var bool $is_loaded
     */
    protected bool $is_loaded = false;

    /**
     * Tracks if entries are stored by id or unique column
     *
     * @var bool $keys_are_unique_column
     */
    protected bool $keys_are_unique_column = false;

    /**
     * Tracks the class used to generate the select input
     *
     * @var string
     */
    protected string $input_select_class = InputSelect::class;


    /**
     * Return the object source contents in JSON string format
     *
     * @return string
     */
    public function __toString(): string
    {
        return Json::encode($this->source);
    }


    /**
     * Return the object contents in JSON string format
     *
     * @return array
     */
    public function __toArray(): array
    {
        return $this->source;
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    abstract public static function getTable(): string;


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    abstract public static function getEntryClass(): string;


    /**
     * Returns the column that is unique for this object
     *
     * @return string|null
     */
    abstract public static function getUniqueColumn(): ?string;


    /**
     * Returns the default database connector to use for this table
     *
     * @return string
     */
    public static function getDefaultConnectorName(): string
    {
        return 'system';
    }


    /**
     * Returns the column considered the "id" column
     *
     * @return string
     */
    public static function getIdColumn(): string
    {
        return 'id';
    }


    /**
     * Returns true if the ID column is the specified column
     *
     * @param string $column
     * @return bool
     */
    public static function idColumnIs(string $column): bool
    {
        return static::getIdColumn() === $column;
    }


    /**
     * Returns if the specified data entry key exists in the data list
     *
     * @param DataEntryInterface|Stringable|string|float|int $key
     * @return bool
     */
    public function keyExists(DataEntryInterface|Stringable|string|float|int $key): bool
    {
        if ($key instanceof DataEntryInterface) {
            $key = $key->getId();
        }

        return parent::keyExists($key);
    }


    /**
     * Returns if the DataEntry entries are stored with ID or key
     *
     * @return bool
     */
    public function getKeysareUniqueColumn(): bool
    {
        return $this->keys_are_unique_column;
    }


    /**
     * Sets if the DataEntry entries are stored with ID or key
     *
     * @param bool $keys_are_unique_column
     * @return static
     */
    public function setKeysareUniqueColumn(bool $keys_are_unique_column): static
    {
        $this->keys_are_unique_column = $keys_are_unique_column;
        return $this;
    }


    /**
     * Returns the query for this object when generating internal content
     *
     * @return string
     */
    public function getQuery(): string
    {
        $this->selectQuery();
        return $this->query;
    }


    /**
     * Set the query for this object when generating internal content
     *
     * @param string $query
     * @param array|null $execute
     * @return static
     */
    public function setQuery(string $query, ?array $execute = null): static
    {
        $this->query   = $query;
        $this->execute = $execute;

        return $this;
    }


    /**
     * Returns the execute array for the query for this object when generating internal content
     *
     * @return array|null
     */
    public function getExecute(): ?array
    {
        $this->selectQuery();
        return $this->execute;
    }


    /**
     * Returns the item with the specified identifier
     *
     * @param Stringable|string|float|int $key
     * @param bool $exception
     * @return DataEntry|null
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, bool $exception = true): ?DataEntryInterface
    {
        // Does this entry exist?
        if (!array_key_exists($key, $this->source)) {
            if ($exception) {
                throw new NotExistsException(tr('Key ":key" does not exist in this ":class" DataList', [
                    ':key'   => $key,
                    ':class' => get_class($this)
                ]));
            }

            return null;
        }

        return $this->ensureDataEntry($key);
    }


    /**
     * Sets the value for the specified key
     *
     * @param DataEntryInterface $value
     * @param Stringable|string|float|int $key
     * @param bool $skip_null
     * @return static
     */
    public function set(mixed $value, Stringable|string|float|int $key, bool $skip_null = true): static
    {
        if ($value instanceof DataEntryInterface) {
            return parent::set($key, $value);
        }

        throw new OutOfBoundsException(tr('Cannot set value ":value" to key ":key" in the list ":list", it does not have a DataEntryInterface', [
            ':list'  => get_class($this),
            ':key'   => $key,
            ':value' => $value
        ]));
    }


    /**
     * Returns a QueryBuilder object to modify the internal query for this object
     *
     * @return QueryBuilderInterface
     */
    public function getQueryBuilder(): QueryBuilderInterface
    {
        if (!isset($this->query_builder)) {
            $this->query_builder = QueryBuilder::new($this);
        }

        return $this->query_builder;
    }


    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @param array|string|null $columns
     * @return HtmlTableInterface
     */
    public function getHtmlTable(array|string|null $columns = null): HtmlTableInterface
    {
        if ($this->source) {
            // Source is already loaded, use this instead
            // Create and return the table
            return HtmlTable::new()
                ->setId(static::getTable())
                ->setSource($this->getAllRowsMultipleColumns($columns))
                ->setCallbacks($this->callbacks)
                ->setCheckboxSelectors(EnumTableIdColumn::checkbox);
        }

        $this->selectQuery();

        // Create and return the table
        return HtmlTable::new()
            ->setConnector(static::getDefaultConnectorName())
            ->setId(static::getTable())
            ->setSourceQuery($this->query, $this->execute)
            ->setCallbacks($this->callbacks)
            ->setCheckboxSelectors(EnumTableIdColumn::checkbox);
    }


    /**
     * Creates and returns a fancy HTML data table for the data in this list
     *
     * @param array|string|null $columns
     * @return HtmlDataTableInterface
     */
    public function getHtmlDataTable(array|string|null $columns = null): HtmlDataTableInterface
    {
        if ($this->source) {
            // Source is already loaded, use this instead
            // Create and return the table
            return HtmlDataTable::new()
                ->setId(static::getTable())
                ->setSource($this->getAllRowsMultipleColumns($columns))
                ->setCallbacks($this->callbacks)
                ->setCheckboxSelectors(EnumTableIdColumn::checkbox);
        }

        $this->selectQuery();

        // Create and return the table
        return HtmlDataTable::new()
            ->setConnector(static::getDefaultConnectorName())
            ->setId(static::getTable())
            ->setSourceQuery($this->query, $this->execute)
            ->setCallbacks($this->callbacks)
            ->setCheckboxSelectors(EnumTableIdColumn::checkbox);
    }


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string|null $key_column
     * @param string|null $order
     * @param array|null $joins
     * @param array|null $filters
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', ?string $key_column = null, ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface
    {
        $select  = $this->input_select_class::new();
        $execute = [];

        if (!$key_column) {
            $key_column = static::getIdColumn();
        }

        if ($this->is_loaded or count($this->source)) {
            // Data was either loaded from DB or manually added. $value_column may contain query parts, strip em.
            $value_column = trim($value_column);
            $value_column = Strings::fromReverse($value_column, ' ');
            $value_column = str_replace('`', '', $value_column);
            $select->setSource($this->getAllRowsSingleColumn($value_column));

        } else {
            $query = 'SELECT ' . $key_column . ', ' . $value_column . ' 
                      FROM   `' . static::getTable() . '` 
                      ' . Strings::force($joins, ' ');

            if ($filters) {
                $where = [];

                foreach ($filters as $key => $value) {
                    $where[] = $key . SqlQueries::is($key, $value, 'value', $execute);
                }

                $query .= ' WHERE ' . implode(' AND ', $where);
            }

            if ($order === null) {
                // Default order by the value column. Value column may have SQL, make sure its stripped
                $order = Strings::fromReverse($value_column, ' ') . ' ASC';
            }

            // Only order if an order column has been specified
            if ($order) {
                $query .= ' ORDER BY ' . $order;
            }

            // No data was loaded from DB or manually added
            $select
                ->setConnector(static::getDefaultConnectorName())
                ->setSourceQuery($query, $execute);
        }

        return $select;
    }


    /**
     * Creates and returns a CLI table for the data in this list
     *
     * @param array|string|null $columns
     * @param array $filters
     * @param string|null $id_column
     * @return static
     */
    public function displayCliTable(array|string|null $columns = null, array $filters = [], ?string $id_column = 'id'): static
    {
        // If this list is empty, then load data from a query, else show list contents
        if (empty($this->source)) {
            $this->selectQuery();
            $this->source = sql(static::getDefaultConnectorName())->list($this->query, $this->execute);
        }

        return parent::displayCliTable($columns, $filters, $id_column);
    }


    /**
     * Set the specified status for the specified entries
     *
     * @param string|null $status
     * @param string|null $comments
     * @return int
     */
    public function setStatus(?string $status, ?string $comments = null): int
    {
        foreach ($this->source as $entry) {
            $entry->setStatus($status, $comments);
        }

        return count($this->source);
    }


    /**
     * Delete all the entries in this list
     *
     * @param string|null $comments
     * @return int
     */
    public function delete(?string $comments = null): int
    {
        return $this->setStatus('deleted', $comments);
    }


    /**
     * Access the direct list operations for this class
     *
     * @return ListOperationsInterface
     */
    public static function directOperations(): ListOperationsInterface
    {
        return new ListOperations(static::class);
    }


    /**
     * Erase (as in SQL DELETE) the specified entries from the database, also erasing their meta data
     *
     * @return static
     */
    public function erase(): static
    {
        $ids  = [];
        $meta = [];

        $this->loadAll();

        // Delete the meta data entries
        foreach ($this->source as $id => $entry) {
            $ids[] = $id;

            if (is_array($entry)) {
                $meta[] = $entry['meta_id'];

            } elseif ($entry instanceof DataEntryInterface) {
                $meta[] = $entry->getMetaId();
            }
        }

        if ($ids) {
            // Delete the meta data
            Meta::eraseEntries($meta);

            // Delete the entries themselves
            sql(static::getDefaultConnectorName())->erase(static::getTable(), ['id' => $ids]);
        }

        return $this;
    }


    /**
     * Undelete the specified entries
     *
     * @note This will set the status "NULL" to the entries in this datalist, NOT the original value of their status!
     * @param string|null $comments
     * @return int
     */
    public function undelete(?string $comments = null): int
    {
        return $this->setStatus(null, $comments);
    }


    /**
     * Returns an array with all id's for the specified entry identifiers
     *
     * @param array $identifiers
     * @return array
     */
    public function listIds(array $identifiers): array
    {
        if ($identifiers) {
            $in = SqlQueries::in($identifiers);

            return sql(static::getDefaultConnectorName())->list('SELECT `id` 
                                                                   FROM   `' . static::getTable() . '` 
                                                                   WHERE  `' . static::getUniqueColumn() . '` IN (' . implode(', ', array_keys($in)) . ')', $in);
        }

        return [];
    }


    /**
     * Add the specified data entry to the data list
     *
     * @param mixed $value
     * @param Stringable|string|float|int|null $key
     * @param bool $skip_null
     * @param bool $exception
     * @return static
     */
    public function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null = true, bool $exception = true): static
    {
        if (!$value instanceof DataEntryInterface) {
            // Value might be NULL if we skip NULLs?
            if (($value !== null) or !$skip_null) {
                throw new OutOfBoundsException(tr('Cannot add specified value ":value" it must be an instance of DataEntryInterface', [
                    ':value' => $value
                ]));
            }
        }

        if ($this->keys_are_unique_column) {
            if ($key) {
                if (!$value->isNew() and ($key != $value->getUniqueColumnValue())) {
                    // Key must either not be specified or match the id of the DataEntry
                    throw new OutOfBoundsException(tr('Cannot add ":type" type DataEntry with id ":id", the specified key ":key" must either match the value ":value" of the unique column ":unique" or be null', [
                        ':value'  => $value->getUniqueColumnValue(),
                        ':unique' => $value::getUniqueColumn(),
                        ':type'   => $value::getDataEntryName(),
                        ':id'     => $value->getId() ?? 'N/A',
                        ':key'    => $key
                    ]));
                }

                // Either the specified DataEntry object has no value for its unique column, or the unique column
                // matches the specified key. Either way, we're good to go

            } else {
                $key = $value->getUniqueColumnValue();
            }

            if (!$key) {
                throw new OutOfBoundsException(tr('Cannot add entry ":value" because the ":class" DataList object should uses unique columns as keys, but has no unique column configured', [
                    ':value' => $value,
                    ':class' => get_class($this)
                ]));
            }

        } else {
            if ($key) {
                if (!$value->isNew() and ($key != $value->getId())) {
                    // Key must either not be specified or match the id of the DataEntry
                    throw new OutOfBoundsException(tr('Cannot add ":type" type DataEntry with id ":id", the specified key ":key" must either match the id or be null', [
                        ':type' => $value::getDataEntryName(),
                        ':id'   => $value->getId(),
                        ':key'  => $key
                    ]));
                }

                // Either the specified DataEntry object is new or the id matches the specified key, we're good to go

            } else {
                $key = $value->getId();
            }
        }

        return parent::add($value, $key, $skip_null, $exception);
    }


    /**
     * Returns the current item
     *
     * @return DataEntry|null
     */
    #[ReturnTypeWillChange] public function current(): ?DataEntryInterface
    {
        return $this->ensureDataEntry(key($this->source));
    }


    /**
     * Returns the first element contained in this object without changing the internal pointer
     *
     * @return DataEntryInterface|null
     */
    #[ReturnTypeWillChange] public function getFirstValue(): ?DataEntryInterface
    {
        return $this->ensureDataEntry(array_key_first($this->source));
    }


    /**
     * Returns the last element contained in this object without changing the internal pointer
     *
     * @return DataEntryInterface|null
     */
    #[ReturnTypeWillChange] public function getLastValue(): ?DataEntryInterface
    {
        return $this->ensureDataEntry(array_key_last($this->source));
    }


    /**
     * Ensure the entry we're going to return is from DataEntryInterface interface
     *
     * @param string|float|int $key
     * @return DataEntryInterface
     */
    #[ReturnTypeWillChange] protected function ensureDataEntry(string|float|int $key): DataEntryInterface
    {
        // Ensure the source key is of DataEntryInterface
        if (!$this->source[$key] instanceof DataEntryInterface) {
            // Okay, interesting problem! When we loaded entries through QueryBuilder, we allowed to use whatever hell
            // columns we wanted with whatever hell datatype. For example, a column that normally would be an integer
            // now might be a string which will make the DataEntry setValue methods crash. To avoid this, we cannot rely
            // on the data available in the datalist, we'll have to load the DataEntry manually
            if (isset($this->query_builder)) {
                // Load the DataEntry separately from the database (will require an extra query)
                $this->source[$key] = static::getEntryClass()::get($key);

            } else {
                $this->source[$key] = static::getEntryClass()::new()->setSource($this->source[$key]);
            }
        }

        return $this->source[$key];
    }


    /**
     * Ensures that all objects in the source are DataEntry objects
     *
     * @return $this
     */
    protected function ensureDataEntries(): static
    {
        foreach ($this->source as $key => $value) {
            $this->ensureDataEntry($key);
        }

        return $this;
    }


    /**
     * Will throw an OutOfBoundsException exception if no parent was set for this list
     *
     * @param string $action
     * @return static
     */
    protected function ensureParent(string $action): static
    {
        if (!$this->parent) {
            throw new OutOfBoundsException(tr('Cannot ":action", no parent specified', [':action' => $action]));
        }

        return $this;
    }


    /**
     * Selects if we use the default query or a query from the QueryBuilder
     *
     * @return void
     * @throws OutOfBoundsException
     */
    protected function selectQuery(): void
    {
        // Use the default html_query and html_execute or QueryBuilder html_query and html_execute?
        if (isset($this->query_builder)) {
            $this->query   = $this->query_builder->getQuery();
            $this->execute = $this->query_builder->getExecute();

        } elseif (!isset($this->query)) {
            // Create query with optional filtering for parents_id
            if ($this->parent) {
                $parent_filter = '`' . static::getTable() . '`.`' . Strings::fromReverse($this->parent::getTable(), '_') . '_id` = :parents_id AND ';
                $this->execute['parents_id'] = $this->parent->getId();

            } else {
                $parent_filter = null;
            }

            // Set default query
            $this->query = 'SELECT                        ' . static::getKeyColumn() . ' AS `unique_identifier`, `' . static::getTable() . '`.*
                            FROM                         `' . static::getTable() . '`
                            WHERE  ' . $parent_filter . '`' . static::getTable() . '`.`status` IS NULL';
        }
    }


    /**
     * Returns the column that (by default) is used for keys
     *
     * @return string
     */
    protected function getKeyColumn(): string
    {
        if ($this->keys_are_unique_column) {
            $column = static::getUniqueColumn();

            if (!$column) {
                throw new OutOfBoundsException(tr('The DataList type class ":class" is configured to use its unique column as keys, but no unique column has been defined', [
                    ':class' => get_class($this)
                ]));
            }

            return '`' . static::getTable() . '`.`' . $column . '`';
        }

        return '`' . static::getTable() . '`.`id`';
    }


    /**
     * Load the id list from the database
     *
     * @param bool $clear
     * @param bool $only_if_empty
     * @return static
     */
    public function load(bool $clear = true, bool $only_if_empty = false): static
    {
        $this->selectQuery();

        if (!empty($this->source)) {
            if (!$only_if_empty) {
                if (!$clear) {
                    $this->source = array_merge($this->source, sql(static::getDefaultConnectorName())->listKeyValues($this->query, $this->execute, $this->keys_are_unique_column ? static::getUniqueColumn() : static::getIdColumn()));
                }
            }

            return $this;
        }

        $this->source = sql(static::getDefaultConnectorName())->listKeyValues($this->query, $this->execute, $this->keys_are_unique_column ? static::getUniqueColumn() : static::getIdColumn());
        return $this;
    }


    /**
     * This method will load ALL database entries into this object
     *
     * @return $this
     */
    public function loadAll(): static
    {
        $this->source = sql(static::getDefaultConnectorName())->listKeyValues('SELECT ' . static::getKeyColumn() . ' AS `unique_identifier`, `' . static::getTable() . '`.*
                                                                                     FROM  `' . static::getTable() . '`');
        return $this;
    }


    /**
     * Adds the specified source to the internal source
     *
     * @param IteratorInterface|array|string|null $source
     * @return $this
     */
    public function addSources(IteratorInterface|array|string|null $source): static
    {
        return parent::addSources($source);
    }


    /**
     * Sets the parent
     *
     * @param DataEntryInterface $parent
     * @return static
     */
    public function setParent(DataEntryInterface $parent): static
    {
        // Clear the source to avoid having a parent with the wrong children
        $this->source = [];
        return $this->__setParent($parent);
    }


    /**
     * Returns an array of
     *
     * @param string|null $word
     * @return array
     */
    public function autoCompleteFind(?string $word = null): array
    {
        return sql(static::getDefaultConnectorName())->listKeyValue('SELECT `id`, `' . static::getUniqueColumn() . '`
                                                                           FROM   `' . static::getTable() . '`'
                                                              . ($word ? ' WHERE  `' . static::getUniqueColumn() . '` LIKE :like' : null) . '
                                                                           LIMIT   ' . CliAutoComplete::getLimit(),
            $word ? [':like' => $word. '%'] : null);
    }
}