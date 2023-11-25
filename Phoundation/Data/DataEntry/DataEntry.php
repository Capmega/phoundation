<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry;

use Exception;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\User;
use Phoundation\Cli\Cli;
use Phoundation\Cli\CliColor;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Meta\Interfaces\MetaInterface;
use Phoundation\Core\Meta\Meta;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Enums\StateMismatchHandling;
use Phoundation\Data\DataEntry\Exception\DataEntryAlreadyExistsException;
use Phoundation\Data\DataEntry\Exception\DataEntryDeletedException;
use Phoundation\Data\DataEntry\Exception\DataEntryException;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntry\Exception\DataEntryReadonlyException;
use Phoundation\Data\DataEntry\Exception\DataEntryStateMismatchException;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryDefinitions;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\DataConfigPath;
use Phoundation\Data\Traits\DataDatabaseConnector;
use Phoundation\Data\Traits\DataDebug;
use Phoundation\Data\Traits\DataDisabled;
use Phoundation\Data\Traits\DataMetaEnabled;
use Phoundation\Data\Traits\DataReadonly;
use Phoundation\Data\Validator\ArrayValidator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Data\Validator\Validator;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Date\DateTime;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\DataEntryForm;
use Phoundation\Web\Html\Components\Input\InputText;
use Phoundation\Web\Html\Components\Interfaces\DataEntryFormInterface;
use Phoundation\Web\Html\Enums\InputType;
use Phoundation\Web\Html\Enums\InputTypeExtended;
use Throwable;


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
abstract class DataEntry implements DataEntryInterface
{
    use DataDebug;
    use DataReadonly;
    use DataDisabled;
    use DataConfigPath;
    use DataMetaEnabled;
    use DataEntryDefinitions;
    use DataDatabaseConnector;


    /**
     * Contains the data for all information of this data entry
     *
     * @var array $source
     */
    protected array $source = [];

    /**
     * Default protected keys, keys that may not leave this object
     *
     * @var array|string[]
     */
    protected array $protected_fields = ['password', 'key'];

    /**
     * These keys should not ever be processed
     *
     * @var array $meta_fields
     */
    protected static array $meta_fields = [
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
     * If true, this DataEntry will allow the creation of new entries
     *
     * @var bool $allow_create
     */
    protected bool $allow_create = true;

    /**
     * If true, this DataEntry will allow modification of existing entries
     *
     * @var bool $allow_modify
     */
    protected bool $allow_modify = true;

    /**
     * Global loading flag, when data is loaded into the object from a database
     *
     * @var bool $is_loading
     */
    protected bool $is_loading = true;

    /**
     * Returns true if the DataEntry object internal data structure has been updated
     *
     * @var bool $is_modified
     */
    protected bool $is_modified = false;

    /**
     * True when data is being applied through the DataEntry::apply() method
     *
     * @var bool $is_applying
     */
    protected bool $is_applying = false;

    /**
     * Returns true if the DataEntry object was just successfully saved
     *
     * @var bool $is_saved
     */
    protected bool $is_saved = false;

    /**
     * If true, the data in this DataEntry has been validated
     *
     * @var bool $is_validated
     */
    protected bool $is_validated = false;

    /**
     * Query builder to create the load query
     *
     * @var QueryBuilder|null
     */
    protected ?QueryBuilder $query_builder = null;

    /**
     * If true, all data will be validated before it is saved
     *
     * @var bool $validate
     */
    protected bool $validate = true;

    /**
     * Tracks if the meta-system is enabled for this DataEntry object
     *
     * @var bool $meta_enabled
     */
    protected bool $meta_enabled = true;


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
        return $this->source;
    }


    /**
     * DataEntry class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null $column
     * @param bool $meta_enabled
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, ?string $column = null, bool $meta_enabled = true)
    {
        $column = static::ensureColumn($identifier, $column);

        // Meta enabled?
        $this->meta_enabled = $meta_enabled;

        // Set up the fields for this object
        $this->setMetaDefinitions();
        $this->setDefinitions($this->definitions);

        if ($identifier) {
            $this->load($identifier, $column);

        } else {
            $this->setMetaData();
        }
    }


    /**
     * Returns a new DataEntry object
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null $column
     * @param bool $meta_enabled
     * @return static
     */
    public static function new(DataEntryInterface|string|int|null $identifier = null, ?string $column = null, bool $meta_enabled = true): static
    {
        return new static($identifier, $column, $meta_enabled);
    }


