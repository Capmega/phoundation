<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry;

use Phoundation\Cli\Cli;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Phoundation\Data\Iterator;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Json;
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
abstract class DataList extends Iterator implements DataListInterface
{
    /**
     * The list parent
     *
     * @var DataEntry|null $parent
     */
    protected ?DataEntryInterface $parent;

    /**
     * A list of filters that will filter the list data when being loaded
     *
     * @var array $filters
     */
    protected array $filters = [];

    /**
     * The iterator position
     *
     * @var int $position
     */
    protected int $position = 0;

    /**
     * The class for the items in this list when the item is dynamically created
     *
     * @var string $entry_class
     */
    protected string $entry_class;

    /**
     * The query to display an HTML table for this list
     *
     * @var string $html_query
     */
    protected string $html_query;

    /**
     * The execution array for the HTML query
     *
     * @var array|null $html_execute
     */
    protected ?array $html_execute;

    /**
     * The name of the source table for this DataList
     *
     * @var string
     */
    protected static string $table;

    /**
     * The unique column identifier, next to id
     *
     * @var string $unique_column
     */
    protected string $unique_column = 'seo_name';


    /**
     * DataList class constructor
     *
     * @param DataEntry|null $parent
     * @param string|null $id_column
     */
    public function __construct(?DataEntryInterface $parent = null, ?string $id_column = null)
    {
        // Validate the entry class
        if (empty($this->entry_class)) {
            throw new OutOfBoundsException(tr('DataList class has not yet been set. The class should contain some DataEntry::class compatible class name'));
        }

        if (!is_subclass_of($this->entry_class, DataEntryInterface::class)) {
            throw new OutOfBoundsException(tr('Specified entry_class is invalid. The class should be a sub class of DataEntry::class but is a ":is"', [
                ':is' => $this->entry_class
            ]));
        }

        $this->parent = $parent;
        $this->load($id_column);
    }


    /**
     * Return the object contents in JSON string format
     *
     * @return string
     */
    public function __toString(): string
    {
        return Json::encode($this);
    }


    /**
     * Returns new DataList object with an optional parent
     *
     * @param DataEntry|null $parent
     * @param string|null $id_column
     * @return static
     */
    public static function new(?DataEntryInterface $parent = null, ?string $id_column = null): static
    {
        return new static($parent, $id_column);
    }


    /**
     * Returns if the specified data entry exists in the data list
     *
     * @param Stringable|string|float|int $key
     * @return bool
     */
    public function exists(Stringable|string|float|int $key): bool
    {
        if (is_integer($key)) {
            return array_key_exists($key, $this->list);
        }

        return array_key_exists($key->getId(), $this->list);
    }


    /**
     * Returns a list of items that are specified, but not available in this DataList
     *
     * @param DataListInterface|array|string $list
     * @param string|null $always_match
     * @return array
     */
    public function missesKeys(DataListInterface|array|string $list, string $always_match = null): array
    {
        if (is_string($list)) {
            $list = explode(',', $list);
        }

        $return = [];

        foreach ($list as $entry) {
            if (array_key_exists($entry, $this->list)) {
                continue;
            }

            // Can still match if $always_match is available!
            if ($always_match and array_key_exists($always_match, $this->list)) {
                // Okay, this list contains ALL the requested entries due to $always_match
                return [];
            }

            $return[] = $entry;
        }

        return $return;
    }


    /**
     * Returns if all (or optionally any) of the specified entries are in this list
     *
     * @param DataListInterface|array|string $list
     * @param bool $all
     * @param string|null $always_match
     * @return bool
     */
    public function containsKey(DataListInterface|array|string $list, bool $all = true, string $always_match = null): bool
    {
        if (is_string($list)) {
            $list = explode(',', $list);
        }

        foreach ($list as $entry) {
            if (!array_key_exists($entry, $this->list)) {
                if ($all) {
                    // All need to be in the array, but we found one missing.
                    // Can still match if $always_match is available!
                    if ($always_match and array_key_exists($always_match, $this->list)) {
                        // Okay, this list contains ALL the requested entries due to $always_match
                        return true;
                    }

                    return false;
                }
            } else {
                if (!$all) {
                    // only one needs to be in the array, we found one, we're good!
                    return true;
                }
            }
        }

        // All were in the array
        return true;
    }


