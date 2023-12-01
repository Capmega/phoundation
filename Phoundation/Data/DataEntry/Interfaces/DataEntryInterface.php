<?php

namespace Phoundation\Data\DataEntry\Interfaces;

use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Core\Meta\Interfaces\MetaInterface;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Date\DateTime;
use Phoundation\Web\Html\Components\Interfaces\DataEntryFormInterface;
use Stringable;


/**
 * Class DataEntry
 *
 * This class contains the basic data entry traits
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
interface DataEntryInterface extends ArrayableInterface, Stringable
{
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
    public function getQueryBuilder(): QueryBuilderInterface;

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
     * @return static
     */
    public function setAllowModify(bool $allow_modify): static;

    /**
     * Returns a translation table between CLI arguments and internal fields
     *
     * @return array
     */
    public function getCliFields(): array;

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
     * Returns the value for the unique field, which
     *
     * @return string|float|int|null
     */
    public function getUniqueFieldValue(): string|float|int|null;

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
     * Delete the specified entries
     *
     * @param string|null $comments
     * @return static
     */
    public function delete(?string $comments = null): static;

    /**
     * Undelete the specified entries
     *
     * @param string|null $comments
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
     * Returns the field prefix string
     *
     * @return ?string
     */
    public function getFieldPrefix(): ?string;

    /**
     * Sets the field prefix string
     *
     * @param string|null $prefix
     * @return static
     */
    public function setFieldPrefix(?string $prefix): static;

    /**
     * Returns the object that created this data entry
     *
     * @note Returns NULL if this class has no support for created_by information or has not been written to disk yet
     * @return UserInterface|null
     */
    public function getCreatedBy(): ?UserInterface;

    /**
     * Returns the object that created this data entry
     *
     * @note Returns NULL if this class has no support for created_by information or has not been written to disk yet
     * @return DateTime|null
     */
    public function getCreatedOn(): ?DateTime;

    /**
     * Returns the meta-information for this entry
     *
     * @note Returns NULL if this class has no support for meta-information available, or hasn't been written to disk
     *       yet
     *
     * @param bool $load
     * @return MetaInterface|null
     */
    public function getMeta(bool $load = false): ?MetaInterface;

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
     * @param bool $clear_source
     * @param ValidatorInterface|array|null &$source
     * @return static
     */
    public function apply(bool $clear_source = true, ValidatorInterface|array|null &$source = null): static;

    /**
     * Forcibly modify the data for this object with the new specified data, putting the object in readonly mode
     *
     * @note In readonly mode this object will no longer be able to write its data!
     * @param bool $clear_source
     * @param ValidatorInterface|array|null $source
     * @return static
     */
    public function forceApply(bool $clear_source = true, ValidatorInterface|array|null &$source = null): static;

    /**
     * Validates the source data and returns it
     *
     * @param ValidatorInterface|array|null $data
     * @return static
     */
    public function validateMetaState(ValidatorInterface|array|null $data = null): static;

    /**
     * Returns all keys that are protected and cannot be removed from this object
     *
     * @return array
     */
    public function getProtectedFields(): array;

    /**
     * Returns all data for this data entry at once with an array of information
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     * @return array
     */
    public function getSource(): array;

    /**
     * Returns only the specified key from the source of this DataEntry
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     * @return array
     */
    public function getSourceValue(string $key): mixed;

    /**
     * Sets the value for the specified data key
     *
     * @param string $field
     * @param mixed $value
     * @return static
     */
    public function addSourceValue(string $field, mixed $value): static;

    /**
     * Will save the data from this data entry to database
     *
     * @param bool $force
     * @param string|null $comments
     * @return static
     */
    public function save(bool $force = false, ?string $comments = null): static;

    /**
     * Creates and returns a CLI table for the data in this entry
     *
     * @param string|null $key_header
     * @param string|null $value_header
     * @return void
     */
    public function getCliForm(?string $key_header = null, ?string $value_header = null): void;

    /**
     * Creates and returns an HTML for the data in this entry
     *
     * @return DataEntryFormInterface
     */
    public function getHtmlDataEntryForm(): DataEntryFormInterface;

    /**
     * Load all data directly from the specified array.
     *
     * @note ONLY use this to load data that came from a trusted and validated source! This method will NOT validate
     *       your data, use DataEntry::apply() instead for untrusted data.
     * @param array $source
     * @param bool $init
     * @return $this
     */
    public function setSourceString(array $source, bool $init = false): static;

    /**
     * Returns the definitions for the fields in this table
     *
     * @return DefinitionsInterface|null
     */
    public function getDefinitions(): ?DefinitionsInterface;

    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string;


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string;


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string;

    /**
     * Returns true if this object has the specified status
     *
     * @param string $status
     * @return bool
     */
    public function hasStatus(string $status): bool;

    /**
     * Returns a DataEntry object matching the specified identifier
     *
     * @note This method also accepts DataEntry objects, in which case it will simply return this object. This is to
     *       simplify "if this is not DataEntry object then this is new DataEntry object" into
     *       "PossibleDataEntryVariable is DataEntry::new(PossibleDataEntryVariable)"
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null $column
     * @param bool $meta_enabled
     * @return static|null
     */
    public static function get(DataEntryInterface|string|int|null $identifier, ?string $column = null, bool $meta_enabled = false): ?static;

    /**
     * Returns the name for this object that can be displayed
     *
     * @return string
     */
    function getDisplayName(): string;
}