    /**
     * Returns a new DataEntry object from the specified array source
     *
     * @param array $source
     * @param bool $meta_enabled
     * @return $this
     */
    public static function fromSource(array $source, bool $meta_enabled = true): static
    {
        return static::new(meta_enabled: $meta_enabled)->setSourceString($source);
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
    abstract public static function getDataEntryName(): string;


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    abstract public static function getUniqueField(): ?string;


    /**
     * Returns if this DataEntry will validate data before saving
     *
     * @return bool
     */
    public function getValidate(): bool
    {
        return $this->validate;
    }


    /**
     * Sets if this DataEntry will validate data before saving
     *
     * @return $this
     */
    public function setValidate(bool $validate): static
    {
        $this->validate = $validate;
        return $this;
    }


    /**
     * Returns the query builder for this data entry
     *
     * @return QueryBuilderInterface
     */
    public function getQueryBuilder(): QueryBuilderInterface
    {
        if (!$this->query_builder) {
            $this->query_builder = QueryBuilder::new($this);
        }

        return $this->query_builder;
    }


    /**
     * Returns true if the internal data structures have been modified
     *
     * @return bool
     */
    public function isModified(): bool
    {
        return $this->is_modified;
    }


    /**
     * Returns true if the data in this DataEntry has been validated
     *
     * @return bool
     */
    public function isValidated(): bool
    {
        return $this->is_validated;
    }


    /**
     * Returns true if the data in this DataEntry is currently in a state of being applied through DataEntry::apply()
     *
     * @return bool
     */
    public function isApplying(): bool
    {
        return $this->is_applying;
    }


    /**
     * Returns true if the DataEntry was just successfully saved
     *
     * @return bool
     */
    public function isSaved(): bool
    {
        return $this->is_saved;
    }


    /**
     * Returns true if this object can be saved in the database
     *
     * Objects loaded from configuration (for the moment) cannot be saved and will return false. Trying to execute the
     * DataEntry::save() call will result in an exception
     *
     * @return bool
     */
    public function canBeSaved(): bool
    {
        return !$this->config_path;
    }


    /**
     * Returns id for this database entry that can be used in logs
     *
     * @return bool
     */
    public function getAllowCreate(): bool
    {
        return $this->allow_create;
    }


    /**
     * Returns id for this database entry that can be used in logs
     *
     * @param bool $allow_create
     * @return static
     */
    public function setAllowCreate(bool $allow_create): static
    {
        $this->allow_create = $allow_create;
        return $this;
    }


    /**
     * Returns if this DataEntry will allow modification of existing entries
     *
     * @return bool
     */
    public function getAllowModify(): bool
    {
        return $this->allow_modify;
    }


    /**
     * Sets if this DataEntry will allow modification of existing entries
     *
     * @param bool $allow_modify
     * @return static
     */
    public function setAllowModify(bool $allow_modify): static
    {
        $this->allow_modify = $allow_modify;
        return $this;
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
        foreach (static::new()->getDefinitions() as $definitions) {
            if ($definitions->getCliField() and $definitions->getCliAutoComplete()) {
                $arguments[$definitions->getCliField()] = $definitions->getCliAutoComplete();
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
     * @return array
     */
    public function getCliFields(): array
    {
        $return = [];

        foreach ($this->definitions as $field => $definitions) {
            if ($definitions->getCliField()) {
                $return[$field] = $definitions->getCliField();
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
    public static function getHelpText(?string $help = null): string
    {
        if ($help) {
            $help = trim($help);
            $help = preg_replace('/ARGUMENTS/', CliColor::apply(strtoupper(tr('ARGUMENTS')), 'white'), $help);
        }

        $groups = [];
        $fields = static::new()->getDefinitions();
        $return = PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . CliColor::apply(strtoupper(tr('REQUIRED ARGUMENTS')), 'white');

        // Get the required fields and gather a list of available help groups
        foreach ($fields as $id => $definitions) {
            if (!$definitions->getOptional()) {
                $fields->delete($id);
                $return .= PHP_EOL . PHP_EOL . Strings::size($definitions->getCliField(), 39) . ' ' . $definitions->getHelpText();
            }

            $groups[$definitions->getHelpGroup()] = true;
        }

        // Get the fields and group them by help_group
        foreach ($groups as $group => $nothing) {
            $body = '';

            if ($group) {
                $header = PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . CliColor::apply(strtoupper(trim($group)), 'white');
            } else {
                $header = PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . CliColor::apply(strtoupper(tr('Miscellaneous information')), 'white');
            }

            foreach ($fields as $id => $definitions) {
                if ($definitions->getHelpGroup() === $group) {
                    $fields->delete($id);
                    $body .= PHP_EOL . PHP_EOL . Strings::size($definitions->getCliField(), 39) . ' ' . $definitions->getHelpText();
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
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null $column
     * @param bool $meta_enabled
     * @return static|null
     * @throws OutOfBoundsException|DataEntryNotExistsException|DataEntryDeletedException
     */
    public static function get(DataEntryInterface|string|int|null $identifier, ?string $column = null, bool $meta_enabled = false): ?static
    {
        if (!$identifier) {
            // No identifier specified, return an empty object
            return new static(null, null, $meta_enabled);
        }

        if (is_object($identifier)) {
            // This already is a DataEntry object, no need to create one. Validate that this is the same class
            if (!$identifier instanceof static) {
                throw new OutOfBoundsException(tr('Specified DataEntry identifier has the class ":has" but should have this object\'s class ":should"', [
                    ':has'    => get_class($identifier),
                    ':should' => static::class
                ]));
            }

            $entry = $identifier;

        } else {
            $entry = new static($identifier, $column, $meta_enabled);
        }

        if ($entry->isNew()) {
            // So this entry does not exist in the database. Does it perhaps exist in configuration?
            $path = static::new()->getConfigPath();

            if ($path) {
                // See if there is a configuration entry in the specified path
                $entry = Config::getArray($path . $identifier, []);

                if (count($entry)) {
                    // Return a new DataEntry object from the configuration source
                    return static::fromSource($entry, $meta_enabled)->setReadonly(true);
                }
            }

            throw DataEntryNotExistsException::new(tr('The ":class" entry ":identifier" does not exist', [
                ':class'      => static::getClassName(),
                ':identifier' => $identifier
            ]))->makeWarning();
        }

        if ($entry->isDeleted()) {
            // This entry has been deleted and can only be viewed by user with the "deleted" right
            if (!Session::getUser()->hasAllRights('deleted')) {
                throw DataEntryDeletedException::new(tr('The ":class" entry ":identifier" is deleted', [
                    ':class'      => static::getClassName(),
                    ':identifier' => $identifier
                ]))->makeWarning();

            }
        }

        return $entry;
    }


    /**
     * Returns the name for this user that can be displayed
     *
     * @return string
     */
    function getDisplayName(): string
    {
        $postfix = null;

        if ($this->getStatus() === 'deleted') {
            $postfix = ' ' . tr('[DELETED]');
        }

        return $this->getSourceFieldValue('string', static::getUniqueField()) . $postfix;
    }


    /**
     * Returns a random DataEntry object
     *
     * @param string $database_connector
     * @param bool $meta_enabled
     * @return static|null
     */
    public static function getRandom(string $database_connector = 'system', bool $meta_enabled = true): ?static
    {
        $table = static::getTable();
        $identifier = sql($database_connector)->getInteger('SELECT `id` FROM `' . $table . '` ORDER BY RAND() LIMIT 1;');

        if ($identifier) {
            return static::get($identifier, 'id', $meta_enabled);
        }

        throw new OutOfBoundsException(tr('Cannot select random record for table ":table", no records found', [
            ':table' => $table
        ]));
    }


    /**
     * Returns true if an entry with the specified identifier exists
     *
     * @param string|int $identifier The unique identifier, but typically not the database id, usually the seo_email,
     *                               or seo_name
     * @param string|null $column
     * @param int|null $not_id
     * @param bool $throw_exception If the entry does not exist, instead of returning false will throw a
     *                                    DataEntryNotExistsException
     * @return bool
     */
    public static function exists(string|int $identifier, ?string $column = null, ?int $not_id = null, bool $throw_exception = false): bool
    {
        if (!$identifier) {
            throw new OutOfBoundsException(tr('Cannot check if ":class" class DataEntry exists, no identifier specified', [
                ':class' => static::getClassName()
            ]));
        }

        $column  = static::ensureColumn($identifier, $column);
        $execute = [':identifier' => $identifier];

        if ($not_id) {
            $execute[':id'] = $not_id;
        }

        $exists = sql()->getColumn('SELECT `id` 
                                          FROM   `' . static::getTable() . '` 
                                          WHERE  `' . $column . '`   = :identifier
                                          ' . ($not_id ? 'AND `id` != :id' : '') . ' 
                                          LIMIT  1', $execute);

        if (!$exists and $throw_exception) {
            throw DataEntryAlreadyExistsException::new(tr('The ":type" type data entry with identifier ":id" already exists', [
                ':type' => static::getClassName(),
                ':id'   => $identifier
            ]))->makeWarning();
        }

        return (bool) $exists;
    }


    /**
     * Returns true if an entry with the specified identifier does not exist
     *
     * @param string|int $identifier The unique identifier, but typically not the database id, usually the
     *                                    seo_email, or seo_name
     * @param string|null $column
     * @param int|null $id If specified, will ignore the found entry if it has this ID as it will be THIS
     *                                    object
     * @param bool $throw_exception If the entry exists (and does not match id, if specified), instead of
     *                                    returning false will throw a DataEntryNotExistsException
     * @return bool
     */
    public static function notExists(string|int $identifier, ?string $column = null, ?int $id = null, bool $throw_exception = false): bool
    {
        if (!$identifier) {
            throw new OutOfBoundsException(tr('Cannot check if ":class" class DataEntry not exists, no identifier specified', [
                ':class' => static::getClassName()
            ]));
        }

        $column  = static::ensureColumn($identifier, $column);
        $execute = [':identifier' => $identifier];

        if ($id) {
            $execute[':id'] = $id;
        }

        $exists = sql()->getColumn('SELECT `id` 
                                          FROM   `' . static::getTable() . '` 
                                          WHERE  `' . $column . '` = :identifier
                                          ' . ($id ? 'AND `id`   != :id' : '') . ' 
                                          LIMIT  1', $execute);

        if ($exists and $throw_exception) {
            throw DataEntryAlreadyExistsException::new(tr('The ":type" type data entry with identifier ":id" already exists', [
                ':type' => static::getClassName(),
                ':id'   => $identifier
            ]))->makeWarning();
        }

        return !$exists;
    }


    /**
     * Returns the class name of this DataEntry object
     *
     * @return string
     */
    public static function getClassName(): string
    {
        return Strings::fromReverse(static::class, '\\');
    }


    /**
     * Returns true if this is a new entry that hasn't been written to the database yet
     *
     * @return bool
     */
    public function isNew(): bool
    {
        return !$this->getId();
    }


    /**
     * Returns id for this database entry
     *
     * @return int|null
     */
    public function getId(): int|null
    {
        return $this->getSourceFieldValue('int', 'id');
    }


    /**
     * Returns id for this database entry that can be used in logs
     *
     * @return string
     */
    public function getLogId(): string
    {
        return $this->getSourceFieldValue('int', 'id') . ' / ' . (static::getUniqueField() ? $this->getSourceFieldValue('string', static::getUniqueField()) : '-');
    }


    /**
     * Returns status for this database entry
     *
     * @return ?string
     */
    public function getStatus(): ?string
    {
        return $this->getSourceFieldValue('string', 'status');
    }


    /**
     * Returns true if this object has the specified status
     *
     * @param string $status
     * @return bool
     */
    public function hasStatus(string $status): bool
    {
        return $status === $this->getSourceFieldValue('string', 'status');
    }


    /**
     * Returns true if this DataEntry has the specified status
     *
     * @param string|null $status
     * @return bool
     */
    public function isStatus(?string $status): bool
    {
        return $this->getSourceFieldValue('string', 'status') === $status;
    }


    /**
     * Returns true if this data entry object is deleted
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->isStatus('deleted');
    }


    /**
     * Set the status for this database entry
     *
     * @param string|null $status
     * @param string|null $comments
     * @return static
     */
    public function setStatus(?string $status, ?string $comments = null): static
    {
        $this->checkReadonly('set-status ' . $status);

        if ($this->getId()) {
            sql($this->database_connector)->dataEntrySetStatus($status, static::getTable(), [
                'id'      => $this->getId(),
                'meta_id' => $this->getMetaId()
            ], $comments, $this->meta_enabled);
        }

        $this->source['status'] = $status;
        return $this;
//        return $this->setSourceValue('status', $status);
    }


    /**
     * Returns the meta-state for this database entry
     *
     * @return ?string
     */
    public function getMetaState(): ?string
    {
        return $this->getSourceFieldValue('string', 'meta_state');
    }


    /**
     * Set the meta-state for this database entry
     *
     * @param string|null $state
     * @return static
     */
    protected function setMetaState(?string $state): static
    {
        return $this->setSourceValue('meta_state', $state);
    }


    /**
     * Sets the password for this user
     *
     * @param string|null $password
     * @return static
     */
    protected function setPasswordDirectly(?string $password): static
    {
        $this->source['password'] = $password;
        return $this;
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
        $this->checkReadonly('erase');
        $this->getMeta()->erase();

        sql($this->database_connector)->erase(static::getTable(), ['id' => $this->getId()]);
        return $this;
    }


    /**
     * Returns the field prefix string
     *
     * @return ?string
     */
    public function getFieldPrefix(): ?string
    {
        return $this->definitions->getFieldPrefix();
    }


    /**
     * Sets the field prefix string
     *
     * @param string|null $prefix
     * @return static
     */
    public function setFieldPrefix(?string $prefix): static
    {
        $this->definitions->setFieldPrefix($prefix);
        return $this;
    }


    /**
     * Returns the object that created this data entry
     *
     * @note Returns NULL if this class has no support for created_by information or has not been written to disk yet
     * @return UserInterface|null
     */
    public function getCreatedBy(): ?UserInterface
    {
        $created_by = $this->getSourceFieldValue('int', 'created_by');

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
        $created_on = $this->getSourceFieldValue('string', 'created_on');

        if ($created_on === null) {
            return null;
        }

        return new DateTime($created_on);
    }


    /**
     * Returns the meta-information for this entry
     *
     * @note Returns NULL if this class has no support for meta-information available, or hasn't been written to disk
     *       yet
     *
     * @param bool $load
     * @return MetaInterface|null
     */
    public function getMeta(bool $load = true): ?MetaInterface
    {
        if ($this->isNew()) {
            // New DataEntry objects have no meta-information
            return null;
        }

        $meta_id = $this->getSourceFieldValue('int', 'meta_id');

        if ($meta_id === null) {
            throw new DataEntryException(tr('DataEntry ":id" does not have meta_id information', [
                ':id' => $this->getId()
            ]));
        }

        return new Meta($meta_id, $load);
    }


    /**
     * Add the specified action to the meta history
     *
     * @param string $action
     * @param string $comments
     * @param array|string|null $diff
     * @return static
     */
    public function addToMetaHistory(string $action, string $comments, array|string|null $diff): static
    {
        if ($this->isNew()) {
            throw new OutOfBoundsException(tr('Cannot add meta-information, this ":class" object is still new', [
                ':class' => $this->getDataEntryName()
            ]));
        }

        $this->getMeta()->action($action, $comments, get_null(Strings::force($diff)));

        return $this;
    }


    /**
     * Returns the meta id for this entry
     *
     * @return int|null
     */
    public function getMetaId(): ?int
    {
        return $this->getSourceFieldValue('int', 'meta_id');
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
     * Modify the data for this object with the new specified data
     *
     * @param bool $clear_source
     * @param ValidatorInterface|array|null &$source
     * @return static
     */
    public function apply(bool $clear_source = true, ValidatorInterface|array|null &$source = null): static
    {
        return $this->checkReadonly('apply')->doApply($clear_source, $source, false);
    }


    /**
     * Forcibly modify the data for this object with the new specified data, putting the object in readonly mode
     *
     * @note In readonly mode this object will no longer be able to write its data!
     * @param bool $clear_source
     * @param ValidatorInterface|array|null $source
     * @return static
     */
    public function forceApply(bool $clear_source = true, ValidatorInterface|array|null &$source = null): static
    {
        return $this->doApply($clear_source, $source, true);
    }


    /**
     * Modify the data for this object with the new specified data
     *
     * @param bool $clear_source
     * @param ValidatorInterface|array|null &$source
     * @param bool $force
     * @return static
     */
    protected function doApply(bool $clear_source, ValidatorInterface|array|null &$source, bool $force): static
    {
        // Are we allowed to create or modify this DataEntry?
        if ($this->getId()) {
            if (!$this->allow_modify) {
                // auto modify not allowed, sorry!
                throw new ValidationFailedException(tr('Cannot modify :entry', [
                    ':entry' => static::getDataEntryName()
                ]));
            }
        } else {
            if (!$this->allow_create) {
                // auto create not allowed, sorry!
                throw new ValidationFailedException(tr('Cannot create new :entry', [
                    ':entry' => static::getDataEntryName()
                ]));
            }
        }

        $this->is_applying  = true;
        $this->is_validated = false;
        $this->is_saved     = false;

        // Select the correct data source and validate the source data. Specified data may be a DataValidator, an array
        // or null. After selecting a data source it will be a DataValidator object which we will then give to the
        // DataEntry::validate() method
        //
        // When in force mode we will NOT clear the failed fields so that they can be sent back to the user for
        // corrections
        $data_source = Validator::get($source);

        if ($this->debug) {
            Log::information('APPLY ' . static::getDataEntryName() . ' (' . get_class($this) . ')', 10);
            Log::information('CURRENT DATA', 10);
            Log::vardump($this->source);
            Log::information('SOURCE', 10);
            Log::vardump($data_source);
            Log::information('SOURCE DATA', 10);
            Log::vardump($data_source->getSource());
        }

        // Get the source array from the validator into the DataEntry object
        if ($force) {
            // Force was used, but the object will now be in readonly mode, so we can save failed data
            // Validate data and copy data into the source array
            $data_source = $this->doNotValidate($data_source, $clear_source);
            $this->copyValuesToSource($data_source, true, true);

        } else {
            // Validate data and copy data into the source array
            $data_source = $this->validate($data_source, $clear_source);

            if ($this->debug) {
                Log::information('APPLYING DATA', 10);
                Log::vardump($data_source);
            }

            // Ensure DataEntry Meta state is okay, then generate the diff data and copy data array to internal data
            $this
                ->validateMetaState($data_source)
                ->createDiff($data_source)
                ->copyValuesToSource($data_source, true);
        }

        $this->is_applying = false;

        if ($this->debug) {
            Log::information('DATA AFTER APPLY', 10);
            Log::vardump($this->source);
        }

        return $this;
    }


    /**
     * Validates the source data and returns it
     *
     * @param ValidatorInterface|array|null $data
     * @return static
     */
    public function validateMetaState(ValidatorInterface|array|null $data = null): static
    {
        // Check entry meta-state. If this entry was modified in the meantime, can we update?
        if ($this->getMetaState()) {
            if (isset_get($data['meta_state']) !== $this->getMetaState()) {
                // State mismatch! This means that somebody else updated this record while we were modifying it.
                switch ($this->state_mismatch_handling) {
                    case StateMismatchHandling::ignore:
                        Log::warning(tr('Ignoring database and user meta-state mismatch for ":type" type record with ID ":id" and old state ":old" and new state ":new"', [
                            ':id' => $this->getId(),
                            ':type' => static::getDataEntryName(),
                            ':old' => $this->getMetaState(),
                            ':new' => $data['meta_state'],
                        ]));
                        break;

                    case StateMismatchHandling::allow_override:
                        // Okay, so the state did NOT match, and we WILL throw the state mismatch exception, BUT we WILL
                        // update the state data so that a second attempt can succeed
                        $data['meta_state'] = $this->getMetaState();
                        break;

                    case StateMismatchHandling::restrict:
                        throw new DataEntryStateMismatchException(tr('Database and user meta-state for ":type" type record with ID ":id" do not match', [
                            ':id' => $this->getId(),
                            ':type' => static::getDataEntryName()
                        ]));
                }
            }
        }

        return $this;
    }


    /**
     * Generate diff data that will be stored and used by the meta system
     *
     * @param array|null $data
     * @return static
     */
    protected function createDiff(?array $data): static
    {
        if (Meta::isEnabled()) {
            if ($data === null) {
                $diff = [
                    'from' => [],
                    'to' => $this->source
                ];
            } else {
                $diff = [
                    'from' => [],
                    'to' => []
                ];

                // Check all keys and register changes
                foreach ($this->definitions as $key => $definition) {
                    if ($definition->getReadonly() or $definition->getDisabled() or $definition->isMeta()) {
                        continue;
                    }

                    if (isset_get($data[$key]) === null) {
                        continue;
                    }

                    if (isset_get($this->source[$key]) != isset_get($data[$key])) {
                        // If both records were empty (from NULL to 0 for example) then don't register
                        if ($this->source[$key] or $data[$key]) {
                            $diff['from'][$key] = (string)$this->source[$key];
                            $diff['to'][$key] = (string)$data[$key];
                        }
                    }
                }
            }

            try {
                // Truncate the diff to 64K for storage
                $this->diff = Json::encodeTruncateToMaxSize($diff, 65530);

            } catch (Exception | Throwable $e) {
                // Just in case the truncated JSON encoding somehow failed, make sure we can continue!
                Notification::new($e)
                    ->log()
                    ->send();

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
     * @param array $source The data for this DataEntry object
     * @param bool $modify
     * @param bool $directly
     * @param bool $force
     * @return static
     */
    protected function copyValuesToSource(array $source, bool $modify, bool $directly = false, bool $force = false): static
    {
        if ($this->definitions->isEmpty()) {
            throw new OutOfBoundsException(tr('Data keys were not defined for this ":class" class', [
                ':class' => get_class($this)
            ]));
        }

        // Setting fields will make $this->is_validated false, so store the current value;
        $validated = $this->is_validated;

        foreach ($this->definitions as $key => $definition) {
            // Meta-keys cannot be set through DataEntry::setData()
            if ($definition->isMeta()) {
                continue;
            }

            if ($this->is_applying and !$force) {
                if ($definition->getReadonly() or $definition->getDisabled()) {
                    // Apply cannot update readonly or disabled fields
                    continue;
                }
            }

            if (array_key_exists($key, $source)) {
                $value = $source[$key];
            } else {
                // This key doesn't exist at all in the data entry, default it
                $value = $definition->getDefault();

                // Still empty? If it's a new entry, there maybe an initial default value
                if (!$value and $this->isNew()) {
                    $value = $definition->getInitialDefault();
                }
            }

            switch ($key) {
                case 'password':
                    $this->setPasswordDirectly($value);
                    continue 2;
            }

            if (!$modify) {
                // Remove prefix / postfix if defined
                if ($definition->getPrefix()) {
                    $value = Strings::from($value, $definition->getPrefix());
                }

                if ($definition->getPostfix()) {
                    $value = Strings::untilReverse($value, $definition->getPostfix());
                }
            }

            if ($directly or $this->definitions->get($key)?->getDirectUpdate()) {
                // Store data directly, bypassing the set method for this key
                $this->setSourceValue($key, $value);

            } else {
                // Store this data through the set method to ensure datatype and filtering is done correctly
                $method = $this->convertFieldToSetMethod($key);

                if ($this->debug) {
                    Log::information('ABOUT TO SET SOURCE KEY "' . $key . '" WITH METHOD: ' . $method . ' (' . (method_exists($this, $method) ? 'exists' : 'NOT exists') . ') TO VALUE "' . Strings::log($value). '"', 10);
                }

                // Only apply if a method exists for this variable
                if (!method_exists($this, $method)){
                    // There is no method accepting this data. This might be because it is a virtual column that gets
                    // resolved at validation time. Check this with the definitions object
                    if ($this->definitions->get($key)?->getVirtual()) {
                        continue;
                    }

                    throw new OutOfBoundsException(tr('Cannot set source key ":key" because the class definitions have no method defined for DataEntry class ":class"', [
                        ':key'   => $key,
                        ':class' => Strings::fromReverse(get_class($this), '\\')
                    ]));
                }

                $this->$method($value);
            }
        }

        $this->is_validated = $validated;
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
     * Adds a single extra key that is protected and cannot be removed from this object
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
     *
     * @param bool $filter_meta If true, will also filter out the DataEntry meta-fields
     * @return array
     */
    public function getSource(bool $filter_meta = false): array
    {
        if ($filter_meta) {
            // Remove meta-fields too
            return Arrays::remove(Arrays::remove($this->source, static::$meta_fields), $this->protected_fields);
        }

        return Arrays::remove($this->source, $this->protected_fields);
    }


    /**
     * Loads the specified data into this DataEntry object
     *
     * @param Iterator|array $source
     * @param bool $meta_enabled
     * @return static
     */
    public function setSource(Iterator|array $source, bool $meta_enabled = true): static
    {
        $this->meta_enabled = $meta_enabled;

        return $this->setMetaData((array) $source)
                    ->copyValuesToSource((array) $source, false);
    }


    /**
     * Returns only the specified key from the source of this DataEntry
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     * @return array
     */
    public function getSourceValue(string $key): mixed
    {
        if (array_key_exists($key, $this->source)) {
            return $this->source[$key];
        }

        throw new OutOfBoundsException(tr('Specified key ":key" does not exist in this DataEntry ":class" object', [
            ':class' => get_class($this),
            ':key'   => $key
        ]));
    }


    /**
     * Returns the value for the specified data key
     *
     * @param string $type
     * @param string $field
     * @param mixed|null $default
     * @return mixed
     */
    protected function getSourceFieldValue(string $type, string $field, mixed $default = null): mixed
    {
        $this->checkProtected($field);
        return isset_get_typed($type, $this->source[$field], $default, false);
    }


    /**
     * Return the data used for validation.
     *
     * This method may be overridden to add more columns. See User class for example, where "password" will also be
     * stripped as it will never be validated as it will be updated directly
     *
     * @return array
     */
    protected function getDataForValidation(): array
    {
        return Arrays::remove($this->source, [
            'id',
            'created_by',
            'created_on',
            'status',
            'meta_id',
            'meta_state'
        ]);
    }


    /**
     * Sets all meta-data for this data entry at once with an array of information
     *
     * @param ?array $data
     * @return static
     * @throws OutOfBoundsException
     */
    protected function setMetaData(?array $data = null): static
    {
        // Reset meta fields
        foreach (static::$meta_fields as $field) {
            $this->source[$field] = null;
        }

        if ($data === null) {
            // No data set
            return $this;
        }

        if ($this->definitions->isEmpty()) {
            throw new OutOfBoundsException(tr('Data keys were not defined for this ":class" class', [
                ':class' => get_class($this)
            ]));
        }

        foreach ($data as $key => $value) {
            // Only these keys will be set through setMetaData()
            if (!in_array($key, static::$meta_fields)) {
                continue;
            }

            // Store the meta data
            $this->source[$key] = $value;
        }

        return $this;
    }


    /**
     * Sets the value for the specified data key
     *
     * @param string $field
     * @param mixed $value
     * @param bool $force
     * @return static
     */
    protected function setSourceValue(string $field, mixed $value, bool $force = false): static
    {
        if ($this->debug) {
            Log::information('TRY SET SOURCE VALUE FIELD "' . $field . '" TO "' . Strings::force($value) . ' [' . gettype($value) . ']"', 10);
        }

        // Only save values that are defined for this object
        if (!$this->definitions->keyExists($field)) {
            if ($this->definitions->isEmpty()) {
                throw new DataEntryException(tr('The ":class" class has no fields defined yet', [
                    ':class' => get_class($this)
                ]));
            }

            throw new DataEntryException(tr('Not setting field ":field", it is not defined for the ":class" class', [
                ':field' => $field,
                ':class' => get_class($this)
            ]));
        }

        // Skip all meta fields like id, created_on, meta_id, etc etc etc..
        if (in_array($field, static::$meta_fields)) {
            return $this;
        }

        // If the key is defined as readonly or disabled, it cannot be updated unless it's a new object or a
        // static value.
        $definition = $this->definitions->get($field);

        // If a field is ignored we won't update anything
        if ($definition->getIgnored()) {
            return $this;
        }

        //        if ($this->is_applying and !$force) {
//            if ($definition->getReadonly() or $definition->getDisabled()) {
//                // The data is being set through DataEntry::apply() but this column is readonly
//                Log::debug('FIELD "' . $field . '" IS READONLY', 10);
//                return $this;
//            }
//        }

        $default = $definition->getDefault();

        // What to do if we don't have a value? Data should already have been validated, so we know the value is
        // optional (would not have passed validation otherwise), so it either defaults or NULL
        if ($value === null) {
            //  By default, all columns with empty values will be pushed to NULL unless specified otherwise
            $value = $default;
        }

        // Value may be set with default value while the field was empty, which is the same. Make value empty
        if ((isset_get($this->source[$field]) === null) and ($value === $default)) {
            // If the previous value was empty and the current value is the same as the default value then there was no
            // modification, we simply applied a default value

        } else {
            // The DataEntry::is_modified can only be modified if it is not TRUE already. The DataEntry is considered
            // modified if the user is modifying and the entry changed
            if (!$this->is_modified and !$definition->getIgnoreModify()) {
                $this->is_modified = (isset_get($this->source[$field]) !== $value);
            }
        }

        if ($this->debug) {
            Log::debug('MODIFIED FIELD "' . $field . '" FROM "' . $this->source[$field] . '" [' . gettype(isset_get($this->source[$field])) . '] TO "' . $value . '" [' . gettype($value) . '], MARKED MODIFIED: ' . Strings::fromBoolean($this->is_modified), 10);
        }

        // Update the field value
        $this->source[$field] = $value;
        $this->is_validated   = false;

        return $this;
    }


    /**
     * Sets the value for the specified data key
     *
     * @param string $field
     * @param mixed $value
     * @return static
     */
    public function addSourceValue(string $field, mixed $value): static
    {
        if (!array_key_exists($field, $this->source)) {
            $this->source[$field] = [];
        }

        if (!is_array($this->source[$field])) {
            throw new OutOfBoundsException(tr('Cannot *add* data value to key ":key", the value datatype is not "array"', [
                ':key' => $field
            ]));
        }

        $this->source[$field][] = $value;
        return $this;
    }


    /**
     * Returns if the specified DataValue key can be visible outside this object or not
     *
     * @param string $field
     * @return void
     */
    protected function checkProtected(string $field): void
    {
        if (empty($field)) {
            throw new OutOfBoundsException(tr('Empty field name specified'));
        }

        if (in_array($field, $this->protected_fields)) {
            throw new OutOfBoundsException(tr('Specified DataValue key ":key" is protected and cannot be accessed', [
                ':key' => $field
            ]));
        }
    }


    /**
     * Rewrite the specified variable into the set method for that variable
     *
     * @param string $field
     * @return string
     */
    protected function convertFieldToSetMethod(string $field): string
    {
        // Convert underscore to camelcase
        // Remove the prefix from the field
        if ($this->definitions->getFieldPrefix()) {
            $field = Strings::from($field, $this->definitions->getFieldPrefix());
        }

        $return = explode('_', $field);
        $return = array_map('ucfirst', $return);
        $return = implode('', $return);

        return 'set' . ucfirst($return);
    }


    /**
     * Only return columns that actually contain data
     *
     * @param bool $insert
     * @return array
     */
    protected function getDataColumns(bool $insert): array
    {
        $return = [];

        foreach ($this->definitions as $field => $definition) {
            if ($insert) {
                // We're about to insert
                if (in_array($field, $this->fields_filter_on_insert)) {
                    continue;
                }
            } else {
                // We're about to update
                if ($definition->getReadonly() or $definition->getDisabled()) {
                    // Don't update readonly or disabled columns, only meta-fields should pass
                    if (!$definition->isMeta()) {
                        continue;
                    }
                }
            }

            $field = $definition->getField();

            if ($definition->getVirtual()) {
                // This is a virtual column, ignore it.
                continue;
            }

            // Apply definition default
            $return[$field] = isset_get($this->source[$field]) ?? $definition->getDefault();

            // Ensure value is scalar
            if ($return[$field] and !is_scalar($return[$field])) {
                if (is_enum($return[$field])) {
                    $return[$field] = $return[$field]->value;

                } else {
                    $return[$field] = (string) $return[$field];
                }
            }

            // Apply definition prefix and postfix only if they are not empty
            $prefix = $definition->getPrefix();

            if ($prefix) {
                $return[$field]  = $prefix . $return[$field];
            }

            $postfix = $definition->getPostfix();

            if ($postfix) {
                $return[$field] .= $postfix;
            }
        }

        if ($this->debug) {
            Log::information('DATA SENT TO SQL', 10);
            Log::vardump($return);
        }

        return $return;
    }


    /**
     * Will save the data from this data entry to database
     *
     * @param bool $force
     * @param string|null $comments
     * @return static
     */
    public function save(bool $force = false, ?string $comments = null): static
    {
        $this->checkReadonly('save');

        if (!$this->is_modified and !$force) {
            // Nothing changed, no reason to save
            if ($this->debug) {
                Log::information('NOTHING CHANGED FOR ID "' . $this->source['id'] . '"', 10);
            }

            return $this;
        }

        if (!$this->is_validated) {
            // Object must ALWAYS be validated before writing!
            if ($this->debug) {
                Log::information('VALIDATING DATAENTRY WITH ID "' . $this->source['id'] . '"', 10);
            }

            // The data in this object hasn't been validated yet! Do so now...
            $source = $this->getDataForValidation();

            // Merge the validated data over the current data
            $this->source = array_merge($this->source, $this->validate(ArrayValidator::new($source), true));
        }

        if ($this->readonly or $this->disabled) {
            throw new DataEntryReadonlyException(tr('Cannot save this ":name" object, the object is readonly or disabled', [
                ':name' => static::getDataEntryName()
            ]));
        }

        // Debug this specific entry?
        if ($this->debug) {
            Log::information('SAVING DATAENTRY WITH ID "' . $this->source['id'] . '"', 10);
            $debug = Sql::debug(true);
        }

        // Write the entry
        $this->source['id'] = sql($this->database_connector)->dataEntryWrite(static::getTable(), $this->getDataColumns(true), $this->getDataColumns(false), $comments, $this->diff, $this->meta_enabled);

        if ($this->debug) {
            Log::information('SAVED DATAENTRY WITH ID "' . $this->source['id'] . '"', 10);
        }

        // Return debug mode if required
        if (isset($debug)) {
            Sql::debug($debug);
        }

        // Write the list, if set
        $this->list?->save();

        // Done!
        $this->is_modified = false;
        $this->is_saved    = true;

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
        Cli::displayForm($this->source, $key_header, $value_header);
    }


    /**
     * Creates and returns an HTML for the data in this entry
     *
     * @return DataEntryFormInterface
     */
    public function getHtmlDataEntryForm(): DataEntryFormInterface
    {
        return DataEntryForm::new()
            ->setSource($this->source)
            ->setReadonly($this->readonly)
            ->setDisabled($this->disabled)
            ->setDefinitions($this->definitions);
    }


    /**
     * Extracts the data from the validator without validating
     *
     * @param ValidatorInterface $validator
     * @param bool $clear_source
     * @return array
     */
    protected function doNotValidate(ValidatorInterface $validator, bool $clear_source): array
    {
        $return = [];
        $source = $validator->getSource();
        $prefix = $this->definitions->getFieldPrefix();

        foreach ($source as $key => $value) {
            $return[Strings::from($key, $prefix)] = $value;

            if ($clear_source) {
                $validator->removeSourceKey($key);
            }
        }

        return $return;
    }


    /**
     * Validate all fields for this DataEntry
     *
     * @note This method will also fix field names in case field prefix was specified
     *
     * @param ValidatorInterface $validator
     * @param bool $clear_source
     * @return array
     */
    protected function validate(ValidatorInterface $validator, bool $clear_source): array
    {
        if (!$this->validate) {
            // This data entry won't validate data, just continue.
            return $validator->getSource();
        }

        // Set ID so that the array validator can do unique lookups, etc.
        // Tell the validator what table this DataEntry is using and get the field prefix so that the validator knows
        // what fields to select
        $validator
            ->setId($this->getId())
            ->setTable(static::getTable());

        $prefix = $this->definitions->getFieldPrefix();

        // Go over each field and let the field definition do the validation since it knows the specs
        foreach ($this->definitions as $definition) {
            if ($definition->isMeta()) {
                // This field is metadata and should not be modified or validated, plain ignore it.
                continue;
            }

            if ($definition->getReadonly() or $definition->getDisabled()) {
                // This field cannot be modified and should not be validated, unless its new or has a static value
                if (!$this->isNew() and !$definition->getValue()) {
                    $validator->removeSourceKey($definition->getField());
                    continue;
                }
            }

            $definition->validate($validator, $prefix);
        }

        try {
            // Execute the validate method to get the results of the validation
            $source = $validator->validate($clear_source);
            $this->is_validated = true;

        } catch (ValidationFailedException $e) {
            // Add the DataEntry object type to the exception message
            throw $e->setMessage('(' . get_class($this) . ') ' . $e->getMessage());
        }

        // Fix field names if prefix was specified
        if ($prefix) {
            $return = [];

            foreach ($source as $key => $value) {
                $return[Strings::from($key, $prefix)] = $value;
            }

            return $return;
        }

        return $source;
    }


    /**
     * Returns either the specified field, or if $translate has content, the alternate field name
     *
     * @param string $field
     * @return string
     */
    protected function getAlternateValidationField(string $field): string
    {
        if (!$this->definitions->keyExists($field)) {
            throw new OutOfBoundsException(tr('Specified field name ":field" does not exist', [
                ':field' => $field
            ]));
        }

        $alt = $this->definitions->get($field)->getCliField();
        $alt = Strings::until($alt, ' ');
        $alt = trim($alt);

        return get_null($alt) ?? $field;
    }


    /**
     * Load all data directly from the specified array.
     *
     * @note ONLY use this to load data that came from a trusted and validated source! This method will NOT validate
     *       your data, use DataEntry::apply() instead for untrusted data.
     * @param array $source
     * @param bool $init
     * @return $this
     */
    public function setSourceString(array $source, bool $init = true): static
    {
        $this->is_loading = true;

        if ($init) {
            // Load data with object init
            $this->setMetaData($source)->copyValuesToSource($source, false);

        } else {
            $this->source = $source;
        }

        $this->is_modified = false;
        $this->is_loading  = false;
        $this->is_saved    = false;
        return $this;
    }


    /**
     * Returns either the specified valid column, or if empty, a default column
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param string|null $column
     * @return string|null
     */
    protected static function ensureColumn(DataEntryInterface|string|int|null $identifier, ?string $column): ?string
    {
        if ($column) {
            // Column was specified. Identifier MAY be empty but that is fine as a value actually might be NULL
            return $column;
        }

        if ($identifier) {
            // No identifier specified either, this is just an empty DataEntry object
            return null;
        }

        // Column is NOT required, try to assign default. Assume `id` for numeric identifiers, or else the unique field
        if (is_numeric($identifier)) {
            return 'id';
        }

        $return = static::getUniqueField();

        if ($return) {
            return $return;
        }

        throw new OutOfBoundsException(tr('Failed to access DataEntry type ":type", an identifier ":identifier" was specified without column, the identifier is not numeric and the DataEntry object has no unique field specified', [
            ':type'       => static::getDataEntryName(),
            ':identifier' => $identifier
        ]));
    }


    /**
     * Load all object data from database
     *
     * @param string|int $identifier
     * @param string|null $column
     * @return void
     */
    protected function load(string|int $identifier, ?string $column): void
    {
        $this->is_loading = true;

        // Get the data using the query builder
        $data = $this->getQueryBuilder()
            ->setMetaEnabled($this->meta_enabled)
            ->setDatabaseConnector($this->database_connector)
            ->addSelect('`' . static::getTable() . '`.*')
            ->addWhere('`' . static::getTable() . '`.`' . $column . '` = :identifier', [':identifier' => $identifier])
            ->get();

        // Store all data in the object
        $this->setMetaData((array) $data)
             ->copyValuesToSource((array) $data, false);

        // Reset state
        $this->is_loading  = false;
        $this->is_saved    = false;
        $this->is_modified = false;

        // If this is a new entry, assign the identifier by default (NOT id though, since that is a DB identifier!)
        if ($this->isNew() and $column !== 'id') {
            $this->setSourceValue($column, $identifier, true);
        }
    }


    /**
     * Returns the meta-fields that apply for all DataEntry objects
     *
     * @return void
     */
    protected function setMetaDefinitions(): void
    {
        $this->definitions = Definitions::new()
            ->setTable(static::getTable())
            ->addDefinition(Definition::new($this, 'id')
                ->setReadonly(true)
                ->setInputType(InputTypeExtended::dbid)
                ->addClasses('text-center')
                ->setSize(3)
                ->setCliAutoComplete(true)
                ->setTooltip(tr('This field contains the unique identifier for this object inside the database. It cannot be changed and is used to identify objects'))
                ->setLabel(tr('Database ID')))
            ->addDefinition(Definition::new($this, 'created_on')
                ->setReadonly(true)
                ->setInputType(InputType::datetime_local)
                ->setNullInputType(InputType::text)
                ->addClasses('text-center')
                ->setSize(3)
                ->setTooltip(tr('This field contains the exact date / time when this object was created'))
                ->setLabel(tr('Created on')))
            ->addDefinition(Definition::new($this, 'created_by')
                ->setReadonly(true)
                ->setSize(3)
                ->setLabel(tr('Created by'))
                ->setTooltip(tr('This field contains the user who created this object. Other users may have made further edits to this object, that information may be found in the object\'s meta data'))
                ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                    if ($this->isNew()) {
                        // This is a new DataEntry object, so the creator is.. well, you!
                        return InputText::new()
                            ->setDisabled(true)
                            ->addClasses('text-center')
                            ->setValue(Session::getUser()->getDisplayName())
                            ->render();
                    } else {
                        // This is created by a user or by the system user
                        if ($source[$key]) {
                            return InputText::new()
                                ->setDisabled(true)
                                ->addClasses('text-center')
                                ->setValue(User::get($source[$key],  null)->getDisplayName())
                                ->render();
                        } else {
                            return InputText::new()
                                ->setDisabled(true)
                                ->addClasses('text-center')
                                ->setValue(tr('System'))
                                ->render();
                        }
                    }
                }))
            ->addDefinition(Definition::new($this, 'meta_id')
                ->setReadonly(true)
                ->setVisible(false)
                ->setInputType(InputTypeExtended::dbid)
                ->setNullInputType(InputType::text)
                ->setTooltip(tr('This field contains the identifier for this object\'s audit history'))
                ->setLabel(tr('Meta ID')))
            ->addDefinition(Definition::new($this, 'meta_state')
                ->setReadonly(true)
                ->setVisible(false)
                ->setInputType(InputType::text)
                ->setTooltip(tr('This field contains a cache identifier value for this object. This information usually is of no importance to normal users'))
                ->setLabel(tr('Meta state')))
            ->addDefinition(Definition::new($this, 'status')
                ->setOptional(true)
                ->setReadonly(true)
                ->setInputType(InputType::text)
                ->setTooltip(tr('This field contains the current status of this object. A typical status is "Ok", but objects may also be "Deleted" or "In process", for example. Depending on their status, objects may be visible in tables, or not'))
//                ->setDisplayDefault(tr('Ok'))
                ->addClasses('text-center')
                ->setSize(3)
                ->setLabel(tr('Status')));
    }


    /**
     * Sets and returns the field definitions for the data fields in this DataEntry object
     *
     * Format:
     *
     * [
     *   field => [key => value],
     *   field => [key => value],
     *   field => [key => value],
     * ]
     *
     * "field" should be the database table column name
     *
     * Field keys:
     *
     * FIELD          DATATYPE           DEFAULT VALUE  DESCRIPTION
     * value          mixed              null           The value for this entry
     * visible        boolean            true           If false, this key will not be shown on web, and be readonly
     * virtual        boolean            false          If true, this key will be visible and can be modified but it
     *                                                  won't exist in database. It instead will be used to generate
     *                                                  a different field
     * element        string|null        "input"        Type of element, input, select, or text or callable function
     * type           string|null        "text"         Type of input element, if element is "input"
     * readonly       boolean            false          If true, will make the input element readonly
     * disabled       boolean            false          If true, the field will be displayed as disabled
     * label          string|null        null           If specified, will show a description label in HTML
     * size           int [1-12]         12             The HTML boilerplate column size, 1 - 12 (12 being the whole
     *                                                  row)
     * source         array|string|null  null           Array or query source to get contents for select, or single
     *                                                  value for text inputs
     * execute        array|null         null           Bound execution variables if specified "source" is a query
     *                                                  string
     * complete       array|bool|null    null           If defined must be bool or contain array with key "noword"
     *                                                  and "word". each key must contain a callable function that
     *                                                  returns an array with possible words for shell auto
     *                                                  completion. If bool, the system will generate this array
     *                                                  automatically from the rows for this field
     * cli            string|null        null           If set, defines the alternative column name definitions for
     *                                                  use with CLI. For example, the column may be name, whilst
     *                                                  the cli column name may be "-n,--name"
     * optional       boolean            false          If true, the field is optional and may be left empty
     * title          string|null        null           The title attribute which may be used for tooltips
     * placeholder    string|null        null           The placeholder attribute which typically shows an example
     * maxlength      string|null        null           The maxlength attribute which typically shows an example
     * pattern        string|null        null           The pattern the value content should match in browser client
     * min            string|null        null           The minimum amount for numeric inputs
     * max            string|null        null           The maximum amount for numeric inputs
     * step           string|null        null           The up / down step for numeric inputs
     * default        mixed              null           If "value" for entry is null, then default will be used
     * null_disabled  boolean            false          If "value" for entry is null, then use this for "disabled"
     * null_readonly  boolean            false          If "value" for entry is null, then use this for "readonly"
     * null_type      boolean            false          If "value" for entry is null, then use this for "type"
     *
     * @param DefinitionsInterface $definitions
     */
    abstract protected function setDefinitions(DefinitionsInterface $definitions): void;
}