    /**
     * Returns if all (or optionally any) of the specified entries are in this list
     *
     * @param DataListInterface|array|string $list
     * @param bool $all
     * @param string|null $always_match
     * @return bool
     */
    public function containsValue(DataListInterface|array|string $list, bool $all = true, string $always_match = null): bool
    {
        if (is_string($list)) {
            $list = explode(',', $list);
        }

        foreach ($list as $entry) {
            if (!in_array($entry, $this->list)) {
                if ($all) {
                    // All need to be in the array, but we found one missing.
                    // Can still match if $always_match is available!
                    if ($always_match and in_array($always_match, $this->list)) {
                        // Okay, this list contains ALL the requested entries due to $always_match
                        return true;
                    }

                    return false;
                }
            } else {
                if (!$all) {
                    // only one needs to be in the array, we found one, we're good!
                    return true;
                }
            }
        }

        // All were in the array
        return true;
    }


    /**
     * Returns the entire internal list
     *
     * @return array
     */
    public function list(): array
    {
        return array_flip($this->list);
    }


    /**
     * Returns the internal list filtered by the specified keyword
     *
     * @param string|null $keyword
     * @return array
     */
    public function filteredList(?string $keyword): array
    {
        $return = [];
        $keyword = strtolower((string) $keyword);

        foreach ($this->list() as $value) {
            if (!$keyword or str_contains(strtolower(trim($value)), $keyword)) {
                $return[] = $value;
            }
        }

        return $return;
    }


    /**
     * Returns the list of internal ID's
     *
     * @return array
     */
    public function idList(): array
    {
        return array_keys($this->list);
    }


    /**
     * Returns all configured filters to apply when loading the data list
     *
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }


    /**
     * Set the query for this object when shown as HTML table
     *
     * @param string $query
     * @param array|null $execute
     * @return static
     */
    public function setHtmlQuery(string $query, ?array $execute = null): static
    {
        $this->html_query   = $query;
        $this->html_execute = $execute;

        return $this;
    }


    /**
     * Returns the query for this object when shown as HTML table
     *
     * @return string
     */
    public function getHtmlQuery(): string
    {
        return $this->html_query;
    }


    /**
     * Returns the table name that is the source for this DataList object
     *
     * @return string
     */
    public function getTable(): string
    {
        return self::$table;
    }


    /**
     * Add a filter to apply when loading the data list
     *
     * @param string $key
     * @return static
     */
    public function removeFilter(string $key): static
    {
        unset($this->filters[$key]);
        return $this;
    }


    /**
     * Returns the schema Table object for the table that is the source for this DataList object
     *
     * @return \Phoundation\Databases\Sql\Schema\Table
     */
    public function getTableSchema(): \Phoundation\Databases\Sql\Schema\Table
    {
        return sql()->schema()->table(self::$table);
    }


    /**
     * Clears multiple filters to apply when loading the data list
     *
     * @return static
     */
    public function clearFilters(): static
    {
        $this->filters = [];
        return $this;
    }


    /**
     * Set multiple filters to apply when loading the data list
     *
     * @note This will clear all already defined filters
     * @param array $filters
     * @return static
     */
    public function setFilters(array $filters): static
    {
        $this->filters = [];
        return $this->addFilters($filters);
    }


    /**
     * Add multiple filters to apply when loading the data list
     *
     * @param array $filters
     * @return static
     */
    public function addFilters(array $filters): static
    {
        foreach ($filters as $key => $value) {
            $this->addFilter($key, $value);
        }

        return $this;
    }


    /**
     * Add a filter to apply when loading the data list
     *
     * @param string $key
     * @param array|string|int|null $value
     * @return static
     */
    public function addFilter(string $key, array|string|int|null $value): static
    {
        if ($value !== null) {
            if ($key === 'status') {
                $this->filters[$key] = get_null($value);
            } else {
                $this->filters[$key] = $value;
            }
        }

        return $this;
    }


    /**
     * Returns the item with the specified identifier
     *
     * @param Stringable|string|float|int $key
     * @param bool $exception
     * @return DataEntry|null
     */
    #[ReturnTypeWillChange] function get(Stringable|string|float|int $key, bool $exception = false): ?DataEntryInterface
    {
        // Does this entry exist?
        if (!array_key_exists($key, $this->list)) {
            throw new OutOfBoundsException(tr('Key ":key" does not exist in this DataList', [
                ':key' => $key
            ]));
        }

        // Is this entry loaded?
        if (!is_object($this->list[$key])) {
            $this->list[$key] = $this->entry_class::get($key);
        }

        return $this->list[$key];
    }


    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @return Table
     */
    public function getHtmlTable(): Table
    {
        if (!isset($this->html_query)) {
            throw new OutOfBoundsException(tr('Cannot generate HMTL table for ":class", no html query specified', [
                ':class' => get_class($this)
            ]));
        }

        // Create and return the table
        return Table::new()
            ->setSourceQuery($this->html_query, $this->html_execute)
            ->setCheckboxSelectors(true);
    }


