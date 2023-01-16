<?php

namespace Phoundation\Data;

use DateTime;
use Phoundation\Cli\Cli;
use Phoundation\Core\Arrays;
use Phoundation\Core\Meta;
use Phoundation\Data\Exception\DataEntryNotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Accounts\Users\User;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\Html\Components\DataEntryForm;



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
abstract class DataEntry
{
    use DataEntryNameDescription;



    /**
     * The label name for this data entry, used in errors, etc
     *
     * @var string $entry_name
     */
    protected static string $entry_name;

    /**
     * The table name where this data entry is stored
     *
     * @var string $table
     */
    protected string $table;

    /**
     * The unique column identifier, next to id
     *
     * @var string $unique_column
     */
    protected string $unique_column = 'seo_name';

    /**
     * Default protected keys, keys that may not leave this object
     *
     * @var array|string[]
     */
    protected array $protected_keys = ['password', 'key'];

    /**
     * Contains the data for all information of this data entry
     *
     * @var array $data
     */
    protected array $data = [];

    /**
     * Meta information about the keys in this DataEntry
     *
     * @var array $keys
     */
    protected array $keys = [];

    /**
     * Meta information about how the keys will be displayed on forms
     *
     * @var array $form_keys
     */
    protected array $form_keys = [];

    /**
     * Columns that will NOT be inserted
     *
     * @var array $remove_columns_on_insert
     */
    protected array $remove_columns_on_insert = ['id'];

    /**
     * Columns that will NOT be updated
     *
     * @var array $remove_columns_on_update
     */
    protected array $remove_columns_on_update = ['meta_id', 'created_by', 'created_on'];

    /**
     * A list with optional linked other DataEntry objects
     *
     * @var DataList|null
     */
    protected ?DataList $list = null;



