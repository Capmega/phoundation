<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Interfaces;

use PDOStatement;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Content\Documents\Interfaces\SpreadSheetInterface;
use Phoundation\Core\Interfaces\IntegerableInterface;
use Phoundation\Core\Meta\Interfaces\MetaInterface;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Exception\DataEntryCannotBeDeletedException;
use Phoundation\Data\Enums\EnumLoadParameters;
use Phoundation\Data\Enums\EnumSoftHard;
use Phoundation\Data\Interfaces\CacheableObjectInterface;
use Phoundation\Data\Interfaces\EntryInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;
use ReturnTypeWillChange;
use Stringable;
use Throwable;


interface DataEntryInterface extends EntryInterface, IntegerableInterface, CacheableObjectInterface
{
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
    public static function getEntryName(): string;

    /**
     * Returns the column that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string;

    /**
     * Returns a DataEntry object matching the specified identifier that MUST exist in the database
     *
     * This method also accepts DataEntry objects of the same class, in which case it will simply return the specified
     * object, as long as it exists in the database.
     *
     * If the DataEntry does not exist in the database, then this method will check if perhaps it exists as a
     * configuration entry. This requires DataEntry::$config_path to be set. DataEntries from configuration will be in
     * readonly mode automatically as they cannot be stored in the database.
     *
     * DataEntries from the database will also have their status checked. If the status is "deleted", then a
     * DataEntryDeletedException will be thrown
     *
     * @note The test to see if a DataEntry object exists in the database can be either DataEntry::isNew() or
     *       DataEntry::getId(), which should return a valid database id
     *
     * @param IdentifierInterface|array|string|int|null $identifier                    Identifier for the DataEntry object to
     *                                                                                 load. Can be specified with a
     *                                                                                 [column => value] array, though also
     *                                                                                 accepts an integer value which will convert
     *                                                                                 to [id_column => integer_value] or a string
     *                                                                                 value which will convert to
     *                                                                                 [unique_column => string_value]]
     * @param EnumLoadParameters|null                   $on_null_identifier       Specifies how this load method will handle
     *                                                                                 the specified identifier being NULL.
     *                                                                                 Options are: EnumLoadParameters::exception
     *                                                                                 (Throws a
     *                                                                                 DataEntryNoIdentifierSpecifiedException),
     *                                                                                 EnumLoadParameters::null (will return NULL)
     *                                                                                 or EnumLoadParameters::this (Will return
     *                                                                                 the object as-is, without loading
     *                                                                                 anything). Defaults to
     *                                                                                 EnumLoadParameters::exception
     * @param EnumLoadParameters|null                   $on_not_exists            Specifies how this load method will handle
     *                                                                                 the specified identifier not existing in
     *                                                                                 the database. Options are:
     *                                                                                 EnumLoadParameters::exception (Throws a
     *                                                                                 DataEntryNotExistsException),
     *                                                                                 EnumLoadParameters::null (will return NULL)
     *                                                                                 or EnumLoadParameters::this (Will return
     *                                                                                 the object as-is, without loading anything)
     *                                                                                 Defaults to EnumLoadParameters::exception
     *
     * @return static|null
     */
    public function load(IdentifierInterface|array|string|int|null $identifier = null, ?EnumLoadParameters $on_null_identifier = null, ?EnumLoadParameters $on_not_exists = null): ?static;

    /**
     * Returns if this DataEntry validates data before saving
     *
     * @return bool
     */
    public function getValidate(): bool;

    /**
     * Sets if this DataEntry validates data before saving
     *
     * @param bool $validate
     *
     * @return static
     */
    public function setValidate(bool $validate): static;

    /**
     * Returns the query builder for this data entry
     *
     * @param bool $reset [false] If true, will reset the QueryBuilder before returning it
     *
     * @return QueryBuilderInterface|null
     */
    public function getQueryBuilderObject(bool $reset = false): ?QueryBuilderInterface;

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
     * Returns true if this is a new entry that  has not been written to the database yet
     *
     * @return bool
     */
    public function isNew(): bool;

