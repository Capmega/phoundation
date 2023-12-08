<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry;

use Phoundation\Cli\Cli;
use Phoundation\Core\Meta\Meta;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Phoundation\Data\DataEntry\Interfaces\ListOperationsInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\DataParent;
use Phoundation\Data\Traits\DataReadonly;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\HtmlDataTable;
use Phoundation\Web\Html\Components\HtmlTable;
use Phoundation\Web\Html\Components\Input\InputSelect;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Phoundation\Web\Html\Components\Interfaces\HtmlDataTableInterface;
use Phoundation\Web\Html\Components\Interfaces\HtmlTableInterface;
use Phoundation\Web\Html\Enums\TableIdColumn;
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
abstract class DataList extends Iterator implements DataListInterface
{
    use DataReadonly;
    use DataParent {
        setParent as setParentTrait;
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
    protected ?array $execute;

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
     * Tracks if entries are stored by id or unique field
     *
     * @var bool $store_with_unique_field
     */
    protected bool $store_with_unique_field = false;


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
     * DataList class constructor
     */
    public function __construct(?array $ids = null)
    {
        parent::__construct();

        if ($ids) {
            $this->load();
        }
    }


    /**
     * Returns a new DataList object
     */
    public static function new(?array $ids = null): static
    {
        return new static($ids);
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
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    abstract public static function getUniqueField(): ?string;


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
    public function getStoreWithUniqueField(): bool
    {
        return $this->store_with_unique_field;
    }


    /**
     * Sets if the DataEntry entries are stored with ID or key
     *
     * @param bool $store_with_unique_field
     * @return static
     */
    public function setStoreWithUniqueField(bool $store_with_unique_field): static
    {
        $this->store_with_unique_field = $store_with_unique_field;
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
     * Returns the schema Table object for the table that is the source for this DataList object
     *
     * @return \Phoundation\Databases\Sql\Schema\Table
     */
    public function getTableSchema(): \Phoundation\Databases\Sql\Schema\Table
    {
        return sql()->schema()->table(static::getTable());
    }


    /**
     * Returns the item with the specified identifier
     *
     * @param Stringable|string|float|int $key
     * @param bool $exception
     * @return DataEntry|null
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, bool $exception = false): ?DataEntryInterface
    {
        // Does this entry exist?
        if (!array_key_exists($key, $this->source)) {
            if ($exception) {
                throw new OutOfBoundsException(tr('Key ":key" does not exist in this DataList', [
                    ':key' => $key
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
                ->setSource($this->getSourceColumns($columns))
                ->setCallbacks($this->callbacks)
                ->setTableIdColumn(TableIdColumn::checkbox);
        }

        $this->selectQuery();

        // Create and return the table
        return HtmlTable::new()
            ->setId(static::getTable())
            ->setSourceQuery($this->query, $this->execute)
            ->setCallbacks($this->callbacks)
            ->setTableIdColumn(TableIdColumn::checkbox);
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
                ->setSource($this->getSourceColumns($columns))
                ->setCallbacks($this->callbacks)
                ->setTableIdColumn(TableIdColumn::checkbox);
        }

        $this->selectQuery();

        // Create and return the table
        return HtmlDataTable::new()
            ->setId(static::getTable())
            ->setSourceQuery($this->query, $this->execute)
            ->setCallbacks($this->callbacks)
            ->setTableIdColumn(TableIdColumn::checkbox);
    }


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @param string|null $order
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', string $key_column = 'id', ?string $order = null): InputSelectInterface
    {
        $select = InputSelect::new();

        if ($this->is_loaded or count($this->source)) {
            // Data was either loaded from DB or manually added. $value_column may contain query parts, strip em.
            $value_column = trim($value_column);
            $value_column = Strings::fromReverse($value_column, ' ');
            $value_column = str_replace('`', '', $value_column);
            $select->setSource($this->getSourceColumn($value_column));

        } else {
            $query = 'SELECT `' . $key_column . '`, ' . $value_column . ' 
                      FROM   `' . static::getTable() . '` 
                      WHERE  `status` IS NULL';

            if ($order === null) {
                // Default order by the value column. Value column may have SQL, make sure its stripped
                $order = Strings::fromReverse($value_column, ' ') . ' ASC';
            }

            // Only order if an order column has been specified
            if ($order) {
                $query .= ' ORDER BY ' . $order;
            }

            // No data was loaded from DB or manually added
            $select->setSourceQuery($query);
        }

        return $select;
    }


    /**
     * Creates and returns a CLI table for the data in this list
     *
     * @param array|null $columns
     * @param array $filters
     * @param string|null $id_column
     * @return void
     */
    public function CliDisplayTable(?array $columns = null, array $filters = [], ?string $id_column = 'id'): void
    {
        // If this list is empty then load data from query, else show list contents
        if (empty($this->source)) {
            $this->selectQuery();
            $this->source = sql()->list($this->query, $this->execute);
        }

        Cli::displayTable($this->source, $columns, $id_column);
    }


    /**
     * Set the specified status for the specified entries
     *
     * @param string|null $status
     * @param string|null $comments
     * @param bool $meta_enabled
     * @return int
     */
    public function updateStatusAll(?string $status, ?string $comments = null, bool $meta_enabled = true): int
    {
        foreach ($this->source as $entry) {
            sql()->dataEntrySetStatus($status, static::getTable(), $entry, $comments, $meta_enabled);
        }

        return count($this->source);
    }


    /**
     * Delete all the entries in this list
     *
     * @param string|null $comments
     * @return int
     */
    public function deleteAll(?string $comments = null): int
    {
        return $this->updateStatusAll('deleted', $comments);
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
     * @return int
     */
    public function eraseAll(): int
    {
        $ids  = [];
        $meta = [];

        // Delete the meta data entries
        foreach ($this->source as $id => $entry) {
            $ids[] = $id;

            if (is_array($entry)) {
                $meta[] = $entry['meta_id'];

            } elseif ($entry instanceof DataEntry) {
                $meta[] = $entry->getMetaId();
            }
        }

        Meta::eraseEntries($meta);

        // Delete the entries themselves
        $ids = Sql::in($ids);
        return sql()->delete(static::getTable(), ' `id` IN (' . Sql::inColumns($ids) . ')', $ids);
    }


    /**
     * Undelete the specified entries
     *
     * @note This will set the status "NULL" to the entries in this datalist, NOT the original value of their status!
     * @param string|null $comments
     * @return int
     */
    public function undeleteAll(?string $comments = null): int
    {
        return $this->updateStatusAll(null, $comments);
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
            $in = Sql::in($identifiers);

            return sql()->list('SELECT `id` 
                                  FROM   `' . static::getTable() . '` 
                                  WHERE  `' . static::getUniqueField() . '` IN (' . implode(', ', array_keys($in)) . ')', $in);
        }

        return [];
    }


    /**
     * Add the specified data entry to the data list
     *
     * @param mixed $value
     * @param Stringable|string|float|int|null $key
     * @param bool $skip_null
     * @return static
     */
    public function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null = true): static
    {
        if (!$value instanceof DataEntryInterface) {
            // Value might be NULL if we skip NULLs?
            if (($value !== null) or !$skip_null) {

                throw new OutOfBoundsException(tr('Cannot add specified value ":value" it must be an instance of DataEntryInterface', [
                    ':value' => $value
                ]));
            }
        }

        if ($this->store_with_unique_field) {
            if ($key and ($key != $value->getUniqueFieldValue())) {
                // Key must either not be specified or match the id of the DataEntry
                throw new OutOfBoundsException(tr('Cannot add ":type" type DataEntry with id ":id", the specified key ":key" must either match the id or be null', [
                    ':type' => $value::getDataEntryName(),
                    ':id'   => $value->getId(),
                    ':key'  => $key
                ]));
            }

            $key = $value->getUniqueFieldValue();

        } else {
            if ($key and ($key != $value->getId())) {
                // Key must either not be specified or match the id of the DataEntry
                throw new OutOfBoundsException(tr('Cannot add ":type" type DataEntry with id ":id", the specified key ":key" must either match the id or be null', [
                    ':type' => $value::getDataEntryName(),
                    ':id'   => $value->getId(),
                    ':key'  => $key
                ]));
            }

            $key = $value->getId();
        }


        return parent::add($value, $key, $skip_null);
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
    #[ReturnTypeWillChange] public function getFirst(): ?DataEntryInterface
    {
        return $this->ensureDataEntry(array_key_first($this->source));
    }


    /**
     * Returns the last element contained in this object without changing the internal pointer
     *
     * @return DataEntryInterface|null
     */
    #[ReturnTypeWillChange] public function getLast(): ?DataEntryInterface
    {
        return $this->ensureDataEntry(array_key_last($this->source));
    }


    /**
     * Ensure the entry we're going to return is from DataEntryInterface interface
     *
     * @param string|float|int $key
     * @return DataEntryInterface
     */
    protected function ensureDataEntry(string|float|int $key): DataEntryInterface
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
     */
    protected function selectQuery(): void
    {
        // Use the default html_query and html_execute or QueryBuilder html_query and html_execute?
        if (isset($this->query_builder)) {
            $this->query   = $this->query_builder->getQuery();
            $this->execute = $this->query_builder->getExecute();

        } elseif (!isset($this->query)) {
            throw new OutOfBoundsException(tr('Cannot generate HMTL table for ":class", no html query specified', [
                ':class' => get_class($this)
            ]));
        }
    }


    /**
     * Load the id list from the database
     *
     * @param bool $clear
     * @return static
     */
    public function load(bool $clear = true): static
    {
        $this->selectQuery();

        if ($clear or empty($this->source)) {
            $this->source = sql()->listKeyValues($this->query, $this->execute);

        } else {
            $this->source = array_merge($this->source, sql()->listKeyValues($this->query, $this->execute));
        }

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
     * Returns the total amounts for all columns together
     *
     * @note This specific method will just return a row with empty values. Its up to the classes implementing DataList
     *       to override this method and return meaningful totals.
     *
     * @param array|string $columns
     * @return array
     */
    public function getTotals(array|string $columns): array
    {
        return array_combine($columns, array_fill(0, count($columns), null));
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
        return $this->setParentTrait($parent);
    }
}