    /**
     * Creates and returns a fancy HTML data table for the data in this list
     *
     * @return DataTable
     */
    public function getHtmlDataTable(): DataTable
    {
        if (!isset($this->html_query)) {
            throw new OutOfBoundsException(tr('Cannot generate HMTL data table for ":class", no html query specified', [
                ':class' => get_class($this)
            ]));
        }

        // Create and return the table
        return DataTable::new()
            ->setId(self::$table)
            ->setSourceQuery($this->html_query, $this->html_execute)
            ->setCheckboxSelectors(true);
    }


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @return SelectInterface
     */
    abstract public function getHtmlSelect(): SelectInterface;


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
        $list = $this->loadDetails($columns, $filters);
        Cli::displayTable($list, $columns, $id_column);
    }


    /**
     * Set the specified status for the specified entries
     *
     * @param string|null $status
     * @param array $entries
     * @param string|null $comments
     * @return int
     */
    public function setStatus(?string $status, array $entries, ?string $comments = null): int
    {
        return sql()->setStatus($status, self::$table, $entries, $comments);
    }


    /**
     * Delete the specified entries
     *
     * @param array $entries
     * @param string|null $comments
     * @return int
     */
    public function dbDelete(array $entries, ?string $comments = null): int
    {
showdie('$entries IS IN CORRECT HERE, AS SQL EXPECTS IT, IT SHOULD BE AN ARRAY FOR A SINGLE ROW!');
        return $this->setStatus('deleted', $entries, $comments);
    }


    /**
     * Undelete the specified entries
     *
     * @param array $entries
     * @param string|null $comments
     * @return int
     */
    public function dbUndelete(array $entries, ?string $comments = null): int
    {
showdie('$entries IS IN CORRECT HERE, AS SQL EXPECTS IT, IT SHOULD BE AN ARRAY FOR A SINGLE ROW!');
        return $this->setStatus(null, $entries, $comments);
    }


    /**
     * Returns an array with all id's for the specified entry identifiers
     *
     * @param array $identifiers
     * @return array
     */
    public function listIds(array $identifiers): array
    {
        $in = Sql::in($identifiers);

        return sql()->list('SELECT `id` 
                                  FROM   `' . self::$table . '` 
                                  WHERE  `' . $this->unique_column . '` IN (' . implode(', ', array_keys($in)) . ')', $in);
    }


//    /**
//     * Erase the specified entries
//     *
//     * @param array $entries
//     * @return int
//     */
//    public function erase(array $entries): int
//    {
//        return sql()->erase(self::$table_name, $entries);
//    }


    /**
     * Add the specified data entry to the data list
     *
     * @param DataEntry|null $entry
     * @return static
     */
    protected function addEntry(?DataEntryInterface $entry): static
    {
        if ($entry) {
            $this->list[$entry->getId()] = $entry;
        }

        return $this;
    }


    /**
     * Remove the specified data entry from the data list
     *
     * @param DataEntry|int|null $entry
     * @return static
     */
    protected function removeEntry(DataEntryInterface|int|null $entry): static
    {
        if ($entry) {
            if (is_object($entry)) {
                $entry = $entry->getId();
            }

            unset($this->list[$entry]);
        }

        return $this;
    }


    /**
     * Remove all the entries from the DataList
     *
     * @return static
     */
    protected function clearEntries(): static
    {
        $this->list = [];
        return $this;
    }


    /**
     * If the list has not yet loaded its content, do so now
     *
     * @return void
     */
    protected function ensureLoaded(): void
    {
        if (!isset($this->list)) {
            $this->load();
        }
    }


    /**
     * Will throw an OutOfBoundsException exception if no parent was set for this list
     *
     * @param string $action
     * @return void
     */
    protected function ensureParent(string $action): void
    {
        if (!$this->parent) {
            throw new OutOfBoundsException(tr('Cannot ":action", no parent specified', [':action' => $action]));
        }
    }


    /**
     * Returns the current item
     *
     * @return DataEntry|null
     */
    #[ReturnTypeWillChange] public function current(): ?DataEntryInterface
    {
        return $this->get(key($this->list));
    }


    /**
     * Load the id list from database
     *
     * @param string|null $id_column
     * @return static
     */
    abstract protected function load(?string $id_column = null): static;


    /**
     * Load the data list elements from database
     *
     * @param array|string|null $columns
     * @param array $filters
     * @param array $order_by
     * @return array
     */
    abstract protected function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array;


    /**
     * Save the data list elements to database
     *
     * @return static
     */
    abstract public function save(): static;
}