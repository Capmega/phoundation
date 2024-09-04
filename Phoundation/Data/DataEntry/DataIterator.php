<?php

/**
 * Class DataIterator
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry;

use Phoundation\Cli\CliAutoComplete;
use Phoundation\Core\Meta\Meta;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Interfaces\DataIteratorInterface;
use Phoundation\Data\DataEntry\Interfaces\ListOperationsInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataConfigPath;
use Phoundation\Data\Traits\TraitDataDebug;
use Phoundation\Data\Traits\TraitDataMetaEnabled;
use Phoundation\Data\Traits\TraitDataReadonly;
use Phoundation\Data\Traits\TraitDataRestrictions;
use Phoundation\Data\Traits\TraitMethodBuildManualQuery;
use Phoundation\Databases\Connectors\Connector;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Databases\Sql\SqlQueries;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Config;
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


class DataIterator extends Iterator implements DataIteratorInterface
{
    use TraitDataConfigPath;
    use TraitDataDebug;
    use TraitDataReadonly;
    use TraitDataMetaEnabled;
    use TraitMethodBuildManualQuery;


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
     * If true it means that this data list has data loaded from a database
     *
     * @var bool $is_loaded
     */
    protected bool $is_loaded = false;

    /**
     * If set, use the query generated by this builder instead of the default query
     *
     * @var QueryBuilderInterface
     */
    protected QueryBuilderInterface $query_builder;

    /**
     * Tracks if entries are stored by id or unique column
     *
     * @var bool $keys_are_unique_column
     */
    protected bool $keys_are_unique_column = true;

    /**
     * Tracks the class used to generate the select input
     *
     * @var string $input_select_class
     */
    protected string $input_select_class = InputSelect::class;

    /**
     * Tracks what SQL columns will be used in loading data
     *
     * @note Defaults to ' `' . static::getTable() . '`.* '
     * @var string|null $sql_columns
     */
    protected ?string $sql_columns = null;

    /**
     * Tracks if this list requires a parent, or not
     *
     * @var bool $require_parent
     */
    protected bool $require_parent = false;


    /**
     * Returns true if the ID column is the specified column
     *
     * @param string $column
     *
     * @return bool
     */
    public static function uniqueColumnIs(string $column): bool
    {
        return (static::getUniqueColumn() ?? static::getIdColumn()) === $column;
    }


    /**
     * Returns the column that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return static::getIdColumn();
    }


    /**
     * Returns the column that is the ID column for this object. Typically this is "id" but may be changed as needed by
     * overriding this method
     *
     * @return string|null
     */
    public static function getIdColumn(): ?string
    {
        return 'id';
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
     * Return the object source contents in JSON string format
     *
     * @return string
     */
    public function __toString(): string
    {
        return Json::encode($this->source);
    }


    /**
     * Returns if the specified data entry key exists in the data list
     *
     * @param DataEntryInterface|Stringable|string|float|int $key
     *
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
     * Set the query for this object when generating internal content
     *
     * @param string     $query
     * @param array|null $execute
     *
     * @return static
     */
    public function setQuery(string $query, ?array $execute = null): static
    {
        $this->query   = $query;
        $this->execute = $execute;

        return $this;
    }


    /**
     * Selects if we use the default query or a query from the QueryBuilder
     *
     * @param array|null $identifiers
     *
     * @return void
     */
    protected function selectQuery(?array $identifiers = null): void
    {
        // Use the default html_query and html_execute or QueryBuilder html_query and html_execute?
        if (isset($this->query_builder)) {
            $this->query   = $this->query_builder->getQuery();
            $this->execute = $this->query_builder->getExecute();

        } elseif (!isset($this->query) or $identifiers) {
            // Define default identifiers
            if ($identifiers === null) {
                $identifiers = ['status' => null];
            }

            // Create query with optional filtering for parents_id
            if ($this->parent) {
                $parent_filter = '`' . static::getTable() . '`.`' . Strings::fromReverse($this->parent::getTable(), '_') . '_id` = :parents_id AND ';
                $this->execute['parents_id'] = $this->parent->getId();

            } else {
                $parent_filter = null;
            }

            $this->buildManualQuery($identifiers, $where, $joins, $group, $order, $this->execute);

            // Set default query
            $this->query = 'SELECT                  ' . $this->getSqlColumns() . '
                            FROM                   `' . static::getTable() . '`
                            ' . $joins . '
                            WHERE  ' . $parent_filter .  $where . $group . $order;
        }
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
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return null;
    }


    /**
     * Returns if this Iterator requires a parent or not
     *
     * @return bool
     */
    public function getRequireParent(): bool
    {
        return $this->require_parent;
    }


    /**
     * Sets if this Iterator requires a parent or not
     *
     * @param bool $require_parent
     *
     * @return static
     */
    public function setRequireParent(bool $require_parent): static
    {
        $this->require_parent = $require_parent;
        return $this;
    }


    /**
     * Returns what SQL columns will be used in loading data
     *
     * @return string
     */
    public function getSqlColumns(): string
    {
        return $this->sql_columns ?? ' ' . static::getTableIdColumn() . ' AS `unique_identifier`, `' . static::getTable() . '`.* ';
    }


    /**
     * Sets what SQL columns will be used in loading data
     *
     * @param string|null $columns
     *
     * @return static
     */
    public function setSqlColumns(?string $columns): static
    {
        $this->sql_columns = $columns;
        return $this;
    }


    /**
     * Returns the column that (by default) is used for keys
     *
     * @return string
     */
    protected function getTableIdColumn(): string
    {
        return '`' . static::getTable() . '`.`' . (static::getUniqueColumn() ?? static::getIdColumn()) . '`';
    }


    /**
     * Returns the entry with the specified identifier
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return DataEntry|null
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, bool $exception = true): ?DataEntryInterface
    {
        // Does this entry exist?
        if (array_key_exists($key, $this->source)) {
            return $this->ensureObject($key);
        }

        if ($exception) {
            throw new NotExistsException(tr('Key ":key" does not exist in this ":class" DataIterator', [
                ':key'   => $key,
                ':class' => get_class($this),
            ]));
        }

        return null;
    }


    /**
     * Returns the random entry
     *
     * @return DataEntry|null
     */
    #[ReturnTypeWillChange] public function getRandom(): ?DataEntryInterface
    {
        if (empty($this->source)) {
            return null;
        }

        return $this->ensureObject(array_rand($this->source, 1));
    }


    /**
     * Returns the data types that are allowed and accepted for this data iterator
     *
     * @return string|null
     */
    public static function getDefaultContentDataType(): ?string
    {
        return DataEntry::class;
    }


    /**
     * Sets the value for the specified key
     *
     * @param DataEntryInterface          $value
     * @param Stringable|string|float|int $key
     * @param bool                        $skip_null_values
     *
     * @return static
     */
    public function set(mixed $value, Stringable|string|float|int $key, bool $skip_null_values = true): static
    {
        if ($value instanceof DataEntryInterface) {
            return parent::set($key, $value);
        }

        throw new OutOfBoundsException(tr('Cannot set value ":value" to key ":key" in the list ":list", it does not have a DataEntryInterface', [
            ':list'  => get_class($this),
            ':key'   => $key,
            ':value' => $value,
        ]));
    }


    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @param array|string|null $columns
     *
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
                        ->setConnector(static::getConnector())
                        ->setId(static::getTable())
                        ->setSourceQuery($this->query, $this->execute)
                        ->setCallbacks($this->callbacks)
                        ->setCheckboxSelectors(EnumTableIdColumn::checkbox);
    }


    /**
     * Returns the default database connector to use for this table
     *
     * @return string
     */
    public static function getConnector(): string
    {
        return 'system';
    }


    /**
     * Returns a database connector for this DataEntry object
     *
     * @return ConnectorInterface
     */
    public static function getConnectorObject(): ConnectorInterface
    {
        return new Connector(static::getConnector());
    }


    /**
     * Creates and returns a fancy HTML data table for the data in this list
     *
     * @param array|string|null $columns
     *
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
                            ->setConnector(static::getConnector())
                            ->setId(static::getTable())
                            ->setSourceQuery($this->query, $this->execute)
                            ->setCallbacks($this->callbacks)
                            ->setCheckboxSelectors(EnumTableIdColumn::checkbox);
    }


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
    public function getHtmlSelect(string $value_column = 'name', ?string $key_column = null, ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface
    {
        $select  = $this->input_select_class::new();
        $execute = [];

        if (!$key_column) {
            $key_column = static::getUniqueColumn();
        }

        if ($this->is_loaded or count($this->source)) {
            // Data was either loaded from DB or manually added. $value_column may contain query parts, strip em.
            $value_column = trim($value_column);
            $value_column = Strings::fromReverse($value_column, ' ');
            $value_column = str_replace('`', '', $value_column);

            $select->setSource($this->getAllRowsSingleColumn($value_column, true));

        } else {
            $query = 'SELECT ' . $key_column . ', ' . $value_column . ' 
                      FROM  `' . static::getTable() . '` 
                      ' . Strings::force($joins, ' ');

            if ($filters) {
                $where = [];

                foreach ($filters as $key => $value) {
                    if (str_contains($key, '.')) {
                        $key = Strings::ensureSurroundedWith($key, '`');

                    } else {
                        $key = '`' . static::getTable() . '`.' . Strings::ensureSurroundedWith($key, '`');
                    }

                    $where[] = SqlQueries::is($key, $value, 'value', $execute);
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
            $select->setDebug($this->debug)
                   ->setConnector(static::getConnectorObject())
                   ->setSourceQuery($query, $execute);
        }

        return $select;
    }


    /**
     * Creates and returns a CLI table for the data in this list
     *
     * @param array|string|null $columns
     * @param array             $filters
     * @param string|null       $id_column
     *
     * @return static
     */
    public function displayCliTable(array|string|null $columns = null, array $filters = [], ?string $id_column = 'id'): static
    {
        // If this list is empty, then load data from a query, else show list contents
        if (empty($this->source)) {
            $this->selectQuery();
            $this->source = sql(static::getConnectorObject())->setDebug($this->debug)
                                                             ->list($this->query, $this->execute);
        }

        return parent::displayCliTable($columns, $filters, $id_column);
    }


    /**
     * Delete all the entries in this list
     *
     * @param string|null $comments
     *
     * @return int
     */
    public function delete(?string $comments = null): int
    {
        return $this->setStatus('deleted', $comments);
    }


    /**
     * Set the specified status for the specified entries
     *
     * @param string|null $status
     * @param string|null $comments
     *
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
            sql(static::getConnectorObject())->setDebug($this->debug)
                                             ->erase(static::getTable(), ['id' => $ids]);
        }

        return $this;
    }


    /**
     * This method will load ALL database entries into this object
     *
     * @return static
     */
    public function loadAll(): static
    {
        $this->source = sql(static::getConnectorObject())->setDebug($this->debug)
                                                         ->listKeyValues('SELECT ' . static::getTableIdColumn() . ' AS `unique_identifier`, `' . static::getTable() . '`.*
                                                                                FROM  `' . static::getTable() . '`');

        return $this;
    }


    /**
     * Undelete the specified entries
     *
     * @note This will set the status "NULL" to the entries in this datalist, NOT the original value of their status!
     *
     * @param string|null $comments
     *
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
     *
     * @return array
     */
    public function listIds(array $identifiers): array
    {
        if ($identifiers) {
            $in = SqlQueries::in($identifiers);

            return sql(static::getConnectorObject())->setDebug($this->debug)
                                                    ->list('SELECT `id` 
                                                            FROM   `' . static::getTable() . '` 
                                                            WHERE  `' . static::getUniqueColumn() . '` IN (' . implode(', ', array_keys($in)) . ')', $in);
        }

        return [];
    }


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
    public function append(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null_values = true, bool $exception = true): static
    {
        // Skip NULL values?
        if ($value === null) {
            if ($skip_null_values) {
                return $this;
            }
        }

        if (!$value instanceof DataEntryInterface) {
            // Value might be NULL if we skip NULLs?
            if (($value !== null) or !$skip_null_values) {
                if (is_data_scalar($value)) {
                    // Try to load the specified value from the database, assuming $value is the unique identifier
                    try {
                        $value = static::getDefaultContentDataType()::load($value);

                    } catch (DataEntryNotExistsException) {
                        throw new OutOfBoundsException(tr('Cannot add specified value ":value" it must be an instance of DataEntryInterface or a unique identifier value for the class ":class"', [
                            ':value' => $value,
                            ':class' => static::getDefaultContentDataType()
                        ]));
                    }

                    // Now we have a DataEntryInterface type value, we can continue, yay!

                } else {
                    throw new OutOfBoundsException(tr('Cannot add specified value ":value" it must be an instance of DataEntryInterface', [
                        ':value' => $value,
                    ]));
                }
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
                        ':key'    => $key,
                    ]));
                }

                // Either the specified DataEntry object has no value for its unique column, or the unique column
                // matches the specified key. Either way, we're good to go

            } else {
                $key = $value->getUniqueColumnValue();

                if (!$key) {
                    throw new OutOfBoundsException(tr('Cannot add entry ":value" because the ":class" DataIterator object should use unique column ":column" as keys, but has no unique column value', [
                        ':column' => $value->getUniqueColumn(),
                        ':value'  => $value,
                        ':class'  => get_class($this),
                    ]));
                }
            }

        } else {
            if ($key) {
                if (!$value->isNew() and ($key != $value->getId())) {
                    // Key must either not be specified or match the id of the DataEntry
                    throw new OutOfBoundsException(tr('Cannot add ":type" type DataEntry with id ":id", the specified key ":key" must either match the id or be null', [
                        ':type' => $value::getDataEntryName(),
                        ':id'   => $value->getId(),
                        ':key'  => $key,
                    ]));
                }

                // Either the specified DataEntry object is new or the id matches the specified key, we're good to go

            } else {
                $key = $value->getId();
            }
        }

        return parent::append($value, $key, $skip_null_values, $exception);
    }


    /**
     * Clears all entries in this DataIterator, but also all entries in this iterator from the table
     *
     * @note: Clearing means that the entries will NOT be deleted, it will set the status for all entries to "deleted"
     *        and update the unique column to NULL
     *
     * @return static
     */
    public function clearIteratorFromTable(): static
    {
        foreach ($this->source as $entry) {
            $this->ensureObject($entry)
                     ->setStatus('deleted')
                     ->setUniqueColumnValue(null)
                     ->save();
        }

        return parent::clear();
    }


    /**
     * Clears all entries in this DataIterator, but also ALL entries from the table
     *
     * @param string|bool|null $status
     *
     * @return static
     */
    public function clearAllFromTable(string|bool|null $status = false): static
    {
        if ($status === false) {
            $results = sql()->query('SELECT `id` FROM `' . static::getTable() . '`');

        } else {
            $execute = [':status' => $status];
            $results = sql()->query('SELECT `id` FROM `' . static::getTable() . '` WHERE' . SqlQueries::is('status', $status, 'status', $execute), $execute);
        }

        foreach ($results as $entry) {
            $this->ensureObject($entry['id'])
                 ->setStatus('deleted')
                 ->setUniqueColumnValue(null)
                 ->save();
        }

        return parent::clear();
    }


    /**
     * Creates a new DataEntry object and returns it
     *
     * @param array|DataEntryInterface|string|int|null $identifier
     *
     * @return DataEntryInterface
     */
    protected function newObject(array|DataEntryInterface|string|int|null $identifier = null): DataEntryInterface
    {
        return $this->getAcceptedDataType()::new($identifier)
                                           ->setRestrictions($this->restrictions);
    }


    /**
     * Ensure the entry we're going to return is from DataEntryInterface interface
     *
     * @param string|float|int $key
     *
     * @return DataEntryInterface
     */
    #[ReturnTypeWillChange] protected function ensureObject(string|float|int $key): DataEntryInterface
    {
        // Ensure the source key is of DataEntryInterface
        if (!$this->source[$key] instanceof DataEntryInterface) {
            // Okay, interesting problem! When we loaded entries through QueryBuilder, we allowed to use whatever hell
            // columns we wanted with whatever hell datatype. For example, a column that normally would be an integer
            // now might be a string which will make the DataEntry setValue methods crash. To avoid this, we cannot rely
            // on the data available in the datalist, we'll have to load the DataEntry manually
            if (isset($this->query_builder)) {
                // Load the DataEntry separately from the database (will require an extra query)
                $entry = $this->newObject($key);

            } else {
                if (is_array($this->source[$key])) {
                    if (static::uniqueColumnIs('id')) {
                        // Entries are stored with database ID
                        if (!is_numeric($key)) {
                            throw new OutOfBoundsException(tr('Invalid ":class" ID key ":key" encountered. The key should be a numeric database ID', [
                                ':class' => get_class($this),
                                ':key'   => $key,
                            ]));
                        }

                        // Ensure the id key is available in the entry
                        $this->source[$key][$this->getAcceptedDataType()::getIdColumn()] = $key;

                    } else {
                        if (empty($this->source[$key][static::getUniqueColumn()])) {
                            // No database ID available, and entries are not stored by ID so we can't get ID
                            throw new OutOfBoundsException(tr('Cannot ensure DataEntry for key ":key", Iterator source data does not contain the unique id column ":column"', [
                                ':key'    => $key,
                                ':column' => static::getUniqueColumn()
                            ]));
                        }
                    }

                    // Copy the source into the entry
                    $entry = $this->newObject()->setSource($this->source[$key]);

                } else {
                    // Load the entry manually from DB. REQUIRES the DataEntry object to have a unique column specified!
                    $entry = $this->newObject($this->source[$key]);
                }
            }

            if ($entry->isLoadedFromConfiguration()) {
                // Entries loaded from configuration are always readonly
                $entry->setReadonly(true);
            }

            $this->source[$key] = $entry;
        }

        return $this->source[$key];
    }


    /**
     * Returns the current entry
     *
     * @return DataEntry|null
     */
    #[ReturnTypeWillChange] public function current(): ?DataEntryInterface
    {
        return $this->ensureObject(key($this->source));
    }


    /**
     * Returns the first element contained in this object without changing the internal pointer
     *
     * @return DataEntryInterface|null
     */
    #[ReturnTypeWillChange] public function getFirstValue(): ?DataEntryInterface
    {
        return $this->ensureObject(array_key_first($this->source));
    }


    /**
     * Returns the last element contained in this object without changing the internal pointer
     *
     * @return DataEntryInterface|null
     */
    #[ReturnTypeWillChange] public function getLastValue(): ?DataEntryInterface
    {
        return $this->ensureObject(array_key_last($this->source));
    }


    /**
     * Load the id list from the database
     *
     * @param array|null $identifiers
     * @param bool       $clear         Will clear the DataIterator source before loading
     * @param bool       $only_if_empty Will only load if the current DataIterator source is empty
     *
     * @return static
     */
    public function load(?array $identifiers = null, bool $clear = true, bool $only_if_empty = false): static
    {
        $this->selectQuery($identifiers);

        if (!empty($this->source)) {
            if ($clear) {
                $this->source = [];

            } else {
                if (!$only_if_empty) {
                    $this->source = array_merge(
                        $this->source,
                        sql(static::getConnectorObject())->setDebug($this->debug)
                                                         ->listKeyValues(
                                                             $this->query,
                                                             $this->execute,
                                                             static::getUniqueColumn()));
                }
            }

            return $this;
        }

        $this->source = sql(static::getConnectorObject())->setDebug($this->debug)
                                                         ->listKeyValues($this->query, $this->execute);

        if ($this->configuration_path) {
            $this->source = array_merge($this->source, $this->loadFromConfiguration());
        }

        return $this;
    }


    /**
     * Load configuration from the specified configuration path
     *
     * @return array
     */
    protected function loadFromConfiguration(): array
    {
        $source      = Config::getArray(Strings::ensureEndsNotWith($this->configuration_path, '.'), []);
        $entry       = static::getDefaultContentDataType();
        $entry       = new $entry();
        $definitions = $entry->getDefinitionsObject();

        // Ensure all entry definition columns are available, apply default values where they don't
        foreach ($source as $key => &$value) {
            $value['status'] = 'configuration';

            foreach ($definitions as $column => $definition) {
                if (array_key_exists($column, $value)) {
                    continue;
                }

                // Apply the default value for this column
                $value[$column] = $definition->getDefault();
            }
        }

        unset($value);
        return $source;
    }


    /**
     * Adds the specified source to the internal source
     *
     * @param IteratorInterface|array|string|null $source
     * @param bool                                $clear_keys
     * @param bool                                $exception
     *
     * @return static
     */
    public function addSource(IteratorInterface|array|string|null $source, bool $clear_keys = false, bool $exception = true): static
    {
        return parent::addSource($source, $clear_keys, $exception);
    }


    /**
     * Returns an array of
     *
     * @param string|null $word
     *
     * @return array
     */
    public function autoCompleteFind(?string $word = null): array
    {
        return sql(static::getConnectorObject())->setDebug($this->debug)
                                                ->listKeyValue('SELECT `id`, `' . static::getUniqueColumn() . '`
                                                                      FROM   `' . static::getTable() . '`' . ($word ? ' WHERE  `' . static::getUniqueColumn() . '` LIKE :like' : null) . '
                                                                      LIMIT   ' . CliAutoComplete::getLimit(), $word ? [':like' => $word . '%'] : null);
    }


    /**
     * Ensures that all objects in the source are DataEntry objects
     *
     * @return static
     */
    protected function ensureDataEntries(): static
    {
        foreach ($this->source as $key => $value) {
            $this->ensureObject($key);
        }

        return $this;
    }


    /**
     * Will throw an OutOfBoundsException exception if no parent was set for this list
     *
     * @param string $action
     *
     * @return static
     */
    protected function ensureParent(string $action): static
    {
        if (!$this->parent and $this->require_parent) {
            throw new OutOfBoundsException(tr('Cannot ":action", no parent specified', [':action' => $action]));
        }

        return $this;
    }
}