    /**
     * Returns id for this database entry
     *
     * @param bool        $exception
     * @param string|null $suffix
     *
     * @return string|int|null
     */
    public function getId(bool $exception = true, ?string $suffix = null): string|int|null;

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
     * Returns all data for this data entry at once with an array of information
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     *
     * @param bool $filter_meta              If true, will filter out the DataEntry meta-columns
     * @param bool $filter_protected_columns If true, will filter out the DataEntry protected columns (typically
     *                                       passwords, etc)
     *
     * @return array
     */
    public function getSource(bool $filter_meta = false, bool $filter_protected_columns = true): array;

    /**
     * Set the status for this database entry
     *
     * @param string|null $status
     * @param string|null $comments
     * @param bool        $auto_save
     *
     * @return static
     */
    public function setStatus(?string $status, ?string $comments = null, bool $auto_save = true): static;

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
     * @param bool        $auto_save
     *
     * @return static
     */
    public function delete(?string $comments = null, bool $auto_save = true): static;


    /**
     * Undelete the specified entries
     *
     * @param string|null $comments
     * @param bool        $auto_save
     *
     * @return static
     */
    public function undelete(?string $comments = null, bool $auto_save = true): static;

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
    public function getPrefix(): ?string;

    /**
     * Sets the column prefix string
     *
     * @param string|null $prefix
     *
     * @return static
     */
    public function setPrefix(?string $prefix): static;

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
     * @return PhoDateTimeInterface|null
     */
    public function getCreatedOnObject(): ?PhoDateTimeInterface;

    /**
     * Returns the meta-information for this entry
     *
     * @note Returns NULL if this class has no support for meta-information available, or  has not been written to disk
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
     * @param bool                           $require_clean_source
     * @param ValidatorInterface|array|null &$source
     *
     * @return static
     */
    public function apply(bool $require_clean_source = true, ValidatorInterface|array|null &$source = null): static;

    /**
     * Forcibly modify the data for this object with the new specified data, putting the object in readonly mode
     *
     * @note In readonly mode this object will no longer be able to write its data!
     *
     * @param bool                          $require_clean_source
     * @param ValidatorInterface|array|null $source
     *
     * @return static
     */
    public function forceApply(bool $require_clean_source = true, ValidatorInterface|array|null &$source = null): static;

    /**
     * Will validate the source data of this DataEntry object
     *
     * @return static
     */
    public function validate(): static;

    /**
     * Returns all keys that are protected and cannot be removed from this object
     *
     * @return array
     */
    public function getProtectedColumns(): array;


    /**
     * Returns only the specified key from the source of this DataEntry
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     *
     * @param Stringable|string|float|int $key
     * @param mixed                       $default
     * @param bool|null                   $exception
     *
     * @return array
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): mixed;

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
    public function save(bool $force = false, bool $skip_validation = false, ?string $comments = null): static;

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
     * @param array|string|null $status
     * @param bool              $strict
     *
     * @return bool
     */
    public function hasStatus(array|string|null $status, bool $strict = true): bool;

    /**
     * Returns the name for this object that can be displayed
     *
     * @return string|null
     */
    function getDisplayName(): ?string;


    /**
     * Add the complete definitions and source from the specified data entry to this data entry
     *
     * @param DataEntryInterface $data_entry
     *
     * @return static
     */
    public function appendDataEntry(DataEntryInterface $data_entry): static;


    /**
     * Add the complete definitions and source from the specified data entry to this data entry
     *
     * @param DataEntryInterface $data_entry
     *
     * @return static
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
     * @return static
     */
    public function injectDataEntry(string $at_key, DataEntryInterface $data_entry, bool $after = true, bool $strip_meta = true): static;


    /**
     * Add the complete definitions and source from the specified data entry to this data entry
     *
     * @param string                                  $at_key
     * @param ElementInterface|ElementsBlockInterface $value
     * @param DefinitionInterface|array|null          $_definition
     * @param bool                                    $after
     *
     * @return static
     * @todo Improve by first splitting meta data off the new data entry and then ALWAYS prepending it to ensure its at
     *       the front
     */
    public function injectElement(string $at_key, ElementInterface|ElementsBlockInterface $value, DefinitionInterface|array|null $_definition = null, bool $after = true): static;


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
     * @return static
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
    public function getChangedColumns(): array;

