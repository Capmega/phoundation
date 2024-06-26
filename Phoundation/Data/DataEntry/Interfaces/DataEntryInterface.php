<?php

/**
 * Interface DataEntryInterface
 *
 * This class contains the basic data entry traits
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Interfaces;

use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Core\Meta\Interfaces\MetaInterface;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\Interfaces\EntryInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Date\DateTime;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;
use Stringable;

interface DataEntryInterface extends EntryInterface
{
    /**
     * Initializes the DataEntry object
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null                        $column
     * @param bool|null                          $meta_enabled
     *
     * @return $this
     */
    public function init(DataEntryInterface|string|int|null $identifier = null, ?string $column = null, ?bool $meta_enabled = null): static;

    /**
     * Returns the default database connector to use for this table
     *
     * @return string
     */
    public static function getConnector(): string;

//    /**
//     * Returns the column considered the "id" column
//     *
//     * @return string
//     */
//    public static function getIdColumn(): string;

    /**
     * Returns true if the ID column is the specified column
     *
     * @param string $column
     *
     * @return bool
     */
    public static function idColumnIs(string $column): bool;

    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string;

    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string;

    /**
     * Returns the column that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string;

    /**
     * Returns a DataEntry object matching the specified identifier
     *
     * @note This method also accepts DataEntry objects, in which case it will simply return this object. This is to
     *       simplify "if this is not DataEntry object then this is new DataEntry object" into
     *       "PossibleDataEntryVariable is DataEntry::new(PossibleDataEntryVariable)"
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null                        $column
     * @param bool                               $meta_enabled
     * @param bool                               $force
     *
     * @return DataEntryInterface
     */
    public static function load(DataEntryInterface|string|int|null $identifier, ?string $column = null, bool $meta_enabled = false, bool $force = false): static;

    /**
     * Returns if this DataEntry validates data before saving
     *
     * @return bool
     */
    public function getValidate(): bool;

    /**
     * Sets if this DataEntry validates data before saving
     *
     * @return $this
     */
    public function setValidate(bool $validate): static;

    /**
     * Returns the query builder for this data entry
     *
     * @return QueryBuilderInterface
     */
    public function getQueryBuilderObject(): QueryBuilderInterface;

    /**
     * Returns true if the internal data structures have been modified
     *
     * @return bool
     */
    public function isModified(): bool;

    /**
     * Returns true if the data in this DataEntry has been validated
     *
     * @return bool
     */
    public function isValidated(): bool;

    /**
     * Returns true if the DataEntry was just successfully saved
     *
     * @return bool
     */
    public function isSaved(): bool;

    /**
     * Returns true if the data in this DataEntry is currently in a state of being applied through DataEntry::apply()
     *
     * @return bool
     */
    public function isApplying(): bool;

    /**
     * Returns id for this database entry that can be used in logs
     *
     * @return bool
     */
    public function getAllowCreate(): bool;

    /**
     * Returns id for this database entry that can be used in logs
     *
     * @param bool $allow_create
     *
     * @return static
     */
    public function setAllowCreate(bool $allow_create): static;

    /**
     * Returns if this DataEntry will allow modification of existing entries
     *
     * @return bool
     */
    public function getAllowModify(): bool;

    /**
     * Sets if this DataEntry will allow modification of existing entries
     *
     * @param bool $allow_modify
     *
     * @return static
     */
    public function setAllowModify(bool $allow_modify): static;

    /**
     * Returns a translation table between CLI arguments and internal columns
     *
     * @return array
     */
    public function getCliColumns(): array;

    /**
     * Returns true if this is a new entry that hasn't been written to the database yet
     *
     * @return bool
     */
    public function isNew(): bool;

    /**
     * Returns id for this database entry
     *
     * @return int|null
     */
    public function getId(): int|null;

    /**
     * Returns the unique identifier for this database entry, which will be the ID column if it does not have any
     *
     * @return string|float|int|null
     */
    public function getUniqueColumnValue(): string|float|int|null;

    /**
     * Returns id for this database entry that can be used in logs
     *
     * @return string
     */
    public function getLogId(): string;

    /**
     * Returns true if this DataEntry has the specified status
     *
     * @param string|null $status
     *
     * @return bool
     */
    public function isStatus(?string $status): bool;

    /**
     * Returns status for this database entry
     *
     * @return ?string
     */
    public function getStatus(): ?string;

    /**
     * Set the status for this database entry
     *
     * @param string|null $status
     * @param string|null $comments
     *
     * @return static
     */
    public function setStatus(?string $status, ?string $comments = null): static;

    /**
     * Returns the meta-state for this database entry
     *
     * @return ?string
     */
    public function getMetaState(): ?string;

    /**
     * Returns the meta-columns for this database entry
     *
     * @return array|null
     */
    public function getMetaColumns(): ?array;

    /**
     * Delete the specified entries
     *
     * @param string|null $comments
     *
     * @return static
     */
    public function delete(?string $comments = null): static;

    /**
     * Undelete the specified entries
     *
     * @param string|null $comments
     *
     * @return static
     */
    public function undelete(?string $comments = null): static;

    /**
     * Erase this DataEntry from the database
     *
     * @return static
     */
    public function erase(): static;

    /**
     * Returns the column prefix string
     *
     * @return ?string
     */
    public function getColumnPrefix(): ?string;

    /**
     * Sets the column prefix string
     *
     * @param string|null $prefix
     *
     * @return static
     */
    public function setColumnPrefix(?string $prefix): static;

    /**
     * Returns the object that created this data entry
     *
     * @note Returns NULL if this class has no support for created_by information or has not been written to disk yet
     * @return UserInterface|null
     */
    public function getCreatedByObject(): ?UserInterface;

    /**
     * Returns the object that created this data entry
     *
     * @note Returns NULL if this class has no support for created_by information or has not been written to disk yet
     * @return DateTime|null
     */
    public function getCreatedOnObject(): ?DateTime;

    /**
     * Returns the meta-information for this entry
     *
     * @note Returns NULL if this class has no support for meta-information available, or hasn't been written to disk
     *       yet
     *
     * @param bool $load
     *
     * @return MetaInterface|null
     */
    public function getMetaObject(bool $load = false): ?MetaInterface;

    /**
     * Returns the meta id for this entry
     *
     * @return int|null
     */
    public function getMetaId(): ?int;

    /**
     * Returns a string containing all diff data
     *
     * @return string|null
     */
    public function getDiff(): ?string;

    /**
     * Modify the data for this object with the new specified data
     *
     * @param bool                           $clear_source
     * @param ValidatorInterface|array|null &$source
     *
     * @return static
     */
    public function apply(bool $clear_source = true, ValidatorInterface|array|null &$source = null): static;

    /**
     * Forcibly modify the data for this object with the new specified data, putting the object in readonly mode
     *
     * @note In readonly mode this object will no longer be able to write its data!
     *
     * @param bool                          $clear_source
     * @param ValidatorInterface|array|null $source
     *
     * @return static
     */
    public function forceApply(bool $clear_source = true, ValidatorInterface|array|null &$source = null): static;

    /**
     * Will validate the source data of this DataEntry object
     *
     * @return $this
     */
    public function validate(): static;

    /**
     * Returns all keys that are protected and cannot be removed from this object
     *
     * @return array
     */
    public function getProtectedColumns(): array;

    /**
     * Returns all data for this data entry at once with an array of information
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     * @return array
     */
    public function getSource(): array;

    /**
     * Returns a list of all internal source keys
     *
     * @return mixed
     */
    public function getKeys(bool $filter_meta = false): array;

    /**
     * Returns only the specified key from the source of this DataEntry
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     * @return array
     */
    public function get(string $key): mixed;

    /**
     * Sets the value for the specified data key
     *
     * @param string $column
     * @param mixed  $value
     *
     * @return static
     */
    public function addSourceValue(string $column, mixed $value): static;

    /**
     * Will save the data from this data entry to the database
     *
     * @param bool        $force
     * @param string|null $comments
     *
     * @return static
     */
    public function save(bool $force = false, ?string $comments = null): static;

    /**
     * Generates and display a CLI form for the data in this entry
     *
     * @param string|null $key_header
     * @param string|null $value_header
     *
     * @return static
     */
    public function displayCliForm(?string $key_header = null, ?string $value_header = null): static;

    /**
     * Creates and returns an HTML for the data in this entry
     *
     * @return DataEntryFormInterface
     */
    public function getHtmlDataEntryFormObject(): DataEntryFormInterface;

    /**
     * Returns the definitions for the columns in this table
     *
     * @return DefinitionsInterface|null
     */
    public function getDefinitionsObject(): ?DefinitionsInterface;

    /**
     * Returns true if this object has the specified status
     *
     * @param string $status
     *
     * @return bool
     */
    public function hasStatus(string $status): bool;

    /**
     * Returns the name for this object that can be displayed
     *
     * @return string
     */
    function getDisplayName(): string;


    /**
     * Loads the specified data into this DataEntry object
     *
     * @param Iterator|array $source
     *
     * @return static
     */
    public function setSource(Iterator|array $source): static;


    /**
     * Add the complete definitions and source from the specified data entry to this data entry
     *
     * @param DataEntryInterface $data_entry
     *
     * @return $this
     */
    public function appendDataEntry(DataEntryInterface $data_entry): static;


    /**
     * Add the complete definitions and source from the specified data entry to this data entry
     *
     * @param DataEntryInterface $data_entry
     *
     * @return $this
     */
    public function prependDataEntry(DataEntryInterface $data_entry): static;


    /**
     * Add the complete definitions and source from the specified data entry to this data entry
     *
     * @param string             $at_key
     * @param DataEntryInterface $data_entry
     * @param bool               $after
     * @param bool               $strip_meta
     *
     * @return $this
     */
    public function injectDataEntry(string $at_key, DataEntryInterface $data_entry, bool $after = true, bool $strip_meta = true): static;


    /**
     * Add the complete definitions and source from the specified data entry to this data entry
     *
     * @param string                                  $at_key
     * @param ElementInterface|ElementsBlockInterface $value
     * @param DefinitionInterface|array|null          $definition
     * @param bool                                    $after
     *
     * @return $this
     * @todo Improve by first splitting meta data off the new data entry and then ALWAYS prepending it to ensure its at
     *       the front
     */
    public function injectElement(string $at_key, ElementInterface|ElementsBlockInterface $value, DefinitionInterface|array|null $definition = null, bool $after = true): static;


    /**
     * Extracts a DataEntry with the specified columns (in the specified order)
     *
     * The extracted data entry will have the source and definitions
     *
     * The extracted data entry will have the same class and interface as this
     *
     * @param array|string $columns
     * @param int          $flags
     *
     * @return DataEntryInterface
     */
    public function extractDataEntryObject(array|string $columns, int $flags): DataEntryInterface;


    /**
     * Returns whether to use random_id
     *
     * @return bool
     */
    public function getRandomId(): bool;


    /**
     * Sets whether to use random_id
     *
     * @param bool $random_id
     *
     * @return static
     */
    public function setRandomId(bool $random_id): static;


    /**
     * Returns if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @return bool
     */
    public function getMetaEnabled(): bool;


    /**
     * Sets if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @param bool $meta_enabled
     * return static
     */
    public function setMetaEnabled(bool $meta_enabled): static;


    /**
     * Returns whether to use INSERT ON DUPLICATE KEY UPDATE queries instead of insert / update
     *
     * @return bool
     */
    public function getInsertUpdate(): bool;


    /**
     * Sets whether to use INSERT ON DUPLICATE KEY UPDATE queries instead of insert / update
     *
     * @param bool $insert_update
     *
     * @return static
     */
    public function setInsertUpdate(bool $insert_update): static;


    /**
     * Returns how many random id retries to perform
     *
     * @return int
     */
    public function getMaxIdRetries(): int;


    /**
     * Sets how many random id retries to perform
     *
     * @param int $max_id_retries
     *
     * @return static
     */
    public function setMaxIdRetries(int $max_id_retries): static;


    /**
     * Returns an array with the columns that have changed
     *
     * @return array
     */
    public function getChanges(): array;
}
