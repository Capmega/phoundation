<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry;

use Exception;
use Phoundation\Accounts\Users\User;
use Phoundation\Cli\Cli;
use Phoundation\Cli\Color;
use Phoundation\Core\Arrays;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Meta\Meta;
use Phoundation\Core\Session;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Enums\StateMismatchHandling;
use Phoundation\Data\DataEntry\Exception\DataEntryAlreadyExistsException;
use Phoundation\Data\DataEntry\Exception\DataEntryException;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntry\Exception\DataEntryReadonlyException;
use Phoundation\Data\DataEntry\Exception\DataEntryStateMismatchException;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Traits\DataDebug;
use Phoundation\Data\Traits\DataReadonly;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\ArrayValidator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Date\DateTime;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\Html\Components\DataEntryForm;
use Phoundation\Web\Http\Html\Components\Input\InputText;
use Phoundation\Web\Http\Html\Components\Interfaces\DataEntryFormInterface;
use Phoundation\Web\Http\Html\Enums\InputType;
use Phoundation\Web\Http\Html\Enums\InputTypeExtended;
use Stringable;
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
abstract class DataEntry implements DataEntryInterface, Stringable
{
    use DataDebug;
    use DataReadonly;


    /**
     * The label name for this data entry, used in errors, etc
     *
     * @var string $entry_name
     */
    // TODO Check this, will likely go wrong as we have many sub classes of DataEntry
    protected string $entry_name;

    /**
     * The table name where this data entry is stored
     *
     * @var string|null $table
     */
    protected ?string $table = null;

    /**
     * Contains the data for all information of this data entry
     *
     * @var array $data
     */
    protected array $data = [];

    /**
     * Meta information about the keys in this DataEntry
     *
     * @var DefinitionsInterface|null $definitions
     */
    protected ?DefinitionsInterface $definitions = null;

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
     * If true, this DataEntry will allow creation of new entries
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
     * Returns true if the DataEntry object internal data structure has been updated
     *
     * @var bool $is_modified
     */
    protected bool $is_modified = false;

    /**
     * Returns true if the DataEntry object was just successfully saved
     *
     * @var bool $is_saved
     */
    protected bool $is_saved = false;

    /**
     * If true, this DataEntry is new and not loaded from database
     *
     * @var bool $is_new
     */
    protected bool $is_new = true;

    /**
     * If true, the data in this DataEntry has been validated
     *
     * @var bool $is_validated
     */
    protected bool $is_validated = false;


