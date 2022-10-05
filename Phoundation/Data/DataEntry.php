<?php

class DataEntry
{
    /**
     * Contains the database id for this entry
     *
     * @var int|null
     */
    protected ?int $id = null;

    /**
     * Contains the link to the meta system for this entry
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
     * Returns true if this is a new entry that hasn't been written to the database yet
     *
     * @return bool
     */
    public function isNew(): bool
    {

    }



    /**
     * Returns id for this database entry
     *
     * @return ?int
     */
    public function getId(): ?int
    {
        return $this->status;
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
     * @param ?String $status
     * @return DataEntry
     */
    public function setStatus(?String $status): DataEntry
    {
        $this->status = $status;
        return $this;
    }
}