    /**
     * Returns the value of the unique column
     *
     * @param mixed $value
     * @param bool  $force
     *
     * @return static
     */
    public function setUniqueColumnValue(mixed $value, bool $force = false): static;

    /**
     * Adds the specified action to the meta history for this DataEntry object
     *
     * @param string|null                  $action
     * @param string|null                  $comments
     * @param Stringable|array|string|null $data
     *
     * @return static
     */
    public function addMetaAction(?string $action, ?string $comments = null, Stringable|array|string|null $data = null): static;

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
     * @param ConnectorInterface|null $_connector
     * @param string|int|null         $database
     *
     * @return static
     */
    public function setConnectorObject(?ConnectorInterface $_connector, string|int|null $database = null): static;

    /**
     * Sets the QueryBuilder object to modify the internal query for this object
     *
     * @param QueryBuilderInterface $query_builder
     *
     * @return static
     */
    public function setQueryBuilderObject(QueryBuilderInterface $query_builder): static;

    /**
     * Returns a SpreadSheet object with this object's source data in it
     *
     * @return SpreadSheetInterface
     */
    public function getSpreadSheet(): SpreadSheetInterface;

    /**
     * Returns an array containing all the DataEntry state variables
     *
     * @return array
     */
    public function getObjectState(): array;

    /**
     * Sets the state variables for this object
     *
     * @param array $state_array
     *
     * @return static
     */
    public function setObjectState(array $state_array): static;

    /**
     * Returns the previous ID
     *
     * @return int|null
     */
    public function getPreviousId(): ?int;

    /**
     * Returns true if this entry is new or WAS new before it was written
     *
     * @return bool
     */
    public function isCreated(): bool;


    /**
     * Sets the value for the specified data key
     *
     * @param mixed                       $value
     * @param Stringable|string|float|int $key
     * @param bool                        $skip_null_values
     *
     * @return static
     */
    public function set(mixed $value, Stringable|string|float|int $key, bool $skip_null_values = false): static;

    /**
     * Returns if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @return IdentifierInterface|array|string|int|false|null
     */
    public function getIdentifier(): IdentifierInterface|array|string|int|false|null;


    /**
     * Sets if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     *
     * @return static
     */
    public function setIdentifier(IdentifierInterface|array|string|int|false|null $identifier): static;

    /**
     * Returns if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @return bool
     */
    public function getIgnoreDeleted(): bool;

    /**
     * Sets if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @param bool|null $ignore_deleted
     * @return static
     */
    public function setIgnoreDeleted(?bool $ignore_deleted): static;

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
     * Returns if this object is permit_validation_failures or not
     *
     * @return EnumSoftHard
     */
    public function getPermitValidationFailures(): EnumSoftHard;

    /**
     * Sets if this object is permit_validation_failures or not
     *
     * @param EnumSoftHard $permit_validation_failures
     *
     * @return static
     */
    public function setPermitValidationFailures(EnumSoftHard $permit_validation_failures): static;

    /**
     * Returns true if this object has the specified permit_validation_failures
     *
     * @param EnumSoftHard $permit
     *
     * @return bool
     */
    public function hasPermitValidationFailures(EnumSoftHard $permit): bool;

    /**
     * Returns the Throwable exception for this object or null
     *
     * @return Throwable|null
     */
    public function getException(): ?Throwable;

    /**
     * Sets the Throwable ID for this object
     *
     * @param Throwable|null $exception
     * @return static
     */
    public function setException(?Throwable $exception): static;

    /**
     * Returns true if this object has an exception set
     *
     * @return bool
     */
    public function hasException(): bool;

    /**
     * Returns whether this object will allow columns that  are not permitted
     *
     * @return bool
     */
    public function getAllowUnpermittedColumns(): bool;

