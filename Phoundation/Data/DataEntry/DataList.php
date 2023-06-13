<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry;

use Phoundation\Cli\Cli;
use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\Html\Components\DataTable;
use Phoundation\Web\Http\Html\Components\Table;
use ReturnTypeWillChange;


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
abstract class DataList implements DataListInterface
{
    /**
     * The list parent
     *
     * @var DataEntry|null $parent
     */
    protected ?DataEntry $parent;

    /**
     * The data list
     *
     * @var array $list
     */
    protected array $list;

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
     * The execute array for the HTML query
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
     * @param InterfaceDataEntry|null $parent
     * @param string|null $id_column
     */
    public function __construct(?InterfaceDataEntry $parent = null, ?string $id_column = null)
    {
        // Validate the entry class
        if (isset($this->entry_class)) {
            if (!is_subclass_of($this->entry_class, DataEntry::class)) {
                throw new OutOfBoundsException(tr('Specified entry_class is invalid. The class should be a sub class of DataEntry::class but is a ":class"', [
                    ':class' => $this->entry_class
                ]));
            }
        } else {
            throw new OutOfBoundsException(tr('DataList class has not yet been set. The class should contain some DataEntry::class compatible class name'));
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
     * Return the object contents in array format
     *
     * @return array
     */
    public function __toArray(): array
    {
        return $this->list;
    }


    /**
     * Returns new DataList object with an optional parent
     *
     * @param InterfaceDataEntry|null $parent
     * @param string|null $id_column
     * @return static
     */
    public static function new(?InterfaceDataEntry $parent = null, ?string $id_column = null): static
    {
        return new static($parent, $id_column);
    }


    /**
     * Returns the amount of items in this list
     *
     * @return int
     */
    public function getCount(): int
    {
        return count($this->list);
    }


    /**
     * Returns if the specified data entry exists in the data list
     *
     * @param InterfaceDataEntry|int $entry
     * @return bool
     */
    public function exists(InterfaceDataEntry|int $entry): bool
    {
        if (is_integer($entry)) {
            return array_key_exists($entry, $this->list);
        }

        return array_key_exists($entry->getId(), $this->list);
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
     * @param int $identifier
     * @return InterfaceDataEntry|null
     */
    #[ReturnTypeWillChange] public function get(int $identifier): ?InterfaceDataEntry
    {
        // Does this entry exist?
        if (!array_key_exists($identifier, $this->list)) {
            throw new OutOfBoundsException(tr('Key ":key" does not exist in this DataList', [
                ':key' => $identifier
            ]));
        }

        // Is this entry loaded?
        if (!is_object($this->list[$identifier])) {
            $this->list[$identifier] = $this->entry_class::get($identifier);
        }

        return $this->list[$identifier];
    }


    /**
     * Returns the current item
     *
     * @return InterfaceDataEntry|null
     */
    #[ReturnTypeWillChange] public function current(): ?InterfaceDataEntry
    {
        return $this->get(key($this->list));
    }


    /**
     * Jumps to the next element
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function next(): static
    {
        next($this->list);
        return $this;
    }


    /**
     * Jumps to the next element
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function previous(): static
    {
        prev($this->list);
        return $this;
    }


    /**
     * Returns the current iterator position
     *
     * @return int
     */
    public function key(): int
    {
        return key($this->list);
    }


    /**
     * Returns if the current element exists or not
     *
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->list[key($this->list)]);
    }


    /**
     * Rewinds the internal pointer to 0
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function rewind(): static
    {
        reset($this->list);
        return $this;
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
    public function delete(array $entries, ?string $comments = null): int
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
    public function undelete(array $entries, ?string $comments = null): int
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
     * @param InterfaceDataEntry|null $entry
     * @return static
     */
    protected function addEntry(?InterfaceDataEntry $entry): static
    {
        if ($entry) {
            $this->list[$entry->getId()] = $entry;
        }

        return $this;
    }


    /**
     * Remove the specified data entry from the data list
     *
     * @param InterfaceDataEntry|int|null $entry
     * @return static
     */
    protected function removeEntry(InterfaceDataEntry|int|null $entry): static
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
     * @return array
     */
    abstract protected function loadDetails(array|string|null $columns, array $filters = []): array;


    /**
     * Save the data list elements to database
     *
     * @return static
     */
    abstract public function save(): static;
}