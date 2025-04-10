<?php

/**
 * Class DataEntryCore
 *
 * This class implements the DataEntry class
 *
 * @see \Phoundation\Data\Entry
 * @see \Phoundation\Data\DataEntries\DataEntry
 * @see \Phoundation\Data\DataEntries\Definitions\Definitions
 * @see \Phoundation\Data\DataEntries\Definitions\Definition
 * @see \Phoundation\Data\DataEntries\DataIterator
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries;

use Exception;
use PDOStatement;
use Phoundation\Accounts\Config\Config;
use Phoundation\Accounts\Config\Exception\ConfigEmptyException;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\User;
use Phoundation\Cache\Cache;
use Phoundation\Cache\InstanceCache;
use Phoundation\Cli\CliColor;
use Phoundation\Content\Documents\Interfaces\SpreadSheetInterface;
use Phoundation\Content\Documents\SpreadSheet;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Meta\Interfaces\MetaInterface;
use Phoundation\Core\Meta\Meta;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Definitions;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Enums\EnumStateMismatchHandling;
use Phoundation\Data\DataEntries\Exception\DataEntryAlreadyExistsException;
use Phoundation\Data\DataEntries\Exception\DataEntryColumnDefinitionInvalidException;
use Phoundation\Data\DataEntries\Exception\DataEntryColumnNotDefinedException;
use Phoundation\Data\DataEntries\Exception\DataEntryDeletedException;
use Phoundation\Data\DataEntries\Exception\DataEntryException;
use Phoundation\Data\DataEntries\Exception\DataEntryInvalidCacheException;
use Phoundation\Data\DataEntries\Exception\DataEntryInvalidIdentifierException;
use Phoundation\Data\DataEntries\Exception\DataEntryIsNewException;
use Phoundation\Data\DataEntries\Exception\DataEntryMetaException;
use Phoundation\Data\DataEntries\Exception\DataEntryNoIdentifierSpecifiedException;
use Phoundation\Data\DataEntries\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntries\Exception\DataEntryNotSavedException;
use Phoundation\Data\DataEntries\Exception\DataEntryReadonlyException;
use Phoundation\Data\DataEntries\Exception\DataEntryStateMismatchException;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataDefinitions;
use Phoundation\Data\EntryCore;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataCacheKey;
use Phoundation\Data\Traits\TraitDataConnector;
use Phoundation\Data\Traits\TraitDataDebug;
use Phoundation\Data\Traits\TraitDataDisabled;
use Phoundation\Data\Traits\TraitDataIdentifier;
use Phoundation\Data\Traits\TraitDataIgnoreDeleted;
use Phoundation\Data\Traits\TraitDataInsertUpdate;
use Phoundation\Data\Traits\TraitDataMaxIdRetries;
use Phoundation\Data\Traits\TraitDataMetaColumns;
use Phoundation\Data\Traits\TraitDataMetaEnabled;
use Phoundation\Data\Traits\TraitDataRandomId;
use Phoundation\Data\Traits\TraitDataReadonly;
use Phoundation\Data\Traits\TraitDataRestrictions;
use Phoundation\Data\Traits\TraitMethodBuildManualQuery;
use Phoundation\Data\Traits\TraitMethodsGetTypesafe;
use Phoundation\Data\Traits\TraitMethodsTableState;
use Phoundation\Data\Traits\TraitMethodsVirtualColumns;
use Phoundation\Data\Validator\ArrayValidator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Exception\ValidatorException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Data\Validator\Validator;
use Phoundation\Databases\Connectors\Connector;
use Phoundation\Databases\Sql\Exception\SqlTableDoesNotExistException;
use Phoundation\Databases\Sql\Exception\SqlUnknownDatabaseException;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Databases\Sql\SqlDataEntry;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Date\PhoDateTime;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhoException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Exception\ReadOnlyModeException;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Utils\Utils;
use Phoundation\Web\Html\Components\Forms\DataEntryForm;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;
use Phoundation\Web\Html\Enums\EnumInputType;
use Stringable;
use Throwable;
use TypeError;

class DataEntryCore extends EntryCore implements DataEntryInterface, IdentifierInterface
{
    use TraitDataCacheKey;
    use TraitDataConnector;
    use TraitDataDebug;
    use TraitDataDisabled;
    use TraitDataDefinitions;
    use TraitDataIdentifier;
    use TraitDataIgnoreDeleted;
    use TraitDataInsertUpdate;
    use TraitDataMaxIdRetries;
    use TraitDataMetaEnabled;
    use TraitDataMetaColumns {
        setMetaColumns as protected __setMetaColumns;
    }
    use TraitDataRandomId;
    use TraitDataReadonly {
        setReadonly as protected __setReadonly;
    }
    use TraitDataRestrictions;
    use TraitMethodBuildManualQuery;
    use TraitMethodsTableState;
    use TraitMethodsGetTypesafe;
    use TraitMethodsVirtualColumns;


    /**
     * Columns that will NOT be inserted
     *
     * @var array $columns_filter_on_insert
     */
    protected array $columns_filter_on_insert;

    /**
     * A list with optional linked other DataEntry objects
     *
     * @var DataIterator|null
     */
    protected ?DataIterator $list = null;

    /**
     * What to do when a record state mismatch was detected
     *
     * @var EnumStateMismatchHandling $state_mismatch_handling
     */
    protected EnumStateMismatchHandling $state_mismatch_handling = EnumStateMismatchHandling::ignore;

    /**
     * $diff information showing what changed
     *
     * @var string|null $diff
     */
    protected ?string $diff = null;

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
     * Global flag tracking if the entire object and its definitions have been initialized
     *
     * @var bool $is_initialized
     */
    protected bool $is_initialized = false;

    /**
     * Global loading flag, when data is loaded into the object from a database
     *
     * @var bool $is_loading
     */
    protected bool $is_loading = false;

    /**
     * Global loaded flag, if set it means that data in the source has been loaded from database or configuration
     *
     * @var bool $is_loaded
     */
    protected bool $is_loaded = false;

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
     * Tracks what columns have been changed
     *
     * @var array $changes
     */
    protected array $changes = [];

    /**
     * Tracks what the ID was before saving (either NULL or current ID)
     */
    protected ?int $previous_id = null;

    /**
     * Tracks if this DataEntry was just created with a DataEntry::save() call
     */
    protected bool $is_created = false;

    /**
     * The lowest possible ID that will be auto generated
     *
     * @var int $id_lower_limit
     */
    protected int $id_lower_limit = 1;

    /**
     * The highest possible ID that will be auto generated
     *
     * @var int $id_upper_limit
     */
    protected int $id_upper_limit = PHP_INT_MAX;

    /**
     * Tracks if this DataEntry object may be destroyed with unsaved modifications
     *
     * @var bool|null $allow_modified_destruct
     */
    protected ?bool $allow_modified_destruct = null;

    /**
     * Tracks if this object was loaded from cache
     *
     * @var bool $is_loaded_from_cache
     */
    protected bool $is_loaded_from_cache = false;

    /**
     * The specified columns to load. If empty, load all columns
     *
     * @var array
     */
    protected array $columns = [];


    /**
     * DataEntry class constructor
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier The unique identifier for the data for this
     *                                                                    DataEntry object. When specified, the
     *                                                                    constructor will automatically load this data
     *                                                                    using DataEntry::load(). If specified, the
     *                                                                    identifier MUST exist either in database or
     *                                                                    configuration. If the specified identifier is
     *                                                                    FALSE, the object will NOT initialize, and the
     *                                                                    DataEntry::initialize() method must be called
     *                                                                    separately.
     */
    public function __construct(IdentifierInterface|array|string|int|false|null $identifier = false)
    {
        $this->cache = Cache::isEnabled();

        if ($identifier === false) {
            // If the identifier is false, do NOT automatically initialize the DataEntry object
            return;
        }

        // Initialize the DataEntry object
        $this->initialize($identifier === null ? false : $identifier);
    }


    /**
     * Initializes this DataEntry object
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier  The unique identifier for the data for this
     *                                                                     DataEntry object. If FALSE, the DataEntry
     *                                                                     object will initialize the definitions, but
     *                                                                     not the source data
     *
     * @return static
     */
    public function initialize(IdentifierInterface|array|string|int|false|null $identifier = false): static
    {
        if ($this->debug) {
            Log::debug('INITIALIZING CLASS "' . Strings::fromReverse(static::class, '\\') . '" WITH IDENTIFIER "' . Strings::log($identifier) . '"', 10, echo_header: false);
        }

        // Set the identifier
        $this->setIdentifier($identifier)
             ->is_initializing_source = true;

        // Set meta_columns for this class
        if (!isset($this->meta_columns)) {
            $this->meta_columns = static::getDefaultMetaColumns();
        }

        // Set up the definitions for this object and initialize meta-data
        $this->setMetaDefinitions()
             ->setDefinitions($this->definitions)
             ->setMetaData()
             ->columns_filter_on_insert = [static::getIdColumn()];

        $this->is_initialized = true;

        if ($identifier) {
            // An identifier was specified, load data immediately using DataEntry::load() (Data MUST exist!)
            return $this->load($identifier);
        }

        if ($identifier === false) {
            // Do not initialize the object source
            return $this->ready();
        }

        // Pre-initialize the DataEntry object
        return $this->copyValuesToSource([], false)
                    ->ready();
    }


    /**
     * DataEntry destructor
     *
     * @todo For now this check is only performed when enabled with configuration as this can cause a lot of unexpected behaviour. Improve this later
     * @todo in the future  this should check if the Core is in uncaught exception state. If so, do nothing. If Core is in script execution state, this should throw an DataEntryNotSavedException
     */
    public function __destruct()
    {
        if ($this->is_modified and !$this->readonly) {
            $enabled = config()->getBoolean('development.dataentries.destruct.modified.check', false);

            if ($enabled) {
                // Cannot destroy a modified DataEntry object without either resetting it or saving it
                if (!Core::getErrorState() and !PhoException::hasBeenCreated()) {
                    throw DataEntryNotSavedException::new(tr('Cannot destroy the ":class" object, it has unsaved modifications', [
                        ':class' => $this::class
                    ]));
                }

                // Core was handling an uncaught exception, the exception likely caused this state
                Log::warning(ts('Object of class ":class" is destroyed while still having unsaved modifications, this is likely due to the (uncaught) exception that occurred', [
                    ':class' => $this::class
                ]));
            }
        }
    }


    /**
     * Returns true if this DataEntry object may be destroyed with unsaved modifications
     *
     * @note There is a variety of reasons why a DataEntry object would be destroyed without saving first, though most
     *       of these reasons involve exception conditions. An DataEntry::apply() might fail with an exception, leaving
     *       the object in a modified state, which would cause a secondary exception by itself.
     *
     * @return bool
     */
    public function getAllowModifiedDestruct(): bool
    {
        if ($this->allow_modified_destruct === null) {
            return config()->getBoolean('development.data-entries.modified-destruct.allow', false);
        }

        return $this->allow_modified_destruct;
    }


    /**
     * Returns true if this DataEntry object may be destroyed without
     *
     * @param bool|null $allow_modified_destruct
     *
     * @return DataEntryCore
     */
    public function setAllowModifiedDestruct(?bool $allow_modified_destruct): static
    {
        $this->allow_modified_destruct = $allow_modified_destruct;
        return $this;
    }


    /**
     * Returns this DataEntry object as an integer
     *
     * @return int
     */
    public function __toInteger(): int
    {
        return get_integer($this->getId(), false);
    }


    /**
     * Return the object contents in JSON string format
     *
     * @return void
     */
    public function __clone(): void
    {
        unset($this->source[static::getIdColumn()]);
    }


    /**
     * Loads the data for the current identifier
     *
     * @return static
     */
    protected function loadIdentifier(): static
    {
        $this->loadFromDatabase();

        if ($this->isNew()) {
            // Source is still empty, so nothing was loaded from database (or, SQL table doesn't exist, also possible!)
            // Try to load it from configuration if this DataEntry supports that
            $this->tryLoadFromConfiguration($this->identifier);
        }

        return $this;
    }


    /**
     * Processes what to do if the found DataEntry is deleted
     *
     * @return void
     */
    protected function processDeleted(): void
    {
        // This entry has been deleted and can only be viewed by user with the "access_deleted" right
        if ($this->ignore_deleted or Session::getUserObject()->hasAllRights('access_deleted')) {
            Log::warning(ts('Continuing load of dataEntry object ":class" with identifier ":identifier" and log id ":log_id" with status "deleted"', [
                ':class'      => static::class,
                ':identifier' => $this->identifier,
                ':log_id'     => $this->getLogId()
            ]), 3);
            return;
        }

        throw DataEntryDeletedException::new(tr('Cannot load ":class" class object with identifier ":identifier", it has status "deleted"', [
            ':class'      => static::getClassName(),
            ':identifier' => $this->identifier,
        ]))->addData([
            'class'      => static::getClassName(),
            'identifier' => $this->identifier,
        ]);
    }


    /**
     * Returns the default meta data for DataEntry object
     *
     * @return array
     */
    final public function getDefaultMetaColumns(): array
    {
        return [
            'id',
            'created_on',
            'created_by',
            'meta_id',
            'status',
            'meta_state',
        ];
    }


    /**
     * Returns the column considered the "id" column
     *
     * @return string
     */
    public static function getIdColumn(): string
    {
        return 'id';
    }


    /**
     * Returns the configuration path for this DataEntry object, if it has one, or NULL instead
     *
     * @return string|null
     */
    public static function getConfigurationPath(): ?string
    {
        return null;
    }


    /**
     * Returns either the specified valid column, or if empty, a default column
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     *
     * @return string|null
     */
    protected static function determineColumn(IdentifierInterface|array|string|int|false|null $identifier): ?string
    {
        if (!$identifier) {
            // No identifier specified, this is just an empty DataEntry object
            return null;
        }

        // If the identifier is numeric, then the column MUST be the ID column
        if (is_numeric($identifier)) {
            if ($identifier < 0) {
                // This identifier is negative so cannot be a database identifier, and can only be a configuration
                // source identifier. This can ONLY be valid if the DataEntry has DataEntry::getConfigurationPath() set to
                // pull the entry from configuration.
                if (empty(static::getConfigurationPath())) {
                    throw new DataEntryInvalidIdentifierException(tr('Invalid identifier ":identifier" specified for ":class" DataEntry object, it is a negative number but the class has no configuration path specified', [
                        ':class'      => static::class,
                        ':identifier' => $identifier,
                    ]));
                }
            }
            return static::getIdColumn();
        }

        if ($identifier instanceof DataEntryInterface) {
            // Specified identifier is actually a data entry, we don't need a column
            return null;
        }

        if (is_array($identifier)) {
            // The specified identifier is an array. If it has a single key, we can determine the column
            return match (count($identifier)) {
                1       => key($identifier),
                default => throw new OutOfBoundsException(tr('Cannot determine column from identifier ":identifier", it contains multiple columns', [
                    ':identifier' => $identifier
                ])),
            };
        }

        // If it's not numeric, then it must be a string, so it must have been the unique column
        $return = static::getUniqueColumn();

        if ($return) {
            return $return;
        }

        // This particular implementation of the DataEntry doesn't have a unique column specified
        throw new OutOfBoundsException(tr('Failed to resolve ":class" DataEntry because identifier ":identifier" is not numeric and the class has no unique column specified', [
            ':type'       => static::class,
            ':identifier' => $identifier,
        ]));
    }


    /**
     * Returns the column that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return null;
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
    {
        return 'DataEntry';
    }


    /**
     * Returns the configured meta-column definitions for this DataEntry object
     *
     * Each database field requires a definition object where you define the name, data type and format of that field,
     * how to validate and render it, how to label it when shown, etc.
     *
     *  Definition options:
     *
     *  METHOD            DATATYPE           DEFAULT     DESCRIPTION
     *  setValue()        mixed              null        The default value for this entry
     *  setRender()       boolean            true        If false, this key will not be shown on web, and and the value
     *                                                   will be readonly
     *  setVisible()      boolean            true        If false, this key will render as a hidden element
     *  setVirtual()      boolean            false       If true, this key will be visible and can be modified but it
     *                                                   won't exist in database. It instead will be used to manipulate
     *                                                   a different field
     *  setElement()      string|null        "input"     Type of element, input, select, or text or callable function
     *  setType()         string|null        "text"      Type of input element, if element is "input"
     *  setReadonly()     boolean            false       If true, will make the input element readonly
     *  setDisabled()     boolean            false       If true, the field will be displayed as disabled
     *  setLabel()        string|null        null        If specified, will show a description label in HTML
     *  setSize()         int [1-12]         12          The HTML boilerplate column size, 1 - 12 (12 being the whole
     *                                                   row)
     *  setSource()       array|string|null  null        Array or query source to get contents for select, or single
     *                                                   value for text inputs
     *  setSourceData()
     *  setExecute()      array|null         null        Bound execution variables if specified "source" is a query
     *                                                   string
     *  setComplete()     array|bool|null    null        If defined must be bool or contain array with key "noword"
     *                                                   and "word". each key must contain a callable function that
     *                                                   returns an array with possible words for shell auto
     *                                                   completion. If bool, the system will generate this array
     *                                                   automatically from the rows for this field
     *  setCli()          string|null        null        If set, defines the alternative column name definitions for
     *                                                   use with CLI. For example, the column may be name, whilst
     *                                                   the cli column name may be "-n,--name"
     *  setOptional()     boolean            false       If true, the field is optional and may be left empty
     *  setTitle()        string|null        null        The title attribute which may be used for tooltips
     *  setPlaceholder()  string|null        null        The placeholder attribute which typically shows an example
     *  setMaxlength      string|null        null        The maxlength attribute which typically shows an example
     *  setPattern        string|null        null        The pattern the value content should match in browser client
     *  setMin            string|null        null        The minimum amount for numeric inputs
     *  setMax            string|null        null        The maximum amount for numeric inputs
     *  setStep           string|null        null        The up / down step for numeric inputs
     *  setDefault        mixed              null        If "value" for entry is null, then default will be used
     *  setNullDisabled() boolean            false       If "value" for entry is null, then use this for "disabled"
     *  setNullReadonly() boolean            false       If "value" for entry is null, then use this for "readonly"
     *  setNullType()     boolean            false       If "value" for entry is null, then use this for "type"
     *
     * @return static
     */
    protected function setMetaDefinitions(): static
    {
        $definitions = Definitions::new($this)->setTable(static::getTable());

        foreach ($this->meta_columns as $meta_column) {
            switch ($meta_column) {
                case 'id':
                    $definitions->add(Definition::new('id')
                                                ->setDisabled(true)
                                                ->setInputType(EnumInputType::dbid)
                                                ->addClasses('text-center')
                                                ->setSize(3)
                                                ->setCliAutoComplete(true)
                                                ->setTooltip(tr('This column contains the unique identifier for this object inside the database. It cannot be changed and is used to identify objects'))
                                                ->setLabel(tr('Database ID')));
                    break;

                case 'created_on':
                    $definitions->add(DefinitionFactory::newCreatedOn());
                    break;

                case 'created_by':
                    $definitions->add(DefinitionFactory::newCreatedBy());
                    break;

                case 'meta_id':
                    $definitions->add(DefinitionFactory::newMetaId());
                    break;

                case 'status':
                    $definitions->add(DefinitionFactory::newStatus()
                                                       ->setNullDisplay(tr('Ok')));
                    break;

                case 'meta_state':
                    $definitions->add(DefinitionFactory::newMetaState());
                    break;

                default:
                    throw new OutOfBoundsException(tr('Unknown meta definition column ":column" specified', [
                        ':column' => $meta_column,
                    ]));
            }
        }

        $this->definitions = $definitions->add(DefinitionFactory::newDivider('new-divider')
                                                                ->addPreRenderFunctions(function(DefinitionInterface $definition, array $source, mixed $value) {
                                                                    // Only render this when displaying meta-elements
                                                                    $definition->setRender(!$this->isNew() and $this->getDefinitionsObject()->getRenderMeta());
                                                                }));

        return $this;
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return null;
    }


    /**
     * Returns true if this is a new entry that hasn't been written to the database yet
     *
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->getId(false) === null;
    }


    /**
     * Returns true if this is an entry that exists in the database
     *
     * @return bool
     */
    public function isNotNew(): bool
    {
        return $this->getId(false) !== null;
    }


    /**
     * Returns id for this database entry
     *
     * @param bool $exception
     *
     * @return int|null
     * @throws DataEntryNotSavedException
     */
    public function getId(bool $exception = true): int|null
    {
        $id = $this->getTypesafe('int', static::getIdColumn());

        if (empty($id)) {
            if ($exception) {
                throw new DataEntryNotSavedException(tr('Cannot return ID for ":class" class object, it has no database id so has not been saved in the database', [
                    ':class' => static::getClassName(),
                ]));
            }
        }

        return $id;
    }


    /**
     * Sets the id field for this DataEntry object
     *
     * @param int|null $id
     *
     * @return static
     */
    protected function setId(?int $id): static
    {
        return $this->set($id, 'id');
    }


    /**
     * Returns a database id that can be displayed for users
     *
     * @return string|null
     */
    public function getDisplayId(): ?string
    {
        return $this->formatDisplayVariables(($this->getId(false) ?? ts('N/A')));
    }


    /**
     * Returns id for this database entry that can be used in logs
     *
     * @return string
     */
    public function getLogId(): string
    {
        return ($this->getId(false) ?? ts('N/A')) . ' / ' . (static::getUniqueColumn() ? $this->getTypesafe('string', static::getUniqueColumn()) : '-');
    }


    /**
     * Returns the unique identifier for this database entry, which will be the ID column if it does not have any
     *
     * @param bool $exception
     *
     * @return string|float|int|null
     */
    public function getUniqueColumnValue(bool $exception = true): string|float|int|null
    {
        $key = static::getUniqueColumn();

        if ($key) {
            // Test if this key was defined to begin with! If not, throw an exception to clearly explain what's wrong
            if ($this->getDefinitionsObject()->keyExists($key)) {
                return $this->getTypesafe('string|float|int|null', static::getUniqueColumn());
            }

            throw new OutOfBoundsException(tr('Specified unique key ":key" is not defined for the ":class" class DataEntry object', [
                ':class' => get_class($this),
                ':key'   => $key,
            ]));
        }

        return $this->getId($exception);
    }


    /**
     * Returns the value of the unique column
     *
     * @param mixed $value
     * @param bool  $force
     *
     * @return static
     */
    public function setUniqueColumnValue(mixed $value, bool $force = false): static
    {
        return $this->set($value, static::getUniqueColumn(), $force);
    }


    /**
     * Checks if the DataEntry object is new and throws Data
     *
     * @param string $action
     *
     * @return static
     */
    protected function checkNew(string $action): static
    {
        if ($this->isNew()) {
            throw new DataEntryNotSavedException(tr('Cannot perform ":action" on ":class" DataEntry, it has not yet been saved to the database', [
                ':action' => $action,
                ':class'  => static::class,
            ]));
        }

        return $this;
    }


    /**
     * Checks if the specified DataValue key can be visible outside this object or not
     *
     * @param string $column
     *
     * @return void
     */
    protected function checkProtected(string $column): void
    {
        if (empty($column)) {
            throw new OutOfBoundsException(tr('Empty column name specified'));
        }

        if (in_array($column, $this->protected_columns)) {
            throw new OutOfBoundsException(tr('Specified DataValue key ":key" is protected and cannot be accessed', [
                ':key' => $column,
            ]));
        }
    }


    /**
     * Sets the value for the specified data key
     *
     * @param mixed                       $value
     * @param Stringable|string|float|int $key
     * @param bool                        $skip_null_values
     *
     * @return static
     */
    public function set(mixed $value, Stringable|string|float|int $key, bool $skip_null_values = false): static
    {
        if ($this->debug) {
            Log::debug('TRY SET "' . Strings::fromReverse(static::class, '\\') . '::$' . $key . ' TO "' . Strings::log($value) . ' [' . gettype($value) . ']"', 10, echo_header: false);
        }

        // Make sure that definitions are available or give a clear error on what is going on
        if (empty($this->definitions)) {
            if ($this->is_initialized) {
                throw new DataEntryException(tr('The ":class" class has been initialized but has no definitions object', [
                    ':class' => get_class($this),
                ]));
            }

            $this->initialize(false);
        }

        // Only save values that are defined for this object
        if (!$this->getDefinitionsObject()->keyExists($key)) {
            if ($this->getDefinitionsObject()->isEmpty()) {
                throw new DataEntryException(tr('The ":class" class has no columns defined yet', [
                    ':class' => get_class($this),
                ]));
            }

            throw DataEntryColumnNotDefinedException::new(tr('Not setting column ":column", it is not defined for the ":class" class', [
                ':column' => $key,
                ':class'  => get_class($this),
            ]))->setData([
                'column' => $key
            ]);
        }

        // If the key is defined as readonly or disabled, it cannot be updated unless it's a new object or a
        // static value.
        $definition = $this->getDefinitionsObject()->get($key);

        // If a column is ignored, we won't update anything
        if ($definition->getIgnored()) {
            Log::warning(ts('Not updating DataEntry object ":object" column ":column" because it has the "ignored" flag set', [
                ':column' => $key,
                ':object' => get_class($this),
            ]), 6);

            return $this;
        }

        if ($value === null) {
            // Apply default values
            if ($this->isNew()) {
                $value = $definition->getInitialDefault() ?? $definition->getDefault();

            } else  {
                $value = $definition->getDefault();
            }

            if ($value === null) {
                if ($skip_null_values) {
                    if ($this->debug) {
                        Log::debug('NOT SETTING "' . Strings::fromReverse(static::class, '\\') . '::$' . $key . ', SKIPPING NULL VALUE', 10, echo_header: false);
                    }

                    return $this;
                }
            }

            if ($this->debug) {
                Log::debug('USE DEFAULT VALUE "' . Strings::log($value) . '" FOR FIELD "' . Strings::fromReverse(static::class, '\\') . '::$' . $key . '"', 10, echo_header: false);
            }
        }

        if (array_get_safe($this->source, $key) !== $value) {
            if (!$this->is_modified and !$definition->getIgnoreModify()) {
                $this->is_modified = true;
                $this->is_saved    = false;
            }

            if ($this->debug) {
                Log::debug('FIELD "' . Strings::fromReverse(static::class, '\\') . '::' . $key . '" WAS MODIFIED FROM "' . array_get_safe($this->source, $key) . '" [' . gettype(array_get_safe($this->source, $key)) . '] TO "' . $value . '" [' . gettype($value) . '], MARKED MODIFIED: ' . Strings::fromBoolean($this->is_modified), 10, echo_header: false);
            }

        } else {
            if ($this->debug) {
                Log::debug('FIELD "' . Strings::fromReverse(static::class, '\\') . '::' . $key . '" WAS NOT MODIFIED', 10, echo_header: false);
            }
        }

        // Update the column value
        $this->changes[]    =  $key;
        $this->source[$key] =  $value;
        $this->is_validated = (!$this->is_modified and $this->is_validated);
        $this->is_created   = (!$this->is_modified and $this->is_created);
        $this->is_saved     = (!$this->is_modified and $this->is_saved);

        return $this;
    }


    /**
     * Returns a list of all internal source keys
     *
     * @return mixed
     */
    public function getSourceKeys(bool $filter_meta = false): array
    {
        $keys = array_keys($this->source);

        if ($filter_meta) {
            return Arrays::removeValues($keys, $this->meta_columns);
        }

        return $keys;
    }


    /**
     * Initializes this DataEntry with the specified identifier data
     *
     * @note The ID column will NEVER be initialized
     *
     * @note This method should never receive NULL identifiers since those should be handled by the various load methods
     *
     * @note This method should never receive DataEntryInterface objects since this would always load
     *
     * @param DataIteratorInterface|array|string|int $identifier
     *
     * @return static
     */
    protected function initializeSource(DataIteratorInterface|array|string|int $identifier): static
    {
        if (is_numeric($identifier)) {
            // NEVER initialize the ID column
            return $this->ready();
        }

        if (is_string($identifier)) {
            // Initialize only the unique column
            return $this->set($identifier, static::getUniqueColumn())
                        ->ready();
        }

        if (is_array($identifier)) {
            // Initialize all columns that are NOT the ID column for this DataEntry object
            foreach($identifier as $column => $value) {
                if ($column !== static::getIdColumn()) {
                    $this->setColumnValueWithObjectSetter($column, $value, false, $this->getDefinitionsObject()->get($column));
                }
            }

        } elseif ($identifier instanceof DataIteratorInterface) {
            // Get the source from the DataIteratorInterface and try again
            return $this->initializeSource($identifier->getSource(false, false));
        }

        return $this->ready();
    }


    /**
     * Returns a DataEntry object matching the specified identifier that MUST exist in the database, or NULL if NULL
     * identifier was specified
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     *
     * @return static|null
     */
    public function loadOrInitialize(IdentifierInterface|array|string|int|false|null $identifier = false): ?static
    {
        try {
            return $this->load($identifier);

        } catch (DataEntryNotExistsException) {
            // This entry doesn't yet exist! Ignore, and just presume we want to make THIS particular entry.
            return $this->initializeSource($identifier);
        }
    }


    /**
     * Returns a DataEntry object matching the specified identifier that MUST exist in the database, or NULL if NULL
     * identifier was specified
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     *
     * @return static|null
     */
    public function loadOrNull(IdentifierInterface|array|string|int|false|null $identifier = false): ?static
    {
        if ($this->identifiersAreNull($identifier)) {
            return null;
        }

        return $this->load($identifier);
    }


    /**
     * Returns a DataEntry object matching the specified identifier that MUST exist in the database, or the current
     * object
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     *
     * @return static
     */
    public function loadOrThis(IdentifierInterface|array|string|int|false|null $identifier = false): static
    {
        if ($this->identifiersAreNull($identifier)) {
            return $this;
        }

        return $this->load($identifier);
    }


    /**
     * Returns a DataEntry object matching the specified identifier that MUST exist in the database, or the current
     * object
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     *
     * @return static
     */
    public function loadOrThisInitialize(IdentifierInterface|array|string|int|false|null $identifier = false): static
    {
        try {
            return $this->loadOrThis($identifier);

        } catch (DataEntryNotExistsException) {
            // This entry does not yet exist! Ignore, and just presume we want to make THIS particular entry.
            return $this->initializeSource($identifier);
        }
    }


    /**
     * Returns a DataEntry object matching the specified identifier that MUST exist in the database, or the current
     * object
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     *
     * @return static|null
     */
    public function loadOrNullInitialize(IdentifierInterface|array|string|int|false|null $identifier = false): ?static
    {
        try {
            return $this->loadOrNull($identifier);

        } catch (DataEntryNotExistsException) {
            // This entry does not yet exist! Ignore, and just presume we want to make THIS particular entry.
            return $this->initializeSource($identifier);
        }
    }


    /**
     * Reloads the data for this DataEntry
     *
     * @return static
     */
    public function reload(): static
    {
        // Start loading the object
        $this->is_initializing_source = true;

        // Load data from identifier
        $this->loadIdentifier();

        // This entry exists in the database, yay! Is it not deleted, though?
        if ($this->isDeleted()) {
            $this->processDeleted();
        }

        return $this->ready(true);
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
     * @param IdentifierInterface|array|string|int|false|null $identifier
     *
     * @return static
     */
    public function load(IdentifierInterface|array|string|int|false|null $identifier): static
    {
        if ($this->debug) {
            Log::debug('TRY LOADING CLASS "' . Strings::fromReverse(static::class, '\\') . '" WITH IDENTIFIER "' . Strings::log($identifier) . '"', 10, echo_header: false);
        }

        if ($this->is_loaded) {
            throw DataEntryException::new(tr('Cannot load identifier ":identifier" for ":class" class, the object already has data loaded', [
                ':identifier' => $identifier,
                ':class'      => $this::class
            ]))->setData([
                'source'      => $this->source
            ]);
        }

        if (empty($identifier)) {
            throw DataEntryNoIdentifierSpecifiedException::new(tr('Cannot load data for DataEntry object ":class", no identifier specified', [
                ':class' => $this::class
            ]))->addData([
                'class' => static::getClassName(),
            ]);
        }

        if (!$this->is_initialized) {
            $this->initialize(false);
        }

        if (is_object($identifier)) {
            // This already is a DataEntry object, no need to create one. Validate that this is the same class
            if (($identifier instanceof static) or is_subclass_of(static::class, get_class($identifier))) {
                // The identifier is the same as this, or extended this. Copy its source inside this object
                return $this->setIdentifier($identifier->getIdentifier())
                            ->setSource($identifier->getSource(false, false))
                            ->ready(true);
            }

            throw new OutOfBoundsException(tr('Specified DataEntry identifier ":has" is incompatible with this object\'s class ":should"', [
                ':has'    => get_class($identifier),
                ':should' => static::class,
            ]));
        }

        // Start loading the object
        $this->setIdentifier($identifier)
             ->is_initializing_source = true;

        if ($this->connector === null) {
            // Use the default connector for this DataEntry object
            $this->setConnectorObject(static::getDefaultConnectorObject());
        }

        if ($this->loadFromCache()) {
            // This DataEntry was found in the cache, all is done!
            return $this->ready(true);
        }

        // Load data from identifier
        $this->loadIdentifier();

        // This entry exists in the database, yay! Is it not deleted, though?
        if ($this->isDeleted()) {
            if ($this->debug) {
                Log::debug('CLASS "' . Strings::fromReverse(static::class, '\\') . '" WITH IDENTIFIER "' . Strings::log($identifier) . '" IS DELETED', 10, echo_header: false);
            }

            $this->processDeleted();
        }

        return $this->saveToCache()->ready(true);
    }


    /**
     * Returns the specified columns from the DataEntry object matching the specified identifier
     * (MUST exist in the database)
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     * @param array|string                              $columns
     *
     * @return static
     */
    public function loadColumns(IdentifierInterface|array|string|int|false|null $identifier = false, array|string $columns = 'id'): static
    {
        $columns = Arrays::force($columns);

        foreach ($columns as &$column) {
            if ($column === 'id') {
                $column = static::getIdColumn();
            }
        }

        unset($column);

        $this->cache_key = null;
        $this->columns   = $columns;
        return $this->load($identifier);
    }


    /**
     * Generates and returns a unique cache key for this DataEntry object
     *
     * @param String|null $append_string
     *
     * @return string|null
     */
    public function getCacheKeySeed(?String $append_string = null): ?string
    {
        return 'DataEntry-' . static::class . '-' . Json::encode($this->identifier, JSON_BIGINT_AS_STRING) . '-' . Json::encode($this->columns, JSON_BIGINT_AS_STRING) . '-' . $this->getQueryHash() . ($append_string ? '-' . $append_string : null);
    }


    /**
     * Tries to load this DataEntry object data from the cache layer instead of the database
     *
     * @return bool
     */
    protected function loadFromCache(): bool
    {
        if (is_a($this, Connector::class)) {
            // Connectors are non-cacheable because they would cause endless loops
            return false;
        }

        if (!Cache::isEnabled()) {
            // Caching is disabled for everything
            return false;
        }

        if (!$this->cache) {
            // Caching is disabled for this DataEntry object
            return false;
        }

        if (!InstanceCache::exists('dataentries', $this->getCacheKey())) {
            $data_entry = cache('dataentries')->get($this->getCacheKey());

           if ($data_entry) {
               if ($this->debug) {
                   Log::debug('FOUND CLASS "' . Strings::fromReverse(static::class, '\\') . '" WITH IDENTIFIER "' . Strings::log($this->identifier) . '" IN GLOBAL CACHE WITH KEY "' . $this->getCacheKey() . '"', 10, echo_header: false);
                   Log::printr($data_entry->getSource(as_is: true), 10, echo_header: false);
               }

                if ($data_entry instanceof DataEntryInterface) {
                    // Found it in external cache!
                    $this->is_loaded_from_cache = true;
                    $this->source               = $data_entry->getSource(false, false);
                    return true;
                }

                throw DataEntryInvalidCacheException::new(tr('Failed to load ":class" DataEntry with identifier ":identifier" from cache, cache returned invalid non DataEntry object', [
                    ':class'      => static::class,
                    ':identifier' => $this->identifier
                ]))->setData([
                    'data_entry' => $data_entry
                ]);
            }

            return false;
        }

        // Cached entry exists, gettit!
        $data_entry = InstanceCache::getLastChecked();

        // Only use cached entries that are NOT modified!
        if ($data_entry->isModified()) {
            return false;
        }

        if ($this->debug) {
            Log::debug('FOUND CLASS "' . Strings::fromReverse(static::class, '\\') . '" WITH IDENTIFIER "' . Strings::log($this->identifier) . '" IN INSTANCE CACHE WITH KEY "' . $this->getCacheKey() . '"', 10, echo_header: false);
            Log::debug($this->getCacheKeySeed());
            Log::printr($data_entry->getSource(as_is: true), 10, echo_header: false);
        }

        $this->is_loaded_from_cache = true;
        $this->source               = $data_entry->getSource(false, false);
        return true;
    }


    /**
     * Saves this DataEntry object to cache
     *
     * @return static
     */
    protected function saveToCache(): static
    {
        InstanceCache::set($this, 'dataentries', $this->getCacheKey());

        // Cache the dataentry and update table state
        if (!is_a($this, Connector::class)) {
            if (Cache::isEnabled()) {
                // Connector objects CANNOT be cached
                cache('dataentries')->set($this, $this->getCacheKey());
            }
        }

        return $this;
    }


    /**
     * Saves this DataEntry object to cache
     *
     * @return static
     */
    protected function removeFromCache(): static
    {
        InstanceCache::delete('dataentries', $this->getCacheKey());

        // Cache the dataentry and update table state
        if (!is_a($this, Connector::class)) {
            if (Cache::isEnabled()) {
                // Connector objects CANNOT be cached
                cache('dataentries')->delete($this->getCacheKey());
                $this->setTableState();
            }
        }

        return $this;
    }


    /**
     * Loads a random DataEntry object
     *
     * @return static|null
     */
    public function loadRandom(): ?static
    {
        $identifier = sql(static::getDefaultConnector())->getInteger('SELECT   `id` 
                                                                      FROM     `' . static::getTable() . '` 
                                                                      ORDER BY RAND() 
                                                                      LIMIT    1;');

        if ($identifier) {
            return $this->load($identifier);
        }

        throw new OutOfBoundsException(tr('Cannot select random record for table ":table", no records found', [
            ':table' => static::getTable(),
        ]));
    }


    /**
     * Loads a random DataEntry object with status "test"
     *
     * @return static|null
     */
    public function loadRandomTest(): ?static
    {
        $identifier = sql(static::getDefaultConnector())->getInteger('SELECT   `id` 
                                                                      FROM     `' . static::getTable() . '` 
                                                                      WHERE `status` = "test"
                                                                      ORDER BY RAND() 
                                                                      LIMIT    1;');

        if ($identifier) {
            return $this->load($identifier);
        }

        throw new OutOfBoundsException(tr('Cannot select random record for table ":table", no records found', [
            ':table' => static::getTable(),
        ]));
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
     * Returns true if the ID column is the specified column
     *
     * @param string $column
     *
     * @return bool
     */
    public static function idColumnIs(string $column): bool
    {
        return static::getIdColumn() === $column;
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
     * Returns true if this DataEntry has the specified status
     *
     * @param string|null $status
     *
     * @return bool
     */
    public function isStatus(?string $status): bool
    {
        return $this->getTypesafe('string', 'status') === $status;
    }


    /**
     * Returns the name for this user that can be displayed
     *
     * @return string|null
     */
    public function getDisplayName(): ?string
    {
        return $this->formatDisplayVariables($this->getTypesafe('string', 'name'));
    }


    /**
     * Ensures the display name is correct
     *
     * @param string|int|null $real_name
     * @return string|null
     */
    protected function formatDisplayVariables(string|int|null $real_name): ?string
    {
        if ($this->isNew()) {
            return tr('[NEW]');
        }

        if ($this->getStatus() === 'deleted') {
            return $real_name . ' ' . tr('[DELETED]');
        }

        return (string) $real_name;
    }


//    /**
//     * Add the complete definitions and source from the specified data entry to this data entry
//     *
//     * @param string $at_key
//     * @param mixed $value
//     * @param DefinitionInterface $definition
//     * @param bool $after
//     * @return static
//     * @todo Improve by first splitting meta data off the new data entry and then ALWAYS prepending it to ensure its at the front
//     */
//    public function injectDataEntryValue(string $at_key, string|float|int|null $value, DefinitionInterface $definition, bool $after = true): static
//    {
//        $this->source[$definition->getColumn()] = $value;
//        $this->getDefinitionsObject()->spliceByKey($at_key, 0, [$definition->getColumn() => $definition], $after);
//        return $this;
//    }


    /**
     * Returns status for this database entry
     *
     * @return ?string
     */
    public function getStatus(): ?string
    {
        return $this->getTypesafe('string', 'status');
    }


    /**
     * Sets if this object is readonly or not
     *
     * @param bool $readonly
     *
     * @return static
     */
    public function setReadonly(bool $readonly): static
    {
        if (!$readonly and $this->isLoadedFromConfiguration()) {
            throw new ReadOnlyModeException(tr('Cannot disable readonly mode for the DataEntry ":class" class, it is loaded from configuration and cannot be saved', [
                ':class' => static::class,
            ]));
        }

        return $this->__setReadonly($readonly);
    }


    /**
     * Sets and returns the column definitions for the data columns in this DataEntry object
     *
     * Format:
     *
     * [
     *   column => [key => value],
     *   column => [key => value],
     *   column => [key => value],
     * ]
     *
     * "column" should be the database table column name
     *
     * Column keys:
     *
     * FIELD          DATATYPE           DEFAULT VALUE  DESCRIPTION
     * value          mixed              null           The value for this entry
     * visible        boolean            true           If false, this key will not be shown on web, and be readonly
     * virtual        boolean            false          If true, this key will be visible and can be modified but it
     *                                                  won't exist in database. It instead will be used to generate
     *                                                  a different column
     * element        string|null        "input"        Type of element, input, select, or text or callable function
     * type           string|null        "text"         Type of input element, if element is "input"
     * readonly       boolean            false          If true, will make the input element readonly
     * disabled       boolean            false          If true, the column will be displayed as disabled
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
     *                                                  automatically from the rows for this column
     * cli            string|null        null           If set, defines the alternative column name definitions for
     *                                                  use with CLI. For example, the column may be name, whilst
     *                                                  the cli column name may be "-n,--name"
     * optional       boolean            false          If true, the column is optional and may be left empty
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
     *
     * @return static
     */
    protected function setDefinitions(DefinitionsInterface $definitions): static
    {
        // Each DataEntry object should set its own definitions!
        return $this;
    }


    /**
     * Returns all data for this data entry at once with an array of information
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     *
     * @param bool $filter_meta              If true, will filter out the DataEntry meta-columns
     * @param bool $filter_protected_columns If true, will filter out the DataEntry protected columns (typically
     *                                       passwords, etc)
     * @param bool $as_is
     *
     * @return array
     */
    public function getSource(bool $filter_meta = false, bool $filter_protected_columns = true, bool $as_is = false): array
    {
        if ($as_is) {
            return $this->source;
        }

        $source = $this->getSourceWithResolvedVirtualColumns();

        if ($filter_meta) {
            // Remove meta-columns
            $source = Arrays::removeKeys($source, $this->meta_columns);
        }

        if ($filter_protected_columns) {
            // Remove protected columns
            $source = Arrays::removeKeys($source, $this->protected_columns);
        }

        return $source;
    }


    /**
     * Returns a source array with all source virtual columns resolved (not NULL)
     *
     * @return array
     */
    protected function getSourceWithResolvedVirtualColumns(): array
    {
        $source = [];

        if ($this->definitions) {
            foreach ($this->definitions as $column => $definition) {
                if (!$definition->getContainsData()) {
                    // Don't process data-less columns
                    continue;
                }

                // Get the value from the source, ensure to apply default or initial default values
                $value = array_get_safe($this->source, $column, $this->isNew() ? ($definition->getInitialDefault() ?? $definition->getDefault()) : $definition->getDefault());

                // If the value is null, apply the get method for the column IF IT EXISTS. If the get method doesn't exist,
                // just copy the NULL value as-is
                if ($value === null) {
                    // Meta columns are never virtual, ignore them as accessing them might cause issues
                    if ($this->isMetaColumn($column)) {
                        $source[$column] = $value;
                        continue;
                    }

                    $method = $this->convertColumnToMethod($column, 'get');

                    if (method_exists(static::class, $method)) {
                        $source[$column] = $this->$method();

                    } else {
                        $source[$column] = $value;
                    }

                } else {
                    $source[$column] = $value;
                }
            }
        }

        return $source;
    }


    /**
     * Loads the specified data into this DataEntry object
     *
     * @param DataEntryInterface|IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null                                                          $execute
     * @param bool                                                                $filter_meta
     *
     * @return static
     */
    public function setSource(DataEntryInterface|IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null, bool $filter_meta = false): static
    {
        // Initialize the object
        $this->is_initializing_source = true;
        $this->source                 = [];

        if ($source) {
            if (($source instanceof DataEntryInterface) or ($source instanceof IteratorInterface)) {
                $source = $source->getSource(false, false);

            } elseif ($source instanceof PDOStatement) {
                $source = sql()->list($source, $execute);

            } elseif (is_string($source)) {
                // Source string, must be JSON or SQL query
                try {
                    $source = Json::decode($source);

                } catch (Throwable) {
                    // This is not JSON, is it an SQL query?
                    $source = sql()->list($source, $execute);
                }
            }

            $this->is_loading = true;

            if (!$filter_meta) {
                // Load meta data too
                $this->setMetaData($source);
            }

            // Load data with object init
            $this->copyValuesToSource($source, false);
        }

        // Done!
        return $this->ready();
    }


    /**
     * Try to load this DataEntry from configuration instead of database
     *
     * @param array|string|int $identifier
     *
     * @return static
     */
    protected function tryLoadFromConfiguration(array|string|int $identifier): static
    {
        $path = $this->getConfigurationPath();

        // Can only load from configuration if the configuration path is available
        if ($path) {
            if ($this->debug) {
                Log::debug('TRY LOADING CLASS "' . Strings::fromReverse(static::class, '\\') . '" WITH IDENTIFIER "' . Strings::log($identifier) . '" FROM CONFIGURATION', 10, echo_header: false);
            }

            // This DataEntry supports loading from configuration. Identifier arrays may ONLY contain one column!
            $column = static::determineColumn($identifier);

            if (is_array($identifier)) {
                $identifier = $identifier[$column];
            }

            // Can only load from configuration using unique column, or id column or NULL column (means id column too)
            if (($column === null) or ($column === static::getUniqueColumn()) or ($column === static::getIdColumn())) {
                if (!static::idColumnIs('id')) {
                    throw new DataEntryException(tr('Cannot use configuration paths for DataEntry object ":class" that uses id column ":column" instead of "id"', [
                        ':class'  => static::class,
                        ':column' => static::getIdColumn(),
                    ]));
                }

                // See if there is a configuration entry in the specified path
                $source = $this->loadFromConfiguration($path, $identifier);

                if ($source) {
                    if ($this->debug) {
                        Log::debug('FOUND CLASS "' . Strings::fromReverse(static::class, '\\') . '" WITH IDENTIFIER "' . Strings::log($identifier) . '" IN CONFIGURATION', 10, echo_header: false);
                    }

                    if ($this->columns) {
                        $source = Arrays::keepKeys($source, $this->columns);
                    }

                    // Load the source in this object and make this object readonly
                    return $this->setSource($source)
                                ->setReadonly(true);
                }
            }
        }

        throw DataEntryNotExistsException::new(tr('Cannot load ":class" class object, specified identifier ":identifier" does not exist', [
            ':class'      => static::getClassName(),
            ':identifier' => Json::encode($this->identifier),
        ]))->addData([
            'class'       => static::getClassName(),
            ':identifier' => Json::encode($this->identifier),
        ]);
    }


    /**
     * Loads the DataEntry from configuration instead of the database
     *
     * @param string $path
     * @param string|int $identifier
     * @return array|null
     */
    protected function loadFromConfiguration(string $path, string|int $identifier): ?array
    {
        try {
            $source = config()->getArray(Strings::ensureEndsWith($path, '.') . Config::escape($identifier), []);

        } catch (ConfigEmptyException) {
            // The configuration key exists, but is empty. Act as if it does not exist
            Log::warning(ts('Ignoring empty value for configuration path ":path" while trying to load a ":class" DataEntry object', [
                ':path'  => $path,
                ':class' => static::class
            ]));

            return null;
        }

        if (count($source)) {
            // Found the entry in configuration! Make it a readonly DataEntry object
            $source['id']     = -1;
            $source['status'] = 'configuration';
            $source['name']   = $identifier;

            // Create a DataTypeInterface object but since we can't write configuration, make it readonly!
            return $source;
        }

        // Entry not available in configuration
        return null;
    }


    /**
     * Load all object data from the database table row
     *
     * @return static
     */
    protected function loadFromDatabase(): static
    {
        if ($this->debug) {
            Log::debug('TRY LOADING CLASS "' . Strings::fromReverse(static::class, '\\') . '" WITH IDENTIFIER "' . Strings::log($this->identifier) . '" FROM DATABASE', 10, echo_header: false);
        }

        $this->is_loading = true;
        $this->cache_key  = null;


        // TODO This may no longer be necessary with the upgraded setIdentifier() which already ensures identifier internally is an array!
        if (is_array($this->identifier)) {
            if ((!empty($this->columns)) and (count($this->identifier) > 1)) {
                throw UnderConstructionException::new(tr('Sorry, DataEntry->loadColumns() does not yet support array identifiers'));
            }

            // Filter on multiple columns, multi column filter always pretends filtered column was id column
            static::buildManualQuery($this->identifier, $where, $joins, $group, $order, $execute);
            $column = null;

        } else {
            // For single column queries, determine the column we should use
            $column  = static::determineColumn($this->identifier);
            $where   = '`' . static::getTable() . '`.`' . $column . '` = :identifier';
            $execute = [':identifier' => $this->identifier];
        }

        try {
            $this->executeQueryAndLoadData($where, $execute);

        } catch (SqlUnknownDatabaseException $e) {
            // During project init we can ignore "database not found" exceptions so that config load may still happen
            if (!Core::inInitState()) {
                // But this only works if the DataEntry can load data from configuration
                if (!$this->getConfigurationPath()) {
                    throw $e;
                }
            }
        }

        // Reset state
        $this->is_loading  = false;
        $this->is_saved    = false;
        $this->is_modified = false;

        return $this;
    }


    /**
     * Executes the query and loads the data into the DataEntry
     *
     * @param string $where
     * @param array  $execute
     *
     * @return void
     */
    protected function executeQueryAndLoadData(string $where, array $execute): void
    {
        try {
            // Get the data using the query builder
            $query = $this->getQueryBuilderObject()->setDebug($this->debug)
                                                   ->setMetaEnabled($this->meta_enabled)
                                                   ->setConnectorObject($this->getConnectorObject())
                                                   ->addWhere($where, $execute);

            // Generate columns that will be selected
            if ($this->columns) {
                $query->setSelect(null);

                // Add selects for each specified column
                foreach ($this->columns as $column) {
                    $query->addSelect('`' . static::getTable() . '`.' . $column);
                }

            } else {
                // Load all columns
                $query->setSelect('`' . static::getTable() . '`.*');
            }

            $source = $query->get();

            if ($source) {
                if ($this->debug) {
                    Log::debug('FOUND CLASS "' . Strings::fromReverse(static::class, '\\') . '" WITH IDENTIFIER "' . Strings::log($this->identifier) . '" IN DATABASE', 10, echo_header: false);
                }

                // If data was found, store all data in the object
                $this->setMetaData($source)
                     ->copyValuesToSource($source, false);
            }

        } catch (SqlTableDoesNotExistException $e) {
            // The table for this object does not exist. This means that we're missing an init, perhaps, or maybe
            // even the entire databese doesn't exist? Maybe we're in init or sync mode? Allow the system to continue
            // to check if this entry perhaps is configured, so we can continue
            if (!Core::inInitState()) {
                throw $e;
            }

            // We're in project init state, act as if the entry simply doesn't exist
        }
    }


    /**
     * Returns the hash for the executed query
     *
     * @return string|null
     */
    public function getQueryHash(): ?string
    {
        return $this->getQueryBuilderObject()->getQueryHash();
    }


    /**
     * Returns the query builder for this data entry
     *
     * @return QueryBuilderInterface
     */
    public function getQueryBuilderObject(): QueryBuilderInterface
    {
        if (!$this->query_builder) {
            $this->query_builder = QueryBuilder::new($this)->setDebug($this->debug);
        }

        return $this->query_builder;
    }


    /**
     * Sets the QueryBuilder object to modify the internal query for this object
     *
     * @param QueryBuilderInterface $query_builder
     *
     * @return static
     */
    public function setQueryBuilderObject(QueryBuilderInterface $query_builder): static
    {
        $this->query_builder = $query_builder;
        return $this;
    }


    /**
     * Allows the query builder to be modified through a callback function
     *
     * @param callable $callback
     *
     * @return static
     */
    public function modifyQueryBuilderObject(callable $callback): static
    {
        $callback($this->query_builder);
        return $this;
    }


    /**
     * Copies all specified source entries to the internal source
     *
     * @param array $source The data for this DataEntry object
     * @param bool  $modify
     * @param bool  $directly
     * @param bool  $force
     *
     * @return static
     */
    protected function copyValuesToSource(array $source, bool $modify, bool $directly = false, bool $force = false): static
    {
        $this->checkDefinitionsObject();

        // Setting columns will make $this->is_validated false, so store the current value;
        $validated = $this->is_validated;

        foreach ($this->definitions as $key => $definition) {
            if ($this->debug) {
                Log::debug(      ts('TRY COPYING VALUE FOR ":key" TO SOURCE', [
                    ':key' => $key,
                ]), echo_header: false);
            }

            // Meta-keys cannot be set through DataEntry::setData()
            if ($definition->isMeta()) {
                continue;
            }

            if ($this->is_applying and !$force) {
                if ($definition->getReadonly() or $definition->getDisabled() or !$definition->getRender()) {
                    if (!$definition->getForcedProcessing()) {
                        // Apply cannot update readonly or disabled columns
                        continue;
                    }

                    // This entry is readonly or disabled, but will be forcibly processed
                }
            }

            // Get the value for the current key
            if (array_key_exists($key, $source)) {
                $value = $source[$key];

            } elseif (empty($this->source[$key])) {
                // This is empty in the specified source and empty in the internal source,default it
                if ($this->isNew()) {
                    // This is a new (unsaved) object, apply initial default
                    $value = $definition->getInitialDefault() ?? $definition->getDefault();

                } else {
                    // This is an existing object, apply normal default
                    $value = $definition->getDefault();
                }

            }
            else {
                // The current key is empty in the specified source and exists in the internal source, do not update
                continue;
            }

            if ($definition->getVirtual()) {
                // Virtual columns do nothing if they have no value
                if ($value === null) {
                    continue;
                }

                // This is a virtual column, do NOT apply during load time
                if ($this->is_loading or $this->is_initializing_source) {
                    continue;
                }
            }

            if (!$modify) {
                // Remove prefix / postfix if defined
                if ($definition->getPrefix()) {
                    $value = Strings::from($value, $definition->getPrefix());
                }

                if ($definition->getSuffix()) {
                    $value = Strings::untilReverse($value, $definition->getSuffix());
                }
            }

            try {
                $this->setColumnValueWithObjectSetter($key, $value, $directly, $definition);

            } catch (TypeError | DataEntryException $e) {
                if (!is_string($key)) {
                    throw DataEntryColumnDefinitionInvalidException::new(tr('Detected invalid column definition while copying new source data, Definition column name ":column" of ":class" DataEntry class is invalid, it should be a string', [
                        ':class'  => $this::class,
                        ':column' => $key,
                    ]), $e)->setData([
                                         'class'        => $this::class,
                                         'failed_key'   => $key,
                                         'failed_value' => $value,
                                         'new_source'   => $source,
                                         'definitions'  => $this->getDefinitionsObject()
                                                                ->getSourceKeys()
                                     ]);
                }

                throw DataEntryException::new(tr('Failed to copy new source into internal source for ":class" class', [
                    ':class' => $this::class,
                ]), $e)->setData([
                    'class'        => $this::class,
                    'failed_key'   => $key,
                    'failed_value' => $value,
                    'new_source'   => $source,
                    'definitions'  => $this->getDefinitionsObject()
                                           ->getSourceKeys()
                ]);
            }
        }

        if ($this->sourceLoadedFromConfiguration()) {
            $this->readonly = true;
        }

        $this->is_validated = $validated;
        $this->previous_id  = $this->getId(false);

        return $this;
    }


    /**
     * Updates the specified column with the given value, using the objects setter method (which MUST exist)
     *
     * @param string              $column
     * @param mixed               $value
     * @param bool                $directly
     * @param DefinitionInterface $definition
     *
     * @return void
     */
    protected function setColumnValueWithObjectSetter(string $column, mixed $value, bool $directly, DefinitionInterface $definition): void
    {
        /*
         * Update columns directly if:
         *
         * 1) This DataEntry has no direct methods defined for its source keys
         * 2) This method was called with the $directly flag
         * 3) If this specific column has no direct methods defined and updates directly
         */
        if (!static::requireDefinitionsMethods() or $directly or $this->getDefinitionsObject()->get($column)?->getDirectUpdate()) {
            // Store data directly, bypassing the set method for this key
            $this->set($value, $column);

        } else {
            // Store this data through the set method to ensure datatype and filtering is done correctly
            $method = $this->convertColumnToMethod($column, 'set');

            if (!$definition->inputTypeIsScalar()) {
                // This input type is not scalar and as such has been stored as a JSON array
                $value = Json::ensureDecoded($value);
            }

            try {
                if ($this->getDefinitionsObject()->get($column)->getContainsData()) {
                    if ($this->debug) {
                        Log::debug('SET "' . Strings::fromReverse(static::class, '\\') . '::$' . $column . '" using ' . Strings::fromReverse(static::class, '\\') . '::' . $method . '() ' . (method_exists($this, $method) ? '(exists)' : '(NOT exists)') . ' TO "' . Strings::log($value) . ' [' . gettype($value) . ']"', 10, echo_header: false);
                    }

                    $this->$method($value);
                }

            } catch (Throwable $e) {
                if (($e instanceof SqlTableDoesNotExistException) or ($e instanceof SqlUnknownDatabaseException)) {
                    // These exceptions mean that the database or table accessed does not exist
                    throw $e;
                }

                if (method_exists($this, $method)) {
                    if (str_contains($e->getMessage(), 'must be of type')) {
                        // There is no method accepting this data. This might be because it is a virtual column that gets
                        // resolved at validation time. Check this with the "definitions" object
                        throw DataEntryException::new(tr('Failed to set DataEntry class ":class" source key ":key" with method ":short:::method()" because of a datatype mismatch. Check the column definition and validation rules.', [
                            ':key'    => $column,
                            ':method' => $method,
                            ':class'  => get_class($this),
                            ':short'  => Strings::fromReverse(get_class($this), '\\'),
                        ]), $e)
                        ->setData([
                            'column'   => $column,
                            'datatype' => gettype($value),
                            'value'    => $value,
                            'method'   => $this::class . '::' . $method . '()',
                        ]);
                    }

                    // There is no method accepting this data. This might be because it is a virtual column that gets
                    // resolved at validation time. Check this with the "definitions" object
                    throw new DataEntryException(tr('Failed to set DataEntry class ":class" source key ":key" with method ":short:::method()".', [
                        ':key'    => $column,
                        ':method' => $method,
                        ':class'  => get_class($this),
                        ':short'  => Strings::fromReverse(get_class($this), '\\'),
                    ]), $e);
                }

                // The set method doesn't exist and is required
                throw new DataEntryException(tr('Cannot set DataEntry class ":class" source column ":column" because the class has no linked set method ":short:::method()" defined', [
                    ':short'  => Strings::fromReverse($this::class, '\\'),
                    ':column' => $column,
                    ':method' => $method,
                    ':class'  => $this::class,
                ]), $e);
            }
        }
    }


    /**
     * Returns true if the definitions of this DataEntry have their own methods
     *
     * @return bool
     */
    public static function requireDefinitionsMethods(): bool
    {
        return true;
    }


    /**
     * Converts and returns the specified column name into a get or set method
     *
     * @param string $column
     * @param string $type
     *
     * @return string
     */
    protected function convertColumnToMethod(string $column, string $type): string
    {
        // Convert underscore to camelcase
        // Remove the prefix from the column
        if ($this->getDefinitionsObject()->getPrefix()) {
            $column = Strings::from($column, $this->getDefinitionsObject()->getPrefix());
        }

        $return = explode('_', $column);
        $return = array_map('ucfirst', $return);
        $return = implode('', $return);

        return $type . ucfirst($return);
    }


    /**
     * Sets all meta-data for this data entry at once with an array of information
     *
     * @param ?array $data
     *
     * @return static
     * @throws OutOfBoundsException
     */
    protected function setMetaData(?array $data = null, bool $directly = false): static
    {
        $this->checkDefinitionsObject();

        if ($data === null) {
            // No data specified, all columns should be null
            $this->source = Arrays::setKeys($this->meta_columns, null, $this->source);

        } else {
            // Reset meta columns
            foreach ($this->meta_columns as $column) {
                $this->setColumnValueWithObjectSetter($column, array_get_safe($data, $column), $directly, $this->getDefinitionsObject()->get($column));
            }
        }

        return $this;
    }


    /**
     * Returns true if this DataEntry will use random id's
     *
     * @return bool
     */
    public static function getRandomIdEnabled(): bool
    {
        return true;
    }


    /**
     * Returns a help file generated from the DataEntry keys
     *
     * @param array $auto_complete
     *
     * @return array
     */
    public static function getAutoComplete(array $auto_complete = []): array
    {
        $arguments = [];

        // Extract auto complete for cli parameters from column definitions
        foreach (static::new()->getDefinitionsObject() as $definitions) {
            if ($definitions->getCliColumn() and $definitions->getCliAutoComplete()) {
                $arguments[$definitions->getCliColumn()] = $definitions->getCliAutoComplete();
            }
        }

        // Merge and return found auto complete parameters with specified auto complete parameters
        return array_merge_recursive($auto_complete, [
            'arguments' => $arguments,
        ]);
    }


    /**
     * Returns a help text generated from this DataEntry's column information
     *
     * The help text will contain help information for each column as defined in DataEntry::columns. Since this help
     * text is for the command line, column names will be translated to their command line argument counterparts (so
     * instead of "name" it would show "-n,--name")
     *
     * @param string|null $help
     *
     * @return string
     */
    public static function getHelpText(?string $help = null): string
    {
        if ($help) {
            $help = trim($help);
            $help = preg_replace('/ARGUMENTS/', CliColor::apply(strtoupper(tr('ARGUMENTS')), 'white'), $help);
        }

        $groups  = [];
        $columns = static::new()->getDefinitionsObject();
        $return  = PHP_EOL . PHP_EOL . PHP_EOL . CliColor::apply(strtoupper(tr('REQUIRED ARGUMENTS')), 'white');

        // Get the required columns and gather a list of available help groups
        foreach ($columns as $id => $definitions) {
            if ($definitions->isMeta()) {
                continue;
            }

            if (!$definitions->getRender()) {
                continue;
            }

            if (!$definitions->getOptional()) {
                $columns->removeKeys($id);
                $return .= PHP_EOL . PHP_EOL . Strings::size($definitions->getCliColumn(), 39) . ' ' . $definitions->getHelpText();
            }

            $groups[$definitions->getHelpGroup()] = true;
        }

        // Get the columns and group them by help_group
        foreach ($groups as $group => $nothing) {
            $body = '';

            if ($group) {
                $header = PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . CliColor::apply(strtoupper(trim($group)), 'white');

            } else {
                $header = PHP_EOL . PHP_EOL . PHP_EOL . CliColor::apply(strtoupper(tr('Miscellaneous information')), 'white');
            }

            foreach ($columns as $id => $definitions) {
                if ($definitions->isMeta()) {
                    continue;
                }

                if ($definitions->getHelpGroup() === $group) {
                    $columns->removeKeys($id);
                    $body .= PHP_EOL . PHP_EOL . Strings::size($definitions->getCliColumn(), 39) . ' ' . $definitions->getHelpText();
                }
            }

            if ($group) {
                if ($body) {
                    // We found body text. Add the header and body to the return text
                    $return .= $header . $body;
                }

            } else {
                $miscellaneous = $header . $body;
            }
        }

        // Get the columns that have no group
        return $help . $return . isset_get($miscellaneous) . PHP_EOL;
    }


    /**
     * Updates the columns in the specified source with their new values
     *
     * @param array $source
     *
     * @return static
     */
    public function setMultiple(array $source): static
    {
        if ($this->readonly or $this->disabled) {
            throw new DataEntryReadonlyException(tr('Cannot apply changes to ":name" object, the object is readonly or disabled', [
                ':name' => static::getEntryName(),
            ]));
        }

        return $this->copyValuesToSource($source, true);
    }


    /**
     * Apply the specified data source over the current source
     *
     * @param bool                           $require_clean_source
     * @param ValidatorInterface|array|null &$source
     *
     * @return static
     */
    public function apply(bool $require_clean_source = true, ValidatorInterface|array|null &$source = null): static
    {
        if ($this->readonly or $this->disabled) {
            throw new DataEntryReadonlyException(tr('Cannot apply changes to ":name" object, the object is readonly or disabled', [
                ':name' => static::getEntryName(),
            ]));
        }

        return $this->checkReadonly('apply')
                    ->doApply($require_clean_source, $source, false);
    }


    /**
     * Modify the data for this object with the new specified data
     *
     * @param bool                           $require_clean_source
     * @param ValidatorInterface|array|null &$source
     * @param bool                           $force
     *
     * @return static
     */
    protected function doApply(bool $require_clean_source, ValidatorInterface|array|null &$source, bool $force): static
    {
        // Are we allowed to create or modify this DataEntry?
        if ($this->isNew()) {
            if (!$this->allow_create) {
                // auto create is not allowed, sorry!
                throw new ValidationFailedException(tr('Cannot create new :entry', [
                    ':entry' => static::getEntryName(),
                ]));
            }

        } else {
            if (!$this->allow_modify) {
                // auto modify is not allowed, sorry!
                throw new ValidationFailedException(tr('Cannot modify :entry', [
                    ':entry' => static::getEntryName(),
                ]));
            }
        }

        $this->is_applying  = true;
        $this->is_validated = false;
        $this->is_saved     = false;

        // Select the correct data source and validate the source data. Specified data may be a DataValidator, an array
        // or null. After selecting a data source, it will be a DataValidator object which we will then give to the
        // DataEntry::validate() method
        //
        // When in force mode we will NOT clear the failed columns so that they can be sent back to the user for
        // corrections
        $data_source = Validator::pick($source)->setDefinitionsObject($this->definitions);

        if ($this->debug) {
            Log::debug('APPLY ' . static::getEntryName() . ' (' . get_class($this) . ')', 10, echo_header: false);
            Log::debug('CURRENT DATA', 10         , echo_header: false);
            Log::vardump($this->source            , echo_header: false);
            Log::debug('UNVALIDATED NEW DATA'     , echo_header: false);
            Log::vardump($data_source->getSource(), echo_header: false);
        }

        // Get the source array from the validator into the DataEntry object
        if ($force) {
            // Force was used, but the object will now be in readonly mode, so we can save failed data
            // Validate data and copy data into the source array
            $data_source = $this->doNotValidate($data_source, $require_clean_source);
            $this->copyValuesToSource($data_source, true, true);

        } else {
            // Validate data and copy data into the source array
            $data_source = $this->validateSource($data_source, $require_clean_source, true);

            if ($this->debug) {
                Log::debug('VALIDATED DATA', echo_header: false);
                Log::vardump($data_source, echo_header: false);
            }

            // Ensure DataEntry Meta state is okay, then generate the diff data and copy data array to internal data
            $this->validateMetaState($data_source)
                 ->createDiff($data_source)
                 ->copyValuesToSource($data_source, true);
        }

        $this->is_applying = false;

        if ($this->debug) {
            Log::debug('SOURCE AFTER APPLYING', echo_header: false);
            Log::vardump($this->source, echo_header: false);
        }

        return $this;
    }


    /**
     * Extracts the data from the validator without validating
     *
     * @param ValidatorInterface $validator
     * @param bool               $require_clean_source
     *
     * @return array
     */
    protected function doNotValidate(ValidatorInterface $validator, bool $require_clean_source): array
    {
        $return = [];
        $source = $validator->getSource();
        $prefix = $this->getDefinitionsObject()->getPrefix();

        foreach ($source as $key => $value) {
            $return[Strings::from($key, $prefix)] = $value;

            if ($require_clean_source) {
                $validator->removeKeys($key);
            }
        }

        return $return;
    }


    /**
     * Validate all columns for this DataEntry
     *
     * @note This method will also fix column names in case column prefix was specified
     *
     * @param ValidatorInterface $validator
     * @param bool               $require_clean_source
     * @param bool               $external
     *
     * @return array
     */
    protected function validateSource(ValidatorInterface $validator, bool $require_clean_source, bool $external): array
    {
        if (!$this->validate) {
            // This data entry won't validate data, just continue.
            return $validator->getSource();
        }

        $prefix = $this->getDefinitionsObject()->getPrefix();

        // Set ID so that the array validator can do unique lookups, etc.
        // Tell the validator what table this DataEntry is using and get the column prefix so that the validator knows
        // what columns to select
        $validator->setDataEntryObject($this)
                  ->setDefinitionsObject($this->definitions)
                  ->setPrefix($prefix)
                  ->setMetaColumns($this->getMetaColumns())
                  ->setTable(static::getTable());

        // Go over each column and let the column definition do the validation since it knows the specs
        foreach ($this->definitions as $column => $definition) {
            if ($definition->isMeta()) {
                // This column is metadata and should not be validated. Only apply static values
                if ($definition->getValue()) {
                    $this->source[$column] = $definition->getValue();
                }

                continue;
            }

            if (!array_key_exists($column, $validator->getSource()) and $external) {
                // External data does not apply defaults, if the column doesn't exist, skip it
                continue;
            }

            if ($this->debug) {
                Log::debug('VALIDATING COLUMN "' . get_class($this) . ' > ' . $column . '" WITH VALUE "'  . $this->get($column). ' ['  . gettype($this->get($column)). ']"', echo_header: false);
            }

            try {
                // Execute the validations for this single definition
                $definition->validate($validator);

            } catch (ValidationFailedException $e) {
                throw $e;

            } catch (Throwable $e) {
                throw ValidatorException::new(tr('Encountered an exception while validating column ":column"', [
                    ':column' => $column,
                ]), $e);
            }
        }

        try {
            // Execute the validate method to get the results of the validation
            $source             = $validator->validate($require_clean_source);
            $this->is_validated = true;

        } catch (ValidationFailedException $e) {
            if ($this->debug) {
                Log::debug('FAILED VALIDATION OF "' . get_class($this) . '" DATA ENTRY DATA, SEE FOLLOWING LOG ENTRIES', 10, echo_header: false);
                Log::printr($e->getData());
            }

            // Add the DataEntry object type to the exception message
            throw $e->setMessage('(' . get_class($this) . ') ' . $e->getMessage());
        }

        // Fix column names if prefix was specified
        if ($prefix) {
            $return = [];

            foreach ($source as $key => $value) {
                $return[Strings::from($key, $prefix)] = $value;
            }

            return $return;
        }

        if ($this->debug) {
            Log::debug('DATA AFTER VALIDATION:', echo_header: false);
            Log::printr($source);
        }

        return $source;
    }


    /**
     * Returns only the specified key from the source of this DataEntry
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $exception
     *
     * @return mixed
     */
    public function get(Stringable|string|float|int $key, bool $exception = true): mixed
    {
        if (!$this->getDefinitionsObject()->keyExists($key)) {
            if ($exception) {
                throw new OutOfBoundsException(tr('Specified key ":key" is not defined for the ":class" class DataEntry object', [
                    ':class' => get_class($this),
                    ':key'   => $key,
                ]));
            }
        }

        return array_get_safe($this->source, $key);
    }


    /**
     * Generate diff data that will be stored and used by the meta system
     *
     * @param array|null $data
     *
     * @return static
     */
    protected function createDiff(?array $data): static
    {
        if (Meta::isEnabled()) {
            if ($data === null) {
                $diff = [
                    'from' => [],
                    'to'   => $this->source,
                ];

            } else {
                $diff = [
                    'from' => [],
                    'to'   => [],
                ];

                // Check all keys and register changes
                foreach ($this->definitions as $key => $definition) {
                    if ($definition->getReadonly() or $definition->getDisabled() or $definition->isMeta()) {
                        continue;
                    }

                    if (array_get_safe($this->source, $key) != array_get_safe($data, $key)) {
                        // If both records were empty (from NULL to 0 for example) then don't register
                        if (array_get_safe($this->source,$key) or array_get_safe($data,$key)) {
                            $diff['from'][$key] = (string) array_get_safe($this->source,$key);
                            $diff['to'][$key]   = (string) array_get_safe($data,$key);
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
     * Validates the meta_state of this DataEntry object
     *
     * The meta_state is a unique random code representing its current state. When a user loaded the object, it had a
     * specific state and that state should not have changed when the user saves the object, as that would indicate that
     * the object was changed in the interim.
     *
     * If this is the case, the object will perform one of these three actions, depending on state_mismatch_handling:
     *
     * 1 - Ignore the mismatch and continue (leaving a warning in the log)
     *
     * 2 - Throw a DataEntryStateMismatchException() exception, requiring the user to reload the data and try again
     *
     * 3 - Throw the DataEntryStateMismatchException() exception, but after reloading the meta_state. This allows the
     *     user to try and save a second time, which would then overwrite the previous changes.
     *
     * @param ValidatorInterface|array|null $source
     *
     * @return static
     */
    protected function validateMetaState(ValidatorInterface|array|null $source = null): static
    {
        // Check entry meta-state. If this entry is modified in the meantime, is it okay to update?
        if ($this->getMetaState()) {
            if (array_get_safe($source, 'meta_state')) {
                if (array_get_safe($source, 'meta_state') !== $this->getMetaState()) {
                    // State mismatch! This means that somebody else updated this record while we were modifying it.
                    switch ($this->state_mismatch_handling) {
                        case EnumStateMismatchHandling::ignore:
                            Log::warning(ts('Ignoring database and supplied meta-state mismatch for ":type" type record with ID ":id" and old state ":old" and new state ":new"', [
                                ':id'   => $this->getId(false) ?? ts('N/A'),
                                ':type' => static::getEntryName(),
                                ':old'  => $this->getMetaState(),
                                ':new'  => array_get_safe($source, 'meta_state'),
                            ]));
                            break;

                        case EnumStateMismatchHandling::allow_override:
                            // Okay, so the state did NOT match, and we WILL throw the state mismatch exception, BUT we WILL
                            // update the state data so that a second attempt can succeed
                            $source['meta_state'] = $this->getMetaState();
                            break;

                        case EnumStateMismatchHandling::restrict:
                            throw new DataEntryStateMismatchException(tr('Database and user meta-state for ":type" type record with ID ":id" do not match', [
                                ':id'   => $this->getLogId(),
                                ':type' => static::getEntryName(),
                            ]));
                    }
                }

            } else {
                // The external data has no meta-state specified!
                switch ($this->state_mismatch_handling) {
                    case EnumStateMismatchHandling::ignore:
                        Log::warning(ts('Skipping meta-state check for ":type" type record with ID ":id" and state ":old", supplied data source has no meta-state', [
                            ':id'   => $this->getId(false) ?? ts('N/A'),
                            ':type' => static::getEntryName(),
                            ':old'  => $this->getMetaState(),
                        ]));
                        break;

                    case EnumStateMismatchHandling::allow_override:
                        // Okay, so the state did NOT match, and we WILL throw the state mismatch exception, BUT we WILL
                        // update the state data so that a second attempt can succeed
                        $source['meta_state'] = $this->getMetaState();
                        break;

                    case EnumStateMismatchHandling::restrict:
                        throw new DataEntryStateMismatchException(tr('Supplied data contains no meta-state for ":type" type record with ID ":id"', [
                            ':id'   => $this->getLogId(),
                            ':type' => static::getEntryName(),
                        ]));
                }
            }
        }

        return $this;
    }


    /**
     * Returns the meta-state for this database entry
     *
     * @return ?string
     */
    public function getMetaState(): ?string
    {
        return $this->getTypesafe('string', 'meta_state');
    }


    /**
     * Returns if either $identifiers is NULL, or $identifiers contains an array with only NULL values
     *
     * @param IdentifierInterface|array|string|int|false|null $identifiers
     *
     * @return bool
     */
    protected static function identifiersAreNull(IdentifierInterface|array|string|int|false|null $identifiers): bool
    {
        if ($identifiers === null) {
            return true;
        }

        if (is_array($identifiers)) {
            foreach ($identifiers as $identifier) {
                if ($identifier === null) {
                    continue;
                }

                return false;
            }

            return true;
        }

        return false;
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
     * @param array  $identifiers
     * @param bool   $meta_enabled
     * @param bool   $ignore_deleted
     * @param bool   $exception
     * @param string $filter
     *
     * @return static|null
     */
    public function find(array $identifiers, bool $meta_enabled = false, bool $ignore_deleted = false, bool $exception = true, string $filter = 'AND'): ?static
    {
        if (!$identifiers) {
            // No identifiers specified, identifiers are required!
            throw OutOfBoundsException::new(tr('Cannot find ":class" objects, no identifiers specified', [
                ':class' => static::getClassName(),
            ]));
        }

        // Build the find query, and execute it
        // TODO Do this with the query builder, add functions for this in the query builder
        $where   = [];
        $execute = [];

        foreach ($identifiers as $column => $identifier) {
            $where[]                = '`' . $column . '` = :' . $column;
            $execute[':' . $column] = $identifier;
        }

        $builder = QueryBuilder::new()
                               ->setMetaEnabled($meta_enabled)
                               ->setConnectorObject($this->o_connector)
                               ->addFrom(static::getTable())
                               ->addSelect('`' . static::getTable() . '`.*')
                               ->addWhere(implode(' ' . $filter . ' ', $where), $execute);

        $entry = $builder->get();

        if (!$entry) {
            // This entry does not exist. Exception or return NULL?
            if ($exception) {
                throw DataEntryNotExistsException::new(tr('The ":class" with identifiers ":identifiers" does not exist', [
                    ':class'       => static::getClassName(),
                    ':identifiers' => $identifiers,
                ]));
            }

            return null;
        }

        // The requested entry DOES exist! Create a new DataEntry object!
        $entry = static::newFromSource($entry);

        // Is it deleted tho?
        if ($entry->isDeleted() and !$ignore_deleted) {
            // This entry has been deleted and can only be viewed by user with the "deleted" right
            if (!Session::getUserObject()->hasAllRights('deleted')) {
                throw DataEntryDeletedException::new(tr('The ":class" with identifiers ":identifiers" is deleted', [
                    ':class'       => static::getClassName(),
                    ':identifiers' => $identifiers,
                ]));
            }
        }

        return $entry;
    }


    /**
     * Attempts to find the entry with specified identifier in the database
     *
     * @param array|Stringable|string|int $identifier
     * @param int|null                    $not_id
     *
     * @return array|null
     *
     * @throws OutOfBoundsException
     */
    protected static function findExists(array|Stringable|string|int $identifier, ?int $not_id = null): ?array
    {
        if (!$identifier) {
            throw new OutOfBoundsException(tr('Cannot check if ":class" class DataEntry exists, no identifier specified', [
                ':class' => static::getClassName(),
            ]));
        }

        if (is_array($identifier)) {
            static::buildManualQuery($identifier, $where, $joins, $group, $order, $execute);

        } else {
            $column  = static::determineColumn($identifier);
            $where   = '`' . $column . '` = :identifier';
            $execute = [':identifier' => $identifier];
        }

        if ($not_id) {
            $execute[':id'] = $not_id;
        }

        return sql(static::getDefaultConnector())->getRow('SELECT `id`, `status`
                                                           FROM   `' . static::getTable() . '`
                                                           WHERE  ' . $where . '
                                           ' . ($not_id ? '  AND  `id` != :id' : '') . '
                                                           LIMIT  1', $execute);
    }


    /**
     * Returns true if an entry with the specified identifier exists
     *
     * @param array|Stringable|string|int $identifier The unique identifier, but typically not the database id, usually
     *                                                the seo_email, or seo_name. If specified as an array, it should
     *                                                contain an assoc array with
     *                                                column > value, column > value, column !value
     * @param int|null              $not_id
     * @param bool                  $throw_exception If the entry does not exist, instead of returning false will throw
     *                                               a DataEntryNotExistsException
     *
     * @return bool
     * @throws OutOfBoundsException | DataEntryNotExistsException | DataEntryDeletedException
     */
    public static function exists(array|Stringable|string|int $identifier, ?int $not_id = null, bool $throw_exception = false): bool
    {
        $exists = static::findExists($identifier, $not_id);

        if (!$exists) {
            // Entry does not exist!
            if ($throw_exception) {
                throw DataEntryNotExistsException::new(tr('The ":type" type data entry with identifier ":id" does not exist', [
                    ':type' => static::getClassName(),
                    ':id'   => $identifier,
                ]));
            }

            return false;
        }

        // Entry exists!
        if ($exists['status'] === 'deleted') {
            // But it is deleted...
            if ($throw_exception) {
                throw DataEntryDeletedException::new(tr('The ":type" type data entry with identifier ":id" exists but is deleted', [
                    ':type' => static::getClassName(),
                    ':id'   => $identifier,
                ]));
            }

            // This entry is deleted, act as if it does not exist
            return false;
        }

        // Entry exists and is not deleted, success!
        return true;
    }


    /**
     * Returns true if an entry with the specified identifier does not exist
     *
     * @param array|Stringable|string|int $identifier The unique identifier, but typically not the database id, usually
     *                                                the seo_email, or seo_name. If specified as an array, it should
     *                                                contain an assoc array with
     *                                                column > value, column > value, column !value
     * @param int|null              $id               If specified, will ignore the found entry if it has this ID as it
     *                                                will be THIS object
     * @param bool                  $throw_exception  If the entry exists (and does not match id, if specified), instead
     *                                                of returning false will throw a DataEntryNotExistsException
     *
     * @return bool
     *
     * @throws OutOfBoundsException|DataEntryAlreadyExistsException
     */
    public static function notExists(array|Stringable|string|int $identifier, ?int $id = null, bool $throw_exception = false): bool
    {
        $exists = static::findExists($identifier, $id);

        if ($exists) {
            // Entry exists
            if ($exists['status'] === 'deleted') {
                // But is deleted, so act as if it doesn't
                return true;
            }

            // Exists and is not deleted
            if ($throw_exception) {
                throw DataEntryAlreadyExistsException::new(tr('The ":type" type data entry with identifier ":id" already exists', [
                    ':type' => static::getClassName(),
                    ':id'   => $identifier,
                ]));
            }

            // The entry exists!
            return false;
        }

        // Entry does not exist
        return true;
    }


    /**
     * Returns a human-readable and pretty version of the specified status
     *
     * @param string|null $status
     *1
     *
     * @return string
     */
    public static function getHumanReadableStatus(?string $status): string
    {
        if ($status === null) {
            return tr('Ok');
        }

        $status = str_replace(['_', '-'], ' ', $status);

        return Strings::capitalize($status);
    }


    /**
     * Returns the name of this DataEntry object
     *
     * @return string
     */
    public function getObjectName(): string
    {
        $name = static::class;
        $name = Strings::fromReverse($name, '\\');
        $name = strtolower($name);

        return $name;
    }


    /**
     * Returns true if the specified column is a meta column
     *
     * @param string $column
     *
     * @return bool
     */
    public function isMetaColumn(string $column): bool
    {
        return in_array($column, $this->meta_columns);
    }


    /**
     * Returns if this DataEntry validates data before saving
     *
     * @return bool
     */
    public function getValidate(): bool
    {
        return $this->validate;
    }


    /**
     * Sets if this DataEntry validates data before saving
     *
     * @param bool $validate
     *
     * @return static
     */
    public function setValidate(bool $validate): static
    {
        $this->validate = $validate;

        return $this;
    }


    /**
     * Add the complete definitions and source from the specified data entry to this data entry
     *
     * @param DataEntryInterface $data_entry
     * @param bool               $strip_meta
     *
     * @return static
     * @todo Improve by first splitting meta data off the new data entry and then ALWAYS prepending it to ensure its at
     *       the front
     */
    public function appendDataEntry(DataEntryInterface $data_entry, bool $strip_meta = true): static
    {
        $data_entry   = clone $data_entry;
        $this->source = array_merge($this->source, ($strip_meta ? Arrays::removeKeys($data_entry->getSource(false, false), static::getDefaultMetaColumns()) : $data_entry->getSource(false, false)));

        $this->definitions
             ->appendSource($data_entry->getDefinitionsObject()
                                       ->removeKeys($strip_meta ? static::getDefaultMetaColumns() : null));

        return $this;
    }


    /**
     * Add the complete definitions and source from the specified data entry to this data entry
     *
     * @param DataEntryInterface $data_entry
     * @param bool               $strip_meta
     *
     * @return static
     * @todo Improve by first splitting meta data off the new data entry and then ALWAYS prepending it to ensure its at
     *       the front
     */
    public function prependDataEntry(DataEntryInterface $data_entry, bool $strip_meta = true): static
    {
        $data_entry   = clone $data_entry;
        $this->source = array_merge(($strip_meta ? Arrays::removeKeys($data_entry->getSource(false, false), static::getDefaultMetaColumns()) : $data_entry->getSource(false, false)), $this->source);

        $data_entry->getDefinitionsObject()->removeKeys($strip_meta ? static::getDefaultMetaColumns() : null)
                                           ->appendSource($this->definitions)
                                           ->setDataEntryObject($this);

        return $this;
    }


    /**
     * Add the complete definitions and source from the specified data entry to this data entry
     *
     * @param DataEntryInterface $data_entry
     * @param string             $at_key
     * @param bool               $strip_meta
     * @param bool               $after
     *
     * @return static
     * @todo Improve by first splitting meta data off the new data entry and then ALWAYS prepending it to ensure its at
     *       the front
     */
    public function injectDataEntry(string $at_key, DataEntryInterface $data_entry, bool $after = true, bool $strip_meta = true): static
    {
        $data_entry   = clone $data_entry;
        $this->source = array_merge($this->source, ($strip_meta ? Arrays::removeKeys($data_entry->getSource(false, false), static::getDefaultMetaColumns()) : $data_entry->getSource(false, false)));

        try {
            $this->getDefinitionsObject()->spliceByKey($at_key, 0, $data_entry->getDefinitionsObject()
                                                                   ->removeKeys($strip_meta ? static::getDefaultMetaColumns() : null), $after);
        } catch (OutOfBoundsException $e) {
            throw new OutOfBoundsException(tr('Failed to inject DataEntry object at key ":key", the key does not exist', [
                ':key' => $at_key,
            ]), $e);
        }

        return $this;
    }


    /**
     * Add the complete definitions and source from the specified data entry to this data entry
     *
     * @note When $definition is specified as an object, it will completely overwrite the existing object.
     *
     * @note When $definition is specified as an array, only the specified entries will overwrite the existing object's
     *       entries
     *
     * @note The entries' name CANNOT be changed here!
     *
     * @param string                                  $at_key
     * @param ElementInterface|ElementsBlockInterface $value
     * @param DefinitionInterface|array|null          $definition
     * @param bool                                    $after
     *
     * @return static
     * @todo Improve by first splitting meta data off the new data entry and then ALWAYS prepending it to ensure its at
     *       the front
     */
    public function injectElement(string $at_key, ElementInterface|ElementsBlockInterface $value, DefinitionInterface|array|null $definition = null, bool $after = true): static
    {
        // Render the specified element directly into the definition. Remove the specified column from this source (overwrite, basically)
        $element_definition                             = $value->getDefinitionObject()->setContent($value);
        $this->source[$element_definition->getColumn()] = null;

        try {
            $this->getDefinitionsObject()->spliceByKey($at_key, 0, [$element_definition->getColumn() => $element_definition], $after);

        } catch (OutOfBoundsException $e) {
            throw new OutOfBoundsException(tr('Failed to inject element at key ":key", the key does not exist', [
                ':key' => $at_key,
            ]), $e);
        }

        if ($definition) {
            // Apply specified definitions as well
            if ($definition instanceof DefinitionInterface) {
                $definition->setColumn($element_definition->getColumn());
                $this->getDefinitionsObject()->get($element_definition->getColumn())->setSource($definition->getSource());

            } else {
                // Merge the specified definitions over the existing one
                $definition = Arrays::removeKeys($definition, 'column');
                $rules      = $this->getDefinitionsObject()->get($element_definition->getColumn())->getSource();
                $rules      = array_merge($rules, $definition);

                $this->getDefinitionsObject()->get($element_definition->getColumn())->setSource($rules);
            }
        }

        return $this;
    }


    /**
     * Extracts a DataEntry with the specified columns (in the specified order)
     *
     * The extracted data entry will have the source and definitions
     *
     * The extracted data entry will have the same class and interface as this
     *
     * @param array|string  $columns
     * @param int $flags
     *
     * @return DataEntryInterface
     */
    public function extractDataEntryObject(array|string $columns, int $flags = Utils::MATCH_FULL | Utils::MATCH_REQUIRE): DataEntryInterface
    {
        // Clone this object, then filter the definitions object, then clean the source
        $entry = static::new($this);

        $entry->getDefinitionsObject()
              ->keepMatchingKeys($columns, $flags);

        $entry->cleanSourceFromDefinitions();

        return $entry;
    }


    /**
     * Will remove all source keys that are not in the definitions object
     *
     * @return static
     */
    public function cleanSourceFromDefinitions(): static
    {
        $definitions = $this->definitions;

        foreach ($this->source as $key => $value) {
            if (!$definitions->keyExists($key)) {
                unset($this->source[$key]);
            }
        }

        return $this;
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
     * Returns true if this object was read from configuration
     *
     * Objects loaded from configuration (for the moment) cannot be saved and will return true.
     *
     * @return bool
     */
    public function isReadonly(): bool
    {
        return $this->readonly or $this->sourceLoadedFromConfiguration();
    }


    /**
     * Returns true if this object was read from configuration
     *
     * Objects loaded from configuration (for the moment) cannot be saved and will return true.
     *
     * @return bool
     */
    public function sourceLoadedFromConfiguration(): bool
    {
        return $this->getId(false) < 0;
    }


    /**
     * Returns true if this entry is new or WAS new before it was written
     *
     * @return bool
     */
    public function isCreated(): bool
    {
        return $this->is_created;
    }


    /**
     * Returns the previous ID
     *
     * @return int|null
     */
    public function getPreviousId(): ?int
    {
        return $this->previous_id;
    }


    /**
     * Returns the lowest possible ID that will be auto generated
     *
     * @return int
     */
    public function getIdLowerLimit(): int
    {
        return $this->id_lower_limit;
    }


    /**
     * Sets the lowest possible ID that will be auto generated
     *
     * @param int $id_lower_limit
     *
     * @return static
     */
    public function setIdLowerLimit(int $id_lower_limit): static
    {
        $this->id_lower_limit = $id_lower_limit;

        return $this;
    }


    /**
     * Returns the highest possible ID that will be auto generated
     *
     * @return int
     */
    public function getIdUpperLimit(): int
    {
        return $this->id_upper_limit;
    }


    /**
     * Sets the highest possible ID that will be auto generated
     *
     * @param int $id_upper_limit
     *
     * @return static
     */
    public function setIdUpperLimit(int $id_upper_limit): static
    {
        $this->id_upper_limit = $id_upper_limit;

        return $this;
    }


    /**
     * Returns true if this DataEntry allows the creation of new entries
     *
     * @return bool
     */
    public function getAllowCreate(): bool
    {
        return $this->allow_create;
    }


    /**
     * Sets if this DataEntry allows the creation of new entries
     *
     * @param bool $allow_create
     *
     * @return static
     */
    public function setAllowCreate(bool $allow_create): static
    {
        $this->allow_create = $allow_create;

        return $this;
    }


    /**
     * Returns if this DataEntry allows modification of existing entries
     *
     * @return bool
     */
    public function getAllowModify(): bool
    {
        return $this->allow_modify;
    }


    /**
     * Sets if this DataEntry allows modification of existing entries
     *
     * @param bool $allow_modify
     *
     * @return static
     */
    public function setAllowModify(bool $allow_modify): static
    {
        $this->allow_modify = $allow_modify;

        return $this;
    }


    /**
     * Returns a translation table between CLI arguments and internal columns
     *
     * @return array
     */
    public function getCliColumns(): array
    {
        $return = [];

        foreach ($this->definitions as $column => $definitions) {
            if ($definitions->getCliColumn()) {
                $return[$column] = $definitions->getCliColumn();
            }
        }

        return $return;
    }


    /**
     * Get a result used for auto completion
     *
     * @return string
     */
    public function getAutoCompleteValue(): string
    {
        if (static::getUniqueColumn()) {
            return array_get_safe($this->source, static::getUniqueColumn());
        }

        return (string) $this->getId(false);
    }


    /**
     * Returns true if this object has the specified status
     *
     * @param string|null $status
     *
     * @return bool
     */
    public function hasStatus(?string $status): bool
    {
        return $status === $this->getTypesafe('string', 'status');
    }


    /**
     * Delete this entry
     *
     * @param string|null $comments
     * @param bool        $auto_save
     *
     * @return static
     */
    public function delete(?string $comments = null, bool $auto_save = true): static
    {
        if ($this->hasMetaColumn('status')) {
            if ($this->isModified()) {
                throw new OutOfBoundsException(tr('Cannot delete DataEntry ":class" with identifier ":identifier" because it has modifications that have not yet been saved', [
                    ':class'      => static::class,
                    ':identifier' => $this->getUniqueColumnValue(),
                ]));
            }

            if (static::getUniqueColumn()) {
                // When deleting an entry, the unique column goes to NULL
                $this->source[static::getUniqueColumn()] = null;
                $this->save(true, true);
            }

            return $this->setStatus('deleted', $comments, $auto_save);
        }

        // This DataEntry class does NOT support status entries, erase instead
        return $this->erase();
    }


    /**
     * Set the status for this database entry
     *
     * @param string|null $status
     * @param string|null $comments
     * @param bool        $auto_save
     *
     * @return static
     */
    public function setStatus(?string $status, ?string $comments = null, bool $auto_save = true): static
    {
        if (!$this->is_loading) {
            if ($this->hasMetaColumn('status')) {
                if ($status and (strlen($status) > 32)) {
                    throw new OutOfBoundsException(tr('Cannot set DataEntry ":class" status to ":status", status length cannot be more than 32 characters', [
                        ':status' => $status,
                        ':class'  => static::class,
                    ]));
                }

                $this->checkReadonly('set-status "' . $status . '"')
                     ->saveToCache()
                     ->source['status'] = $status;

                $this->setTableState();

                if ($auto_save and $this->isNotNew()) {
                    SqlDataEntry::new(sql($this->o_connector), $this)
                                ->setDebug($this->debug)
                                ->setStatus($status, $comments);
                }
            }
        }

        return $this->set($status, 'status');
    }


    /**
     * Undelete the specified entries
     *
     * @param string|null $comments
     * @param bool        $auto_save
     *
     * @return static
     */
    public function undelete(?string $comments = null, bool $auto_save = true): static
    {
        return $this->setStatus(null, $comments, $auto_save);
    }


    /**
     * Erase this DataEntry from the database
     *
     * @return static
     */
    public function erase(): static
    {
        if ($this->isNew()) {
            throw new DataEntryIsNewException(tr('Cannot erase ":class" DataEntry object, it is new and not stored in the database yet', [
                ':class' => static::class,
            ]));
        }

        if ($this->meta_enabled) {
            if (!$this->hasMetaColumn('meta_id')) {
                throw new DataEntryMetaException(tr('Cannot erase ":class" DataEntry object, it has meta enabled, but no meta_id column', [
                    ':class' => static::class,
                ]));
            }

            $this->checkReadonly('erase')->getMetaObject()
                                         ->erase();
        }

        sql($this->o_connector)->erase(static::getTable(), ['id' => $this->getId()]);

        return $this->removeFromCache();
    }


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
    public function getMetaObject(bool $load = true): ?MetaInterface
    {
        if ($this->isNew()) {
            // New DataEntry objects have no meta-information
            return null;
        }

        $meta_id = $this->getTypesafe('int', 'meta_id');

        if ($meta_id === null) {
            throw new DataEntryException(tr('DataEntry ":id" does not have meta_id information', [
                ':id' => $this->getLogId(),
            ]));
        }

        return new Meta($meta_id, $load);
    }


    /**
     * Adds the specified action to the meta history for this DataEntry object
     *
     * @param string|null                  $action
     * @param string|null                  $comments
     * @param Stringable|array|string|null $data
     *
     * @return static
     */
    public function addMetaAction(?string $action, ?string $comments = null, Stringable|array|string|null $data = null): static
    {
        $this->getMetaObject()->action($action, $comments, $data);

        return $this;
    }


    /**
     * Returns the prefix to use for all DataEntry key names
     *
     * @return string|null
     */
    public function getPrefix(): ?string
    {
        return $this->getDefinitionsObject()->getPrefix();
    }


    /**
     * Sets the prefix to use for all DataEntry key names
     *
     * @param string|null $prefix
     *
     * @return static
     */
    public function setPrefix(?string $prefix): static
    {
        $this->getDefinitionsObject()->setPrefix($prefix);
        return $this;
    }


    /**
     * Returns the users_id that created this data entry
     *
     * @note Returns NULL if this class has no support for created_by information or has not been written to disk yet
     *
     * @return int|null
     */
    public function getCreatedBy(): ?int
    {
        return $this->getTypesafe('int', 'created_by');
    }


    /**
     * Returns the user object that created this data entry
     *
     * @note Returns NULL if this class has no support for created_by information or has not been written to disk yet
     *
     * @return UserInterface|null
     */
    public function getCreatedByObject(): ?UserInterface
    {
        $created_by = $this->getTypesafe('int', 'created_by');

        if ($created_by === null) {
            return null;
        }

        return new User($created_by);
    }


    /**
     * Sets the created_by field for this DataEntry object
     *
     * @param int|null $created_by
     *
     * @return static
     */
    protected function setCreatedBy(?int $created_by): static
    {
        return $this->set($created_by, 'created_by');
    }


    /**
     * Returns the created on value in integer format
     *
     * @note Returns NULL if this class has no support for created_on information or has not been written to disk yet
     *
     * @return string|int|null
     */
    public function getCreatedOn(): string|int|null
    {
        return $this->getTypesafe('string|int', 'created_on');
    }


    /**
     * Returns the object that created this data entry
     *
     * @note Returns NULL if this class has no support for created_by information or has not been written to disk yet
     * @return PhoDateTimeInterface|null
     */
    public function getCreatedOnObject(): ?PhoDateTimeInterface
    {
        $created_on = $this->getTypesafe('string', 'created_on');

        if ($created_on === null) {
            return null;
        }

        return new PhoDateTime($created_on);
    }


    /**
     * Sets the created_on field for this DataEntry object
     *
     * @param string|int|null $created_on
     *
     * @return static
     */
    protected function setCreatedOn(string|int|null $created_on): static
    {
        if (is_int($created_on)) {
            $created_on = PhoDateTime::new($created_on)->format('mysql');
        }

        return $this->set($created_on, 'created_by');
    }


    /**
     * Returns the meta-information for this entry
     *
     * @note Returns NULL if this class has no support for meta-information available, or hasn't been written to disk
     *       yet
     *
     * @return array
     */
    public function getMetaData(): array
    {
        $meta = Arrays::keepKeys($this->source, $this->meta_columns);
        $meta = Arrays::ensureReturn($meta, $this->meta_columns);

        return $meta;
    }


    /**
     * Add the specified action to the meta history
     *
     * @param string            $action
     * @param string            $comments
     * @param array|string|null $diff
     *
     * @return static
     */
    public function addToMetaHistory(string $action, string $comments, array|string|null $diff): static
    {
        if ($this->isNew()) {
            throw new OutOfBoundsException(tr('Cannot add meta-information, this ":class" object is still new', [
                ':class' => $this->getEntryName(),
            ]));
        }

        $this->getMetaObject()->action($action, $comments, get_null(Strings::force($diff)));

        return $this;
    }


    /**
     * Returns the meta id for this entry
     *
     * @return int|null
     */
    public function getMetaId(): ?int
    {
        return $this->getTypesafe('int', 'meta_id');
    }


    /**
     * Sets the meta_id field for this DataEntry object
     *
     * @param int|null $id
     *
     * @return static
     */
    protected function setMetaId(?int $id): static
    {
        return $this->set($id, 'meta_id');
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
     * Forcibly modify the data for this object with the new specified data, putting the object in readonly mode
     *
     * @note In readonly mode this object will no longer be able to write its data!
     *
     * @param bool                          $require_clean_source
     * @param ValidatorInterface|array|null $source
     *
     * @return static
     */
    public function forceApply(bool $require_clean_source = true, ValidatorInterface|array|null &$source = null): static
    {
        return $this->doApply($require_clean_source, $source, true);
    }


    /**
     * Returns an array with the columns that have changed
     *
     * @return array
     */
    public function getChanges(): array
    {
        return $this->changes;
    }


    /**
     * Sets the value for the specified data key
     *
     * @param string $column
     * @param mixed  $value
     *
     * @return static
     */
    public function addSourceValue(string $column, mixed $value): static
    {
        if (!array_key_exists($column, $this->source)) {
            $this->source[$column] = [];
        }

        if (!is_array($this->source[$column])) {
            throw new OutOfBoundsException(tr('Cannot *add* data value to key ":key", the value datatype is not "array"', [
                ':key' => $column,
            ]));
        }

        $this->source[$column][] = $value;

        return $this;
    }


    /**
     * Will save the data from this data entry to the database
     *
     * @param bool        $force
     * @param bool        $skip_validation
     * @param string|null $comments
     *
     * @return static
     */
    public function save(bool $force = false, bool $skip_validation = false, ?string $comments = null): static
    {
        if ($this->saveBecauseModified($force)) {
            // Object must ALWAYS be validated before writing! Validate data and write it to the database.
            $this->validate($skip_validation)->write($comments)->saveToCache()->setTableState();
        }

        return $this;
    }


    /**
     * Returns true if this DataEntry should be saved, false if it should not be saved
     *
     * @param bool $force
     *
     * @return bool
     */
    protected function saveBecauseModified(bool $force): bool
    {
        if (($this->isNew() or $this->is_modified or $force) and !$this->readonly) {
            // About to save, but first check if Phoundation is not in readonly mode
            $this->checkReadonly('save');
            return true;
        }

        // Not new, nothing changed, no forcing, so there is no reason to save
        if ($this->debug) {
            Log::debug('NOT SAVING IN DB, NOTHING CHANGED FOR "' . get_class($this) . '" ID "' . $this->getLogId() . '"', 10, echo_header: false);
        }

        return false;
    }


    /**
     * Writes the data to the database
     *
     * @param string|null $comments
     *
     * @return static
     */
    protected function write(?string $comments = null): static
    {
        if ($this->readonly or $this->disabled) {
            throw new DataEntryReadonlyException(tr('Cannot save this ":name" object, the object is readonly or disabled', [
                ':name' => static::getEntryName(),
            ]));
        }

        // Debug this specific entry?
        if ($this->debug) {
            Log::debug('SAVING DATA ENTRY "' . get_class($this) . '" WITH ID "' . $this->getLogId() . '"', 10, echo_header: false);
            sql($this->o_connector)->setDebug($this->debug);
        }

        // Ensure all linked NULL columns are resolved. This means that -for example- object_code and object_id, which
        // both are different pieces of data pointing to the same object, both have values. If one is NULL due to
        // $this->setVirtualData() resetting it, it will be resolved here. DataEntry->getSource() will resolve these
        // links automatically so just copy getSource() over the internal source.
        $this->source = $this->getSource(false, false);

        // Write the data and store the returned ID column
        $this->source = array_replace($this->source, SqlDataEntry::new(sql($this->o_connector), $this)
                                                                 ->setDebug($this->debug)
                                                                 ->write($comments));

        if ($this->debug) {
            Log::information('SAVED DATA ENTRY "' . get_class($this) . '" WITH ID "' . $this->getLogId() . '"', 10);
        }

        // Write the list, if exists
        $this->list?->save();

        // Done!
        $this->is_modified =  false;
        $this->is_saved    =  true;
        $this->is_created  = ($this->previous_id === null);
        $this->previous_id =  $this->getId();

        return $this;
    }


    /**
     * Returns true if this DataEntry object has been fully initialized with its definitions available
     *
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->is_initialized;
    }


    /**
     * Returns an array containing all the DataEntry state variables
     *
     * @return array
     */
    public function getObjectState(): array
    {
        return [
            'id'                     => $this->getId(false),
            'is_saved'               => $this->is_saved,
            'is_new'                 => $this->isNew(),
            'is_created'             => $this->is_created,
            'is_modified'            => $this->is_modified,
            'is_validated'           => $this->is_validated,
            'is_loading'             => $this->is_loading,
            'is_loaded_from_cache'   => $this->is_loaded_from_cache,
            'is_initializing_source' => $this->is_initializing_source,
            'is_initialized'         => $this->is_initialized,
            'previous_id'            => $this->previous_id,
            'id_lower_limit'         => $this->id_lower_limit,
            'id_upper_limit'         => $this->id_upper_limit,
        ];
    }


    /**
     * Only return columns that actually contain data
     *
     * @param bool $insert
     *
     * @return array
     */
    public function getSqlColumns(bool $insert): array
    {
        $return = [];

        // Run over all definitions and generate a data column
        foreach ($this->definitions as $column => $definition) {
            if ($insert) {
                // We're about to insert
                if (in_array($column, $this->columns_filter_on_insert)) {
                    if ($definition->isMeta()) {
                        continue;
                    }
                }
            }

            $column = $definition->getColumn();

            if ($definition->getVirtual()) {
                // This is a virtual column, ignore it.
                continue;
            }

            // Apply definition default
            $return[$column] = array_get_safe($this->source, $column) ?? $definition->getDefault();

            // Ensure value is string, float, int, or NULL
            if (($return[$column] !== null) and !is_scalar($return[$column])) {
                if (is_enum($return[$column])) {
                    $return[$column] = $return[$column]->value;

                } elseif ($return[$column] instanceof Stringable) {
                    $return[$column] = (string) $return[$column];

                } else {
                    $return[$column] = Json::ensureEncoded($return[$column]);
                }
            }

            // Apply definition prefix and postfix only if they are not empty
            $prefix = $definition->getPrefix();
            $suffix = $definition->getSuffix();

            if ($prefix) {
                $return[$column] = $prefix . $return[$column];
            }

            if ($suffix) {
                $return[$column] .= $suffix;
            }
        }

        if ($this->debug) {
            Log::debug('DATA SENT TO SQL FOR "' . get_class($this) . '"', 10, echo_header: false);
            Log::vardump($return, echo_header: false);
        }

        return $return;
    }


    /**
     * Will validate the source data of this DataEntry object if it has not yet been validated
     *
     * @param bool $skip_validation
     *
     * @return static
     */
    public function validate(bool $skip_validation = false): static
    {
        if (!$this->is_validated) {
            if ($skip_validation) {
                // Act as if the entry is fully validated
                $this->is_validated = true;

            } else {
                // The data in this object hasn't been validated yet! Do so now...
                if ($this->debug) {
                    Log::debug('VALIDATING "' . get_class($this) . '" DATA ENTRY WITH ID "' . $this->getLogId() . '"', echo_header: false);
                }

                // Gather data that required validation
                $source = $this->getDataForValidation();

                // Merge the validated data over the current data
                // WARNING! DO NOT EXECUTE validateSourceData DIRECTLY IN THE ARRAY_MERGE! $this->validateSourceData()
                // updates $this->source and the array_merge() call will use the initial version (so without the
                // modifications from $this->validateSourceData())
                $source       = $this->validateSource(ArrayValidator::new($source), true, false);
                $this->source = array_merge($this->source, $source);
            }
        }

        return $this;
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
        return Arrays::removeKeys($this->source, $this->meta_columns);
    }


    /**
     * Creates and returns an HTML for the data in this entry
     *
     * @return DataEntryFormInterface
     * @todo Move this to the EntryCore class
     */
    public function getHtmlDataEntryFormObject(): DataEntryFormInterface
    {
        return DataEntryForm::new()
                            ->setDataEntryObject($this)
                            ->setSource($this->source)
                            ->setReadonly($this->readonly)
                            ->setDisabled($this->disabled)
                            ->setDefinitionsObject($this->getDefinitionsObject());
    }


    /**
     * Returns a SpreadSheet object with this object's source data in it
     *
     * @return SpreadSheetInterface
     * @todo Move this to the EntryCore class
     */
    public function getSpreadSheet(): SpreadSheetInterface
    {
        return new SpreadSheet($this);
    }


    /**
     * Set the meta-state for this database entry
     *
     * @param string|null $state
     *
     * @return static
     */
    protected function setMetaState(?string $state): static
    {
        return $this->set($state, 'meta_state');
    }


    /**
     * Adds a list of extra keys that are protected and cannot be removed from this object
     *
     * @param array $keys
     *
     * @return static
     */
    protected function addProtectedKeys(array $keys): static
    {
        foreach ($keys as $key) {
            $this->addProtectedColumn($key);
        }

        return $this;
    }


    /**
     * Returns either the specified column, or if $translate has content, the alternate column name
     *
     * @param string $column
     *
     * @return string
     */
    protected function getAlternateValidationColumn(string $column): string
    {
        if (!$this->getDefinitionsObject()->keyExists($column)) {
            throw new OutOfBoundsException(tr('Specified column name ":column" does not exist', [
                ':column' => $column,
            ]));
        }

        $alt = $this->getDefinitionsObject()->get($column)->getCliColumn();
        $alt = Strings::until($alt, ' ');
        $alt = trim($alt);

        return get_null($alt) ?? $column;
    }


    /**
     * Returns true if this DataEntry was loaded from configuration
     *
     * @return bool
     */
    public function isLoadedFromConfiguration(): bool
    {
        return $this->hasStatus('configuration');
    }


    /**
     * Called when the DataEntry object is ready with initializing or loading and sets all flags correctly
     *
     * @param bool $is_loaded
     *
     * @return static
     */
    protected function ready(bool $is_loaded = false): static
    {
        $this->is_initializing_source = false;
        $this->is_modified            = false;
        $this->is_loading             = false;
        $this->is_saved               = false;
        $this->is_loaded              = $is_loaded;

        return $this;
    }


    /**
     * Returns true if this DataEntry object was loaded from cache
     *
     * @return bool
     */
    public function isLoadedFromCache(): bool
    {
        return $this->is_loaded_from_cache;
    }
}