    /**
     * DataEntry class constructor
     *
     * @param string|int|null $identifier
     */
    public function __construct(string|int|null $identifier = null)
    {
        $this->setKeys();

        if ($identifier) {
            if (is_numeric($identifier)) {
                $this->data['id'] = $identifier;
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
     * Returns a User object
     *
     * @param string|int|null $identifier
     * @return static
     */
    public static function new(string|int|null $identifier = null): static
    {
        return new static($identifier);
    }



    /**
     * Returns a User object for the user owning the specified email address
     *
     * @note This method also accepts DataEntry objects, in which case it will simply return this object. This is to
     *       simplify "if this is not DataEntry object then this is new DataEntry object" into
     *       "PossibleDataEntryVariable is DataEntry::new(PossibleDataEntryVariable)"
     * @param DataEntry|string|int|null $identifier
     * @return static|null
     */
    public static function get(DataEntry|string|int $identifier = null): ?static
    {
        if (is_object($identifier)) {
            // This already is a DataEntry object, no need to create one
            return $identifier;
        }

        $entry = new static($identifier);

        if ($entry->getId()) {
            return $entry;
        }

        throw new DataEntryNotExistsException(tr('The ":label" entry ":identifier" does not exist', [
            ':label'      => self::$entry_name,
            ':identifier' => $identifier
        ]));
    }



    /**
     * Returns true if an entry with the specified identifier exists
     *
     * @param string|int|null $identifier
     * @return bool
     */
    public static function exists(string|int $identifier = null): bool
    {
        return (bool) static::new($identifier)->getId();
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
        return $this->getDataValue('id') . ' / ' . $this->getDataValue($this->unique_column);
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
     * @return static
     */
    public function setStatus(?String $status): static
    {
        return $this->setDataValue('status', $status);
    }



    /**
     * Returns the user that created this data entry
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
     * Returns the user that created this data entry
     *
     * @note Returns NULL if this class has no support for created_by information or has not been written to disk yet
     * @return User|null
     */
    public function getCreatedOn(): ?DateTime
    {
        $created_on = $this->getDataValue('created_by');

        if ($created_on === null) {
            return null;
        }

        return new DateTime($created_on);
    }



    /**
     * Returns the meta information for this class
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
     * Modify the data for this user with the new specified data
     *
     * @param array|null $data
     * @return static
     * @throws OutOfBoundsException
     */
    public function modify(?array $data): static
    {
        return $this->setData($data, true);
    }



    /**
     * Sets all data for this data entry at once with an array of information
     *
     * @param array|null $data
     * @param bool $modify
     * @return static
     */
    protected function setData(?array $data, bool $modify = false): static
    {
        if ($data === null) {
            // No data set
            return $this;
        }

        if (empty($this->keys)) {
            throw new OutOfBoundsException(tr('Data keys were not defined for this ":class" class', [
                ':class' => gettype($this)
            ]));
        }

        foreach ($data as $key => $value) {
            // These keys cannot be set through setData()
            switch ($key) {
                case 'id':
                    // no-break
                case 'created_by':
                    // no-break
                case 'created_on':
                    // no-break
                case 'status':
                    // no-break
                case 'meta_id':
                    // Go to next key
                    continue 2;

                case 'password':
                    if ($modify) {
                        continue 2;
                    }

                    // Passwords are always set directly
                    $this->setPasswordDirectly($value);
                    continue 2;

                case $this->unique_column:
                    // Store this data directly
                    if ($modify) {
                        continue 2;
                    }

                    $this->setDataValue($this->unique_column, $value);
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
    public function getProtectedKeys(): array
    {
        return $this->protected_keys;
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
        $this->protected_keys[] = $key;
        return $this;
    }



    /**
     * Returns all data for this data entry at once with an array of information
     *
     * @note This method filters out all keys defined in self::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     * @return array
     */
    public function getData(): array
    {
        return Arrays::remove($this->data, $this->protected_keys);
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
        $this->data['id']         = null;
        $this->data['created_by'] = null;
        $this->data['created_on'] = null;
        $this->data['meta_id']    = null;
        $this->data['status']     = null;

        if ($data === null) {
            // No data set
            return $this;
        }

        if (empty($this->keys)) {
            throw new OutOfBoundsException(tr('Data keys were not defined for this ":class" class', [
                ':class' => gettype($this)
            ]));
        }

        foreach ($data as $key => $value) {
            // Only these keys will be set through setMetaData()
            switch ($key) {
                case 'id':
                    // no-break
                case 'created_by':
                    // no-break
                case 'created_on':
                    // no-break
                case 'status':
                    // no-break
                case 'meta_id':
                    // Store the meta data
                    $this->data[$key] = $value;

                default:
                    // Go to next key
                    continue 2;
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
    protected function setDataValue(string $key, mixed $value): static
    {
        if ($key === 'meta_id') {
            throw new OutOfBoundsException(tr('The "meta_id" key cannot be changed'));
        }

        if ($value !== null) {
            $this->data[$key] = $value;
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
            throw new OutOfBoundsException(tr('Cannot *add* data value to key ":key", the key is not an array', [
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
        if (in_array($key, $this->protected_keys)) {
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
     * Returns the data to add for an SQL insert
     *
     * @return array
     */
    protected function getInsertColumns(): array
    {
        $return = Arrays::remove($this->data, $this->remove_columns_on_insert);
        $return = Arrays::keep($return, array_keys($this->keys));

        return $return;
    }



    /**
     * Returns the data to add for an SQL update
     *
     * @return array
     */
    protected function getUpdateColumns(): array
    {
        $return = Arrays::remove($this->data, $this->remove_columns_on_update);
        $return = Arrays::keep($return, array_keys($this->keys));

        return $return;
    }



    /**
     * Will save the data from this data entry to database
     *
     * @todo BUG: After the first insert, this object will be missing the meta information like created_by, created_on, meta_id. Reload?
     * @return static
     */
    public function save(): static
    {
        // Write the entry
        $this->data['id'] = sql()->write($this->table, $this->getInsertColumns(), $this->getUpdateColumns());

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
            ->setKeys($this->keys)
            ->setFormKeys($this->form_keys);
    }



    /**
     * Load all user data from database
     *
     * @param string|int $identifier
     * @return void
     */
    protected function load(string|int $identifier): void
    {
        if (is_integer($identifier)) {
            $data = sql()->get('SELECT * FROM `' . $this->table . '` WHERE `id`                           = :id'                     , [':id'                => $identifier]);
        } else {
            $data = sql()->get('SELECT * FROM `' . $this->table . '` WHERE `' . $this->unique_column . '` = :' . $this->unique_column, [$this->unique_column => $identifier]);
        }

        // Store all data in the object
        $this->setMetaData($data);
        $this->setData($data);
    }



    /**
     * Will set the available data keys for this data entry
     *
     * @return void
     */
    abstract protected function setKeys(): void;
}