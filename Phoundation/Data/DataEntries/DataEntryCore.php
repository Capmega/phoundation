<?php

/**
 * Class DataEntryCore
 *
 * This class implements the DataEntry class
 *
 * @see \Phoundation\Data\Entry for detailed documentation on how to use DataEntry objects
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
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Accounts\Users\User;
use Phoundation\Cache\Cache;
use Phoundation\Cache\LocalCache;
use Phoundation\Cli\CliColor;
use Phoundation\Content\Documents\Interfaces\SpreadSheetInterface;
use Phoundation\Content\Documents\SpreadSheet;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Meta\Interfaces\MetaInterface;
use Phoundation\Core\Meta\Meta;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Definitions;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\Enums\EnumStateMismatchHandling;
use Phoundation\Data\DataEntries\Exception\DataEntryExistsException;
use Phoundation\Data\DataEntries\Exception\DataEntryColumnDefinitionInvalidException;
use Phoundation\Data\DataEntries\Exception\DataEntryColumnsNotDefinedException;
use Phoundation\Data\DataEntries\Exception\DataEntryDeletedException;
use Phoundation\Data\DataEntries\Exception\DataEntryException;
use Phoundation\Data\DataEntries\Exception\DataEntryInvalidCacheException;
use Phoundation\Data\DataEntries\Exception\DataEntryInvalidIdentifierException;
use Phoundation\Data\DataEntries\Exception\DataEntryIsNewException;
use Phoundation\Data\DataEntries\Exception\DataEntryMetaException;
use Phoundation\Data\DataEntries\Exception\DataEntryNoIdentifierSpecifiedException;
use Phoundation\Data\DataEntries\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntries\Exception\DataEntryNotInitializedException;
use Phoundation\Data\DataEntries\Exception\DataEntryNotSavedException;
use Phoundation\Data\DataEntries\Exception\DataEntryReadonlyException;
use Phoundation\Data\DataEntries\Exception\DataEntryStateMismatchException;
use Phoundation\Data\DataEntries\Exception\DataEntryTypeException;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataDefinitions;
use Phoundation\Data\EntryCore;
use Phoundation\Data\Enums\EnumLoadParameters;
use Phoundation\Data\Enums\EnumSoftHard;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataCacheKey;
use Phoundation\Data\Traits\TraitDataClassException;
use Phoundation\Data\Traits\TraitDataColumns;
use Phoundation\Data\Traits\TraitDataConnector;
use Phoundation\Data\Traits\TraitDataDebug;
use Phoundation\Data\Traits\TraitDataDisabled;
use Phoundation\Data\Traits\TraitDataEventHandler;
use Phoundation\Data\Traits\TraitDataIdentifier;
use Phoundation\Data\Traits\TraitDataIgnoreDeleted;
use Phoundation\Data\Traits\TraitDataInsertUpdate;
use Phoundation\Data\Traits\TraitDataMaxIdRetries;
use Phoundation\Data\Traits\TraitDataMetaColumns;
use Phoundation\Data\Traits\TraitDataMetaEnabled;
use Phoundation\Data\Traits\TraitDataPermitValidationFailures;
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
use Phoundation\Databases\Sql\Exception\SqlContstraintDuplicateEntryException;
use Phoundation\Databases\Sql\Exception\SqlTableDoesNotExistException;
use Phoundation\Databases\Sql\Exception\SqlUnknownDatabaseException;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Databases\Sql\SqlDataEntry;
use Phoundation\Databases\Sql\SqlQueries;
use Phoundation\Date\Enums\EnumDateFormat;
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
    use TraitDataClassException {
        setException as protected __setException;
    }
    use TraitDataConnector;
    use TraitDataDebug;
    use TraitDataDisabled;
    use TraitDataDefinitions;
    use TraitDataEventHandler;
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
    use TraitDataColumns;
    use TraitDataPermitValidationFailures;


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
     * Global "loaded" flag, if set it means that data in the source has been loaded from database or configuration
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
     * @var QueryBuilderInterface|null
     */
    protected ?QueryBuilderInterface $query_builder = null;

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
     * Tracks the handling of NULL identifiers
     *
     * @var EnumLoadParameters $on_null_identifier
     */
    protected EnumLoadParameters $on_null_identifier = EnumLoadParameters::exception;

    /**
     * Tracks the handling of identifier not found
     *
     * @var EnumLoadParameters $on_not_exists
     */
    protected EnumLoadParameters $on_not_exists = EnumLoadParameters::exception;

    /**
     * Tracks columns that aren't defined but permitted anyway
     *
     * @var array|null $permitted_columns
     */
    protected ?array $permitted_columns = null;

    /**
     * Tracks whether this object will allow columns that aren't permitted
     *
     * @var bool $allow_unpermitted_columns
     */
    protected bool $allow_unpermitted_columns = true;

    /**
     * Tracks the various flags with meta information about this DataEntry object
     *
     * @var array|false[]
     */
    protected array $flags = [
        'is_loaded_from_cache'        => false,
        'is_loaded_from_local_cache'  => false,
        'is_loaded_from_global_cache' => false,
    ];


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
     * @param EnumLoadParameters|null                         $on_null_identifier
     * @param EnumLoadParameters|null                         $on_not_exists
     */
    public function __construct(IdentifierInterface|array|string|int|false|null $identifier = false, ?EnumLoadParameters $on_null_identifier = null, ?EnumLoadParameters $on_not_exists = null)
    {
        $this->use_cache = Cache::isEnabled();

        if ($identifier === false) {
            // If the identifier is false, don't automatically initialize the DataEntry object
            $this->ready();
            return;
        }

        // Initialize the DataEntry object
        $this->setOnLoadNullIdentifier($on_null_identifier)
             ->setOnLoadNotExists($on_not_exists);

        if ($identifier) {
            // An identifier was specified, load data immediately using DataEntry::load() (Data MUST exist!)
            $o_data_entry = $this->load($identifier, $on_null_identifier, $on_not_exists);

            if ($o_data_entry !== $this) {
                // DataEntry::load() returned a cached DataEntry object instead of $this, so copy the contents
                $this->setSourceDirect($o_data_entry->getSource())
                     ->setObjectState($o_data_entry->getObjectState());
            }

        } elseif ($identifier === null) {
            // Pre-initialize the DataEntry object
            $this->initialize(false)
                 ->copyMetaDataToSource()
                 ->copyValuesToSource([], false)
                 ->ready();
        }
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
                // Can't destroy a modified DataEntry object without either resetting it or saving it
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
     * Initializes this DataEntry object with a unique code or ID column.
     *
     * Checks the source for a value in the unique identifier column. If that is not found, checks for a value in the
     * ID column. If neither of these are found, throws an exception.
     *
     * @param array $source
     *
     * @return static
     */
    protected function initializeFromSource(array $source): static
    {
        $unique_column = static::getUniqueColumn();

        if ($unique_column) {
            // This DataEntry object has a unique column defined. Does the source have it too for initialization?
            $unique_column_value = array_get_safe($source, $unique_column);

            if ($unique_column_value) {
                return $this->initialize($unique_column_value);
            }
        }

        // Either this DataEntry has no unique column, or the source doesn't have the specified unique column.
        // Try the ID column instead
        $id_column       = static::getIdColumn();
        $id_column_value = array_get_safe($source, $id_column);

        if ($id_column_value) {
            // This source has a correct ID column, initialize with that.
            return $this->initialize($id_column_value);
        }

        return $this->initialize(false);
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
            Log::dump('INITIALIZING CLASS "' . Strings::fromReverse(static::class, '\\') . '" WITH IDENTIFIER "' . Strings::log($identifier) . '"', 10, echo_header: false);
        }

        // Set the identifier
        $this->setIdentifier($identifier)
             ->ensureMetaColumns()
             ->is_initializing_source = true;

        // Set up the definitions for this object and initialize meta-data
        $this->setMetaDefinitions()
             ->setDefinitionsObject($this->o_definitions)
             ->columns_filter_on_insert = [static::getIdColumn()];

        $this->is_initialized = true;

        return $this;
    }


    /**
     * Ensures that the meta columns for this DataEntry object have been defined
     *
     * @return static
     */
    protected function ensureMetaColumns(): static
    {
        // Set meta_columns for this class
        if (empty($this->meta_columns)) {
            $this->meta_columns = static::getDefaultMetaColumns();
        }

        return $this;
    }


    /**
     * Returns the handling of NULL identifiers
     *
     * @return EnumLoadParameters|null
     */
    public function getOnLoadNullIdentifier(): ?EnumLoadParameters
    {
        return $this->on_null_identifier;
    }


    /**
     * Sets the handling of NULL identifiers
     *
     * @param EnumLoadParameters|null $on_null_identifier
     *
     * @return static
     */
    public function setOnLoadNullIdentifier(?EnumLoadParameters $on_null_identifier): static
    {
        if ($on_null_identifier) {
            $this->on_null_identifier = $on_null_identifier;
        }

        return $this;
    }


    /**
     * Returns the handling of identifier not found
     *
     * @return EnumLoadParameters|null
     */
    public function getOnLoadNotExists(): ?EnumLoadParameters
    {
        return $this->on_not_exists;
    }


    /**
     * Sets the handling of identifier not found
     *
     * @param EnumLoadParameters|null $on_not_exists
     *
     * @return static
     */
    public function setOnLoadNotExists(?EnumLoadParameters $on_not_exists): static
    {
        if ($on_not_exists) {
            $this->on_not_exists = $on_not_exists;
        }

        return $this;
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
     * @param string $action
     *
     * @return static
     * @throws DataEntryNoIdentifierSpecifiedException
     */
    protected function loadIdentifier(string $action): static
    {
        $this->loadFromDatabase();

        if ($this->isNew()) {
            // Source is still empty, so nothing was loaded from database (or, SQL table doesn't exist, also possible!)
            // Try to load it from configuration if this DataEntry supports that
            $this->tryLoadFromConfiguration();
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
        if (
            $this->ignore_deleted or Session::getUserObject()
                                            ->hasAllRights('access-deleted')
        ) {
            Log::warning(ts('Continuing load of dataEntry object ":class" with identifier ":identifier" and log id ":log_id" with status "deleted"', [
                ':class'      => static::class,
                ':identifier' => $this->identifier,
                ':log_id'     => $this->getLogId()
            ]),          3);
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
            'modified_on',
            'modified_by',
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
        $o_definitions = Definitions::new($this)
                                  ->setTable(static::getTable());

        foreach ($this->meta_columns as $meta_column) {
            switch ($meta_column) {
                case 'id':
                    $o_definitions->add(Definition::new('id')
                                                ->setDisabled(true)
                                                ->setInputType(EnumInputType::dbid)
                                                ->addClasses('text-center')
                                                ->setSize(4)
                                                ->setCliAutoComplete(true)
                                                ->setTooltip(tr('This column contains the unique identifier for this object inside the database. It cannot be changed and is used to identify objects'))
                                                ->setLabel(tr('Database ID')));
                    break;

                case 'created_on':
                    $o_definitions->add(DefinitionFactory::newCreatedOn());
                    break;

                case 'created_by':
                    $o_definitions->add(DefinitionFactory::newCreatedBy());
                    break;

                case 'modified_on':
                    $o_definitions->add(DefinitionFactory::newModifiedOn());
                    break;

                case 'modified_by':
                    $o_definitions->add(DefinitionFactory::newModifiedBy());
                    break;

                case 'meta_id':
                    $o_definitions->add(DefinitionFactory::newMetaId());
                    break;

                case 'status':
                    $o_definitions->add(DefinitionFactory::newStatus()
                                                       ->setNullDisplay(tr('Ok')));
                    break;

                case 'meta_state':
                    $o_definitions->add(DefinitionFactory::newMetaState());
                    break;

                default:
                    throw new OutOfBoundsException(tr('Unknown meta definition column ":column" specified', [
                        ':column' => $meta_column,
                    ]));
            }
        }

        $this->o_definitions = $o_definitions->add(DefinitionFactory::newDivider('meta-divider')
                                                                    ->addPreRenderFunctions(function(DefinitionInterface $o_definition, array $source, mixed $value) {
                                                                        // Only render this when displaying meta-elements
                                                                        $o_definition->setRender(!$this->isNew() and
                                                                                                  $this->getDefinitionsObject()->getRenderMeta() and
                                                                                                  $o_definition->getRender());
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
     * @param bool        $exception
     * @param string|null $suffix
     *
     * @return string|int|null
     */
    public function getId(bool $exception = true, ?string $suffix = null): string|int|null
    {
        $id = $this->getTypesafe('int', static::getIdColumn());

        if (empty($id)) {
            if ($exception) {
                throw new DataEntryNotSavedException(tr('Cannot return ID for ":class" class object, it has no database id so has not been saved in the database', [
                    ':class' => static::getClassName(),
                ]));
            }
        }

        return Strings::getWithSuffix($id, $suffix);
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
        return ($this->getId(false) ?? ts('N/A')) . ' / ' . (static::getUniqueColumn() ? $this->getTypesafe('string|int', static::getUniqueColumn()) : '-');
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
                ':class' => static::class,
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
            Log::dump('TRY SET "' . Strings::fromReverse(static::class, '\\') . '::$' . $key . ' TO "' . Strings::log($value) . ' [' . gettype($value) . ']"', 10, echo_header: false);
        }

        // Make sure that definitions are available or give a clear error on what is going on
        if (empty($this->o_definitions)) {
            if ($this->is_initialized) {
                throw new DataEntryException(tr('The ":class" class has been initialized but has no definitions object', [
                    ':class' => static::class,
                ]));
            }

            $this->initialize(false);
        }

        // Only save values that are defined for this object
        if (!$this->getDefinitionsObject()->keyExists($key)) {
            // The column is not defined. Is it permitted, perhaps?
            if (!$this->columnIsPermitted($key)) {
                // Not permitted either. Do we have definitions at all?
                if ($this->getDefinitionsObject()->isEmpty()) {
                    throw new DataEntryException(tr('The ":class" class has no columns defined yet', [
                        ':class' => static::class,
                    ]));
                }

                // Yeah, this column is not allowed
                throw DataEntryColumnsNotDefinedException::new(tr('Not setting column ":column", it is not defined for the ":class" class', [
                    ':column' => $key,
                    ':class'  => static::class,
                ]))->setData([
                    'column' => $key
                ]);
            }

            // Column is permitted but has no Definition available!
            $o_definition = null;

        } else {
            // If the key is defined as readonly or disabled, it cannot be updated unless it's a new object or a
            // static value.
            $o_definition = $this->getDefinitionsObject()->get($key);

            // If a column is ignored, we won't update anything
            if ($o_definition->getIgnored()) {
                Log::warning(ts('Not updating DataEntry object ":object" column ":column" because it has the "ignored" flag set', [
                    ':column' => $key,
                    ':object' => static::class,
                ]), 6);

                return $this;
            }

            if (is_empty($value)) {
                // Apply default values
                if ($this->isNew()) {
                    $value = $o_definition->getInitialDefault() ?? $o_definition->getDefault();

                } else {
                    $value = $o_definition->getDefault();
                }

                if ($value === null) {
                    if ($skip_null_values) {
                        if ($this->debug) {
                            Log::dump('NOT SETTING "' . Strings::fromReverse(static::class, '\\') . '::$' . $key . ', SKIPPING NULL VALUE', 10, echo_header: false);
                        }

                        return $this;
                    }
                }

                if ($this->debug) {
                    Log::dump('USE DEFAULT VALUE "' . Strings::log($value) . '" FOR FIELD "' . Strings::fromReverse(static::class, '\\') . '::$' . $key . '"', 10, echo_header: false);
                }
            }
        }

        // Try to modify the value for the column
        if (array_get_safe($this->source, $key) !== $value) {
            if (!$this->is_modified and !$o_definition?->getIgnoreModify()) {
                $this->is_modified = true;
                $this->is_saved    = false;
            }

            if ($this->debug) {
                Log::dump('FIELD "' . Strings::fromReverse(static::class, '\\') . '::' . $key . '" WAS MODIFIED FROM "' . array_get_safe($this->source, $key) . '" [' . gettype(array_get_safe($this->source, $key)) . '] TO "' . Strings::force($value) . '" [' . gettype($value) . '], MARKED MODIFIED: ' . Strings::fromBoolean($this->is_modified), 10, echo_header: false);
            }

        } else {
            if ($this->debug) {
                Log::dump('FIELD "' . Strings::fromReverse(static::class, '\\') . '::' . $key . '" WAS NOT MODIFIED', 10, echo_header: false);
            }
        }

        // Update the column value
        $this->changes[]    = $key;
        $this->source[$key] = $value;
        $this->is_validated = (!$this->is_modified and $this->is_validated);
        $this->is_created   = (!$this->is_modified and $this->is_created);
        $this->is_saved     = (!$this->is_modified and $this->is_saved);

        return $this;
    }


    /**
     * Returns a list of all internal source keys
     *
     * @param bool $filter_meta
     * @param bool $filter_protected_columns
     *
     * @return array
     */
    public function getSourceKeys(bool $filter_meta = false, bool $filter_protected_columns = true): array
    {
        return array_keys($this->getSource($filter_meta, $filter_protected_columns));
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
            try {
                foreach ($identifier as $column => $value) {
                    if ($column !== static::getIdColumn()) {
                        $this->setColumnValueWithObjectSetter($value, $column, false, $this->getDefinitionsObject()
                                                                                           ->get($column));
                    }
                }

            } catch (TypeError|DataEntryException|DataEntryTypeException $e) {
                throw DataEntryTypeException::new(tr('Failed to load data from identifier for class ":class"', [
                    ':class' => static::class,
                ]), $e)
                ->setData([
                    'source' => $identifier
                ]);
            }

        } elseif ($identifier instanceof DataIteratorInterface) {
            // Get the source from the DataIteratorInterface and try again
            return $this->initializeSource($identifier->getSource(false, false));
        }

        return $this->ready();
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
        $this->loadIdentifier('reload');

        // This entry exists in the database, yay! Is it not deleted, though?
        if ($this->isDeleted()) {
            $this->processDeleted();
        }

        return $this->ready(true);
    }


    /**
     * Returns the specified columns from the DataEntry object matching the specified identifier
     * (MUST exist in the database)
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     * @param EnumLoadParameters|null                   $on_null_identifier
     * @param EnumLoadParameters|null                   $on_not_exists
     * @param array|string                              $columns
     *
     * @return static
     */
    public function loadColumns(IdentifierInterface|array|string|int|null $identifier, ?EnumLoadParameters $on_null_identifier = null, ?EnumLoadParameters $on_not_exists = null, array|string $columns = 'id'): static
    {
        return $this->setColumns($columns)
                    ->load($identifier, $on_null_identifier, $on_not_exists);
    }


    /**
     * Returns a DataEntry object matching the specified identifier that MUST exist in the database
     *
     * This method also accepts DataEntry objects of the same class, in which case it will simply return the specified
     * object, as long as it exists in the database.
     *
     * If the DataEntry doesn't exist in the database, then this method will check if perhaps it exists as a
     * configuration entry. This requires DataEntry::$config_path to be set. DataEntries from configuration will be in
     * readonly mode automatically as they can't be stored in the database.
     *
     * DataEntries from the database will also have their status checked. If the status is "deleted", then a
     * DataEntryDeletedException will be thrown
     *
     * @note The test to see if a DataEntry object exists in the database can be either DataEntry::isNew() or
     *       DataEntry::getId(), which should return a valid database id
     *
     * @param IdentifierInterface|array|string|int|null $identifier              Identifier for the DataEntry object to
     *                                                                           load. Can be specified with a
     *                                                                           [column => value] array, though also
     *                                                                           accepts an integer value which will
     *                                                                           convert to [id_column => integer_value]
     *                                                                           or a string value which will convert to
     *                                                                           [unique_column => string_value]]
     *
     * @param EnumLoadParameters|null                   $on_null_identifier      Specifies how this load method will
     *                                                                           handle the specified identifier being
     *                                                                           NULL. Options are:
     *                                                                           EnumLoadParameters::exception: Throws a
     *                                                                           DataEntryNoIdentifierSpecifiedException
     *                                                                           EnumLoadParameters::null: Will return
     *                                                                           NULL
     *                                                                           EnumLoadParameters::this: Will return
     *                                                                           the object as-is, without loading
     *                                                                           anything).
     *
     *                                                                           Defaults to
     *                                                                           EnumLoadParameters::exception
     *
     * @param EnumLoadParameters|null                   $on_not_exists           Specifies how this load method will
     *                                                                           handle the specified identifier not
     *                                                                           existing in the database. Options are:
     *                                                                           EnumLoadParameters::exception: Throws a
     *                                                                           DataEntryNotExistsException.
     *                                                                           EnumLoadParameters::null: Returns NULL
     *                                                                           EnumLoadParameters::this Returns this,
     *                                                                           the object as-is, without loading
     *                                                                           anything.
     *
     *                                                                           Defaults to
     *                                                                           EnumLoadParameters::exception
     *
     * @return static|null
     *
     * @throws DataEntryNoIdentifierSpecifiedException Thrown when the specified identifier is empty and
     *                                                 $on_null_identifier is set to EnumLoadParameters::exception
     * @throws DataEntryNotExistsException             Thrown when the specified identifier doesn't exist and
     *                                                 $on_not_exists is set to EnumLoadParameters::exception
     */
    public function load(IdentifierInterface|array|string|int|null $identifier = null, ?EnumLoadParameters $on_null_identifier = null, ?EnumLoadParameters $on_not_exists = null): ?static
    {
        if ($this->debug) {
            Log::dump('LOAD METHOD CALLED FOR CLASS "' . static::class . '" WITH IDENTIFIER "' . Json::encode($identifier ?? $this->identifier, force_single_line: true) . '"', 10, echo_header: false);
        }

        if ($this->is_loaded) {
            throw DataEntryException::new(tr('Cannot load identifier ":identifier" for ":class" class, the object already has data loaded', [
                ':identifier' => $identifier,
                ':class'      => $this::class
            ]))->setData([
                'source' => $this->source
            ]);
        }

        if (is_object($identifier)) {
            if (!$this->is_initialized) {
                $this->initialize(false);
            }

            $this->is_initializing_source = true;

            // This already is a DataEntry object, no need to create one. Validate that this is the same class
            if (($identifier instanceof static) or is_subclass_of(static::class, get_class($identifier))) {
                // The identifier is the same as this, or extended this. Copy its source inside this object
                return $this->setOnLoadNullIdentifier($on_null_identifier)
                            ->setOnLoadNotExists($on_not_exists)
                            ->setIdentifier($identifier->getIdentifier())
                            ->setSourceDirect($identifier->getSourceUnprocessed())
                            ->setObjectState($identifier->getObjectState());
            }

            throw new OutOfBoundsException(tr('Specified DataEntry identifier ":has" is incompatible with this object\'s class ":should"', [
                ':has'    => $identifier::class,
                ':should' => static::class,
            ]));
        }

        if (!$this->is_initialized) {
            $this->initialize(false);
        }

        // Set the identifier and event handling modifiers
        $this->setOnLoadNullIdentifier($on_null_identifier)
             ->setOnLoadNotExists($on_not_exists)
             ->setIdentifier($identifier)
             ->is_initializing_source = true;

        if (empty($this->identifier)) {
            // Oh noes, no identifier specified!
            switch ($this->on_null_identifier) {
                case EnumLoadParameters::null:
                    return null;

                case EnumLoadParameters::this:
                    return $this;

                case EnumLoadParameters::exception:
                    throw DataEntryNoIdentifierSpecifiedException::new(tr('Cannot load ":class" DataEntry object, it has no identifier specified', [
                        ':class'  => static::class,
                    ]))->addData([
                        'class'  => static::class,
                    ]);
            }
        }

        if (empty($this->connector)) {
            // Use the default connector for this DataEntry object
            $this->setConnectorObject(static::getDefaultConnectorObject());
        }

        if ($this->getUseCache()) {
            // Connector classes can't be cached!
            if (!is_a($this, Connector::class)) {
                // Try loading the DataEntry object from cache
                $o_data_entry = static::loadFromCache($this->getCacheKey(), $this->getUseLocalCache(), $this->getUseGlobalCache(), $this->debug);

                if ($o_data_entry) {
                    // This DataEntry was found in the cache, all is done!
                    return $o_data_entry->setIsLoaded()
                                        ->ready(true);
                }

                if ($this->debug) {
                    Log::dump('CACHE MISS FOR CLASS "' . static::class . '" WITH IDENTIFIER "' . Json::encode($identifier ?? $this->identifier, force_single_line: true) . '"', 10, echo_header: false);
                }
            }

        } elseif ($this->debug) {
            Log::dump('SKIPPED CACHE FOR CLASS "' . static::class . '" WITH IDENTIFIER "' . Json::encode($identifier ?? $this->identifier, force_single_line: true) . '"', 10, echo_header: false);
        }

        try {
            // Load data from identifier
            $this->loadIdentifier('load');

            // This entry exists in the database, yay! Is it not deleted, though?
            if ($this->isDeleted()) {
                if ($this->debug) {
                    Log::dump('CLASS "' . Strings::fromReverse(static::class, '\\') . '" WITH IDENTIFIER "' . Strings::log($identifier) . '" IS DELETED', 10, echo_header: false);
                }

                $this->processDeleted();
            }

            return $this->saveToLocalCache($this->getCacheKey())
                        ->saveToGlobalCache($this->getCacheKey())
                        ->ready(true);

        } catch (DataEntryNotExistsException $e) {
            // Handle entry not exist exceptions, depending on the $this->on_not_exists setting
            switch ($this->on_not_exists) {
                case EnumLoadParameters::null:
                    return null;

                case EnumLoadParameters::this:
                    return $this->initializeSource($identifier)->ready(true);

                case EnumLoadParameters::exception:
                    throw $e;
            }

            // The exception throwing here is because PHPstorm's static analyzer fails -as is tradition- to see that the switch (above) would catch everything.
            throw $e;
        }
    }


    /**
     * Returns a DataEntry object matching the specified identifier that MUST exist in the database, or NULL if NULL
     * identifier was specified
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     * @param EnumLoadParameters|null                   $on_not_exists
     *
     * @return static|null
     */
    public function loadNull(IdentifierInterface|array|string|int|null $identifier = null, ?EnumLoadParameters $on_not_exists = null): ?static
    {
        return $this->load($identifier, EnumLoadParameters::null, $on_not_exists);
    }


    /**
     * Returns a DataEntry object matching the specified identifier that MUST exist in the database, or the current
     * object
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     * @param EnumLoadParameters|null                   $on_not_exists
     *
     * @return static
     */
    public function loadThis(IdentifierInterface|array|string|int|null $identifier = null, ?EnumLoadParameters $on_not_exists = null): static
    {
        return $this->load($identifier, EnumLoadParameters::this, $on_not_exists);
    }


    /**
     * Returns a DataEntry object matching the specified identifier that MUST exist in the database, or NULL if NULL
     * identifier was specified
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     * @param EnumLoadParameters|null                   $on_null_identifier
     *
     * @return static|null
     */
    public function loadOrThis(IdentifierInterface|array|string|int|null $identifier = null, ?EnumLoadParameters $on_null_identifier = null): ?static
    {
        return $this->load($identifier, $on_null_identifier, EnumLoadParameters::this);
    }


    /**
     * Returns a DataEntry object matching the specified identifier that MUST exist in the database, or NULL if NULL
     * identifier was specified
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     * @param EnumLoadParameters|null                   $on_null_identifier
     *
     * @return static|null
     */
    public function loadOrNull(IdentifierInterface|array|string|int|null $identifier = null, ?EnumLoadParameters $on_null_identifier = null): ?static
    {
        return $this->load($identifier, $on_null_identifier, EnumLoadParameters::null);
    }


    /**
     * Returns a DataEntry object matching the specified identifier that MUST exist in the database, or the current
     * object
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     *
     * @return static
     */
    public function loadThisOrThis(IdentifierInterface|array|string|int|null $identifier = null): static
    {
        return $this->load($identifier, EnumLoadParameters::this, EnumLoadParameters::this);
    }


    /**
     * Returns a DataEntry object matching the specified identifier that MUST exist in the database, or the current
     * object
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     *
     * @return static
     */
    public function loadThisOrNull(IdentifierInterface|array|string|int|null $identifier = null): static
    {
        return $this->load($identifier, EnumLoadParameters::this, EnumLoadParameters::null);
    }


    /**
     * Returns a DataEntry object matching the specified identifier that MUST exist in the database, or the current
     * object
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     *
     * @return static|null
     */
    public function loadNullOrThis(IdentifierInterface|array|string|int|null $identifier = null): ?static
    {
        return $this->load($identifier, EnumLoadParameters::null, EnumLoadParameters::this);
    }


    /**
     * Returns a DataEntry object matching the specified identifier that MUST exist in the database, or the current
     * object
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     *
     * @return static|null
     */
    public function loadNullOrNull(IdentifierInterface|array|string|int|null $identifier = null): ?static
    {
        return $this->load($identifier, EnumLoadParameters::null, EnumLoadParameters::null);
    }


    /**
     * Generates and returns a unique cache key for the specified identifer / columns
     *
     * @param array|false|null $identifier
     * @param array|null       $columns
     *
     * @return string|null
     */
    public static function generateCacheKeySeed(array|false|null $identifier, ?array $columns): ?string
    {
        if ($identifier) {
            return PROJECT . '#DataEntry#' . static::class . '#' . Json::encode(['identifier' => $identifier, 'columns' => $columns], JSON_BIGINT_AS_STRING, force_single_line: true);
        }

        // There is no identifier, meaning that this object is not cacheable
        return null;
    }


    /**
     * Returns a unique cache key for this DataEntry object
     *
     * @return string|null
     */
    public function getCacheKeySeed(): ?string
    {
        return static::generateCacheKeySeed($this->identifier, $this->columns);
    }


    /**
     * Tries to load (and return) this DataEntry object data from the cache layer instead of the database
     *
     * @param string|null $cache_key
     * @param bool        $local
     * @param bool        $global
     *
     * @return DataEntryInterface|null
     */
    protected static function loadFromCache(?string $cache_key, bool $local, bool $global): ?DataEntryInterface
    {
        if (empty($cache_key)) {
            // No cache key? Nothing to check in cache
            return null;
        }

        if (!$local and $global) {
            // Caching is disabled for this object
            return null;
        }

        if (Log::passesThreshold(3)) {
            Log::action(ts('Searching cache for DataEntry object with key ":key"', [':key' => $cache_key]), 3);
        }

        if ($local) {
            if (LocalCache::exists(static::class, $cache_key)) {
                // Cached entry exists, get it!
                $data_entry = LocalCache::getLastChecked();

                if (Log::passesThreshold(3)) {
                    Log::success(ts('Local cache hit for DataEntry object with key ":key"', [':key' => $cache_key]), 3);
//                    Log::printr($data_entry->getSourceUnprocessed(), 2, echo_header: false);
                }

                return $data_entry->setIsLoadedFromLocalCache();
            }
        }

        if ($global) {
            $data_entry = cache('dataentries')->get($cache_key);

            if ($data_entry) {
                if ($data_entry instanceof DataEntryInterface) {
                    // Found it in external cache!

                    if (Log::passesThreshold(3)) {
                        Log::success(ts('Global cache hit for DataEntry object with key ":key"', [':key' => $cache_key]), 3);
//                        Log::printr($data_entry->getSourceUnprocessed(), 2, echo_header: false);
                    }

                    // We didn't have this DataEntry in local cache, so save it there for future use
                    return $data_entry->saveToLocalCache($cache_key)
                                      ->setIsLoadedFromGlobalCache();
                }

                throw DataEntryInvalidCacheException::new(tr('Failed to load DataEntry object with key ":key" from cache, cache returned invalid non DataEntry object', [
                    ':key'   => $cache_key
                ]))->setData([
                    'invalid_data' => $data_entry
                ]);
            }
        }

        if (Log::passesThreshold(3)) {
            if ($global or $local) {
                Log::warning(ts('Cache miss for DataEntry object with key ":key"', [
                        ':key'   => static::class . ' / ' . $cache_key]
                ), 3);

            } else {
                Log::warning(ts('No cache used for DataEntry object with key ":key" because global and local cache have been disabled', [
                    ':key'   => static::class . ' / ' . $cache_key
                ]), 3);
            }
        }

        return null;
    }


    /**
     * Saves this DataEntry object to cache
     *
     * @param string|null $cache_key
     *
     * @return static
     */
    protected function saveToLocalCache(?string $cache_key): static
    {
        if (empty($cache_key)) {
            return $this;
        }

        if (Log::passesThreshold(2)) {
            Log::action(tr('Saving DataEntry ":class" object to local cache with key ":key"', [
                ':class' => static::class,
                ':key'   => static::class . ' / ' . $cache_key,
            ]), 2);
        }

        // Cache the DataEntry and update table state if local cache is enabled and this DataEntry didn't come from local cache
        if ($this->getUseLocalCache()) {
            LocalCache::set($this, static::class, $this->getCacheKey());
        }

        return $this;
    }


    /**
     * Saves this DataEntry object to cache
     *
     * @param string|null $cache_key
     *
     * @return static
     */
    protected function saveToGlobalCache(?string $cache_key): static
    {
        if (empty($cache_key)) {
            return $this;
        }

        if (is_a($this, Connector::class)) {
            // Connectors are non-cacheable because they will cause endless loops when cached
            return $this;
        }
        if (Log::passesThreshold(2)) {
            Log::action(tr('Saving DataEntry ":class" object to global cache with key ":key"', [
                ':class' => static::class,
                ':key'   => $cache_key,
            ]), 2);
        }
        // Cache the DataEntry and update table state if global cache is enabled and this DataEntry didn't come from global cache
        if ($this->getUseGlobalCache()) {
            cache('dataentries')->set($this, $cache_key);
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
        LocalCache::delete('dataentries', $this->getCacheKey());

        // Cache the DataEntry and update table state
        if (Cache::isEnabled()) {
            // Connector objects CANNOT be cached
            if (!is_a($this, Connector::class)) {
                cache('dataentries')->delete($this->getCacheKey());
                LocalCache::delete(static::class, $this->getCacheKey());
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
     * Loads a random DataEntry object
     *
     * @param string     $where
     * @param array|null $execute
     *
     * @return static|null
     */
    public function loadRandomWhere(string $where, ?array $execute = null): ?static
    {
        $identifier = sql(static::getDefaultConnector())->getInteger('SELECT   `id` 
                                                                      FROM     `' . static::getTable() . '` 
                                                                      WHERE ' . $where . '
                                                                      ORDER BY RAND() 
                                                                      LIMIT    1;', $execute);


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
     *
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

        return (string)$real_name;
    }


//    /**
//     * Add the complete definitions and source from the specified data entry to this data entry
//     *
//     * @param string $at_key
//     * @param mixed $value
//     * @param DefinitionInterface $o_definition
//     * @param bool $after
//     * @return static
//     * @todo Improve by first splitting meta data off the new data entry and then ALWAYS prepending it to ensure its at the front
//     */
//    public function injectDataEntryValue(string $at_key, string|float|int|null $value, DefinitionInterface $o_definition, bool $after = true): static
//    {
//        $this->source[$o_definition->getColumn()] = $value;
//        $this->getDefinitionsObject()->spliceByKey($at_key, 0, [$o_definition->getColumn() => $o_definition], $after);
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
     * @param bool      $readonly
     * @param bool|null $set_disabled
     *
     * @return static
     */
    public function setReadonly(bool $readonly, ?bool $set_disabled = null): static
    {
        if (!$readonly and $this->isLoadedFromConfiguration()) {
            throw new ReadOnlyModeException(tr('Cannot disable readonly mode for the DataEntry ":class" class, it is loaded from configuration and cannot be saved', [
                ':class' => static::class,
            ]));
        }

        return $this->__setReadonly($readonly, $set_disabled);
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
     * @param DefinitionsInterface $o_definitions
     *
     * @return static
     */
    protected function setDefinitionsObject(DefinitionsInterface $o_definitions): static
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
     *
     * @return array
     */
    public function getSource(bool $filter_meta = false, bool $filter_protected_columns = true): array
    {
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
     * Returns the source for this DataEntry as-is
     *
     * @return array
     */
    public function getSourceUnprocessed(): array
    {
        return $this->source;
    }


    /**
     * Returns a source array with all source virtual columns resolved (not NULL)
     *
     * @return array
     */
    protected function getSourceWithResolvedVirtualColumns(): array
    {
        $source = [];

        if ($this->o_definitions) {
            foreach ($this->o_definitions as $column => $o_definition) {
                if (!$o_definition->getContainsData()) {
                    // Don't process data-less columns
                    continue;
                }

                // Get the value from the source, ensure to apply default or initial default values
                $value = array_get_safe($this->source, $column, $this->isNew() ? ($o_definition->getInitialDefault() ?? $o_definition->getDefault()) : $o_definition->getDefault());

                // If the value is null, apply the get method for the column IF IT EXISTS. If the get method doesn't exist,
                // just copy the NULL value as-is
                if ($value === null) {
                    // Meta columns are never virtual, ignore them as accessing them might cause issues
                    if ($this->isMetaColumn($column)) {
                        $source[$column] = $value;
                        continue;
                    }

                    // Columns that aren't virtual can just be copied directly
                    // Don't process columns that will not render
                    if ($o_definition->getVirtual() and $o_definition->getRender()) {
                        // Try to resolve this column using the get method for that column
                        $method = $this->convertColumnToMethod($column, 'get');

                        if (method_exists(static::class, $method)) {
                            $source[$column] = $this->$method();

                        } else {
                            $source[$column] = $value;
                        }

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
        $this->ensureMetaColumns();
        $this->is_loading = true;
        $this->source     = [];

        if ($source) {
            $source = $this->prepareSource($source, $execute, $filter_meta);

            // Initialize the object
            if (!$this->is_initialized) {
                $this->initializeFromSource($source);
            }

            if (!$filter_meta) {
                // Load meta data too
                $this->copyMetaDataToSource($source);
            }

            // Load data with object init
            $this->copyValuesToSource($source, false);
        }

        // Done!
        return $this->ready();
    }


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
    public function setSourceDirect(DataEntryInterface|IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null, bool $filter_meta = false): static
    {
        // Mark the data in this object as unvalidated because this loading bypassed validation!
        $this->is_loading   = true;
        $this->is_validated = false;
        $this->source       = [];

        if ($source) {
            $source = $this->prepareSource($source, $execute, $filter_meta);

            // Initialize the object
            if (!$this->is_initialized) {
                $this->initializeFromSource($source);
            }

            // Load data directly
            $this->source = $source ?? [];
        }

        // Done!
        return $this->ready();
    }


    /**
     * Prepare the source for loading into this data entry
     *
     * @param DataEntryInterface|IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null                                                          $execute
     * @param bool                                                                $filter_meta
     *
     * @return array|null
     */
    protected function prepareSource(DataEntryInterface|IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null, bool $filter_meta = false): ?array
    {
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

        if ($filter_meta) {
            // Remove meta columns from the given source
            $source = Arrays::removeKeys($source, $this->meta_columns);
        }

        return $source;
    }


    /**
     * Try to load this DataEntry from configuration instead of database
     *
     * @return static
     */
    protected function tryLoadFromConfiguration(): static
    {
        $path = $this->getConfigurationPath();

        // Can only load from configuration if the configuration path is available
        if ($path) {
            if ($this->debug) {
                Log::dump('TRY LOADING CLASS "' . Strings::fromReverse(static::class, '\\') . '" WITH IDENTIFIER "' . Strings::log($this->identifier) . '" FROM CONFIGURATION', 10, echo_header: false);
            }

            // This DataEntry supports loading from configuration. Identifier arrays may ONLY contain one column!
            $column     = static::determineColumn($this->identifier);
            $identifier = $this->identifier[$column];

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
                        Log::dump('FOUND CLASS "' . Strings::fromReverse(static::class, '\\') . '" WITH IDENTIFIER "' . Strings::log($identifier) . '" IN CONFIGURATION', 10, echo_header: false);
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

        throw DataEntryNotExistsException::new(tr('Cannot load ":class" class object because the specified identifier ":identifier" does not exist', [
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
     * @param string     $path
     * @param string|int $identifier
     *
     * @return array|null
     */
    protected function loadFromConfiguration(string $path, string|int $identifier): ?array
    {
        try {
            if (is_numeric_integer($identifier)) {
                // Get the Nth value in the configuration
                $source = config()->getArray($path, []);

                try {
                    $source = get_index_value($source, abs($identifier));

                } catch (OutOfBoundsException) {
                    // The specified identifier doesn't exist in the configuration
                    $source = null;
                }

            } else {
                $source = config()->getArray(Strings::ensureEndsWith($path, '.') . Config::escape($identifier), []);
            }

        } catch (ConfigEmptyException) {
            // The configuration key exists but is empty. Act as if it doesn't exist
            Log::warning(ts('Ignoring empty value for configuration path ":path" while trying to load a ":class" DataEntry object', [
                ':path'  => $path,
                ':class' => static::class
            ]));

            return null;
        }

        if ($source) {
            // Found the entry in configuration! Make it a readonly DataEntry object
            $source['id']     = (is_numeric_integer($identifier) ? $identifier : -1);
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
            Log::dump('TRY LOADING CLASS "' . Strings::fromReverse(static::class, '\\') . '" WITH IDENTIFIER "' . Strings::log($this->identifier) . '" FROM DATABASE', 10, echo_header: false);
        }

        $this->is_loading = true;
        $this->cache_key  = null;

        if ((!empty($this->columns)) and (count($this->identifier) > 1)) {
            throw UnderConstructionException::new(tr('Sorry, DataEntry->loadColumns() does not yet support array identifiers'));
        }

        // Filter on multiple columns, multi column filter always pretends filtered column was id column
        static::buildManualQuery($this->identifier, $where, $joins, $group, $order, $execute);

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

        return $this;
    }


    /**
     * Executes the query and loads the data into the DataEntry
     *
     * @param string|null $where
     * @param array|null  $execute
     *
     * @return void
     */
    protected function executeQueryAndLoadData(?string $where, ?array $execute): void
    {
        try {
            // Get the data using the query builder
            $o_query = $this->getQueryBuilderObject()
                            ->setDebug($this->debug)
                            ->setMetaEnabled($this->getMetaEnabled())
                            ->setConnectorObject($this->getConnectorObject())
                            ->addWhere($where, $execute);

            // Generate columns that will be selected
            if ($this->identifier) {
                if ($this->columns) {
                    // Add SQL SELECT for each specified column
                    foreach ($this->columns as $column) {
                        $o_query->addSelect(SqlQueries::ensureQuotes(static::getTable()) . SqlQueries::ensureQuotes($column));
                    }

                } else {
                    // Load all columns
                    $o_query->addSelect(SqlQueries::ensureQuotes(static::getTable()) . '.*');
                }
            }

            $source = $o_query->get();

            if ($source) {
                if ($this->debug) {
                    Log::dump('FOUND CLASS "' . Strings::fromReverse(static::class, '\\') . '" WITH IDENTIFIER "' . Strings::log($this->identifier) . '" IN DATABASE', 10, echo_header: false);
                }

                // If data was found, store all data in the object
                $this->copyMetaDataToSource($source)
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
        return $this->getQueryBuilderObject()
                    ->getQueryHash();
    }


    /**
     * Returns the query builder for this data entry
     *
     * @param bool $auto_initialize
     *
     * @return QueryBuilderInterface|null
     */
    public function getQueryBuilderObject(bool $auto_initialize = true): ?QueryBuilderInterface
    {
        if (!$this->query_builder and $auto_initialize) {
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
     * Adds the specifications of the given query builder to the query builder of this DataEntry object
     *
     * @param QueryBuilderInterface $o_query_builder
     *
     * @return static
     */
    public function addQueryBuilderObject(QueryBuilderInterface $o_query_builder): static
    {
        $this->getQueryBuilderObject()
             ->addQueryBuilderObject($o_query_builder);

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
     * Returns whether this object will allow columns that aren't permitted
     *
     * @return bool
     */
    public function getAllowUnpermittedColumns(): bool
    {
        return $this->allow_unpermitted_columns;
    }


    /**
     * Sets whether this object will allow columns that aren't permitted
     *
     * @param bool $allow
     *
     * @return static
     */
    public function setAllowUnpermittedColumns(bool $allow): static
    {
        $this->allow_unpermitted_columns = $allow;
        return $this;
    }


    /**
     * Returns true if the specified column is on the permitted columns list
     *
     * @param string $column
     *
     * @return bool
     */
    public function columnIsPermitted(string $column): bool
    {
        if ($this->permitted_columns) {
            return array_key_exists($column, $this->permitted_columns);
        }

        return false;
    }


    /**
     * Returns a list of columns that aren't defined, but are permitted for use
     *
     * @return array|null
     */
    public function getPermittedColumns(): ?array
    {
        if ($this->permitted_columns) {
            return array_keys($this->permitted_columns);
        }

        return null;
    }


    /**
     * Returns a list of columns that aren't defined, but are permitted for use
     *
     * @param array|string|null $columns
     *
     * @return static
     */
    public function setPermittedColumns(array|string|null $columns): static
    {
        $this->permitted_columns = [];
        return $this->addPermittedColumns($columns);
    }


    /**
     * Returns a list of columns that aren't defined, but are permitted for use
     *
     * @param array|string|null $columns
     *
     * @return static
     */
    public function addPermittedColumns(array|string|null $columns): static
    {
        if ($columns) {
            foreach (Arrays::force($columns) as $column) {
                if (!is_scalar($column)) {
                    throw OutOfBoundsException::new(tr('Cannot set column ":column" as permitted, only scalar column names are allowed', [
                        ':column' => $columns
                    ]));
                }

                $this->permitted_columns[$column] = true;
            }
        }

        $this->permitted_columns = get_null($this->permitted_columns);
        return $this;
    }


    /**
     * Determines if the specified key should be copied or not
     *
     * @param DefinitionInterface         $o_definition
     * @param Stringable|string|float|int $key
     * @param bool                        $force
     *
     * @return bool
     */
    protected function mustCopyKeyToSource(DefinitionInterface $o_definition, Stringable|string|float|int $key, bool $force): bool
    {
        if ($this->columns and !array_key_exists($key, $this->columns)) {
            // Don't copy this column
            if ($this->debug) {
                Log::dump(ts('NOT COPYING VALUE FOR ":key" TO SOURCE, KEY NOT SPECIFIED IN COLUMNS', [
                    ':key' => $key,
                ]), echo_header: false);
            }

            return false;
        }

        if ($this->debug) {
            Log::dump(ts('TRY COPYING VALUE FOR ":key" TO SOURCE', [
                ':key' => $key,
            ]), echo_header: false);
        }

        // Meta-keys cannot be set through DataEntry::setData()
        if ($o_definition->isMeta()) {
            return false;
        }

        if ($this->is_applying and !$force) {
            if ($o_definition->getReadonly() or $o_definition->getDisabled() or !$o_definition->getRender()) {
                if (!$o_definition->getForceValidations()) {
                    // Apply can't update readonly or disabled columns
                    return false;
                }

                // This entry is readonly or disabled, but will be forcibly processed
            }
        }

        return true;
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

        foreach ($this->o_definitions as $key => $o_definition) {
            if (!$this->mustCopyKeyToSource($o_definition, $key, $force)) {
                // This key shouldn't be copied to the object's source
                unset($source[$key]);
                continue;
            }

            // Get the value for the current key
            if (array_key_exists($key, $source)) {
                $value = $source[$key];
                unset($source[$key]);

            } elseif (empty($this->source[$key])) {
                // TODO THIS SHOULD (and is??) BE DONE IN DataEntryCore::set()
                // This is empty in the specified source and empty in the internal source,default it
                if ($this->isNew()) {
                    // This is a new (unsaved) object, apply initial default
                    $value = $o_definition->getInitialDefault() ?? $o_definition->getDefault();

                } else {
                    // This is an existing object, apply normal default
                    $value = $o_definition->getDefault();
                }

            } else {
                // The current key is empty in the specified source and exists in the internal source, don't update
                continue;
            }

            if (PLATFORM_CLI and ($value === null)) {
                // NULL values on CLI platform will be ignored because they will always exist
                continue;
            }
            if ($o_definition->getVirtual()) {
                // Virtual columns do nothing if they have no value
                if ($value === null) {
                    continue;
                }
            }

            if (!$modify) {
                // Remove prefix / postfix if defined
                if ($o_definition->getPrefix()) {
                    $key = Strings::from($key, $o_definition->getPrefix());
                }

                if ($o_definition->getSuffix()) {
                    $key = Strings::untilReverse($key, $o_definition->getSuffix());
                }
            }

            try {
                $this->setColumnValueWithObjectSetter($value, $key, $directly, $o_definition);

            } catch (TypeError | DataEntryException | DataEntryTypeException $e) {
                $this->handleCopyValuesToSourceExceptions($e, $source, $value, $key, $force);
            }
        }

        if ($source) {
            $this->copyPermittedValuesToSource($source);
        }

        if ($this->sourceLoadedFromConfiguration()) {
            $this->readonly = true;
        }

        $this->is_validated = $validated;
        $this->previous_id  = $this->getId(false);

        return $this;
    }


    /**
     * Handles exceptions that occurred in DataEntryCore::copyValuesToSource()
     *
     * @param TypeError|DataEntryException|DataEntryTypeException $e
     * @param array                                               $source
     * @param mixed                                               $value
     * @param Stringable|string|float|int                         $key
     * @param bool                                                $force
     *
     * @return void
     */
    protected function handleCopyValuesToSourceExceptions(TypeError | DataEntryException | DataEntryTypeException $e, array $source, mixed $value, Stringable|string|float|int $key, bool $force): void
    {
        if ($e instanceof DataEntryTypeException) {
            if ($force) {
                // Type errors with forced copy values means that we're working with an untrusted source and datatype
                // mismatch may happen, but we'll ignore it. Forced feeding!
                return;
            }
        }

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


    /**
     * Will attempt to copy permitted keys to this DataEntry's source
     *
     * @param array $source
     *
     * @return static
     */
    protected function copyPermittedValuesToSource(array $source): static
    {
        if (!$this->getPermittedColumns()) {
            if (!$this->allow_unpermitted_columns) {
                throw DataEntryColumnsNotDefinedException::new(tr('Cannot copy columns ":columns" from source data into internal source for ":class" class, the column is not defined and no columns are permitted for this object', [
                    ':class'   => $this::class,
                    ':columns' => array_keys($source),
                ]))->setData([
                    'class'         => $this::class,
                    'source'        => $source,
                    'not_permitted' => array_keys($source),
                    'defined'       => $this->getDefinitionsObject()->getSourceKeys(),
                    'permitted'     => '-',
                ]);
            }

            return $this;
        }

        foreach ($source as $key => $value) {
            if (!$this->columnIsPermitted($key)) {
                $failed[] = $key;
            }

            $this->source[$key] = $value;
        }

        if (isset($failed)) {
            throw DataEntryColumnsNotDefinedException::new(tr('Cannot copy columns ":columns" from source data into internal source for ":class" class, the column is not defined nor permitted', [
                ':class'   => $this::class,
                ':columns' => $failed,
            ]))->setData([
                'class'         => $this::class,
                'source'        => $source,
                'not_permitted' => $failed,
                'defined'       => $this->getDefinitionsObject()->getSourceKeys(),
                'permitted'     => $this->getPermittedColumns(),
            ]);
        }

        return $this;
    }


    /**
     * Updates the specified column with the given value, using the objects setter method (which MUST exist)
     *
     * @param string              $column
     * @param mixed               $value
     * @param bool                $directly
     * @param DefinitionInterface $o_definition
     *
     * @return static
     */
    protected function setColumnValueWithObjectSetter(mixed $value, string $column, bool $directly, DefinitionInterface $o_definition): static
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

            if (!$o_definition->inputTypeIsScalar()) {
                // This input type is not scalar and as such has been stored as a JSON array
                $value = Json::ensureDecoded($value);
            }

            try {
                if ($this->getDefinitionsObject()->get($column)->getContainsData()) {
                    if ($this->debug) {
                        Log::dump('SET "' . Strings::fromReverse(static::class, '\\') . '::$' . $column . '" using ' . Strings::fromReverse(static::class, '\\') . '::' . $method . '() ' . (method_exists($this, $method) ? '(exists)' : '(NOT exists)') . ' TO "' . Strings::log($value) . ' [' . get_class_or_datatype($value) . ']"', 10, echo_header: false);
                    }

                    $this->$method($value);
                }

            } catch (Throwable $e) {
                $this->handleSetColumnValueWithObjectSetterException($e, $method, $value, $column);
            }
        }

        return $this;
    }


    /**
     * Handles exceptions that occurred while trying to set object setter
     *
     * @param Throwable $e
     * @param string    $method
     * @param mixed     $value
     * @param string    $column
     *
     * @return never
     * @throws DataEntryException | SqlTableDoesNotExistException | SqlUnknownDatabaseException
     */
    protected function handleSetColumnValueWithObjectSetterException(Throwable $e, string $method, mixed $value, string $column): never
    {
        if (($e instanceof SqlTableDoesNotExistException) or ($e instanceof SqlUnknownDatabaseException)) {
            // These exceptions mean that the database or table accessed doesn't exist
            throw $e;
        }

        if (method_exists($this, $method)) {
            if (str_contains($e->getMessage(), 'must be of type')) {
                // There is no method accepting this data. This might be because it is a virtual column that gets
                // resolved at validation time. Check this with the "definitions" object
                throw DataEntryTypeException::new(tr('Failed to set DataEntry class ":class" source key ":key" with method ":short:::method()" because of a datatype mismatch. Check the column definition and validation rules.', [
                    ':key'    => $column,
                    ':method' => $method,
                    ':class'  => static::class,
                    ':short'  => Strings::fromReverse(static::class, '\\'),
                ]), $e)->setData([
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
                ':class'  => static::class,
                ':short'  => Strings::fromReverse(static::class, '\\'),
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
     * @param ?array $source
     * @param bool   $directly
     *
     * @return static
     */
    protected function copyMetaDataToSource(?array $source = null, bool $directly = false): static
    {
        $this->checkDefinitionsObject();

        if ($source === null) {
            // No data specified, all columns should be null
            $this->source = Arrays::setKeys($this->meta_columns, null, $this->source);

        } else {
            // Reset meta columns
            try {
                foreach ($this->meta_columns as $column) {
                    $this->setColumnValueWithObjectSetter(array_get_safe($source, $column), $column, $directly, $this->getDefinitionsObject()->get($column));
                }

            } catch (TypeError | DataEntryException | DataEntryTypeException $e) {
                throw DataEntryTypeException::new(tr('Failed to load meta data for class ":class"', [
                    ':class'  => static::class,
                ]), $e)
                ->setData([
                    'source' => $source
                ]);
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
        foreach (static::new()->getDefinitionsObject() as $o_definitions) {
            if ($o_definitions->getCliColumn() and $o_definitions->getCliAutoComplete()) {
                $arguments[$o_definitions->getCliColumn()] = $o_definitions->getCliAutoComplete();
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
        foreach ($columns as $id => $o_definitions) {
            if ($o_definitions->isMeta()) {
                continue;
            }

            if (!$o_definitions->getRender()) {
                continue;
            }

            if (!$o_definitions->getOptional()) {
                $columns->removeKeys($id);
                $return .= PHP_EOL . PHP_EOL . Strings::size($o_definitions->getCliColumn(), 39) . ' ' . $o_definitions->getHelpText();
            }

            $groups[$o_definitions->getHelpGroup()] = true;
        }

        // Get the columns and group them by help_group
        foreach ($groups as $group => $nothing) {
            $body = '';

            if ($group) {
                $header = PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL . CliColor::apply(strtoupper(trim($group)), 'white');

            } else {
                $header = PHP_EOL . PHP_EOL . PHP_EOL . CliColor::apply(strtoupper(tr('Miscellaneous information')), 'white');
            }

            foreach ($columns as $id => $o_definitions) {
                if ($o_definitions->isMeta()) {
                    continue;
                }

                if ($o_definitions->getHelpGroup() === $group) {
                    $columns->removeKeys($id);
                    $body .= PHP_EOL . PHP_EOL . Strings::size($o_definitions->getCliColumn(), 39) . ' ' . $o_definitions->getHelpText();
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
                throw new ValidationFailedException(tr('Now allowed to create new :entry', [
                    ':entry' => strtolower(static::getEntryName()),
                ]));
            }

        } else {
            if (!$this->allow_modify) {
                // auto modify is not allowed, sorry!
                throw new ValidationFailedException(tr('Now allowed to modify :entry', [
                    ':entry' => strtolower(static::getEntryName()),
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
        try {
            $data_source = Validator::pick($source)->setDefinitionsObject($this->o_definitions);

        } catch (TypeError $e) {
            if ($this->isInitialized()) {
                throw $e;
            }

            throw new DataEntryNotInitializedException(tr('Cannot apply data to this ":class" object, the object has not yet been initialized', [
                ':class' => static::getEntryName(),
            ]), $e);
        }

        if ($this->debug) {
            Log::dump(($force ? 'FORCE ' : null) . 'APPLY ' . static::getEntryName() . ' (' . static::class . ')', 10, echo_header: false);
            Log::dump('CURRENT DATA'                                                                             , 10, echo_header: false);
            Log::vardump($this->source                                                                               , echo_header: false);
            Log::dump('UNVALIDATED NEW DATA'                                                                         , echo_header: false);
            Log::vardump($force ? $data_source->getBackup() : $data_source->getSource()                              , echo_header: false);
        }

        // Get the source array from the validator into the DataEntry object
        if ($force) {
            // Force was used, but the object will now be in readonly mode, so we can save failed data
            // Validate data and copy data into the source array
            $data_source = $this->doNotValidate($data_source, $require_clean_source, $force);
            $this->copyValuesToSource($data_source, true, false, true);

        } else {
            // Validate data and copy data into the source array
            $data_source = $this->validateSource($data_source, $require_clean_source, true);

            if ($this->debug) {
                Log::dump('VALIDATED DATA', echo_header: false);
                Log::vardump($data_source, echo_header: false);
            }

            // Ensure DataEntry Meta state is okay, then generate the diff data and copy data array to internal data
            $this->validateMetaState($data_source)
                 ->createDiff($data_source)
                 ->copyValuesToSource($data_source, true);
        }

        $this->is_applying = false;

        if ($this->debug) {
            Log::dump('SOURCE AFTER APPLYING', echo_header: false);
            Log::vardump($this->source, echo_header: false);
        }

        return $this;
    }


    /**
     * Extracts the data from the validator without validating
     *
     * @param ValidatorInterface $o_validator
     * @param bool               $require_clean_source
     * @param bool               $force
     *
     * @return array
     */
    protected function doNotValidate(ValidatorInterface $o_validator, bool $require_clean_source, bool $force = false): array
    {
        $return = [];

        if ($force) {
            $source = $o_validator->getBackup();
        } else {
            $source = $o_validator->getSource();
        }

        $prefix = $this->getDefinitionsObject()->getPrefix();

        foreach ($source as $key => $value) {
            $return[Strings::from($key, $prefix)] = $value;

            if ($require_clean_source) {
                $o_validator->removeKeys($key);
            }
        }

        return $return;
    }


    /**
     * Validate all columns for this DataEntry
     *
     * @note This method will also fix column names in case column prefix was specified
     *
     * @param ValidatorInterface $o_validator
     * @param bool               $require_clean_source
     * @param bool               $use_prefix
     *
     * @return array
     */
    protected function validateSource(ValidatorInterface $o_validator, bool $require_clean_source, bool $use_prefix): array
    {
        if (!$this->validate) {
            // This data entry won't validate data, just continue.
            return $o_validator->getSource();
        }

        // Set what prefix to use
        $prefix = $use_prefix ? $this->getDefinitionsObject()->getPrefix() : null;

        // Set ID so that the array validator can do unique lookups, etc.
        // Tell the validator what table this DataEntry is using and get the column prefix so that the validator knows
        // what columns to select
        $o_validator->setDataEntryObject($this)
                    ->setDefinitionsObject($this->o_definitions)
                    ->setPrefix($prefix)
                    ->setMetaColumns($this->getMetaColumns())
                    ->setTable(static::getTable());

        // Go over each column and let the column definition do the validation since it knows the specs
        foreach ($this->o_definitions as $column => $o_definition) {
            if ($o_definition->isMeta()) {
                // This column is metadata and shouldn't be validated. Only apply static values
                if ($o_definition->getValue()) {
                    $this->source[$column] = $o_definition->getValue();
                }

                continue;
            }

// TODO Remove support for whatever this is. If there is some requirement to permit an incomplete dataset (i.e., columns
// TODO that are required are missing) then this should be done with  a separate proprty that can be set by ONLY the
// TODO dataentry that implements this
//            if (!array_key_exists($column, $o_validator->getSource()) and $external) {
//                // External data does not apply defaults, if the column doesn't exist, skip it
//                continue;
//            }

            if ($this->debug) {
                Log::dump('VALIDATING COLUMN "' . static::class . ' > ' . $column . '" WITH VALUE "' . $this->get($column) . ' ['  . gettype($this->get($column)) . ']"', echo_header: false);
            }

            try {
                // Execute the validations for this single definition
                $o_definition->validate($o_validator);

            } catch (ValidationFailedException $e) {
                throw $e;

            } catch (Throwable $e) {
                throw ValidatorException::new(tr('Encountered an exception while validating ":class"', [
                    ':class' => static::class,
                ]), $e);
            }
        }

        try {
            // Execute the validate method to get the results of the validation
            $source             = $o_validator->setPermitValidationFailures($this->getPermitValidationFailures())
                                              ->validate($require_clean_source);
            $this->is_validated = true;

            if (!$this->hasPermitValidationFailures(EnumSoftHard::none)) {
                // The validator MIGHT have a failure that was permitted!
                if ($o_validator->getFailures()) {
                    $this->setException($o_validator->getException())
                         ->setStatus('failedvalidation', auto_save: false);

                } elseif ($this->hasStatus('failedvalidation')) {
                    $this->setStatus(null, auto_save: false);
                }
            }

        } catch (ValidationFailedException $e) {
            if ($this->debug) {
                Log::dump('FAILED VALIDATION OF "' . static::class . '" DATA ENTRY DATA, SEE FOLLOWING LOG ENTRIES', 10, echo_header: false);
                Log::printr($e->getData(), echo_header: false);
            }

            // Add the DataEntry object type to the exception message
            throw $e->setMessage('(' . static::class . ') ' . $e->getMessage());
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
            Log::dump('DATA AFTER VALIDATION:', echo_header: false);
            Log::printr($source, echo_header: false);
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
            // Column is not defined. Is it permitted, maybe?
            if (!$this->columnIsPermitted($key)) {
                if ($exception) {
                    throw new OutOfBoundsException(tr('Specified key ":key" is not defined for the ":class" class DataEntry object', [
                        ':class' => static::class,
                        ':key'   => $key,
                    ]));
                }
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
                foreach ($this->o_definitions as $key => $o_definition) {
                    if ($o_definition->getReadonly() or $o_definition->getDisabled() or $o_definition->isMeta()) {
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
     * If the DataEntry doesn't exist in the database, then this method will check if perhaps it exists as a
     * configuration entry. This requires DataEntry::$config_path to be set. DataEntries from configuration will be in
     * readonly mode automatically as they can't be stored in the database.
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
     * @throws OutOfBoundsException|DataEntryExistsException
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
                throw DataEntryExistsException::new(tr('The ":type" type data entry with identifier ":id" already exists', [
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

        $this->o_definitions
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
                                           ->appendSource($this->o_definitions)
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
     * @note When $o_definition is specified as an object, it will completely overwrite the existing object.
     *
     * @note When $o_definition is specified as an array, only the specified entries will overwrite the existing object's
     *       entries
     *
     * @note The entries' name CANNOT be changed here!
     *
     * @param string                                  $at_key
     * @param ElementInterface|ElementsBlockInterface $value
     * @param DefinitionInterface|array|null          $o_definition
     * @param bool                                    $after
     *
     * @return static
     * @todo Improve by first splitting meta data off the new data entry and then ALWAYS prepending it to ensure its at
     *       the front
     */
    public function injectElement(string $at_key, ElementInterface|ElementsBlockInterface $value, DefinitionInterface|array|null $o_definition = null, bool $after = true): static
    {
        // Render the specified element directly into the definition. Remove the specified column from this source (overwrite, basically)
        $o_element_definition                             = $value->getDefinitionObject()->setContent($value);
        $this->source[$o_element_definition->getColumn()] = null;

        try {
            $this->getDefinitionsObject()->spliceByKey($at_key, 0, [$o_element_definition->getColumn() => $o_element_definition], $after);

        } catch (OutOfBoundsException $e) {
            throw new OutOfBoundsException(tr('Failed to inject element at key ":key", the key does not exist', [
                ':key' => $at_key,
            ]), $e);
        }

        if ($o_definition) {
            // Apply specified definitions as well
            if ($o_definition instanceof DefinitionInterface) {
                $o_definition->setColumn($o_element_definition->getColumn());
                $this->getDefinitionsObject()->get($o_element_definition->getColumn())->setSource($o_definition->getSource());

            } else {
                // Merge the specified definitions over the existing one
                $o_definition = Arrays::removeKeys($o_definition, 'column');
                $rules        = $this->getDefinitionsObject()->get($o_element_definition->getColumn())->getSource();
                $rules        = array_merge($rules, $o_definition);

                $this->getDefinitionsObject()->get($o_element_definition->getColumn())->setSource($rules);
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
        $entry = static::newFromSource($this);

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
        $o_definitions = $this->o_definitions;

        foreach ($this->source as $key => $value) {
            if (!$o_definitions->keyExists($key)) {
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

        foreach ($this->o_definitions as $column => $o_definitions) {
            if ($o_definitions->getCliColumn()) {
                $return[$column] = $o_definitions->getCliColumn();
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
                     ->saveToLocalCache($this->getCacheKey())
                     ->saveToGlobalCache($this->getCacheKey())
                     ->source['status'] = $status;

                $this->changes[] = 'status';
                $this->setTableState();

                if ($auto_save and $this->isNotNew()) {
                    $this->updateMetaState();

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

        if ($this->getMetaEnabled()) {
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
            $created_on = PhoDateTime::new($created_on)->format(EnumDateFormat::mysql_datetime);
        }

        return $this->set($created_on, 'created_on');
    }


    /**
     * Returns the users_id that last modified this data entry
     *
     * @note Returns NULL if this class has no support for modified_by information or has not been written to disk yet
     *
     * @return int|null
     */
    public function getModifiedBy(): ?int
    {
        return $this->getTypesafe('int', 'modified_by');
    }


    /**
     * Returns the user object that last modified this data entry
     *
     * @note Returns NULL if this class has no support for modified_by information or has not been written to disk yet
     *
     * @return UserInterface|null
     */
    public function getModifiedByObject(): ?UserInterface
    {
        $modified_by = $this->getTypesafe('int', 'modified_by');

        if ($modified_by === null) {
            return null;
        }

        return new User($modified_by);
    }


    /**
     * Sets the modified_by field for this DataEntry object
     *
     * @param int|null $modified_by
     *
     * @return static
     */
    protected function setModifiedBy(?int $modified_by): static
    {
        return $this->set($modified_by, 'modified_by');
    }


    /**
     * Returns the modified on value in integer format
     *
     * @note Returns NULL if this class has no support for modified_on information or has not been written to disk yet
     *
     * @return string|int|null
     */
    public function getModifiedOn(): string|int|null
    {
        return $this->getTypesafe('string|int', 'modified_on');
    }


    /**
     * Returns the object that last modified this data entry
     *
     * @note Returns NULL if this class has no support for modified_by information or has not been written to disk yet
     * @return PhoDateTimeInterface|null
     */
    public function getModifiedOnObject(): ?PhoDateTimeInterface
    {
        $modified_on = $this->getTypesafe('string', 'modified_on');

        if ($modified_on === null) {
            return null;
        }

        return new PhoDateTime($modified_on);
    }


    /**
     * Sets the modified_on field for this DataEntry object
     *
     * @param string|int|null $modified_on
     *
     * @return static
     */
    protected function setModifiedOn(string|int|null $modified_on): static
    {
        if (is_int($modified_on)) {
            $modified_on = PhoDateTime::new($modified_on)->format(EnumDateFormat::mysql_datetime);
        }

        return $this->set($modified_on, 'modified_on');
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
        try {
            if ($this->saveBecauseModified($force)) {
                // Object must ALWAYS be validated before writing! Validate data and write it to the database.
                $this->validate($skip_validation)
                     ->write($force, $comments)
                     ->saveToLocalCache($this->getCacheKey())
                     ->saveToGlobalCache($this->getCacheKey())
                     ->setTableState();
            }

        } catch (SqlContstraintDuplicateEntryException $e) {
            // The unique identifier for the entry being added already exists
            throw new DataEntryExistsException(tr('Cannot save ":class" DataEntry, another entry with the unique column ":column" value ":value" for this object already exists', [
                ':class'  => static::class,
                ':column' => $e->getDataKey('column'),
                ':value'  => $e->getDataKey('value'),
            ]), $e);
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
            Log::dump('NOT SAVING IN DB, NOTHING CHANGED FOR "' . static::class . '" ID "' . $this->getLogId() . '"', 10, echo_header: false);
        }

        return false;
    }


    /**
     * Writes the data to the database
     *
     * @param bool        $force
     * @param string|null $comments
     *
     * @return static
     */
    protected function write(bool $force = false, ?string $comments = null): static
    {
        if ($this->readonly or $this->disabled) {
            throw new DataEntryReadonlyException(tr('Cannot save this ":name" object, the object is readonly or disabled', [
                ':name' => static::getEntryName(),
            ]));
        }

        // Debug this specific entry?
        if ($this->debug) {
            Log::dump('SAVING DATA ENTRY "' . static::class . '" WITH ID "' . $this->getLogId() . '"', 10, echo_header: false);
            sql($this->o_connector)->setDebug($this->debug);
        }

        // Ensure all linked NULL columns are resolved. This means that -for example- object_code and object_id, which
        // both are different pieces of data pointing to the same object, both have values. If one is NULL due to
        // $this->setVirtualData() resetting it, it will be resolved here. DataEntry->getSource() will resolve these
        // links automatically so just copy getSource() over the internal source.
        // Write the data and store the returned ID column
        $this->source = $this->getSource(false, false);
        $this->source = array_replace($this->source, SqlDataEntry::new(sql($this->o_connector), $this)
                                                                 ->setDebug($this->debug)
                                                                 ->setForce($force)
                                                                 ->write($comments));

        if ($this->debug) {
            Log::information('SAVED DATA ENTRY "' . static::class . '" WITH ID "' . $this->getLogId() . '"', 10);
        }

        // Write the list, if exists
        $this->list?->save();

        // Update the identifier to a databse ID based identifier
        if (empty($this->identifier)) {
            $this->identifier = ['id' => $this->getId()];
        }

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
     * Returns true if this DataEntry object is currently in the process of loading data
     *
     * @return bool
     */
    public function isLoading(): bool
    {
        return $this->is_loading;
    }


    /**
     * Returns true if this DataEntry object contains data loaded from the database
     *
     * @return bool
     */
    public function isLoaded(): bool
    {
        return $this->is_loaded;
    }


    /**
     * Returns an array containing all the DataEntry state variables
     *
     * @return array
     */
    public function getObjectState(): array
    {
        return [
            'id'                          => $this->getId(false),
            'is_saved'                    => $this->is_saved,
            'is_new'                      => $this->isNew(),
            'is_created'                  => $this->is_created,
            'is_modified'                 => $this->is_modified,
            'is_validated'                => $this->is_validated,
            'is_loading'                  => $this->is_loading,
            'is_loaded'                   => $this->is_loaded,
            'is_loaded_from_cache'        => $this->flags['is_loaded_from_cache'],
            'is_loaded_from_local_cache'  => $this->flags['is_loaded_from_local_cache'],
            'is_loaded_from_global_cache' => $this->flags['is_loaded_from_global_cache'],
            'is_initializing_source'      => $this->is_initializing_source,
            'is_initialized'              => $this->is_initialized,
            'previous_id'                 => $this->previous_id,
            'id_lower_limit'              => $this->id_lower_limit,
            'id_upper_limit'              => $this->id_upper_limit,
        ];
    }


    /**
     * Sets the state variables for this object
     *
     * @param array $state_array
     *
     * @return static
     */
    public function setObjectState(array $state_array): static
    {
        $this->is_saved                             = $state_array['is_saved'];
        $this->is_created                           = $state_array['is_created'];
        $this->is_modified                          = $state_array['is_modified'];
        $this->is_validated                         = $state_array['is_validated'];
        $this->is_loading                           = $state_array['is_loading'];
        $this->is_loaded                            = $state_array['is_loaded'];
        $this->flags['is_loaded_from_cache']        = $state_array['is_loaded_from_cache'];
        $this->flags['is_loaded_from_local_cache']  = $state_array['is_loaded_from_local_cache'];
        $this->flags['is_loaded_from_global_cache'] = $state_array['is_loaded_from_global_cache'];
        $this->is_initialized                       = $state_array['is_initialized'];
        $this->previous_id                          = $state_array['previous_id'];
        $this->id_lower_limit                       = $state_array['id_lower_limit'];
        $this->id_upper_limit                       = $state_array['id_upper_limit'];

        return $this;
    }


    /**
     * Only return columns that actually contain data
     *
     * @param bool $insert
     *
     * @return array
     */
    public function getSqlSource(bool $insert): array
    {
        $return = [];

        // Run over all definitions and generate a data column
        foreach ($this->o_definitions as $column => $o_definition) {
            if ($o_definition->getVirtual()) {
                // This is a virtual column, ignore it.
                continue;
            }

            if ($insert) {
                // We're about to insert, make sure to filter columns that aren't allowed for the first insert
                if (in_array($column, $this->columns_filter_on_insert)) {
                    continue;
                }
            }

            $column = $o_definition->getColumn();

            // TODO The next line should and AFAIK IS applied during validation, what is it doing here? DELETE!
            // Apply definition default
            $return[$column] = array_get_safe($this->source, $column) ?? $o_definition->getDefault();

            // Ensure values are scalar for the SQL query
            if (($return[$column] !== null) and !is_scalar($return[$column])) {
                if (is_enum($return[$column])) {
                    $return[$column] = $return[$column]->value;

                } elseif ($return[$column] instanceof Stringable) {
                    $return[$column] = (string) $return[$column];

                } else {
                    $return[$column] = Json::ensureEncoded($return[$column]);
                }
            }
        }

        if ($this->debug) {
            Log::dump('DATA SENT TO SQL FOR "' . static::class . '"', 10, echo_header: false);
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
                    Log::dump('VALIDATING "' . static::class . '" DATA ENTRY WITH ID "' . $this->getLogId() . '"', echo_header: false);
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
     * Updates the meta_state column with a random value
     *
     * @return static
     */
    protected function updateMetaState(): static
    {
        $this->setMetaState(Strings::getRandom(16));
        return $this;
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
        return $this->flags['is_loaded_from_cache'];
    }


    /**
     * Returns true if this DataEntry object was loaded from cache
     *
     * @return bool
     */
    public function isLoadedFromLocalCache(): bool
    {
        return $this->flags['is_loaded_from_local_cache'];
    }


    /**
     * Returns true if this DataEntry object was loaded from cache
     *
     * @return bool
     */
    public function isLoadedFromGlobalCache(): bool
    {
        return $this->flags['is_loaded_from_global_cache'];
    }


    /**
     * Sets the flag that this DataEntry object was loaded from local cache
     *
     * @return static
     */
    public function setIsLoadedFromLocalCache(): static
    {
        $this->flags['is_loaded_from_cache']       = true;
        $this->flags['is_loaded_from_local_cache'] = true;
        return $this;
    }


    /**
     * Returns true if this DataEntry object was loaded from cache
     *
     * @return static
     */
    public function setIsLoadedFromGlobalCache(): static
    {
        $this->flags['is_loaded_from_cache']        = true;
        $this->flags['is_loaded_from_global_cache'] = true;
        return $this;
    }


    /**
     * Sets the flag that this DataEntry object was loaded from global cache
     *
     * @return static
     */
    public function setIsLoaded(): static
    {
        $this->is_loaded = true;
        return $this;
    }


    /**
     * Returns an integer unique identifier for this DataEntry object
     *
     * @return int
     */
    public function getUniqueObjectIdentifier(): int
    {
        return spl_object_id($this);
    }
}
