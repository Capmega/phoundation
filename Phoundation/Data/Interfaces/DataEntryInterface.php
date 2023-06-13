<?php

declare(strict_types=1);

namespace Phoundation\Data\Interfaces;

use Phoundation\Accounts\Users\User;
use Phoundation\Core\Meta\Meta;
use Phoundation\Data\DataEntry\Interfaces\DefinitionsInterface;
use Phoundation\Date\DateTime;
use Phoundation\Web\Http\Html\Components\Interfaces\DataEntryFormInterface;


/**
 * Class InterfaceDataEntry
 *
 * Interface for DataEntry objects
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
interface DataEntryInterface
{
    /**
     * Return the object contents in JSON string format
     *
     * @return string
     */
    function __toString(): string;

    /**
     * Return the object contents in array format
     *
     * @return array
     */
    function __toArray(): array;

    /**
     * Returns a new DataEntry object
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @return static
     */
    static function new(DataEntryInterface|string|int|null $identifier = null): static;

    /**
     * Returns a help file generated from the DataEntry keys
     *
     * @param array $auto_complete
     * @return array
     */
    static function getAutoComplete(array $auto_complete = []): array;

    /**
     * Returns a translation table between CLI arguments and internal fields
     *
     * @note This methods uses internal caching, the second request will be a cached result
     * @return array
     */
    function getCliFields(): array;

    /**
     * Returns a help text generated from this DataEntry's field information
     *
     * The help text will contain help information for each field as defined in DataEntry::fields. Since this help text
     * is for the command line, field names will be translated to their command line argument counterparts (so instead
     * of "name" it would show "-n,--name")
     *
     * @param string|null $help
     * @return string
     */
    static function getHelp(?string $help = null): string;

    /**
     * Returns a DataEntry object matching the specified identifier
     *
     * @note This method also accepts DataEntry objects, in which case it will simply return this object. This is to
     *       simplify "if this is not DataEntry object then this is new DataEntry object" into
     *       "PossibleDataEntryVariable is DataEntry::new(PossibleDataEntryVariable)"
     * @param DataEntryInterface|string|int|null $identifier
     * @return static|null
     */
    static function get(DataEntryInterface|string|int|null $identifier = null): ?static;

    /**
     * Returns a random DataEntry object
     *
     * @return static|null
     */
    static function getRandom(): ?static;

    /**
     * Returns true if an entry with the specified identifier exists
     *
     * @param string|int|null $identifier The unique identifier, but typically not the database id, usually the
     *                                    seo_email, or seo_name
     * @param bool $throw_exception       If the entry does not exist, instead of returning false will throw a
     *                                    DataEntryNotExistsException
     * @return bool
     */
    static function exists(string|int $identifier = null, bool $throw_exception = false): bool;

    /**
     * Returns true if an entry with the specified identifier does not exists
     *
     * @param string|int|null $identifier The unique identifier, but typically not the database id, usually the
     *                                    seo_email, or seo_name
     * @param int|null $id                If specified, will ignore the found entry if it has this ID as it will be THIS
     *                                    object
     * @param bool $throw_exception       If the entry exists (and does not match id, if specified), instead of
     *                                    returning false will throw a DataEntryNotExistsException
     * @return bool
     */
    static function notExists(string|int $identifier = null, ?int $id = null, bool $throw_exception = false): bool;

    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    static function getTable(): string;

    /**
     * Returns the definitions for the fields in this table
     *
     * @return DefinitionsInterface
     */
    function getFieldDefinitions(): DefinitionsInterface;

    /**
     * Returns true if this is a new entry that hasn't been written to the database yet
     *
     * @return bool
     */
    function isNew(): bool;

    /**
     * Returns id for this database entry
     *
     * @return int|null
     */
    function getId(): int|null;

    /**
     * Returns id for this database entry that can be used in logs
     *
     * @return string
     */
    function getLogId(): string;

    /**
     * Returns status for this database entry
     *
     * @return ?String
     */
    function getStatus(): ?string;

    /**
     * Set the status for this database entry
     *
     * @param string|null $status
     * @param string|null $comments
     * @return static
     */
    function setStatus(?String $status, ?string $comments = null): static;

    /**
     * Returns the meta state for this database entry
     *
     * @return ?String
     */
    function getMetaState(): ?string;

    /**
     * Delete the specified entries
     *
     * @param string|null $comments
     * @return static
     */
    function delete(?string $comments = null): static;

    /**
     * Undelete the specified entries
     *
     * @param string|null $comments
     * @return static
     */
    function undelete(?string $comments = null): static;

    /**
     * Erase this DataEntry from the database
     *
     * @return static
     */
    function erase(): static;

    /**
     * Returns the object that created this data entry
     *
     * @note Returns NULL if this class has no support for created_by information or has not been written to disk yet
     * @return User|null
     */
    function getCreatedBy(): ?User;

    /**
     * Returns the object that created this data entry
     *
     * @note Returns NULL if this class has no support for created_by information or has not been written to disk yet
     * @return DateTime|null
     */
    function getCreatedOn(): ?DateTime;

    /**
     * Returns the meta information for this entry
     *
     * @note Returns NULL if this class has no support for meta information available, or hasn't been written to disk
     *       yet
     * @return Meta|null
     */
    function getMeta(): ?Meta;

    /**
     * Returns the meta id for this entry
     *
     * @return int|null
     */
    function getMetaId(): ?int;

    /**
     * Returns a string containing all diff data
     *
     * @return string|null
     */
    function getDiff(): ?string;

    /**
     * Create the data for this object with the new specified data
     *
     * @param array|null $data
     * @param bool $no_arguments_left
     * @return static
     */
    function create(?array $data = null, bool $no_arguments_left = false): static;

    /**
     * Modify the data for this object with the new specified data
     *
     * @param array|null $data
     * @param bool $no_arguments_left
     * @return static
     */
    function modify(?array $data = null, bool $no_arguments_left = false): static;

    /**
     * Returns all data for this data entry at once with an array of information
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     * @return array
     */
    function getData(): array;

    /**
     * Sets the value for the specified data key
     *
     * @param string $field
     * @param mixed $value
     * @return static
     */
    function addDataValue(string $field, mixed $value): static;

    /**
     * Will save the data from this data entry to database
     *
     * @param string|null $comments
     * @return static
     */
    function save(?string $comments = null): static;

    /**
     * Creates and returns a CLI table for the data in this entry
     *
     * @param string|null $key_header
     * @param string|null $value_header
     * @return void
     */
    function getCliForm(?string $key_header = null, ?string $value_header = null): void;

    /**
     * Creates and returns an HTML for the data in this entry
     *
     * @return DataEntryFormInterface
     */
    public function getHtmlForm(): DataEntryFormInterface;

    /**
     * Modify the form keys
     *
     * @param string $field
     * @param array $settings
     * @return static
     */
    function modifyDefinitions(string $field, array $settings): static;
 }