    /**
     * Sets whether this object will allow columns that  are not permitted
     *
     * @param bool $allow
     *
     * @return static
     */
    public function setAllowUnpermittedColumns(bool $allow): static;

    /**
     * Returns true if the specified column is on the permitted columns list
     *
     * @param string $column
     *
     * @return bool
     */
    public function columnIsPermitted(string $column): bool;

    /**
     * Returns a list of columns that  are not defined, but are permitted for use
     *
     * @return array|null
     */
    public function getPermittedColumns(): ?array;

    /**
     * Returns a list of columns that  are not defined, but are permitted for use
     *
     * @param array|string|null $columns
     *
     * @return static
     */
    public function setPermittedColumns(array|string|null $columns): static;

    /**
     * Returns a list of columns that  are not defined, but are permitted for use
     *
     * @param array|string|null $columns
     *
     * @return static
     */
    public function addPermittedColumns(array|string|null $columns): static;

    /**
     * Returns the source without processing any data first
     *
     * @return array
     */
    public function getSourceUnprocessed(): array;

    /**
     * Loads the specified data into this DataEntry object directly, circumventing the definitions
     *
     * @warning THIS IS CONSIDERED DANGEROUS. You can load any type of data and column into this DataEntry object, whether its defined / permitted or not!
     *
     * @param DataEntryInterface|IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null                                                          $execute
     * @param bool                                                                $filter_meta
     *
     * @return static
     */
    public function setSourceDirect(DataEntryInterface|IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null, bool $filter_meta = false): static;

    /**
     * Sets the flag that this DataEntry object was loaded from local cache
     *
     * @return static
     */
    public function setIsLoadedFromLocalCache(): static;

    /**
     * Sets the flag that this DataEntry object was loaded from global cache
     *
     * @return static
     */
    public function setIsLoadedFromGlobalCache(): static;

    /**
     * Returns NULL if this specific DataEntry can be deleted, or a string containing the reason why it cannot be deleted
     *
     * @return string|null
     */
    public function getDeleteLockReason(): ?string;

    /**
     * Returns true if this DataEntry object can be deleted
     *
     * @return bool
     */
    public function canBeDeleted(): bool;

    /**
     * Checks if a DataEntry can be deleted, throws a DataEntryCannotBeDeletedException if it cannot be deleted
     *
     * @return static
     * @throws DataEntryCannotBeDeletedException
     */
    public function checkCanBeDeleted(): static;

    /**
     * Returns the value of the unique column even after the DataEntry object has been deleted
     *
     * @return mixed
     */
    public function getOriginalUniqueColumnValue(): mixed;

    /**
     * Returns the server restrictions
     *
     * @return PhoRestrictionsInterface
     */
    public function getRestrictionsObject(): PhoRestrictionsInterface;

    /**
     * Sets the server and filesystem restrictions for this object
     *
     * @param PhoRestrictionsInterface|array|string|null $_restrictions The file restrictions to apply to this object
     * @param bool                                       $write          If $restrictions is not specified as a
     *                                                                   FsRestrictions class, but as a path string, or
     *                                                                   array of path strings, then this method will
     *                                                                   convert that into a FsRestrictions object and
     *                                                                   this is the $write modifier for that object
     * @param string|null                                $label          If $restrictions is not specified as a
     *                                                                   FsRestrictions class, but as a path string, or
     *                                                                   array of path strings, then this method will
     *                                                                   convert that into a FsRestrictions object and
     *                                                                   this is the $label modifier for that object
     */
    public function setRestrictionsObject(PhoRestrictionsInterface|array|string|null $_restrictions = null, bool $write = false, ?string $label = null): static;

    /**
     * Returns either the specified restrictions, or this object's restrictions, or system default restrictions
     *
     * @param PhoRestrictionsInterface|null $_restrictions
     *
     * @return PhoRestrictionsInterface
     */
    public function ensureRestrictionsObject(?PhoRestrictionsInterface $_restrictions): PhoRestrictionsInterface;
}
