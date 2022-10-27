<?php

namespace Phoundation\Data;

use DateTime;
use Phoundation\Core\Meta;
use Phoundation\Users\User;



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
trait DataEntry
{
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
     * DataEntry contructor
     *
     * @param int|null $id
     */
    public function __construct(?int $id)
    {
        if ($id) {
            $this->id = $id;
            $this->load($id);
        }
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
     * @return DataEntry
     */
    public function setStatus(?String $status): DataEntry
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
     * Returns the user that created this data entry
     *
     * @param User|null $user
     * @return DataEntry
     */
    public function setCreatedBy(?User $user): DataEntry
    {
        $this->created_by = $user->getid();
        return $this;
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
     * Returns the user that modified this data entry
     *
     * @param User|null $user
     * @return DataEntry
     */
    public function setModifiedBy(?User $user): DataEntry
    {
        $this->modified_by = $user->getid();
        return $this;
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
     * @param array $data
     * @return DataEntry
     */
    public function setData(array $data): DataEntry
    {
        $this->data = $data;
        return $this;
    }



    /**
     * Returns all data for this data entry at once with an array of information
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }


    /**
     * Sets the value for the specified data key
     *
     * @param string $key
     * @param mixed $value
     * @return DataEntry
     */
    protected function setDataValue(string $key, mixed $value): DataEntry
    {
        $this->data[$key] = $value;
        return $this;
    }



    /**
     * Returns the value for the specified data key
     *
     * @return mixed
     */
    protected function getDataValue(string $key): mixed
    {
        return isset_get($this->data[$key]);
    }



    /**
     * Will load the data from this data entry from database
     *
     * @param int $id
     * @return void
     */
    abstract function load(int $id): void;



    /**
     * Will save the data from this data entry to database
     *
     * @return void
     */
    abstract function save(): void;
}