    /**
     * DataEntry class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param bool $init
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, bool $init = true)
    {
        if (empty($this->entry_name)) {
            throw new OutOfBoundsException(tr('No "entry_name" specified for class ":class"', [
                ':class' => get_class($this)
            ]));
        }

        if (!$this->table) {
            throw new OutOfBoundsException(tr('No table specified for class ":class"', [
                ':class' => get_class($this)
            ]));
        }

        if (empty($this->unique_field)) {
            throw new OutOfBoundsException(tr('No unique_column specified for class ":class"', [
                ':class' => get_class($this)
            ]));
        }

        // Set up the fields for this object
        $this->initMetaDefinitions();
        $this->initDefinitions($this->definitions);

        if ($identifier) {
            if (is_numeric($identifier)) {
                $this->data['id'] = $identifier;

            } elseif (is_object($identifier)) {
                $this->data['id'] = $identifier->getId();

            } else {
                $this->data[$this->unique_field] = $identifier;
            }

            $this->load($identifier, $init);

        } elseif ($init) {
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
     * @param DataEntryInterface|string|int|null $identifier
     * @param bool $init
     * @return static
     */
    public static function new(DataEntryInterface|string|int|null $identifier = null, bool $init = false): static
    {
        return new static($identifier);
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
     * Returns true if the DataEntry was just successfully saved
     *
     * @return bool
     */
    public function isSaved(): bool
    {
        return $this->is_saved;
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
     * Returns id for this database entry that can be used in logs
     *
     * @return bool
     */
    public function getAllowModify(): bool
    {
        return $this->allow_modify;
    }


    /**
     * Returns id for this database entry that can be used in logs
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
            if ($definitions->getCliField() and $definitions->getAutoComplete()) {
                $arguments[$definitions->getCliField()] = $definitions->getAutoComplete();
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
    public static function getHelp(?string $help = null): string
    {
        if ($help) {
            $help = trim($help);
            $help = preg_replace('/ARGUMENTS/', Color::apply(strtoupper(tr('ARGUMENTS')), 'white'), $help);
        }

        $groups = [];
        $fields = static::new()->getDefinitions();
        $return = PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . Color::apply(strtoupper(tr('REQUIRED ARGUMENTS')), 'white');

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
                $header = PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . Color::apply(strtoupper(trim($group)), 'white');
            } else {
                $header = PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . Color::apply(strtoupper(tr('Miscellaneous information')), 'white');
            }

            foreach ($fields as $id => $definitions) {
                if ($definitions->getHelpGroup() === $group) {
                    $fields->delete($id);
                    $body .=  PHP_EOL . PHP_EOL . Strings::size($definitions->getCliField(), 39) . ' ' . $definitions->getHelpText();
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
     * @param DataEntryInterface|string|int|null $identifier
     * @param bool $init
     * @return static|null
     */
    public static function get(DataEntryInterface|string|int|null $identifier = null, bool $init = false): ?static
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

        $entry = new static($identifier, $init);

        if ($entry->getId()) {
            return $entry;
        }

        throw DataEntryNotExistsException::new(tr('The ":label" entry ":identifier" does not exist', [
            ':label'      => static::getClassName(),
            ':identifier' => $identifier
        ]))->makeWarning();
    }


    /**
     * Returns a random DataEntry object
     *
     * @param bool $init
     * @return static|null
     */
    public static function getRandom(bool $init = false): ?static
    {
        $table      = static::new()->getTable();
        $identifier = sql()->getInteger('SELECT `id` FROM `' . $table . '` ORDER BY RAND() LIMIT 1;');

        if ($identifier) {
            return static::get($identifier, $init);
        }

        throw new OutOfBoundsException(tr('Cannot select random record for table ":table", no records found', [
            ':table' => $table
        ]));
    }


    /**
     * Returns true if an entry with the specified identifier exists
     *
     * @param string $field
     * @param string|int|null $identifier The unique identifier, but typically not the database id, usually the
     *                                    seo_email, or seo_name
     * @param int|null $not_id
     * @param bool $throw_exception       If the entry does not exist, instead of returning false will throw a
     *                                    DataEntryNotExistsException
     * @return bool
     */
    public static function exists(string $field, string|int $identifier = null, ?int $not_id = null, bool $throw_exception = false): bool
    {
        if (!$identifier) {
            throw new OutOfBoundsException(tr('Cannot check if ":class" class DataEntry exists, no identifier specified', [
                ':class' => static::getClassName()
            ]));
        }

        $execute = [':identifier' => $identifier];

        if ($not_id) {
            $execute[':id'] = $not_id;
        }

        $exists  = sql()->getColumn('SELECT `id` 
                                           FROM   `' . static::new()->getTable() . '` 
                                           WHERE  `' . $field . '` = :identifier
                                           ' . ($not_id ? 'AND `id` != :id' : '') . ' 
                                           LIMIT  1', $execute);

        if (!$exists and $throw_exception) {
            throw DataEntryAlreadyExistsException::new(tr('The ":type" type data entry with identifier ":id" already exists', [
                ':type' => static::getClassName(),
                ':id' => $identifier
            ]))->makeWarning();
        }

        return (bool) $exists;
    }


    /**
     * Returns true if an entry with the specified identifier does not exist
     *
     * @param string $field
     * @param string|int|null $identifier The unique identifier, but typically not the database id, usually the
     *                                    seo_email, or seo_name
     * @param int|null $id If specified, will ignore the found entry if it has this ID as it will be THIS
     *                                    object
     * @param bool $throw_exception If the entry exists (and does not match id, if specified), instead of
     *                                    returning false will throw a DataEntryNotExistsException
     * @return bool
     */
    public static function notExists(string $field, string|int $identifier = null, ?int $id = null, bool $throw_exception = false): bool
    {
        if (!$identifier) {
            throw new OutOfBoundsException(tr('Cannot check if ":class" class DataEntry not exists, no identifier specified', [
                ':class' => static::getClassName()
            ]));
        }

        $execute = [':identifier' => $identifier];

        if ($id) {
            $execute[':id'] = $id;
        }

        $exists  = sql()->getColumn('SELECT `id` 
                                           FROM   `' . static::new()->getTable() . '` 
                                           WHERE  `' . $field . '` = :identifier
                                           ' . ($id ? 'AND `id` != :id' : '') . ' 
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
     * Returns the table name used by this object
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public function getDataEntryName(): string
    {
        return $this->entry_name;
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
        return $this->is_new;
    }


    /**
     * Returns id for this database entry
     *
     * @return int|null
     */
    public function getId(): int|null
    {
        return $this->getDataValue('int', 'id');
    }


    /**
     * Returns id for this database entry that can be used in logs
     *
     * @return string
     */
    public function getLogId(): string
    {
        return $this->getDataValue('int', 'id') . ' / ' . $this->getDataValue('string', $this->unique_field);
    }


    /**
     * Returns status for this database entry
     *
     * @return ?String
     */
    public function getStatus(): ?string
    {
        return $this->getDataValue('string', 'status');
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
        return $this->getDataValue('string', 'meta_state');
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
        sql()->erase($this->table, ['id' => $this->getId()]);
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
     * @return User|null
     */
    public function getCreatedBy(): ?User
    {
        $created_by = $this->getDataValue('int', 'created_by');

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
        $created_on = $this->getDataValue('string', 'created_on');

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
        $meta_id = $this->getDataValue('int', 'meta_id');

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
        return $this->getDataValue('int', 'meta_id');
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
     * @param ValidatorInterface|array|null $source
     * @return static
     */
    public function apply(bool $clear_source = true, ValidatorInterface|array|null $source = null): static
    {
        // Are we allowed to create or modify this DataEntry?
        if ($this->getId()) {
            if (!$this->allow_modify) {
                // auto modify not allowed, sorry!
                throw new ValidationFailedException(tr('Cannot modify :entry', [
                    ':entry' => $this->entry_name
                ]));
            }
        } else {
            if (!$this->allow_create) {
                // auto create not allowed, sorry!
                throw new ValidationFailedException(tr('Cannot create new :entry', [
                    ':entry' => $this->entry_name
                ]));
            }
        }

        $this->is_validated = false;
        $this->is_saved     = false;

        // Select the correct data source and validate the source data. Specified data may be a DataValidator, an array
        // or null. After selecting a data source it will be a DataValidator object which we will then give to the
        // DataEntry::validate() method
        $source = $this->selectDataSource($source);

        if ($this->debug) {
            show('APPLY ' . $this->entry_name . ' (' . get_class($this) . ')');
            show('CURRENT DATA');
            show($this->data);
            show('SOURCE');
            show($source);
        }

        $source = $this->validate($source, $clear_source);

        if ($this->debug) {
            show('APPLYING DATA');
            show($source);
        }

        // Ensure DataEntry Meta state is okay, then generate the diff data and copy data array to internal data
        $this
            ->validateMetaState($source)
            ->setDiff($source)
            ->setData($source, true);

        if ($this->debug) {
            show('NEW DATA');
            show($this->data);
        }

        return $this;
    }


    /**
     * Forcibly modify the data for this object with the new specified data, putting the object in readonly mode
     *
     * @note In readonly mode this object will no longer be able to write its data!
     * @param ValidatorInterface|array|null $data
     * @return static
     */
    public function forceApply(ValidatorInterface|array|null $data = null): static
    {
        $data = $this->selectDataSource($data);

        return $this
                ->setReadonly(true)
                ->setData($data->forceRead(), true);
    }


    /**
     * Validates the source data and returns it
     *
     * @param ValidatorInterface|array|null $data
     * @return ValidatorInterface
     */
    public function selectDataSource(ValidatorInterface|array|null $data = null): ValidatorInterface
    {
        // Determine data source for this modification
        if (!$data) {
            // Use default data depending on platform
            if (PLATFORM_HTTP) {
                return PostValidator::new();
            }

            // This is the default for the CLI platform
            return ArgvValidator::new();
        }

        if (is_object($data)) {
            // The specified data source is a DataValidatorInterface type validator
            return $data;
        }

        // Data source is an array, put it in an ArrayValidator
        return ArrayValidator::new($data);
    }


    /**
     * Validates the source data and returns it
     *
     * @param ValidatorInterface|array|null $data
     * @return static
     */
    public function validateMetaState(ValidatorInterface|array|null $data = null): static
    {
        // Check entry meta state. If this entry was modified in the meantime, can we update?
        if ($this->getMetaState()) {
            if (isset_get($data['meta_state']) !== $this->getMetaState()) {
                // State mismatch! This means that somebody else updated this record while we were modifying it.
                switch ($this->state_mismatch_handling) {
                    case StateMismatchHandling::ignore:
                        Log::warning(tr('Ignoring database and user meta state mismatch for ":type" type record with ID ":id" and old state ":old" and new state ":new"', [
                            ':id'   => $this->getId(),
                            ':type' => $this->entry_name,
                            ':old'  => $this->getMetaState(),
                            ':new'  => $data['meta_state'],
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
                            ':type' => $this->entry_name
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
                foreach ($this->definitions as $key => $definition) {
                    if ($definition->getReadonly() or $definition->getDisabled() or $definition->getMeta()) {
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
     * @param array $data The data for this DataEntry object
     * @param bool $modify
     * @return static
     */
    protected function setData(array $data, bool $modify): static
    {
        if ($this->definitions->isEmpty()) {
            throw new OutOfBoundsException(tr('Data keys were not defined for this ":class" class', [
                ':class' => get_class($this)
            ]));
        }

        // Setting fields will make $this->is_validated false, so store the current value;
        $validated = $this->is_validated;

        foreach ($this->definitions->getKeys() as $key) {
            // Meta keys cannot be set through DataEntry::setData()
            if (in_array($key, self::$meta_fields)) {
                continue;
            }

            if (array_key_exists($key, $data)) {
                $value = $data[$key];
            } else {
                // This key doesn't exist at all in the data entry, default it
                $value = $this->definitions->get($key)->getDefault();
            }

            switch ($key) {
                case 'password':
                    $this->setPasswordDirectly($value);
                    continue 2;
            }

            if (!$modify) {
                // Remove prefix / postfix if defined
                $definition = $this->definitions->get($key);

                if ($definition->getPrefix()) {
                    $value = Strings::from($value, $definition->getPrefix());
                }

                if ($definition->getPostfix()) {
                    $value = Strings::untilReverse($value, $definition->getPrefix());
                }
            }

            // Store this data through the methods to ensure datatype and filtering is done correctly
            $method = $this->convertFieldToSetMethod($key);

            if ($this->debug) {
                show('KEY: ' . $key . ' / METHOD: ' . $method . '(' . Strings::fromBoolean(method_exists($this, $method)) . ')');
            }

            // Only apply if a method exist for this variable
            if (method_exists($this, $method)){
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
        foreach (self::$meta_fields as $field) {
            $this->data[$field] = null;
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
            if (!in_array($key, self::$meta_fields)) {
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
     * @param string $field
     * @param mixed $value
     * @param bool $force
     * @return static
     */
    protected function setDataValue(string $field, mixed $value, bool $force = false): static
    {
//        show('REQUEST FIELD: ' . $field . ' "' . $this->definitions->get($field)->getReadonly() . '"');
        // Only save values that are defined for this object
        if ($this->definitions->exists($field)) {
            // Skip all meta fields like id, created_on, meta_id, etc etc etc..
            if (!in_array($field, self::$meta_fields)) {
                // If the key is defined as readonly or disabled, it cannot be updated unless it's a new object.
                $definition = $this->definitions->get($field);

                if ($force or !$this->getId() or (!$definition->getReadonly() and !$definition->getDisabled())) {
                    $default = $definition->getDefault();

                    // What to do if we don't have a value? Data should already have been validated, so we know the
                    // value is optional (would not have passed validation otherwise) so it either defaults or NULL
                    if (!$value) {
                        //  By default, all columns with empty values will be pushed to NULL unless specified otherwise
                        $value = $default;
                    }

                    // Value may be set with default value while field was empty, which is the same. Make value empty
                    if ((isset_get($this->data[$field]) === null) and ($value === $default)) {
                        // If the previous value was empty and the current value is the same as the default value
                        // then there was no modification, it just defaulted
                    } else {
                        // The DataEntry::is_modified can only be modified if it is not TRUE already.
                        // The DataEntry is considered modified if user is modifying and the entry changed
                        if (!$this->is_modified and !$definition->getReadonly() and !$definition->getIgnoreModify()) {
                            $this->is_modified = (isset_get($this->data[$field]) !== $value);
                        }
                    }

                    // Update the field value
                    $this->data[$field] = $value;
                    $this->is_validated = false;

                    if ($this->debug) {
                        show('FIELD: ' . $field . ' FROM "' . isset_get($this->data[$field]) . '" [' . gettype(isset_get($this->data[$field])) . '] TO "' . $value . '" [' . gettype($value) . '] > MODIFIED: ' . Strings::fromBoolean($this->is_modified));
                    }
                }
            }

        } elseif ($this->definitions->isEmpty()) {
            throw new DataEntryException(tr('The ":class" class has no fields defined yet', [
                ':class' => get_class($this)
            ]));
        }

        return $this;
    }


    /**
     * Sets the value for the specified data key
     *
     * @param string $field
     * @param mixed $value
     * @return static
     */
    public function addDataValue(string $field, mixed $value): static
    {
        if (!array_key_exists($field, $this->data)) {
            $this->data[$field] = [];
        }

        if (!is_array($this->data[$field])) {
            throw new OutOfBoundsException(tr('Cannot *add* data value to key ":key", the value datatype is not "array"', [
                ':key' => $field
            ]));
        }

        $this->data[$field][] = $value;
        return $this;
    }


    /**
     * Returns the value for the specified data key
     *
     * @param string $type
     * @param string $field
     * @param mixed|null $default
     * @return mixed
     */
    protected function getDataValue(string $type, string $field, mixed $default = null): mixed
    {
        $this->checkProtected($field);
        return isset_get_typed($type, $this->data[$field], $default);
    }


    /**
     * Returns if the specified DataValue key can be visible outside this object or not
     *
     * @param string $field
     * @return void
     */
    protected function checkProtected(string $field): void
    {
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
     * @return array
     */
    protected function getDataColumns(): array
    {
        $return = [];

        foreach ($this->definitions as $definition) {
            $field = $definition->getField();

            if ($definition->getVirtual()) {
                // This is a virtual column, ignore it.
                continue;
            }

            // Get the value. If the value is NULL, get the default (might also be NULL, but still...)
            $return[$field] = isset_get($this->data[$field]) ?? $definition->getDefault();

            $prefix = $definition->getPrefix();

            if ($prefix) {
                $return[$field] = $prefix . $return[$field];
            }

            $postfix = $definition->getPostfix();

            if ($postfix) {
                $return[$field] .= $postfix;
            }
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
        if (!$this->is_modified) {
            // Nothing changed, no reason to save
            return $this;
        }

        if (!$this->is_validated) {
            // The data in this object hasn't been validated yet! Do so now...
//show($this->data);
//            $this->data = $this->validate(ArrayValidator::new($this->data), true);
//showdie($this->data);

        }

        if ($this->readonly) {
            throw new DataEntryReadonlyException(tr('Cannot save this ":name" object, the object is readonly', [
                ':name' => $this->entry_name
            ]));
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
        Cli::displayForm($this->data, $key_header, $value_header);
    }


    /**
     * Creates and returns an HTML for the data in this entry
     *
     * @return DataEntryFormInterface
     */
    public function getHtmlForm(): DataEntryFormInterface
    {
        return DataEntryForm::new()
            ->setSource($this->data)
            ->setDefinitions($this->definitions)
            ->setKeysDisplay($this->field_display);
    }


//    /**
//     * Modify the form keys
//     *
//     * @param string $field
//     * @param array $settings
//     * @return static
//     */
//    public function modifyDefinitions(string $field, array $settings): static
//    {
//        if (!$this->definitions->exists($field)) {
//            throw new OutOfBoundsException(tr('Specified form key ":key" does not exist', [
//                ':key' => $field
//            ]));
//        }
//
//        foreach ($settings as $settings_key => $settings_value) {
//            if ($settings_key === 'size') {
//                $this->field_display[$field] = $settings_value;
//            } else {
//                $this->fields[$field][$settings_key] = $settings_value;
//            }
//        }
//
//        return $this;
//    }


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
        $prefix = $this->definitions->getFieldPrefix();

        foreach ($this->definitions as $definition) {
            // Let the field definition do the validation, as it knows the specs
            $definition->validate($validator, $prefix);
        }

        $validator->noArgumentsLeft($clear_source);
        $source = $validator->validate($clear_source);

        // Fix field names if prefix was specified
        if ($prefix) {
            $return = [];

            foreach ($source as $key => $value) {
                $return[Strings::from($key, $prefix)] = $value;
            }

            return $return;
        }

        $this->is_validated = true;
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
        if (!$this->definitions->exists($field)) {
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
     * Load all data directly from the specified array
     *
     * @param array $source
     * @param bool $init
     * @return $this
     */
    public function setSource(array $source, bool $init = false): static
    {
        if ($init) {
            // Load data with object init
            $this->setMetaData($source)->setData($source, false);
        } else {
            $this->data = $source;
        }

        $this->is_new      = false;
        $this->is_modified = false;
        $this->is_saved    = false;
        return $this;
    }


    /**
     * Load all object data from database
     *
     * @param string|int $identifier
     * @param bool $init
     * @return void
     */
    protected function load(string|int $identifier, bool $init = true): void
    {
        if (is_numeric($identifier)) {
            $data = sql()->get('SELECT * FROM `' . $this->table . '` WHERE `id`                          = :id'                    , [':id'                   => $identifier]);
        } else {
            $data = sql()->get('SELECT * FROM `' . $this->table . '` WHERE `' . $this->unique_field . '` = :' . $this->unique_field, [':'. $this->unique_field => $identifier]);
        }

        if ($data) {
            // Store all data in the object
            if ($init) {
                // Load data with object init.
                $this->setMetaData($data)->setData($data, false);
            } else {
                // Fast load data into internal array
                $this->data = $data;
            }
        } else {
            $this->data = [];
        }

        // Reset state
        $this->is_new      = false;
        $this->is_saved    = false;
        $this->is_modified = false;
    }


    /**
     * Returns the definitions for the fields in this table
     *
     * @return DefinitionsInterface
     */
    public function getDefinitions(): DefinitionsInterface
    {
        return $this->definitions;
    }


    /**
     * Returns the meta fields that apply for all DataEntry objects
     *
     * @return void
     */
    protected function initMetaDefinitions(): void
    {
        $this->definitions = Definitions::new()
            ->setTable($this->table)
            ->addDefinition(Definition::new('id')
                ->setReadonly(true)
                ->setInputType(InputTypeExtended::dbid)
                ->setSize(3)
                ->setAutoComplete(true)
                ->setLabel(tr('Database ID')))
            ->addDefinition(Definition::new('created_on')
                ->setReadonly(true)
                ->setInputType(InputType::datetime_local)
                ->setNullInputType(InputType::text)
                ->setSize(3)
                ->setLabel(tr('Created on')))
            ->addDefinition(Definition::new('created_by')
                ->setReadonly(true)
                ->setSize(3)
                ->setLabel(tr('Created by'))
                ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                    if ($this->isNew()) {
                        // This is a new DataEntry object, so the creator is.. well, you!
                        return InputText::new()
                            ->setDisabled(true)
                            ->setValue(Session::getUser()->getDisplayName())
                            ->render();
                    } else {
                        // This is created by a user or by the system user
                        if ($source[$key]) {
                            return InputText::new()
                                ->setDisabled(true)
                                ->setValue(User::get($source[$key])->getDisplayName())
                                ->render();
                        } else {
                            return InputText::new()
                                ->setDisabled(true)
                                ->setValue(tr('System'))
                                ->render();
                        }
                    }
                }))
            ->addDefinition(Definition::new('meta_id')
                ->setReadonly(true)
                ->setVisible(false)
                ->setInputType(InputTypeExtended::dbid)
                ->setNullInputType(InputType::text)
                ->setSize(3)
                ->setLabel(tr('Meta ID')))
            ->addDefinition(Definition::new('meta_state')
                ->setReadonly(true)
                ->setVisible(false)
                ->setInputType(InputType::text)
                ->setSize(3)
                ->setLabel(tr('Meta state')))
            ->addDefinition(Definition::new('status')
                ->setOptional(true)
                ->setReadonly(true)
                ->setInputType(InputType::text)
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
     * @param \Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface $definitions
     */
    abstract protected function initDefinitions(DefinitionsInterface $definitions): void;
}