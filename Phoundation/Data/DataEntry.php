<?php

namespace Phoundation\Data;

use DateTime;
use Phoundation\Core\Arrays;
use Phoundation\Core\Meta;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Accounts\Users\User;



/**
 * DataEntry trait
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
    /**
     * Default protected keys, keys that may not leave this object
     *
     * @var array|string[]
     */
    protected array $protected_keys = ['password', 'key'];

    /**
     * Contains the database id for this entry
     *
     * @var int|null
     */
    protected ?int $id = null;

    /**
     * The user who created this entry
     *
     * @var int|null $created_by
     */
    protected ?int $created_by = null;

    /**
     * The user who last modified this entry
     *
     * @var int|null $modified_by
     */
    protected ?int $modified_by = null;

    /**
     * The datetime when this entry was created
     *
     * @var DateTime|null $created_on
     */
    protected ?DateTime $created_on = null;

    /**
     * The datetime when this entry was modified
     *
     * @var DateTime|null $modified_on
     */
    protected ?DateTime $modified_on = null;

    /**
     * Identifier of the meta-object for this entry
     *
     * @var int|null $meta_id
     */
    protected ?int $meta_id = null;

    /**
     * Contains the status for this entry
     *
     * @var string|null
     */
    protected ?string $status = null;

    /**
     * Contains the data for all information of this data entry
     *
     * @var array $data
     */
    protected array $data = [];

    /**
     * Key definitions for the data for this entry
     *
     * @var array $keys
     */
    protected array $keys = [];



    /**
     * DataEntry class constructor
     *
     * @param string|int|null $identifier
     */
    protected function construct(string|int|null $identifier = null) {
        $this->setKeys();

        if ($identifier) {
            $this->id = $identifier;
            $this->load($identifier);
        }
    }



    /**
     * Returns a User object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }



    /**
     * Returns a User object for the user owning the specified email address
     *
     * @param string|int|null $identifier
     * @return static
     */
    public static function get(string|int $identifier = null): static
    {
        return new static($identifier);
    }



    /**
     * Returns true if this is a new entry that hasn't been written to the database yet
     *
     * @return bool
     */
    public function isNew(): bool
    {
        return !$this->id;
    }



    /**
     * Returns id for this database entry
     *
     * @return ?int
     */
    public function getId(): ?int
    {
        return $this->id;
    }



    /**
     * Returns status for this database entry
     *
     * @return ?String
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }



    /**
     * Set the status for this database entry
     *
     * @param string|null $status
     * @return static
     */
    public function setStatus(?String $status): static
    {
        $this->status = $status;
        return $this;
    }



    /**
     * Returns the user that created this data entry
     *
     * @note Returns NULL if this class has no support for created_by information or has not been written to disk yet
     * @return User|null
     */
    public function getCreatedBy(): ?User
    {
        return new User($this->created_by);
    }



    /**
     * Returns the user that modified this data entry
     *
     * @note Returns NULL if this class was not modified yet, or has no support for modified_by information
     * @return User|null
     */
    public function getModifiedBy(): ?User
    {
        return new User($this->modified_by);
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
        if ($this->meta_id === null) {
            return null;
        }

        return new Meta($this->meta_id);
    }



    /**
     * Sets all data for this data entry at once with an array of information
     *
     * @param array|null $details
     * @return static
     * @throws OutOfBoundsException
     */
    public function setData(?array $details): static
    {
        if ($details === null) {
            // No data set
            return $this;
        }

        if (empty($this->keys)) {
            throw new OutOfBoundsException(tr('Data keys were not defined for this ":class" class', [
                ':class' => gettype($this)
            ]));
        }

        foreach ($details as $key => $value) {
            // These keys cannot be set through setData()
            switch ($key) {
                case 'id':
                    // no-break
                case 'created_by':
                // no-break
                case 'created_on':
                // no-break
                case 'modified_by':
                // no-break
                case 'modified_on':
                // no-break
                case 'status':
                // no-break
                case 'meta_id':
                    // Go to next key
                    continue 2;
            }

            // Store this data through the methods to ensure datatype and filtering is done correctly
            $method = $this->convertVariableToSetMethod($key);
            $this->$method($value);
        }

        $this->data = $details;
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
    protected function setMetaData(?array $data): static
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
            // Only these keys will be set through setMetaData()
            switch ($key) {
                case 'id':
                    // no-break
                case 'created_by':
                    // no-break
                case 'created_on':
                    // no-break
                case 'modified_by':
                    // no-break
                case 'modified_on':
                    // no-break
                case 'status':
                    // no-break
                case 'meta_id':
                    // Store the meta data
                    $this->$key = $value;

                default:
                    // Go to next key
                    continue 2;
            }
        }

        $this->data = $data;
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
        $this->data[$key] = $value;
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
        $return = ucfirst($return);

        return $return;
    }



    /**
     * Returns the data to add for an SQL insert
     *
     * @return array
     */
    protected function getInsertColumns(): array
    {
        return Arrays::remove($this->data, [
            'id', 'modified_by', 'modified_on'
        ]);
    }



    /**
     * Returns the data to add for an SQL update
     *
     * @return array
     */
    protected function getUpdateColumns(): array
    {
        return Arrays::remove($this->data, [
            'meta_id', 'created_by', 'created_on', 'modified_by', 'modified_on'
        ]);
    }



    /**
     * Will set the available data keys for this data entry
     *
     * @return void
     */
    abstract function setKeys(): void;



    /**
     * Will load the data from this data entry from database
     *
     * @param int $identifier
     * @return void
     */
    abstract function load(int $identifier): void;



    /**
     * Will save the data from this data entry to database
     *
     * @return static
     */
    abstract function save(): static;
}