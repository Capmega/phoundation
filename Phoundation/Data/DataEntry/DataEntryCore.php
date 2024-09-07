<?php

/**
 * Class DataEntryCore
 *
 * This class implements the DataEntry class
 *
 * @see \Phoundation\Data\Entry
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @see \Phoundation\Data\DataEntry\Definitions\Definitions
 * @see \Phoundation\Data\DataEntry\Definitions\Definition
 * @see \Phoundation\Data\DataEntry\DataIterator
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry;

use Exception;
use PDOStatement;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\User;
use Phoundation\Cli\CliColor;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Meta\Interfaces\MetaInterface;
use Phoundation\Core\Meta\Meta;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Enums\EnunmStateMismatchHandling;
use Phoundation\Data\DataEntry\Exception\DataEntryAlreadyExistsException;
use Phoundation\Data\DataEntry\Exception\DataEntryBadException;
use Phoundation\Data\DataEntry\Exception\DataEntryDeletedException;
use Phoundation\Data\DataEntry\Exception\DataEntryException;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntry\Exception\DataEntryReadonlyException;
use Phoundation\Data\DataEntry\Exception\DataEntryStateMismatchException;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryDefinitions;
use Phoundation\Data\EntryCore;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataConfigPath;
use Phoundation\Data\Traits\TraitDataDatabaseConnector;
use Phoundation\Data\Traits\TraitDataDebug;
use Phoundation\Data\Traits\TraitDataDisabled;
use Phoundation\Data\Traits\TraitDataInsertUpdate;
use Phoundation\Data\Traits\TraitDataMaxIdRetries;
use Phoundation\Data\Traits\TraitDataMetaColumns;
use Phoundation\Data\Traits\TraitDataMetaEnabled;
use Phoundation\Data\Traits\TraitDataRandomId;
use Phoundation\Data\Traits\TraitDataReadonly;
use Phoundation\Data\Traits\TraitDataRestrictions;
use Phoundation\Data\Traits\TraitMethodBuildManualQuery;
use Phoundation\Data\Validator\ArrayValidator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Exception\ValidatorException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Data\Validator\Validator;
use Phoundation\Databases\Connectors\Connector;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Sql\Exception\SqlTableDoesNotExistException;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Databases\Sql\SqlDataEntry;
use Phoundation\Date\DateTime;
use Phoundation\Date\Interfaces\DateTimeInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Utils\Utils;
use Phoundation\Web\Html\Components\Forms\DataEntryForm;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormInterface;
use Phoundation\Web\Html\Components\Input\InputText;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;
use Phoundation\Web\Html\Components\P;
use Phoundation\Web\Html\Enums\EnumInputType;
use Stringable;
use Throwable;


class DataEntryCore extends EntryCore implements DataEntryInterface
{
    use TraitDataConfigPath;
    use TraitDataDatabaseConnector;
    use TraitDataDebug;
    use TraitDataDisabled;
    use TraitDataEntryDefinitions;
    use TraitDataInsertUpdate;
    use TraitDataMaxIdRetries;
    use TraitDataMetaEnabled;
    use TraitDataMetaColumns {
        setMetaColumns as protected __setMetaColumns;
    }
    use TraitDataRandomId;
    use TraitDataReadonly;
    use TraitDataRestrictions;
    use TraitMethodBuildManualQuery;


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
     * @var EnunmStateMismatchHandling $state_mismatch_handling
     */
    protected EnunmStateMismatchHandling $state_mismatch_handling = EnunmStateMismatchHandling::ignore;

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
     * Global loading flag, when data is loaded into the object from a database
     *
     * @var bool $is_loading
     */
    protected bool $is_loading = false;

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
     * The identifier for this DataEntry object
     *
     * @var array|DataEntryInterface|string|int|null $identifier
     */
    protected array|DataEntryInterface|string|int|null $identifier;


    /**
     * DataEntry class constructor
     *
     * @param array|DataEntryInterface|string|int|null $identifier
     * @param bool|null                                $meta_enabled
     * @param bool                                     $init
     */
    public function __construct(array|DataEntryInterface|string|int|null $identifier = null, ?bool $meta_enabled = null, bool $init = true)
    {
        // Try to load the DataEntry from the database with the given identifier and column
        if (!isset($this->meta_columns)) {
            $this->meta_columns = static::getDefaultMetaColumns();
        }

        // Set up the columns for this object
        $this->setMetaDefinitions();
        $this->setDefinitions($this->definitions);

        // Store meta_enabled and identifier information
        $this->setMetaEnabled($meta_enabled)
             ->identifier = $identifier;

        if ($init) {
            $this->init(false, false);
        }
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
     * Initializes the DataEntry object using an array identifier
     *
     * @param bool $identifier_must_exist
     *
     * @return void
     */
    protected function initArrayIdentifier(bool $identifier_must_exist): void
    {
        $this->loadFromDatabase($this->identifier);

        if ($this->isNew()) {
            if ($identifier_must_exist) {
                // Throw the DataEntry does not exist exception
                throw DataEntryNotExistsException::new(tr('Cannot load ":class" class object, entry with identifiers ":identifiers" does not exist', [
                    ':class'       => static::getClassName(),
                    ':identifiers' => Json::encode($this->identifier),
                ]))->addData([
                    'class'       => static::class,
                    'identifiers' => Json::encode($this->identifier),
                ]);
            }
        }
    }


    /**
     * Initializes the DataEntry object
     *
     * @param bool $identifier_must_exist
     *
     * @return void
     */
    protected function initScalarIdentifier(bool $identifier_must_exist): void
    {
        $this->loadFromDatabase($this->identifier);

        if ($this->isNew()) {
            // So this entry does not exist in the database (or, SQL table doesn't exist either).
            // Does it perhaps have configuration load support and exist in configuration?
            if ($this->tryLoadFromConfiguration($this->identifier)) {
                // Yay, found it in configuration!
                return;
            }

            if ($identifier_must_exist) {
                throw DataEntryNotExistsException::new(tr('Cannot load ":class" class object, specified column ":column" with identifier ":identifier" does not exist', [
                    ':class'      => static::getClassName(),
                    ':column'     => static::determineColumn($this->identifier),
                    ':identifier' => $this->identifier,
                ]))->addData([
                    'class'      => static::getClassName(),
                    'column'     => static::determineColumn($this->identifier),
                    'identifier' => $this->identifier,
                ]);
            }
        }
    }


    /**
     * Initializes the DataEntry object
     *
     * @param bool $identifier_must_exist
     * @param bool $ignore_deleted
     *
     * @return static
     */
    public function init(bool $identifier_must_exist, bool $ignore_deleted): static
    {
        $this->columns_filter_on_insert = [static::getIdColumn()];
        $this->database_connector       =  static::getConnector();

        if ($this->identifier) {
            if (is_array($this->identifier)) {
                $this->initArrayIdentifier($identifier_must_exist);

            } else {
                if ($this->identifier instanceof DataEntryInterface) {
                    // Identifier is a DataEntry itself. Copy the DataEntry source directly and we're done!
                    $this->source = $this->identifier->getSource();

                } else {
                    // Load data from database
                    $this->initScalarIdentifier($identifier_must_exist);
                }
            }

            // This entry exists in the database, yay! Is it not deleted, though?
            if ($this->isDeleted()) {
                $this->processDeleted($ignore_deleted);
            }

        } else {
            // No identifier specified, this is a new DataEntry object
            $this->setMetaData();
        }

        return $this;
    }


    /**
     * Processes what to do if the found DataEntry is deleted
     *
     * @param bool $ignore_deleted
     *
     * @return void
     */
    protected function processDeleted(bool $ignore_deleted): void
    {
        // This entry has been deleted and can only be viewed by user with the "access_deleted" right
        if ($ignore_deleted or Session::getUserObject()->hasAllRights('access_deleted')) {
            return;
        }

        throw DataEntryDeletedException::new(tr('Cannot load ":class" class object, specified column ":column" with identifier ":identifier" is deleted', [
            ':class'      => static::getClassName(),
            ':column'     => static::determineColumn($this->identifier),
            ':identifier' => $this->identifier,
        ]))->addData([
            'class' => static::class,
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
     * Returns the default database connector to use for this table
     *
     * @return string
     */
    public static function getConnector(): string
    {
        return 'system';
    }


    /**
     * Returns a database connector for this DataEntry object
     *
     * @return ConnectorInterface
     */
    public static function getConnectorObject(): ConnectorInterface
    {
        return new Connector(static::getConnector());
    }


    /**
     * Returns either the specified valid column, or if empty, a default column
     *
     * @param DataEntryInterface|string|int|null $identifier
     *
     * @return string|null
     */
    protected static function determineColumn(DataEntryInterface|string|int|null $identifier): ?string
    {
        if (!$identifier) {
            // No identifier specified, this is just an empty DataEntry object
            return null;
        }

        // If the identifier is numeric, then the column MUST be the ID column
        if (is_numeric($identifier)) {
            return static::getIdColumn();
        }

        if ($identifier instanceof DataEntryInterface) {
            // Specified identifier is actually a data entry, we don't need a column
            return null;
        }

        // If its not numeric, then it must be a string, so it must have been the unique column
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
    public static function getDataEntryName(): string
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
     * @return void
     */
    protected function setMetaDefinitions(): void
    {
        $definitions = Definitions::new()->setTable(static::getTable());

        foreach ($this->meta_columns as $meta_column) {
            switch ($meta_column) {
                case 'id':
                    $definitions->add(Definition::new($this, 'id')
                                                ->setDisabled(true)
                                                ->setInputType(EnumInputType::dbid)
                                                ->addClasses('text-center')
                                                ->setSize(3)
                                                ->setCliAutoComplete(true)
                                                ->setTooltip(tr('This column contains the unique identifier for this object inside the database. It cannot be changed and is used to identify objects'))
                                                ->setLabel(tr('Database ID')));
                    break;

                case 'created_on':
                    $definitions->add(Definition::new($this, 'created_on')
                                                ->setDisabled(true)
                                                ->setInputType(EnumInputType::datetime_local)
                                                ->setDbNullInputType(EnumInputType::text)
                                                ->addClasses('text-center')
                                                ->setSize(3)
                                                ->setTooltip(tr('This column contains the exact date / time when this object was created'))
                                                ->setLabel(tr('Created on')));
                    break;

                case 'created_by':
                    $definitions->add(Definition::new($this, 'created_by')
                                                ->setDisabled(true)
                                                ->setSize(3)
                                                ->setLabel(tr('Created by'))
                                                ->setTooltip(tr('This column contains the user who created this object. Other users may have made further edits to this object, that information may be found in the object\'s meta data'))
                                                ->setContent(function (DefinitionInterface $definition, string $key, string $column_name, array $source) {
                                                    if ($this->isNew()) {
                                                        // This is a new DataEntry object, so the creator is.. Well, you!
                                                        return InputText::new()
                                                                        ->setDisabled(true)
                                                                        ->addClasses('text-center')
                                                                        ->setValue(Session::getUserObject()
                                                                                          ->getDisplayName());
                                                    } else {
                                                        // This is created by a user or by the system user
                                                        if ($source[$key]) {
                                                            return InputText::new()
                                                                            ->setDisabled(true)
                                                                            ->addClasses('text-center')
                                                                            ->setValue(User::load($source[$key])
                                                                                           ->getDisplayName());
                                                        } else {
                                                            return InputText::new()
                                                                            ->setDisabled(true)
                                                                            ->addClasses('text-center')
                                                                            ->setValue(tr('System'));
                                                        }
                                                    }
                                                }));
                    break;

                case 'meta_id':
                    $definitions->add(Definition::new($this, 'meta_id')
                                                ->setDisabled(true)
                                                ->setRender(false)
                                                ->setInputType(EnumInputType::dbid)
                                                ->setDbNullInputType(EnumInputType::text)
                                                ->setTooltip(tr('This column contains the identifier for this object\'s audit history'))
                                                ->setLabel(tr('Meta ID')));
                    break;

                case 'status':
                    $definitions->add(Definition::new($this, 'status')
                                                ->setOptional(true)
                                                ->setDisabled(true)
                                                ->setInputType(EnumInputType::text)
                                                ->setTooltip(tr('This column contains the current status of this object. A typical status is "Ok", but objects may also be "Deleted" or "In process", for example. Depending on their status, objects may be visible in tables, or not'))
//                                                ->setDisplayDefault(tr('Ok'))
                                                ->addClasses('text-center')
                                                ->setSize(3)
                                                ->setLabel(tr('Status')));
                    break;

                case 'meta_state':
                    $definitions->add(Definition::new($this, 'meta_state')
                                                ->setDisabled(true)
                                                ->setRender(false)
                                                ->setInputType(EnumInputType::text)
                                                ->setTooltip(tr('This column contains a cache identifier value for this object. This information usually is of no importance to normal users'))
                                                ->setLabel(tr('Meta state')));
                    break;

                default:
                    throw new OutOfBoundsException(tr('Unknown meta definition column ":column" specified', [
                        ':column' => $meta_column,
                    ]));
            }
        }

        $this->definitions = $definitions;
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
        return $this->getId() === null;
    }


    /**
     * Returns id for this database entry
     *
     * @return int|null
     */
    public function getId(): int|null
    {
        return $this->getTypesafe('int', $this->getIdColumn());
    }


    /**
     * Returns the unique identifier for this database entry, which will be the ID column if it does not have any
     *
     * @return string|float|int|null
     */
    public function getUniqueColumnValue(): string|float|int|null
    {
        $key = static::getUniqueColumn();

        if ($key) {
            // Test if this key was defined to begin with! If not, throw an exception to clearly explain what's wrong
            if ($this->definitions->keyExists($key)) {
                return $this->getTypesafe('string|float|int|null', static::getUniqueColumn());
            }

            throw new OutOfBoundsException(tr('Specified unique key ":key" is not defined for the ":class" class DataEntry object', [
                ':class' => get_class($this),
                ':key'   => $key,
            ]));
        }

        return $this->getId();
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
     * Returns the value for the specified data key
     *
     * @param string     $type
     * @param string     $column
     * @param mixed|null $default
     *
     * @return mixed
     */
    protected function getTypesafe(string $type, string $column, mixed $default = null): mixed
    {
        $this->checkProtected($column);

        return isset_get_typed($type, $this->source[$column], $default, false);
    }


    /**
     * Returns if the specified DataValue key can be visible outside this object or not
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
     * @param mixed $value
     * @param string $column
     * @param bool   $force
     *
     * @return static
     */
    public function set(mixed $value, string $column, bool $force = false): static
    {
        if ($this->debug) {
            Log::debug('TRY SET SOURCE VALUE FIELD "' . get_class($this) . '>' . $column . '" TO "' . Strings::force($value) . ' [' . gettype($value) . ']"', 10, echo_header: false);
        }

        // Only save values that are defined for this object
        if (!$this->definitions->keyExists($column)) {
            if ($this->debug) {
                Log::debug('NOT SETTING SOURCE VALUE FIELD "' . get_class($this) . '>' . $column . '" THE FIELD IS NOT DEFINED. THE FOLLOWING KEYS ARE DEFINED:', 10, echo_header: false);
                Log::printr($this->definitions->getSourceKeys());
            }

            if ($this->definitions->isEmpty()) {
                throw new DataEntryException(tr('The ":class" class has no columns defined yet', [
                    ':class' => get_class($this),
                ]));
            }

            throw new DataEntryException(tr('Not setting column ":column", it is not defined for the ":class" class', [
                ':column' => $column,
                ':class'  => get_class($this),
            ]));
        }

        // Skip all meta-columns like id, created_on, meta_id, etc., etc., etc...
        if (in_array($column, $this->meta_columns) and !$force) {
            if ($this->debug) {
                Log::debug('NOT SETTING SOURCE VALUE FIELD "' . get_class($this) . '>' . $column . '", IT IS META FIELD. USE FORCE TO MODIFY ANYWAY', 10, echo_header: false);
                Log::printr($this->definitions->getSourceKeys());
            }

            return $this;
        }

        // If the key is defined as readonly or disabled, it cannot be updated unless it's a new object or a
        // static value.
        $definition = $this->definitions->get($column);

        // If a column is ignored, we won't update anything
        if ($definition->getIgnored()) {
            Log::warning(tr('Not updating DataEntry object ":object" column ":column" because it has the "ignored" flag set', [
                ':column' => $column,
                ':object' => get_class($this),
            ]), 6);

            return $this;
        }

//        if ($this->is_applying and !$force) {
//            if ($definition->getReadonly() or $definition->getDisabled()) {
//                // The data is being set through DataEntry::apply() but this column is readonly
//                Log::debug('FIELD "' . $column . '" IS READONLY', 10);
//                return $this;
//            }
//        }
//        $default = $definition->getDefault();
//
//        // What to do if we don't have a value? Data should already have been validated, so we know the value is
//        // optional (would not have passed validation otherwise), so it either defaults or NULL
//        if ($value === null) {
//            //  By default, all columns with empty values will be pushed to NULL unless specified otherwise
//            $value = $default;
//        }
//        // Detect if setting this value constitutes a modification or not
//        if ((isset_get($this->source[$column]) === null) and ($value === $definition->getDefault())) {
//            // If the previous value was empty and the current value is the same as the default value then there was no
//            // modification, we simply applied a default value
//
//        } else {
//            // The DataEntry::is_modified can only be modified if it is not TRUE already. The DataEntry is considered
//            // modified if the user is modifying and the entry changed
//            if (!$this->is_modified and !$definition->getIgnoreModify()) {
//                $this->is_modified = (isset_get($this->source[$column]) !== $value);
//            }
//        }

        if (!$this->is_modified and !$definition->getIgnoreModify()) {
            $this->is_modified = (isset_get($this->source[$column]) !== $value);

            if ($this->debug) {
                Log::debug('MODIFIED FIELD "' . get_class($this) . '>' . $column . '" FROM "' . $this->source[$column] . '" [' . gettype(isset_get($this->source[$column])) . '] TO "' . $value . '" [' . gettype($value) . '], MARKED MODIFIED: ' . Strings::fromBoolean($this->is_modified), 10, echo_header: false);
            }
        }

        // Update the column value
        $this->changes[]       = $column;
        $this->source[$column] = $value;
        $this->is_validated    = false;

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
     * @param array|DataEntryInterface|string|int|null $identifier
     * @param bool                                     $meta_enabled
     * @param bool                                     $ignore_deleted
     *
     * @return static
     * @throws DataEntryDeletedException|DataEntryNotExistsException|SqlTableDoesNotExistException|OutOfBoundsException
     */
    public static function load(array|DataEntryInterface|string|int|null $identifier, bool $meta_enabled = false, bool $ignore_deleted = false): static
    {
        if (!$identifier) {
            // No identifier specified, an identifier is required to load a DataEntry
            throw DataEntryNotExistsException::new(tr('Cannot load ":class" class object, no identifier specified', [
                ':class' => static::getClassName(),
            ]))->addData([
                'class' => static::class,
            ]);
        }

        if (is_object($identifier)) {
            // This already is a DataEntry object, no need to create one. Validate that this is the same class
            if (!$identifier instanceof static) {
                throw new OutOfBoundsException(tr('Specified DataEntry identifier has the class ":has" but should have this object\'s class ":should"', [
                    ':has'    => get_class($identifier),
                    ':should' => static::class,
                ]));
            }

            return $identifier;
        }

        return static::new($identifier, $meta_enabled, false)->init(true, $ignore_deleted);
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
     * Returns a new DataEntry object from the specified array source
     *
     * @param DataEntryInterface|array $source
     *
     * @return static
     */
    public static function newFromSource(DataEntryInterface|array $source): static
    {
        if ($source instanceof DataEntryInterface) {
            if ($source instanceof static) {
                return clone $source;
            }

            throw new DataEntryBadException(
                tr('The specified source ":source" must be either an array or an instance of ":static"', [
                    ':static' => static::class,
                    ':source' => get_class($source),
                ])
            );
        }

        return static::new()->setSource($source);
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
     * @return string
     */
    public function getDisplayName(): string
    {
        $postfix = null;

        if ($this->getStatus() === 'deleted') {
            $postfix = ' ' . tr('[DELETED]');
        }

        return $this->getTypesafe('string', static::getUniqueColumn() ?? 'id') . $postfix;
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
//        $this->definitions->spliceByKey($at_key, 0, [$definition->getColumn() => $definition], $after);
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
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void {
        // Each DataEntry object should set its own definitions!
    }


    /**
     * Returns all data for this data entry at once with an array of information
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     *
     * @param bool $filter_meta If true, will also filter out the DataEntry meta-columns
     *
     * @return array
     */
    public function getSource(bool $filter_meta = false): array
    {
        if ($filter_meta) {
            // Remove meta-columns too
            return Arrays::removeKeys(Arrays::removeKeys($this->source, $this->meta_columns), $this->protected_columns);
        }

        return Arrays::removeKeys($this->source, $this->protected_columns);
    }


    /**
     * Loads the specified data into this DataEntry object
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null                                       $execute
     *
     * @return static
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static
    {
        $this->is_loading = true;

        // Load data with object init
        $this->setMetaData($source)->copyValuesToSource($source, false);

        $this->is_modified = true;
        $this->is_loading  = false;
        $this->is_saved    = false;

        return $this;
    }


    /**
     * Try to load this DataEntry from configuration instead of database
     *
     * @param array|string|int $identifier
     *
     * @return bool
     */
    protected function tryLoadFromConfiguration(array|string|int $identifier): bool
    {
        $path   = $this->getConfigurationPath();
        $column = static::determineColumn($identifier);

        // Can only load from configuration if the configuration path is available
        if ($path) {
            // Can only load from configuration using unique column, or NULL column
            if (($column === null) or ($column === static::getUniqueColumn())) {
                if (!static::idColumnIs('id')) {
                    throw new DataEntryException(tr('Cannot use configuration paths for DataEntry object ":class" that uses id column ":column" instead of "id"', [
                        ':class'  => static::class,
                        ':column' => static::getIdColumn(),
                    ]));
                }

                // See if there is a configuration entry in the specified path
                $source = $this->loadFromConfiguration($path, $identifier);

                if ($source) {
                    // Load the source in this object and make this object readonly
                    $this->setSource($source)
                         ->setReadonly(true);

                    return true;
                }
            }
        }

        return false;
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
        $entry = Config::getArray(Strings::ensureEndsWith($path, '.') . Config::escape($identifier), []);

        if (count($entry)) {
            // Found the entry in configuration! Make it a readonly DataEntry object
            $entry['id']     = -1;
            $entry['status'] = 'configuration';
            $entry['name']   = $identifier;

            // Create a DataTypeInterface object but since we can't write configuration, make it readonly!
            return $entry;
        }

        // Entry not available in configuration
        return null;
    }


    /**
     * Load all object data from the database table row
     *
     * @param array|string|int $identifier
     *
     * @return static
     */
    protected function loadFromDatabase(array|string|int $identifier): static
    {
        $this->is_loading = true;

        if (is_array($identifier)) {
            // Filter on multiple columns, multi column filter always pretends filtered column was id column
            static::buildManualQuery($identifier, $where, $joins, $group, $order, $execute);

            $column = null;

        } else {
            // For single column queries, determine the column we should use
            $column  = static::determineColumn($identifier);
            $where   = '`' . static::getTable() . '`.`' . $column . '` = :identifier';
            $execute = [':identifier' => $identifier];
        }

        $this->loadData($where, $execute);

        // Reset state
        $this->is_loading  = false;
        $this->is_saved    = false;
        $this->is_modified = false;

        // If this is a new entry, assign the identifier by default (NOT id though, since that is a DB identifier
        // meaning that it would HAVE to exist!)
        if ($this->isNew()) {
            $this->assignIdentifiers($identifier, $column);
        }

        return $this;
    }


    /**
     * Assigns the specified identifier / column (or identifier array) to the DataEntry source
     *
     * @param array|string|int $identifier
     * @param string|null      $column
     *
     * @return void
     */
    protected function assignIdentifiers(array|string|int $identifier, ?string $column): void
    {
        if (is_array($identifier)) {
            foreach ($identifier as $column => $value) {
                if ($column !== static::getIdColumn()) {
                    $this->setColumnValueWithObjectSetter($column, $value, false, $this->definitions->get($column));
                }
            }

        } elseif ($column !== static::getIdColumn()) {
            $this->setColumnValueWithObjectSetter($column, $identifier, false, $this->definitions->get($column));
        }
    }


    /**
     * Executes the query and loads the data into the DataEntry
     *
     * @param string $where
     * @param array  $execute
     *
     * @return void
     */
    protected function loadData(string $where, array $execute): void
    {
        try {
            // Get the data using the query builder
            $data = $this->getQueryBuilderObject()
                         ->setMetaEnabled($this->meta_enabled)
                         ->setDatabaseConnectorName($this->database_connector)
                         ->addSelect('`' . static::getTable() . '`.*')
                         ->addWhere($where, $execute)
                         ->get();

            if ($data) {
                // If data was found, store all data in the object
                $this->setMetaData($data)
                     ->copyValuesToSource($data, false);
            }

        } catch (SqlTableDoesNotExistException $e) {
            // The table for this object does not exist. This means that we're missing an init, perhaps, or maybe
            // even the entire databese doesn't exist? Maybe we're in init or sync mode? Allow the system to continue
            // to check if this entry perhaps is configured, so we can continue
            if (!Core::inInitState()) {
                throw $e;
            }

            // We're in system init state, act as if the entry simply doesn't exist
        }
    }


    /**
     * Returns the query builder for this data entry
     *
     * @return QueryBuilderInterface
     */
    public function getQueryBuilderObject(): QueryBuilderInterface
    {
        if (!$this->query_builder) {
            $this->query_builder = QueryBuilder::new($this);
        }

        return $this->query_builder;
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
        if ($this->definitions->isEmpty()) {
            throw new OutOfBoundsException(tr('Data keys were not defined for this ":class" class', [
                ':class' => get_class($this),
            ]));
        }

        // Setting columns will make $this->is_validated false, so store the current value;
        $validated = $this->is_validated;

        foreach ($this->definitions as $key => $definition) {
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

            if (array_key_exists($key, $source)) {
                $value = $source[$key];

            } else {
                // This key doesn't exist at all in the data entry, default it
                if ($this->isNew()) {
                    $value = $definition->getInitialDefault();

                } else {
                    $value = $definition->getDefault();
                }

                // No default available?
                if ($value === null) {
                    // This value wasn't specified in the source, there are no default values, so continue
                    continue;
                }
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

            $this->setColumnValueWithObjectSetter($key, $value, $directly, $definition);
        }

        if ($this->getId() < 0) {
            $this->readonly = true;
        }

        $this->is_validated = $validated;
        $this->previous_id  = $this->getId();

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
        if (!static::definitionsHaveMethods() or $directly or $this->definitions->get($column)?->getDirectUpdate()) {
            // Store data directly, bypassing the set method for this key
            $this->set($value, $column);

        } else {
            // Store this data through the set method to ensure datatype and filtering is done correctly
            $method = $this->convertColumnToSetMethod($column);

            if (!$definition->inputTypeIsScalar()) {
                // This input type is not scalar and as such has been stored as a JSON array
                $value = Json::ensureDecoded($value);
            }

            if ($this->debug) {
                Log::debug('ABOUT TO SET SOURCE KEY "' . $column . '" WITH METHOD: ' . $method . ' (' . (method_exists($this, $method) ? 'exists' : 'NOT exists') . ') TO VALUE "' . Strings::log($value) . '"', 10, echo_header: false);
            }

            // Only apply if a method exists for this variable
            if (!method_exists($this, $method)) {
                // There is no method accepting this data. This might be because it is a virtual column that gets
                // resolved at validation time. Check this with the definitions object
                if ($this->definitions->get($column)?->getVirtual()) {
                    return;
                }

                throw new OutOfBoundsException(tr('Cannot set source key ":key" because the class has no linked method ":method" defined in DataEntry class ":class"', [
                    ':key'    => $column,
                    ':method' => $method,
                    ':class'  => get_class($this),
                ]));
            }

            $this->$method($value);
        }
    }


    /**
     * Returns true if the definitions of this DataEntry have their own methods
     *
     * @return bool
     */
    public static function definitionsHaveMethods(): bool
    {
        return true;
    }


    /**
     * Rewrite the specified variable into the set method for that variable
     *
     * @param string $column
     *
     * @return string
     */
    protected function convertColumnToSetMethod(string $column): string
    {
        // Convert underscore to camelcase
        // Remove the prefix from the column
        if ($this->definitions->getColumnPrefix()) {
            $column = Strings::from($column, $this->definitions->getColumnPrefix());
        }

        $return = explode('_', $column);
        $return = array_map('ucfirst', $return);
        $return = implode('', $return);

        return 'set' . ucfirst($return);
    }


    /**
     * Returns the column prefix string
     *
     * @return ?string
     */
    public function getColumnPrefix(): ?string
    {
        return $this->definitions->getColumnPrefix();
    }


    /**
     * Sets all meta-data for this data entry at once with an array of information
     *
     * @param ?array $data
     *
     * @return static
     * @throws OutOfBoundsException
     */
    protected function setMetaData(?array $data = null): static
    {
        if ($this->definitions->isEmpty()) {
            throw new OutOfBoundsException(tr('Data keys were not defined for this ":class" class', [
                ':class' => get_class($this),
            ]));
        }

        if ($data === null) {
            // No data specified, all columns should be null
            $this->source = Arrays::setKeys($this->meta_columns, null, $this->source);

        } else {
            // Reset meta columns
            foreach ($this->meta_columns as $column) {
                $this->source[$column] = isset_get($data[$column]);
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
                    // There is body text, add the header and body to the return text
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
     * Modify the data for this object with the new specified data
     *
     * @param bool                           $clear_source
     * @param ValidatorInterface|array|null &$source
     *
     * @return static
     */
    public function apply(bool $clear_source = true, ValidatorInterface|array|null &$source = null): static
    {
        if ($this->readonly or $this->disabled) {
            throw new DataEntryReadonlyException(tr('Cannot apply changes to ":name" object, the object is readonly or disabled', [
                ':name' => static::getDataEntryName(),
            ]));
        }

        return $this->checkReadonly('apply')
                    ->doApply($clear_source, $source, false);
    }


    /**
     * Modify the data for this object with the new specified data
     *
     * @param bool                           $clear_source
     * @param ValidatorInterface|array|null &$source
     * @param bool                           $force
     *
     * @return static
     */
    protected function doApply(bool $clear_source, ValidatorInterface|array|null &$source, bool $force): static
    {
        // Are we allowed to create or modify this DataEntry?
        if ($this->getId()) {
            if (!$this->allow_modify) {
                // auto modify is not allowed, sorry!
                throw new ValidationFailedException(tr('Cannot modify :entry', [
                    ':entry' => static::getDataEntryName(),
                ]));
            }

        } else {
            if (!$this->allow_create) {
                // auto create is not allowed, sorry!
                throw new ValidationFailedException(tr('Cannot create new :entry', [
                    ':entry' => static::getDataEntryName(),
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
        $data_source = Validator::pick($source);
        $data_source->setSourceObjectClass(static::class);

        if ($this->debug) {
            Log::debug('APPLY ' . static::getDataEntryName() . ' (' . get_class($this) . ')', 10, echo_header: false);
            Log::debug('CURRENT DATA', 10         , echo_header: false);
            Log::vardump($this->source            , echo_header: false);
            Log::debug('SOURCE'      , 10         , echo_header: false);
            Log::vardump($data_source             , echo_header: false);
            Log::debug('SOURCE DATA' , 10         , echo_header: false);
            Log::vardump($data_source->getSource(), echo_header: false);
        }

        // Get the source array from the validator into the DataEntry object
        if ($force) {
            // Force was used, but the object will now be in readonly mode, so we can save failed data
            // Validate data and copy data into the source array
            $data_source = $this->doNotValidate($data_source, $clear_source);
            $this->copyValuesToSource($data_source, true, true);

        } else {
            // Validate data and copy data into the source array
            $data_source = $this->validateSourceData($data_source, $clear_source);

            if ($this->debug) {
                Log::debug('APPLYING DATA', 10, echo_header: false);
                Log::vardump($data_source, echo_header: false);
            }

            // Ensure DataEntry Meta state is okay, then generate the diff data and copy data array to internal data
            $this->validateMetaState($data_source)
                 ->createDiff($data_source)
                 ->copyValuesToSource($data_source, true);
        }

        $this->is_applying = false;

        if ($this->debug) {
            Log::debug('DATA AFTER APPLY', 10, echo_header: false);
            Log::vardump($this->source, echo_header: false);
        }

        return $this;
    }


    /**
     * Extracts the data from the validator without validating
     *
     * @param ValidatorInterface $validator
     * @param bool               $clear_source
     *
     * @return array
     */
    protected function doNotValidate(ValidatorInterface $validator, bool $clear_source): array
    {
        $return = [];
        $source = $validator->getSource();
        $prefix = $this->definitions->getColumnPrefix();

        foreach ($source as $key => $value) {
            $return[Strings::from($key, $prefix)] = $value;

            if ($clear_source) {
                $validator->removeSourceKey($key);
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
     * @param bool               $clear_source
     *
     * @return array
     */
    protected function validateSourceData(ValidatorInterface $validator, bool $clear_source): array
    {
        if (!$this->validate) {
            // This data entry won't validate data, just continue.
            return $validator->getSource();
        }

        // Set ID so that the array validator can do unique lookups, etc.
        // Tell the validator what table this DataEntry is using and get the column prefix so that the validator knows
        // what columns to select
        $validator->setId($this->getId())
                  ->setDataEntry($this)
                  ->setMetaColumns($this->getMetaColumns())
                  ->setTable(static::getTable());

        $prefix = $this->definitions->getColumnPrefix();

        // Go over each column and let the column definition do the validation since it knows the specs
        foreach ($this->definitions as $column => $definition) {
            if ($definition->isMeta()) {
                // This column is metadata and should not be modified or validated, plain ignore it.
                continue;
            }

            if ($this->debug) {
                Log::debug('VALIDATING COLUMN "' . get_class($this) . '>' . $column . '"', echo_header: false);
            }

            try {
                // Execute the validations for this single definition
                $definition->validate($validator, $prefix);

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
            $source             = $validator->validate($clear_source);
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
     * @return array
     */
    public function get(string $key): mixed
    {
        if ($this->definitions->keyExists($key)) {
            return isset_get($this->source[$key]);
        }

        throw new OutOfBoundsException(tr('Specified key ":key" is not defined for the ":class" class DataEntry object', [
            ':class' => get_class($this),
            ':key'   => $key,
        ]));
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

                    if (isset_get($this->source[$key]) != isset_get($data[$key])) {
                        // If both records were empty (from NULL to 0 for example) then don't register
                        if ($this->source[$key] or $data[$key]) {
                            $diff['from'][$key] = (string) $this->source[$key];
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
     * @param ValidatorInterface|array|null $data
     *
     * @return static
     */
    protected function validateMetaState(ValidatorInterface|array|null $data = null): static
    {
        // Check entry meta-state. If this entry was modified in the meantime, can we update?
        if ($this->getMetaState()) {
            if (isset_get($data['meta_state']) !== $this->getMetaState()) {
                // State mismatch! This means that somebody else updated this record while we were modifying it.
                switch ($this->state_mismatch_handling) {
                    case EnunmStateMismatchHandling::ignore:
                        Log::warning(tr('Ignoring database and user meta-state mismatch for ":type" type record with ID ":id" and old state ":old" and new state ":new"', [
                            ':id'   => $this->getId(),
                            ':type' => static::getDataEntryName(),
                            ':old'  => $this->getMetaState(),
                            ':new'  => $data['meta_state'],
                        ]));
                        break;

                    case EnunmStateMismatchHandling::allow_override:
                        // Okay, so the state did NOT match, and we WILL throw the state mismatch exception, BUT we WILL
                        // update the state data so that a second attempt can succeed
                        $data['meta_state'] = $this->getMetaState();
                        break;

                    case EnunmStateMismatchHandling::restrict:
                        throw new DataEntryStateMismatchException(tr('Database and user meta-state for ":type" type record with ID ":id" do not match', [
                            ':id'   => $this->getId(),
                            ':type' => static::getDataEntryName(),
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
    public static function find(array $identifiers, bool $meta_enabled = false, bool $ignore_deleted = false, bool $exception = true, string $filter = 'AND'): ?static
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
//            ->setDatabaseConnectorName()
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
     * Returns a DataEntry object matching the specified identifier that MUST exist in the database, or NULL if NULL
     * identifier was specified
     *
     * @param array|DataEntryInterface|string|int|null $identifier
     * @param bool                                     $meta_enabled
     * @param bool                                     $ignore_deleted
     *
     * @return static|null
     */
    public static function loadOrNull(array|DataEntryInterface|string|int|null $identifier, bool $meta_enabled = false, bool $ignore_deleted = false): ?static
    {
        if ($identifier === null) {
            return null;
        }

        return static::load($identifier, $meta_enabled, $ignore_deleted);
    }


    /**
     * Returns a random DataEntry object
     *
     * @param bool $meta_enabled
     *
     * @return static|null
     */
    public static function loadRandom(bool $meta_enabled = false): ?static
    {
        $identifier = sql(static::getConnector())->getInteger('SELECT   `id` 
                                                                                FROM     `' . static::getTable() . '` 
                                                                                ORDER BY RAND() 
                                                                                LIMIT    1;');

        if ($identifier) {
            return static::load($identifier, $meta_enabled);
        }

        throw new OutOfBoundsException(tr('Cannot select random record for table ":table", no records found', [
            ':table' => static::getTable(),
        ]));
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

        return sql(static::getConnector())->get('SELECT `id`, `status` 
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
     * @throws OutOfBoundsException|DataEntryNotExistsException|DataEntryDeletedException
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
        $this->source = array_merge($this->source, ($strip_meta ? Arrays::removeKeys($data_entry->getSource(), static::getDefaultMetaColumns()) : $data_entry->getSource()));

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
        $this->source = array_merge(($strip_meta ? Arrays::removeKeys($data_entry->getSource(), static::getDefaultMetaColumns()) : $data_entry->getSource()), $this->source);

        $data_entry->getDefinitionsObject()
                   ->removeKeys($strip_meta ? static::getDefaultMetaColumns() : null)
                   ->appendSource($this->definitions)
                   ->setDataEntry($this);

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
        $this->source = array_merge($this->source, ($strip_meta ? Arrays::removeKeys($data_entry->getSource(), static::getDefaultMetaColumns()) : $data_entry->getSource()));

        try {
            $this->definitions->spliceByKey($at_key, 0, $data_entry->getDefinitionsObject()
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
        $element_definition                             = $value->getDefinition()->setContent($value->render());
        $this->source[$element_definition->getColumn()] = null;

        try {
            $this->definitions->spliceByKey($at_key, 0, [$element_definition->getColumn() => $element_definition], $after);

        } catch (OutOfBoundsException $e) {
            throw new OutOfBoundsException(tr('Failed to inject element at key ":key", the key does not exist', [
                ':key' => $at_key,
            ]), $e);
        }

        if ($definition) {
            // Apply specified definitions as well
            if ($definition instanceof DefinitionInterface) {
                $definition->setColumn($element_definition->getColumn());
                $this->definitions->get($element_definition->getColumn())->setSource($definition->getSource());

            } else {
                // Merge the specified definitions over the existing one
                $definition = Arrays::removeKeys($definition, 'column');
                $rules      = $this->definitions->get($element_definition->getColumn())->getSource();
                $rules      = array_merge($rules, $definition);

                $this->definitions->get($element_definition->getColumn())->setSource($rules);
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
        $entry = clone $this;

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
        return $this->readonly or $this->isConfigured();
    }


    /**
     * Returns true if this object was read from configuration
     *
     * Objects loaded from configuration (for the moment) cannot be saved and will return true.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->getId() < 0;
    }


    /**
     * Returns true if this entry is new or WAS new before it was written
     *
     * @return bool
     */
    public function isCreated(): bool
    {
        return !$this->isNew() and ($this->previous_id === null);
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
            return isset_get($this->source[static::getUniqueColumn()]);
        }

        return (string) $this->getId();
    }


    /**
     * Returns id for this database entry that can be used in logs
     *
     * @return string
     */
    public function getLogId(): string
    {
        return $this->getTypesafe('int', static::getIdColumn()) . ' / ' . (static::getUniqueColumn() ? $this->getTypesafe('string', static::getUniqueColumn()) : '-');
    }


    /**
     * Returns true if this object has the specified status
     *
     * @param string $status
     *
     * @return bool
     */
    public function hasStatus(string $status): bool
    {
        return $status === $this->getTypesafe('string', 'status');
    }


    /**
     * Delete the specified entries
     *
     * @param string|null $comments
     *
     * @return static
     */
    public function delete(?string $comments = null): static
    {
        if (static::getUniqueColumn()) {
            if ($this->isModified()) {
                throw new OutOfBoundsException(tr('Cannot delete DataEntry ":class" with identifier ":identifier" because it has modifications that have not yet been saved', [
                    ':class'      => static::class,
                    ':identifier' => $this->getUniqueColumnValue(),
                ]));
            }

            // When deleting an entry, the unique column goes to NULL
            $this->source[static::getUniqueColumn()] = null;
            $this->save(true, true);
        }

        return $this->setStatus('deleted', $comments);
    }


    /**
     * Set the status for this database entry
     *
     * @param string|null $status
     * @param string|null $comments
     *
     * @return static
     */
    public function setStatus(?string $status, ?string $comments = null): static
    {
        $this->checkReadonly('set-status "' . $status . '"');

        if ($this->getId()) {
            (new SqlDataEntry(sql($this->database_connector), $this))->setStatus($status, $comments);
        }

        $this->source['status'] = $status;

        return $this;
    }


    /**
     * Undelete the specified entries
     *
     * @param string|null $comments
     *
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
        $this->checkReadonly('erase')
             ->getMetaObject()
                ->erase();

        sql($this->database_connector)->erase(static::getTable(), ['id' => $this->getId()]);

        return $this;
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
                ':id' => $this->getId(),
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
     * @return $this
     */
    public function addMetaAction(?string $action, ?string $comments, Stringable|array|string|null $data): static
    {
        $this->getMetaObject()->action($action, $comments, $data);

        return $this;
    }


    /**
     * Sets the column prefix string
     *
     * @param string|null $prefix
     *
     * @return static
     */
    public function setColumnPrefix(?string $prefix): static
    {
        $this->definitions->setColumnPrefix($prefix);

        return $this;
    }


    /**
     * Returns the user object that created this data entry
     *
     * @note Returns NULL if this class has no support for created_by information or has not been written to disk yet
     *
     * @return UserInterface|null
     */
    public function getCreatedByUserObject(): ?UserInterface
    {
        $created_by = $this->getTypesafe('int', 'created_by');

        if ($created_by === null) {
            return null;
        }

        return new User($created_by);
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
     * Returns the object that created this data entry
     *
     * @note Returns NULL if this class has no support for created_by information or has not been written to disk yet
     * @return DateTimeInterface|null
     */
    public function getCreatedOnDateTimeObject(): ?DateTimeInterface
    {
        $created_on = $this->getTypesafe('string', 'created_on');

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
                ':class' => $this->getDataEntryName(),
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
     * @param bool                          $clear_source
     * @param ValidatorInterface|array|null $source
     *
     * @return static
     */
    public function forceApply(bool $clear_source = true, ValidatorInterface|array|null &$source = null): static
    {
        return $this->doApply($clear_source, $source, true);
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
            // Object must ALWAYS be validated before writing! Validate data and write it to database.
            return $this->validate($skip_validation)->write($comments);
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
        $this->checkReadonly('save');

        if (!$this->is_modified and !$force) {
            // Nothing changed, no reason to save
            if ($this->debug) {
                Log::debug('NOT SAVING IN DB, NOTHING CHANGED FOR "' . get_class($this) . '" ID "' . $this->getId() . '"', 10, echo_header: false);
            }

            return false;
        }

        return true;
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
                ':name' => static::getDataEntryName(),
            ]));
        }

        // Debug this specific entry?
        if ($this->debug) {
            Log::debug('SAVING "' . get_class($this) . '" DATA ENTRY WITH ID "' . $this->getId() . '"', 10, echo_header: false);
            $debug = sql($this->database_connector)->getDebug();
            sql($this->database_connector)->setDebug($this->debug);
        }

        // Write the data and store the returned ID column
        $this->source[static::getIdColumn()] = (new SqlDataEntry(sql($this->database_connector), $this))
            ->write($comments, $this->diff);

        if ($this->debug) {
            Log::information('SAVED DATA ENTRY WITH ID "' . $this->getId() . '"', 10);
        }

        // Return debug mode if required
        if (isset($debug)) {
            sql($this->database_connector)->setDebug($debug);
        }

        // Write the list, if exists
        $this->list?->save();

        // Done!
        $this->previous_id = $this->getId();
        $this->is_modified = false;
        $this->is_saved    = true;

        return $this;
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

            } else {
                // We're about to update
                if ($definition->getReadonly() or $definition->getDisabled()) {
                    // Don't update readonly or disabled columns, only meta-columns should pass
                    if (!$definition->isMeta()) {
                        // For updates we do require meta data!
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
            $return[$column] = isset_get($this->source[$column]) ?? $definition->getDefault();

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

            if ($prefix) {
                $return[$column] = $prefix . $return[$column];
            }

            $postfix = $definition->getPostfix();
            if ($postfix) {
                $return[$column] .= $postfix;
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
                    Log::debug('VALIDATING "' . get_class($this) . '" DATA ENTRY WITH ID "' . $this->getId() . '"', 10, echo_header: false);
                }

                // Gather data that required validation
                $source = $this->getDataForValidation();

                // Merge the validated data over the current data
                $this->source = array_merge($this->source, $this->validateSourceData(ArrayValidator::new($source), true));
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
        return Arrays::removeKeys($this->source, [
            'id',
            'created_by',
            'created_on',
            'status',
            'meta_id',
            'meta_state',
        ]);
    }


    /**
     * Creates and returns an HTML for the data in this entry
     *
     * @return DataEntryFormInterface
     */
    public function getHtmlDataEntryFormObject(): DataEntryFormInterface
    {
        return DataEntryForm::new()
                            ->setDataEntry($this)
                            ->setSource($this->source)
                            ->setReadonly($this->readonly)
                            ->setDisabled($this->disabled)
                            ->setDefinitionsObject($this->definitions);
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
        if (!$this->definitions->keyExists($column)) {
            throw new OutOfBoundsException(tr('Specified column name ":column" does not exist', [
                ':column' => $column,
            ]));
        }

        $alt = $this->definitions->get($column)->getCliColumn();
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
}
