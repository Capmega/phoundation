<?php

namespace Phoundation\Data\DataEntry;

use Exception;
use Phoundation\Accounts\Users\User;
use Phoundation\Accounts\Users\Users;
use Phoundation\Cli\Cli;
use Phoundation\Cli\Color;
use Phoundation\Core\Arrays;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Meta\Meta;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\Enums\StateMismatchHandling;
use Phoundation\Data\DataEntry\Exception\DataEntryAlreadyExistsException;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntry\Exception\DataEntryStateMismatchException;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Data\Traits\DataDebug;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Date\DateTime;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\Html\Components\DataEntryForm;
use Phoundation\Web\Http\Html\Components\Input\InputText;
use Throwable;


/**
 * Class DataEntry
 *
 * This class contains the basic data entry traits
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
abstract class DataEntry implements InterfaceDataEntry
{
    use DataDebug;



    /**
     * The label name for this data entry, used in errors, etc
     *
     * @var string $entry_name
     */
    // TODO Check this, will likely go wrong as we have many sub classes of DataEntry
    protected static string $entry_name;

    /**
     * The table name where this data entry is stored
     *
     * @var string $table
     */
    protected string $table;

    /**
     * Contains the data for all information of this data entry
     *
     * @var array $data
     */
    protected array $data = [];

    /**
     * Meta information about the keys in this DataEntry
     *
     * @var array $fields
     */
    protected array $fields = [];

    /**
     * If specified, will define alternative field names, mostly used for command line field name definitions
     *
     * @var array|null
     */
    protected ?array $cli_fields = null;

    /**
     * The unique column identifier, next to id
     *
     * @var string $unique_field
     */
    protected string $unique_field = 'seo_name';

    /**
     * Default protected keys, keys that may not leave this object
     *
     * @var array|string[]
     */
    protected array $protected_fields = ['password', 'key'];

    /**
     * Meta information about how the keys will be displayed on forms
     *
     * @var array $field_display
     */
    protected array $field_display = [];

    /**
     * These keys should not ever be processed
     *
     * @var array $meta_fields
     */
    protected array $meta_fields = [
        'id',
        'created_by',
        'created_on',
        'status',
        'meta_id',
        'meta_state',
    ];

    /**
     * Columns that will NOT be inserted
     *
     * @var array $fields_filter_on_insert
     */
    protected array $fields_filter_on_insert = ['id'];

    /**
     * A list with optional linked other DataEntry objects
     *
     * @var DataList|null
     */
    protected ?DataList $list = null;

    /**
     * What to do when a record state mismatch was detected
     *
     * @var StateMismatchHandling $state_mismatch_handling
     */
    protected StateMismatchHandling $state_mismatch_handling = StateMismatchHandling::ignore;

    /**
     * $diff information showing what changed
     *
     * @var string|null $diff
     */
    protected ?string $diff = null;

    /**
     * If true, set configuration to display meta data
     *
     * @var bool $display_meta
     */
    protected bool $display_meta = true;

    /**
     * Flag to indicate that user is modifying data. In this case, all keys that are readonly and disabled cannot be
     * changed
     *
     * @var bool $user_modifying
     */
    protected bool $user_modifying = false;



    /**
     * DataEntry class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        if (empty(static::$entry_name)) {
            throw new OutOfBoundsException(tr('No entry_name specified for class ":class"', [
                ':class' => get_class($this)
            ]));
        }

        if (empty($this->table)) {
            throw new OutOfBoundsException(tr('No table specified for class ":class"', [
                ':class' => get_class($this)
            ]));
        }

        if (empty($this->unique_field)) {
            throw new OutOfBoundsException(tr('No unique_column specified for class ":class"', [
                ':class' => get_class($this)
            ]));
        }

        $this->fields = self::getAllFieldDefinitions();

        if ($identifier) {
            if (is_numeric($identifier)) {
                $this->data['id'] = $identifier;

            } elseif (is_object($identifier)) {
                $this->data['id'] = $identifier->getId();

            } else {
                $this->data[$this->unique_field] = $identifier;
            }

            $this->load($identifier);
        } else {
            $this->setMetaData();
        }
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
        return $this->data;
    }


    /**
     * Returns a new DataEntry object
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     * @return static
     */
    public static function new(InterfaceDataEntry|string|int|null $identifier = null): static
    {
        return new static($identifier);
    }


    /**
     * Returns a help file generated from the DataEntry keys
     *
     * @param array $auto_complete
     * @return array
     */
    public static function getAutoComplete(array $auto_complete = []): array
    {
        $arguments = [];

        // Extract auto complete for cli parameters from field definitions
        foreach (static::getFieldDefinitions() as $definitions) {
            if (isset($definitions['cli']) and isset($definitions['complete'])) {
                $arguments[$definitions['cli']] = $definitions['complete'];
            }
        }

        // Merge and return found auto complete parameters with specified auto complete parameters
        return array_merge_recursive($auto_complete, [
            'arguments' => $arguments
        ]);
    }


    /**
     * Returns a translation table between CLI arguments and internal fields
     *
     * @note This methods uses internal caching, the second request will be a cached result
     * @return array
     */
    public function getCliFields(): array
    {
        static $return = null;

        if (!$return) {
            $return = [];

            foreach ($this->fields as $field => $definitions) {
                if (isset_get($definitions['cli'])) {
                    $return[$field] = $definitions['cli'];
                }
            }
        }

        return $return;
    }


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
    public static function getHelp(?string $help = null): string
    {
        if ($help) {
            $help = trim($help);
            $help = preg_replace('/ARGUMENTS/', Color::apply(strtoupper(tr('ARGUMENTS')), 'white'), $help);
        }

        $groups     = [];
        $entry      = static::new();
        $fields     = $entry->getFields();
        $alternates = $entry->getCliFields();
        $return     = PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . Color::apply(strtoupper(tr('REQUIRED ARGUMENTS')), 'white');

        // Get the required fields and gather a list of available help groups
        foreach ($fields as $key => $data) {
            if (isset_get($data['required'])) {
                unset($fields[$key]);
                $return .= PHP_EOL . PHP_EOL . Strings::size(trim(isset_get($alternates[$key], $key)), 39) . ' ' . trim(isset_get($data['help']));
            }

            $groups[isset_get($data['help_group'])] = true;
        }

        // Get the fields and group them by help_group
        foreach ($groups as $group => $nothing) {
            $body = '';

            if ($group) {
                $header = PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . Color::apply(strtoupper(trim($group)), 'white');
            } else {
                $header = PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . Color::apply(strtoupper(tr('Miscellaneous information')), 'white');
            }

            foreach ($fields as $key => $data) {
                if (isset_get($data['help_group']) === $group) {
                    unset($fields[$key]);
                    $body .=  PHP_EOL . PHP_EOL . Strings::size(isset_get($alternates[$key], $key), 39) . ' ' . isset_get($data['help']);
                }
            }

            if ($group) {
                if ($body) {
                    // There is body text, add the header and body to the return text
                    $return .= $header . $body;
                }
            } else {
                $miscellaneous = $header . $body;
            }
        }

        // Get the fields that have no group
        return $help . $return . isset_get($miscellaneous) . PHP_EOL;
    }


    /**
     * Returns a DataEntry object matching the specified identifier
     *
     * @note This method also accepts DataEntry objects, in which case it will simply return this object. This is to
     *       simplify "if this is not DataEntry object then this is new DataEntry object" into
     *       "PossibleDataEntryVariable is DataEntry::new(PossibleDataEntryVariable)"
     * @param InterfaceDataEntry|string|int|null $identifier
     * @return static|null
     */
    public static function get(InterfaceDataEntry|string|int|null $identifier = null): ?static
    {
        if (!$identifier) {
            // No identifier specified, just return an empty object
            return static::new();
        }

        if (is_object($identifier)) {
            // This already is a DataEntry object, no need to create one. Just validate that this is the same class
            if (get_class($identifier) !== static::class) {
                throw new OutOfBoundsException(tr('Specified identifier has the class ":has" but should have the class ":should"', [
                    ':has'    => get_class($identifier),
                    ':should' => static::class
                ]));
            }

            return $identifier;
        }

        $entry = new static($identifier);

        if ($entry->getId()) {
            return $entry;
        }

        throw DataEntryNotExistsException::new(tr('The ":label" entry ":identifier" does not exist', [
            ':label'      => static::$entry_name,
            ':identifier' => $identifier
        ]))->makeWarning();
    }



    /**
     * Returns a random DataEntry object
     *
     * @return static|null
     */
    public static function getRandom(): ?static
    {
        $entry      = new static();
        $table      = $entry->getTable();
        $identifier = sql()->getColumn('SELECT `id` FROM `' . $table . '` ORDER BY RAND() LIMIT 1;');

        if ($identifier) {
            return static::get($identifier);
        }

        throw new OutOfBoundsException(tr('Cannot select random record for table ":table", no records found', [
            ':table' => $table
        ]));
    }



    /**
     * Returns true if an entry with the specified identifier exists
     *
     * @param string|int|null $identifier The unique identifier, but typically not the database id, usually the
     *                                    seo_email, or seo_name
     * @param bool $throw_exception       If the entry does not exist, instead of returning false will throw a
     *                                    DataEntryNotExistsException
     * @return bool
     */
    public static function exists(string|int $identifier = null, bool $throw_exception = false): bool
    {
        if (!$identifier) {
            throw new OutOfBoundsException(tr('Cannot check for ":type" type DataEntry, no identifier specified', [
                ':type' => self::$entry_name
            ]));
        }

        $exists = static::new($identifier)->getId();

        if (!$exists) {
            if ($throw_exception) {
                throw DataEntryAlreadyExistsException::new(tr('The ":type" type data entry with identifier ":id" already exists', [
                    ':type' => self::$entry_name,
                    ':id'   => $identifier
                ]))->makeWarning();
            }
        }

        return (bool) $exists;
    }



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
    public static function notExists(string|int $identifier = null, ?int $id = null, bool $throw_exception = false): bool
    {
        if (!$identifier) {
            throw new OutOfBoundsException(tr('Cannot check for ":type" type DataEntry, no identifier specified', [
                ':type' => self::$entry_name
            ]));
        }

        $exists = static::new($identifier)->getId();

        if ($exists) {
            if ($id !== $exists) {
                if ($throw_exception) {
                    throw DataEntryAlreadyExistsException::new(tr('The ":type" type data entry with identifier ":id" already exists', [
                        ':type' => self::$entry_name,
                        ':id'   => $identifier
                    ]))->makeWarning();
                }
            }
        }

        return !$exists;
    }



    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }



    /**
     * Returns true if this is a new entry that hasn't been written to the database yet
     *
     * @return bool
     */
    public function isNew(): bool
    {
        return !$this->getDataValue('id');
    }



    /**
     * Returns id for this database entry
     *
     * @return int|null
     */
    public function getId(): int|null
    {
        return $this->getDataValue('id');
    }



    /**
     * Returns id for this database entry that can be used in logs
     *
     * @return string
     */
    public function getLogId(): string
    {
        return $this->getDataValue('id') . ' / ' . $this->getDataValue($this->unique_field);
    }



    /**
     * Returns status for this database entry
     *
     * @return ?String
     */
    public function getStatus(): ?string
    {
        return $this->getDataValue('status');
    }



    /**
     * Set the status for this database entry
     *
     * @param string|null $status
     * @param string|null $comments
     * @return static
     */
    public function setStatus(?String $status, ?string $comments = null): static
    {
        sql()->setStatus($status, $this->table, ['id' => $this->getId(), 'meta_id' => $this->getMetaId()], $comments);
        return $this->setDataValue('status', $status);
    }



    /**
     * Returns the meta state for this database entry
     *
     * @return ?String
     */
    public function getMetaState(): ?string
    {
        return $this->getDataValue('meta_state');
    }



    /**
     * Set the meta state for this database entry
     *
     * @param string|null $state
     * @return static
     */
    protected function setMetaState(?String $state): static
    {
        return $this->setDataValue('meta_state', $state);
    }



    /**
     * Delete the specified entries
     *
     * @param string|null $comments
     * @return static
     */
    public function delete(?string $comments = null): static
    {
        return $this->setStatus('deleted', $comments);
    }



    /**
     * Undelete the specified entries
     *
     * @param string|null $comments
     * @return static
     */
    public function undelete(?string $comments = null): static
    {
        return $this->setStatus(null, $comments);
    }



    /**
     * Erase this DataEntry from the database
     *
     * @return static
     */
    public function erase(): static
    {
        sql()->erase('users', ['id' => $this->getId()]);
        return $this;
    }



    /**
     * Returns the object that created this data entry
     *
     * @note Returns NULL if this class has no support for created_by information or has not been written to disk yet
     * @return User|null
     */
    public function getCreatedBy(): ?User
    {
        $created_by = $this->getDataValue('created_by');

        if ($created_by === null) {
            return null;
        }

        return new User($created_by);
    }



    /**
     * Returns the object that created this data entry
     *
     * @note Returns NULL if this class has no support for created_by information or has not been written to disk yet
     * @return DateTime|null
     */
    public function getCreatedOn(): ?DateTime
    {
        $created_on = $this->getDataValue('created_on');

        if ($created_on === null) {
            return null;
        }

        return new DateTime($created_on);
    }



    /**
     * Returns the meta information for this entry
     *
     * @note Returns NULL if this class has no support for meta information available, or hasn't been written to disk
     *       yet
     * @return Meta|null
     */
    public function getMeta(): ?Meta
    {
        $meta_id = $this->getDataValue('meta_id');

        if ($meta_id === null) {
            return null;
        }

        return new Meta($meta_id);
    }



    /**
     * Returns the meta id for this entry
     *
     * @return int|null
     */
    public function getMetaId(): ?int
    {
        return $this->getDataValue('meta_id');
    }



    /**
     * Returns a string containing all diff data
     *
     * @return string|null
     */
    public function getDiff(): ?string
    {
        return $this->diff;
    }


    /**
     * Create the data for this object with the new specified data
     *
     * @param array|null $data
     * @param bool $no_arguments_left
     * @return static
     */
    public function create(?array $data = null, bool $no_arguments_left = false): static
    {
        $this->user_modifying = true;

        if (!is_object($data)) {
            if (PLATFORM_HTTP) {
                // Validator was specified,
                $data = $this->validate(PostValidator::new(), $no_arguments_left);
            } else {
                // Validator was specified,
                $this->setCliFields();
                $data = $this->validate(ArgvValidator::new(), $no_arguments_left);
            }
        }

        $this
            ->setDiff($data)
            ->setData($data)
            ->user_modifying = false;

        return $this->save();
    }


    /**
     * Modify the data for this object with the new specified data
     *
     * @param array|null $data
     * @param bool $no_arguments_left
     * @return static
     */
    public function modify(?array $data = null, bool $no_arguments_left = false): static
    {
        $this->user_modifying = true;

        if (!is_object($data)) {
            if (PLATFORM_HTTP) {
                // Validator was specified,
                $data = $this->validate(PostValidator::new(), $no_arguments_left);
            } else {
                // Validator was specified,
                $this->setCliFields();
                $data = $this->validate(ArgvValidator::new(), $no_arguments_left);
            }
        }

        // Check meta state
        if ($this->getMetaState()) {
            if (isset_get($data['meta_state']) !== $this->getMetaState()) {
                // State mismatch! This means that somebody else updated this record while we were modifying it.
                switch ($this->state_mismatch_handling) {
                    case StateMismatchHandling::ignore:
                        Log::warning(tr('Ignoring database and user meta state mismatch for ":type" type record with ID ":id"', [
                            ':id'   => $this->getId(),
                            ':type' => static::$entry_name
                        ]));
                        break;

                    case StateMismatchHandling::allow_override:
                        // Okay, so the state did NOT match, and we WILL throw the state mismatch exception, BUT we WILL
                        // update the state data so that a second attempt can succeed
                        $data['meta_state'] = $this->getMetaState();
                        break;

                    case StateMismatchHandling::restrict:
                        throw new DataEntryStateMismatchException(tr('Database and user meta state for ":type" type record with ID ":id" do not match', [
                            ':id'   => $this->getId(),
                            ':type' => static::$entry_name
                        ]));
                }
            }
        }

        $this
            ->setDiff($data)
            ->setData($data, true)
            ->user_modifying = false;

        return $this->save();
    }



    /**
     * Generate diff data that will be stored and used by the meta system
     *
     * @param array|null $data
     * @return static
     */
    protected function setDiff(?array $data): static
    {
        if (Meta::isEnabled()) {
            if ($data === null) {
                $diff = [
                    'from' => [],
                    'to'   => $this->data
                ];
            } else {
                $diff = [
                    'from' => [],
                    'to'   => []
                ];

                // Check all keys and register changes
                foreach ($this->fields as $key => $definition) {
                    if (isset_get($this->fields[$key]['readonly']) or isset_get($this->fields[$key]['disabled'])) {
                        continue;
                    }

                    if (in_array($key, $this->meta_fields)) {
                        continue;
                    }

                    if (isset_get($data[$key]) === null) {
                        continue;
                    }

                    if (isset_get($this->data[$key]) != isset_get($data[$key])) {
                        // If both records were empty (from NULL to 0 for example) then don't register
                        if ($this->data[$key] or $data[$key]) {
                            $diff['from'][$key] = (string) $this->data[$key];
                            $diff['to'][$key]   = (string) $data[$key];
                        }
                    }
                }
            }

            try {
                // Truncate the diff to 64K for storage
                $this->diff = Json::encodeTruncateToMaxSize($diff, 65530);

            } catch (Exception|Throwable $e) {
                // Just in case the truncated JSON encoding somehow failed, make sure we can continue!
                Notification::new($e)
                    ->log()->send();

                $this->diff = tr('FAILED TO ENCODE DATA DIFF, SEE SYSTEM LOGS');
            }
        } else {
            $this->diff = null;
        }

        return $this;
    }



    /**
     * Sets all data for this data entry at once with an array of information
     *
     * @param array|null $data The data for this DataEntry object
     * @param bool $modify     If true, then
     * @return static
     */
    protected function setData(?array $data, bool $modify = false): static
    {
        if ($data === null) {
            // No data set
            return $this;
        }

        if (empty($this->fields)) {
            throw new OutOfBoundsException(tr('Data keys were not defined for this ":class" class', [
                ':class' => gettype($this)
            ]));
        }

        foreach ($data as $key => $value) {
            // Meta keys cannot be set through DataEntry::setData()
            if (in_array($key, $this->meta_fields)) {
                continue;
            }

            switch ($key) {
                case 'password':
                    if ($modify) {
                        continue 2;
                    }

                    // Passwords are always set directly
                    $this->setPasswordDirectly($value);
                    continue 2;

                case $this->unique_field:
                    // Store this data directly
                    if ($modify) {
                        continue 2;
                    }

                    $this->setDataValue($this->unique_field, $value);
                    continue 2;
            }

            // Store this data through the methods to ensure datatype and filtering is done correctly
            $method = $this->convertVariableToSetMethod($key);

            // Only apply if a method exist for this variable
            if (method_exists($this, $method)){
                $this->$method($value);
            }
        }

        return $this;
    }



    /**
     * Returns all keys that are protected and cannot be removed from this object
     *
     * @return array
     */
    public function getProtectedFields(): array
    {
        return $this->protected_fields;
    }



    /**
     * Adds a list of extra keys that are protected and cannot be removed from this object
     *
     * @param array $keys
     * @return static
     */
    protected function addProtectedKeys(array $keys): static
    {
        foreach ($keys as $key) {
            $this->addProtectedKey($key);
        }

        return $this;
    }



    /**
     * Adds a single extra key that are protected and cannot be removed from this object
     *
     * @param string $key
     * @return static
     */
    protected function addProtectedKey(string $key): static
    {
        $this->protected_fields[] = $key;
        return $this;
    }



    /**
     * Returns all data for this data entry at once with an array of information
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     * @return array
     */
    public function getData(): array
    {
        return Arrays::remove($this->data, $this->protected_fields);
    }



    /**
     * Sets all metadata for this data entry at once with an array of information
     *
     * @param ?array $data
     * @return static
     * @throws OutOfBoundsException
     */
    protected function setMetaData(?array $data = null): static
    {
        // Reset meta fields
        foreach ($this->meta_fields as $field) {
            $this->data[$field] = null;
        }

        if ($data === null) {
            // No data set
            return $this;
        }

        if (empty($this->fields)) {
            throw new OutOfBoundsException(tr('Data keys were not defined for this ":class" class', [
                ':class' => gettype($this)
            ]));
        }

        foreach ($data as $key => $value) {
            // Only these keys will be set through setMetaData()
            if (!in_array($key, $this->meta_fields)) {
                continue;
            }

            // Store the meta data
            $this->data[$key] = $value;
        }

        return $this;
    }


    /**
     * Sets the value for the specified data key
     *
     * @param string $key
     * @param mixed $value
     * @param bool $force
     * @return static
     */
    protected function setDataValue(string $key, mixed $value, bool $force = false): static
    {
        // Only save values that are defined for this object
        if (array_key_exists($key, $this->fields)) {
            // Skip all meta fields like id, created_on, meta_id, etc etc etc..
            if (!in_array($key, $this->meta_fields)) {
                // If the key is defined as readonly or disabled, it cannot be updated!
                if ($force or (!$this->user_modifying or (empty($this->fields[$key]['readonly']) and empty($this->fields[$key]['disabled'])))) {
                    // Don't update keys with NULL values
                    if ($value !== null) {
                        // If the value is considered empty, however, it might be forced to NULL
                        if (!$value) {
                            //  By default, all columns with empty values will be pushed to NULL unless specified otherwise
                            if (!array_key_exists('db_null', $this->fields[$key]) or $this->fields[$key]['db_null'] !== false) {
                                // Force the data value to NULL
                                $value = null;
                            }
                        }

                        $this->data[$key] = $value;
                    }
                }
            }
        }

        return $this;
    }



    /**
     * Sets the value for the specified data key
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function addDataValue(string $key, mixed $value): static
    {
        if (!array_key_exists($key, $this->data)) {
            $this->data[$key] = [];
        }

        if (!is_array($this->data[$key])) {
            throw new OutOfBoundsException(tr('Cannot *add* data value to key ":key", the value datatype is not "array"', [
                ':key' => $key
            ]));
        }

        $this->data[$key][] = $value;
        return $this;
    }



    /**
     * Returns the value for the specified data key
     *
     * @param string $key
     * @return mixed
     */
    protected function getDataValue(string $key): mixed
    {
        $this->checkProtected($key);
        return isset_get($this->data[$key]);
    }



    /**
     * Returns if the specified DataValue key can be visible outside this object or not
     *
     * @param string $key
     * @return void
     */
    protected function checkProtected(string $key): void
    {
        if (in_array($key, $this->protected_fields)) {
            throw new OutOfBoundsException(tr('Specified DataValue key ":key" is protected and cannot be accessed', [
                ':key' => $key
            ]));
        }
    }



    /**
     * Rewrite the specified variable into the set method for that variable
     *
     * @param string $variable
     * @return string
     */
    protected function convertVariableToSetMethod(string $variable): string
    {
        $return = explode('_', $variable);
        $return = array_map('ucfirst', $return);
        $return = implode('', $return);

        return 'set' . ucfirst($return);
    }



    /**
     * Only return columns that actually contain data
     *
     * @return array
     */
    protected function getDataColumns(): array
    {
        $return = [];

        foreach ($this->fields as $field => $definitions) {
            if (isset_get($definitions['virtual'])) {
                // This is a virtual column, ignore it.
                continue;
            }

            $return[$field] = isset_get($this->data[$field]);
        }

        return $return;
    }



    /**
     * Returns the data to add for an SQL insert
     *
     * @return array
     */
    protected function getInsertColumns(): array
    {
        $return = $this->getDataColumns();
        $return = Arrays::remove($return, $this->fields_filter_on_insert);

        return $return;
    }



    /**
     * Returns the data to add for an SQL update
     *
     * @return array
     */
    protected function getUpdateColumns(): array
    {
        return $this->getDataColumns();
    }



    /**
     * Will save the data from this data entry to database
     *
     * @param string|null $comments
     * @return static
     */
    public function save(?string $comments = null): static
    {
        // Apply defaults
        $this->applyDefaults();

        // Build diff if inserting
        if (!$this->getId()) {
            $this->setDiff(null);
        }

        // Debug this specific entry?
        if ($this->debug) {
            $debug = Sql::debug(true);
        }

        // Write the entry
        $this->data['id'] = sql()->write($this->table, $this->getInsertColumns(), $this->getUpdateColumns(), $comments, $this->diff);

        // Return debug mode if required
        if (isset($debug)) {
            Sql::debug($debug);
        }

        // Write the list, if set
        $this->list?->save();

        // Done!
        return $this;
    }



    /**
     * Creates and returns a CLI table for the data in this entry
     *
     * @param string|null $key_header
     * @param string|null $value_header
     * @return void
     */
    public function getCliForm(?string $key_header = null, ?string $value_header = null): void
    {
        Cli::displayForm($this->data, $key_header, $value_header);
    }



    /**
     * Creates and returns an HTML for the data in this entry
     *
     * @return DataEntryForm
     */
    public function getHtmlForm(): DataEntryForm
    {
        return DataEntryForm::new()
            ->setSource($this->data)
            ->setKeys($this->fields)
            ->setKeysDisplay($this->field_display);
    }


    /**
     * Returns the key definitions for this DataEntry object
     *
     * @return array
     */
    protected function getFields(): array
    {
        return $this->fields;
    }


    /**
     * Modify the form keys
     *
     * @param string $form_key
     * @param array $settings
     * @return static
     */
    public function modifyKeys(string $form_key, array $settings): static
    {
        if (!array_key_exists($form_key, $this->fields)) {
            throw new OutOfBoundsException(tr('Specified form key ":key" does not exist', [
                ':key' => $form_key
            ]));
        }

        foreach ($settings as $key => $value) {
            if ($key === 'size') {
                $this->field_display[$form_key] = $value;
            } else {
                $this->fields[$form_key][$key] = $value;
            }
        }

        return $this;
    }


    /**
     * Validate the data using the specified validator
     *
     * @param ArgvValidator|PostValidator|GetValidator $validator
     * @param bool $no_arguments_left
     * @param bool $modify
     * @return array
     */
    abstract protected function validate(ArgvValidator|PostValidator|GetValidator $validator, bool $no_arguments_left = false, bool $modify = true): array;


    /**
     * Returns either the specified field, or if $translate has content, the alternate field name
     *
     * @param string $field
     * @return string
     */
    protected function getAlternateValidationField(string $field): string
    {
        if (!array_key_exists($field, $this->fields)) {
            throw new OutOfBoundsException(tr('Specified field name ":field" does not exist', [
                ':field' => $field
            ]));
        }

        $alt = isset_get($this->cli_fields[$field]['cli']);
        $alt = Strings::until($alt, ' ');
        $alt = trim($alt);

        return get_null($alt) ?? $field;
    }


    /**
     * Apply defaults to this objects according to the key configuration
     *
     * @return $this
     */
    protected function applyDefaults(): static
    {
        foreach ($this->fields as $key => $configuration) {
            if (in_array($key, $this->meta_fields)) {
                continue;
            }

            if (!isset($this->data[$key])) {
                if (isset_get($configuration['required'])) {
                    throw new OutOfBoundsException(tr('Required field ":field" has not been set', [
                        ':field' => $key
                    ]));
                }

                $this->data[$key] = isset_get($configuration['default']);
            }
        }

        return $this;
    }



    /**
     * Load all object data from database
     *
     * @param string|int $identifier
     * @return void
     */
    protected function load(string|int $identifier): void
    {
        if (is_numeric($identifier)) {
            $data = sql()->get('SELECT * FROM `' . $this->table . '` WHERE `id`                           = :id'                     , [':id'                     => $identifier]);
        } else {
            $data = sql()->get('SELECT * FROM `' . $this->table . '` WHERE `' . $this->unique_field . '` = :' . $this->unique_field, [':'. $this->unique_field => $identifier]);
        }

        // Store all data in the object
        $this->setMetaData($data);
        $this->setData($data);
    }


    /**
     * Returns the field definitions for this DataEntry class
     *
     * @return array
     */
    protected static function getAllFieldDefinitions(): array
    {
         return array_merge([
            'id' => [
                'meta'     => true,
                'type'     => 'numeric',
                'readonly' => true,
                'size'     => 3,
                'label'    => tr('Database ID'),
            ],
            'created_on' => [
                'meta'     => true,
                'readonly' => true,
                'type'     => 'datetime-local',
                'size'     => 3,
                'label'    => tr('Created on'),
            ],
            'created_by' => [
                'meta'    => true,
                'element' => function (string $key, array $data, array $source) {
                    if ($source['created_by']) {
                        return Users::getHtmlSelect($key)
                            ->setSelected(isset_get($source['created_by']))
                            ->setDisabled(true)
                            ->render();
                    } else {
                        return InputText::new()
                            ->setName($key)
                            ->setDisabled(true)
                            ->setValue(tr('System'))
                            ->render();
                    }
                },
                'readonly' => true,
                'size'     => 3,
                'label'    => tr('Created by'),
            ],
            'meta_id' => [
                'meta'     => true,
                'visible'  => false,
                'readonly' => true,
            ],
            'meta_state' => [
                'meta'     => true,
                'visible'  => false,
                'readonly' => true,
            ],
            'status' => [
                'meta'     => true,
                'readonly' => true,
                'label'    => tr('Status'),
                'size'     => 3,
                'default'  => tr('Ok'),
            ],
        ], static::getFieldDefinitions());
    }


    /**
     * Returns the field definitions for the data fields in this DataEntry object
     *
     * @return array
     */
    abstract protected static function getFieldDefinitions(): array;
}